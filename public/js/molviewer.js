/*
 * molviewer.js — 3D structure viewer (3Dmol.js) for the project results page.
 *
 * Loads the uploaded PDB/CIF structure and highlights an individual chain
 * fragment when the user clicks the "view" (eye) button on a table row.
 *
 * Relies on globals defined in the view: `structureUrl` and `structureFormat`
 * ("pdb" or "cif"). When no structure is available both are null and the viewer
 * stays disabled.
 */

let glviewer = null;

// Anchor (element id) of the alignment block for the chain currently shown.
let currentRefAnchor = null;
// Chain currently highlighted / centred in the graph.
let currentChain = null;
// Which panel is visible: '3d' or 'graph'.
let currentView = '3d';
// Label for the last atom the user clicked (replaced on each click).
let atomLabel = null;
// Centroid {x,y,z} of every chain, computed once after the model loads.
let chainCentroids = {};

// Lighter green than the UI accent (#00bc9e): 3Dmol's shading darkens the
// cartoon, so a lighter base keeps the highlighted chain vivid.
const HIGHLIGHT_COLOR = '#4be3a5';
const HIGHLIGHT_HEX = 0x4be3a5;

// Standard UI green used for the central node of the neighbour graph.
const ACCENT_GREEN = '#00bc9e';
// Distance (Å) within which another chain counts as a neighbour.
const NEIGHBOUR_DISTANCE = 5;

// Graph node shades: chains near the chain's start, end, or both endpoints.
const NEAR_START_COLOR = '#d1d5db'; // light grey
const NEAR_END_COLOR = '#6b7280';   // dark grey
const NEAR_BOTH_COLOR = '#9ca3af';  // medium grey

const MUTED_STYLE = { cartoon: { color: '#cfd8dc' } };

// Three-letter residue name -> one-letter code (amino acids + nucleotides).
const ONE_LETTER = {
    ALA: 'A', ARG: 'R', ASN: 'N', ASP: 'D', CYS: 'C', GLN: 'Q', GLU: 'E',
    GLY: 'G', HIS: 'H', ILE: 'I', LEU: 'L', LYS: 'K', MET: 'M', PHE: 'F',
    PRO: 'P', SER: 'S', THR: 'T', TRP: 'W', TYR: 'Y', VAL: 'V', SEC: 'U',
    PYL: 'O', MSE: 'M',
    A: 'A', U: 'U', G: 'G', C: 'C', T: 'T',
    DA: 'A', DU: 'U', DG: 'G', DC: 'C', DT: 'T'
};

function oneLetter(resn) {
    if (!resn) return 'X';
    const key = resn.trim().toUpperCase();
    if (ONE_LETTER[key]) return ONE_LETTER[key];
    return key.length === 1 ? key : 'X';
}

// Convert a CSS "rgb(r, g, b)" / "#rrggbb" string to a 0xRRGGBB number.
function cssColorToHex(str) {
    if (!str) return 0xf8fafc;
    let m = str.match(/(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
    if (m) {
        return (parseInt(m[1], 10) << 16) | (parseInt(m[2], 10) << 8) | parseInt(m[3], 10);
    }
    m = str.match(/^#?([0-9a-f]{6})$/i);
    if (m) return parseInt(m[1], 16);
    return 0xf8fafc;
}

// Sticks colour scheme: carbons take the cartoon/highlight colour, every other
// element keeps its conventional colour (N blue, O red, S yellow, ...).
function highlightStickScheme() {
    const base = ($3Dmol.elementColors &&
        ($3Dmol.elementColors.defaultColors || $3Dmol.elementColors.Jmol)) || {};
    const map = Object.assign({}, base, { C: HIGHLIGHT_HEX });
    return { prop: 'elem', map: map };
}

function onAtomClick(atom, viewer) {
    if (!atom) return;
    if (atomLabel) {
        viewer.removeLabel(atomLabel);
    }
    const label = (atom.chain ? atom.chain + ': ' : '') + oneLetter(atom.resn) + atom.resi;
    atomLabel = viewer.addLabel(label, {
        position: { x: atom.x, y: atom.y, z: atom.z },
        backgroundColor: '#0f172a',
        backgroundOpacity: 0.85,
        fontColor: '#ffffff',
        fontSize: 12
    });
    viewer.render();
}

// Semi-transparent loading overlay shown on top of a viewer/graph element.
function showLoading(el, message) {
    hideLoading(el);
    const overlay = document.createElement('div');
    overlay.className = 'mol-loading';
    overlay.innerHTML =
        '<div class="spinner-border text-secondary" role="status" aria-hidden="true"></div>' +
        '<div class="mt-2 small text-muted">' + (message || 'Loading…') + '</div>';
    el.appendChild(overlay);
}

function hideLoading(el) {
    const overlay = el.querySelector('.mol-loading');
    if (overlay) overlay.remove();
}

// Centroid of every chain, computed once (single pass over all atoms).
function computeChainCentroids() {
    chainCentroids = {};
    const acc = {};
    glviewer.selectedAtoms({}).forEach(function (a) {
        const c = a.chain;
        if (!c) return;
        if (!acc[c]) acc[c] = { x: 0, y: 0, z: 0, n: 0 };
        acc[c].x += a.x; acc[c].y += a.y; acc[c].z += a.z; acc[c].n += 1;
    });
    Object.keys(acc).forEach(function (c) {
        const o = acc[c];
        chainCentroids[c] = { x: o.x / o.n, y: o.y / o.n, z: o.z / o.n };
    });
}

// Add a small, discreet label at the centroid of each neighbouring chain.
function addNeighbourLabels(chain) {
    if (!glviewer) return;
    getNeighbourChains(chain).forEach(function (nb) {
        const c = chainCentroids[nb];
        if (!c) return;
        glviewer.addLabel(nb, {
            position: c,
            backgroundColor: '#ffffff',
            backgroundOpacity: 0.5,
            fontColor: '#64748b',
            fontSize: 9
        });
    });
    glviewer.render();
}

// Neighbours near the FIRST or LAST residue of the given chain (within
// NEIGHBOUR_DISTANCE). Returns the residue range and, per neighbour, whether it
// is close to the start, the end, or both — so the graph can shade them.
function getNeighbourInfo(chain) {
    const empty = { start: null, end: null, neighbours: [] };
    if (!glviewer || !chain) return empty;

    const chainAtoms = glviewer.selectedAtoms({ chain: chain });
    let minResi = Infinity, maxResi = -Infinity;
    chainAtoms.forEach(function (a) {
        if (typeof a.resi === 'number') {
            if (a.resi < minResi) minResi = a.resi;
            if (a.resi > maxResi) maxResi = a.resi;
        }
    });
    if (minResi === Infinity) return empty;

    function chainsNear(resi) {
        let near = [];
        try {
            near = glviewer.selectedAtoms({
                within: { distance: NEIGHBOUR_DISTANCE, sel: { chain: chain, resi: [resi] } }
            });
        } catch (e) {
            near = [];
        }
        const s = new Set();
        near.forEach(function (a) { if (a.chain && a.chain !== chain) s.add(a.chain); });
        return s;
    }

    const startSet = chainsNear(minResi);
    const endSet = (maxResi !== minResi) ? chainsNear(maxResi) : new Set();
    const names = Array.from(new Set([...startSet, ...endSet])).sort();
    const neighbours = names.map(function (c) {
        return { chain: c, nearStart: startSet.has(c), nearEnd: endSet.has(c) };
    });
    return { start: minResi, end: maxResi, neighbours: neighbours };
}

// Just the neighbour chain names (used for the 3D labels).
function getNeighbourChains(chain) {
    return getNeighbourInfo(chain).neighbours.map(function (n) { return n.chain; });
}

// Open the chain sequence modal (same one used by the hits table).
function openChainModal(chain) {
    const title = document.getElementById('sequenceTitle');
    const content = document.getElementById('sequenceContent');
    if (title) title.textContent = chain;
    if (content) {
        content.textContent = (typeof fastaSequences !== 'undefined' && fastaSequences[chain])
            ? fastaSequences[chain]
            : 'Sequence not found';
    }
    const modalEl = document.getElementById('sequenceModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function graphNodeSvg(x, y, r, label, fill, textColor) {
    const fontSize = label.length > 3 ? 10 : 13;
    return '<g class="graph-node" data-chain="' + label + '" style="cursor:pointer;">' +
        '<circle cx="' + x + '" cy="' + y + '" r="' + r + '" fill="' + fill +
        '" stroke="#94a3b8" stroke-width="1"></circle>' +
        '<text x="' + x + '" y="' + y + '" text-anchor="middle" dominant-baseline="central" ' +
        'font-size="' + fontSize + '" font-weight="600" fill="' + textColor +
        '" style="pointer-events:none;">' + label + '</text>' +
        '</g>';
}

// Colour/text for a neighbour node according to which endpoint it is near.
function neighbourNodeStyle(n) {
    if (n.nearStart && n.nearEnd) return { fill: NEAR_BOTH_COLOR, text: '#0f172a' };
    if (n.nearEnd) return { fill: NEAR_END_COLOR, text: '#ffffff' };
    return { fill: NEAR_START_COLOR, text: '#0f172a' };
}

function graphLegendHtml(info) {
    function item(color, text, border) {
        return '<span class="d-inline-flex align-items-center gap-1">' +
            '<span style="display:inline-block;width:11px;height:11px;border-radius:50%;' +
            'background:' + color + ';border:1px solid ' + (border || '#94a3b8') + ';"></span>' +
            text + '</span>';
    }
    const s = info.start != null ? info.start : '?';
    const e = info.end != null ? info.end : '?';
    return '<div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3 small text-muted px-2 pb-1" ' +
        'style="font-size:11px;">' +
        item(ACCENT_GREEN, 'Selected chain', ACCENT_GREEN) +
        item(NEAR_START_COLOR, 'Near start (res. ' + s + ')') +
        item(NEAR_END_COLOR, 'Near end (res. ' + e + ')') +
        item(NEAR_BOTH_COLOR, 'Near both ends') +
        '</div>';
}

function buildGraphView(centerChain, info) {
    const neighbours = info.neighbours;
    const W = 400, H = 400, cx = W / 2, cy = H / 2;
    const nodeR = neighbours.length > 12 ? 16 : 22;
    const ringR = Math.min(cx, cy) - nodeR - 14;

    let edges = '', nodes = '';
    neighbours.forEach(function (n, i) {
        const ang = -Math.PI / 2 + (i * 2 * Math.PI) / neighbours.length;
        const x = cx + ringR * Math.cos(ang);
        const y = cy + ringR * Math.sin(ang);
        const st = neighbourNodeStyle(n);
        edges += '<line x1="' + cx + '" y1="' + cy + '" x2="' + x + '" y2="' + y +
            '" stroke="#cbd5e1" stroke-width="2"></line>';
        nodes += graphNodeSvg(x, y, nodeR, n.chain, st.fill, st.text);
    });

    const center = graphNodeSvg(cx, cy, nodeR + 4, centerChain, ACCENT_GREEN, '#ffffff');

    let empty = '';
    if (neighbours.length === 0) {
        empty = '<text x="' + cx + '" y="' + (cy + 60) +
            '" text-anchor="middle" font-size="12" fill="#64748b">' +
            'No neighbouring chains within ' + NEIGHBOUR_DISTANCE + ' Å</text>';
    }

    const svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" width="100%" height="100%" ' +
        'preserveAspectRatio="xMidYMid meet" style="font-family:Inter,sans-serif;">' +
        edges + nodes + center + empty + '</svg>';

    return '<div style="height:100%;display:flex;flex-direction:column;">' +
        '<div style="flex:1;min-height:0;">' + svg + '</div>' +
        graphLegendHtml(info) + '</div>';
}

function showGraphView() {
    const graphEl = document.getElementById('molgraph');
    const viewerEl = document.getElementById('molviewer');
    if (!graphEl || !viewerEl) return;

    viewerEl.style.display = 'none';
    graphEl.style.display = '';
    currentView = 'graph';

    if (!currentChain) {
        graphEl.innerHTML =
            '<div class="d-flex h-100 align-items-center justify-content-center ' +
            'text-muted small p-3 text-center">Select a chain with the ' +
            '<i class="bi bi-eye mx-1"></i> button first, then open the graph.</div>';
        return;
    }

    // Computing neighbours can take a moment, so show a loading overlay and let
    // the browser paint it before running the (blocking) distance search. A
    // small timeout (rather than rAF) guarantees the callback runs even if the
    // page is not actively painting.
    graphEl.innerHTML = '';
    showLoading(graphEl, 'Building neighbour graph…');
    const chain = currentChain;
    setTimeout(function () {
        // Bail out if the user already switched away or changed chain.
        if (currentView !== 'graph' || currentChain !== chain) return;
        graphEl.innerHTML = buildGraphView(chain, getNeighbourInfo(chain));
    }, 30);
}

function show3DView() {
    const graphEl = document.getElementById('molgraph');
    const viewerEl = document.getElementById('molviewer');
    if (!graphEl || !viewerEl) return;

    graphEl.style.display = 'none';
    viewerEl.style.display = '';
    if (glviewer) {
        glviewer.resize();
        glviewer.render();
    }
    currentView = '3d';
}

function initMolViewer() {
    const el = document.getElementById('molviewer');

    if (!el || typeof structureUrl === 'undefined' || !structureUrl) {
        return;
    }
    if (typeof $3Dmol === 'undefined') {
        el.innerHTML =
            '<div class="p-3 text-danger small">3Dmol.js failed to load.</div>';
        return;
    }

    // Match the viewer background to the page background.
    const pageBg = cssColorToHex(getComputedStyle(document.body).backgroundColor);
    glviewer = $3Dmol.createViewer(el, { backgroundColor: pageBg });

    // Pre-load the structure as soon as the page opens.
    showLoading(el, 'Loading structure…');
    fetch(structureUrl)
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        })
        .then(function (data) {
            glviewer.addModel(data, structureFormat);
            glviewer.setStyle({}, MUTED_STYLE);
            glviewer.setBackgroundColor(pageBg, 1);
            // Clicking any atom reveals its residue (one-letter code + number).
            glviewer.setClickable({}, true, onAtomClick);
            computeChainCentroids();
            glviewer.zoomTo();
            glviewer.render();
            hideLoading(el);
        })
        .catch(function (err) {
            hideLoading(el);
            el.innerHTML =
                '<div class="p-3 text-danger small">Could not load structure.</div>';
            console.error('3Dmol load error:', err);
        });

    // "Go to alignment" jumps to the alignment block of the shown chain.
    const goBtn = document.getElementById('goToAlignmentBtn');
    if (goBtn) {
        goBtn.addEventListener('click', function () {
            if (!currentRefAnchor) return;
            const target = document.getElementById(currentRefAnchor);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // Toggle between the 3D view and the neighbour graph.
    const graphBtn = document.getElementById('showGraphBtn');
    if (graphBtn) {
        graphBtn.addEventListener('click', showGraphView);
    }
    const btn3d = document.getElementById('show3DBtn');
    if (btn3d) {
        btn3d.addEventListener('click', function (e) {
            e.preventDefault();
            show3DView();
        });
    }

    // Clicking a graph node opens that chain's sequence modal.
    const graphEl = document.getElementById('molgraph');
    if (graphEl) {
        graphEl.addEventListener('click', function (e) {
            const node = e.target.closest('.graph-node');
            if (node) openChainModal(node.getAttribute('data-chain'));
        });
    }
}

function highlightChain(chain, refAnchor) {
    if (!glviewer || !chain) {
        return;
    }

    currentChain = chain;

    // A chain is now selected: enable the graph button and return to the 3D view.
    const graphBtn = document.getElementById('showGraphBtn');
    if (graphBtn) graphBtn.disabled = false;
    show3DView();

    // Mute every chain, then highlight the requested one. Sticks are added so
    // that very short fragments (a few residues) remain visible.
    glviewer.setStyle({}, MUTED_STYLE);
    glviewer.setStyle({ chain: chain }, {
        cartoon: { color: HIGHLIGHT_COLOR },
        stick: { radius: 0.2, colorscheme: highlightStickScheme() }
    });

    glviewer.removeAllLabels();
    atomLabel = null;

    // Chain label at the (pre-computed) centroid of the selected chain.
    const centroid = chainCentroids[chain];
    if (centroid) {
        glviewer.addLabel('Chain ' + chain, {
            position: centroid,
            backgroundColor: HIGHLIGHT_COLOR,
            backgroundOpacity: 0.9,
            fontColor: '#0f172a',
            fontSize: 14
        });
    }
    glviewer.zoomTo({ chain: chain });
    glviewer.render();

    const badge = document.getElementById('molChainLabel');
    if (badge) {
        badge.textContent = 'Chain ' + chain;
        badge.style.display = '';
    }

    // Remember the target alignment block and reveal the shortcut button.
    currentRefAnchor = refAnchor || null;
    const goBtn = document.getElementById('goToAlignmentBtn');
    if (goBtn) {
        goBtn.style.display = currentRefAnchor ? '' : 'none';
    }

    // Label the neighbouring chains too. Deferred so the highlight itself shows
    // instantly (the neighbour search can take a moment).
    const forChain = chain;
    setTimeout(function () {
        if (currentChain !== forChain || currentView !== '3d') return;
        addNeighbourLabels(forChain);
    }, 0);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMolViewer);
} else {
    initMolViewer();
}
