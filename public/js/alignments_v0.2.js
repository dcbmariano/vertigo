// alignments.js - variáveis // colore sequencias iguais
let selection = { start: null, end: null, residues: [] };
let isSelecting = false;
let originalAlignmentText = '';
let alignmentBlocks = [];

const baaColors = {
    'A': '#9798FF', 'I': '#9798FF', 'L': '#9798FF', 'M': '#9798FF', 'V': '#9798FF', 'F': '#F79B20', 'W': '#F79B20', 'Y': '#F79B20', 'K': '#FF9799', 'R': '#FF9799', 'D': '#98CB99', 'E': '#98CB99', 'N': '#FEDA96', 'Q': '#FEDA96', 'S': '#FEDA96', 'T': '#FEDA96', 'C': '#FEDA96', 'G': '#9798FF', 'P': '#9798FF', 'H': '#FF9799', '-': '#ffffff'
}; // cor de fundo - atual

const aaGroups = {

    hydrophobic:
        ['A', 'V', 'I', 'L', 'M'],

    aromatic:
        ['F', 'W', 'Y'],

    positive:
        ['K', 'R', 'H'],

    negative:
        ['D', 'E'],

    polar:
        ['S', 'T', 'N', 'Q', 'C'],

    special:
        ['G', 'P']

};


// seleções e eventos
document.addEventListener('DOMContentLoaded', init);
document.addEventListener('mousedown', hideSelectionMenu);
document.getElementById('editSelectionBtn').addEventListener('click', openEditModal);
document.getElementById('saveEditBtn').addEventListener('click', saveEditedSequence);
document.getElementById('saveAlignmentsBtn').addEventListener('click', saveAlignmentsFile);

async function init() {
    /* 
    * Carrega os alinhamentos do projeto, renderiza os blocos
    * e inicializa os tooltips da interface. 
    * */
    const response = await fetch(`/project/${projectId}/alignments`);
    const text = await response.text();
    originalAlignmentText = text;
    alignmentBlocks = parseAlignments(text);
    const blocks = parseAlignments(text);
    document.querySelector('#mutations_found_title').textContent = blocks.length
    renderAlignments(alignmentBlocks);
    initializeTooltips();
    initializeSelection();
}

// ========================================
// PARSER
// ========================================
function parseAlignments(text) {
    /**
     * Divide o conteúdo do arquivo em blocos de alinhamento.
     * Cada bloco inicia com uma linha que começa com '>'.
     */
    return text
        .split(/(?=^>)/m)
        .filter(block => block.trim())
        .map(block => {

            const lines =
                block
                    .trim()
                    .split('\n');

            return {
                header: lines[0],
                lines: lines
            };

        });
}

// ========================================
// RENDER
// ========================================
function renderAlignments(blocks) {
    /**
     * Renderiza todos os blocos de alinhamento na página.
     * Cada bloco é convertido em um card independente.
     */
    const container =
        document.getElementById(
            'alignments'
        );
    container.innerHTML = '';
    blocks.forEach(
        (block, blockIndex) => {
            container.appendChild(
                createAlignmentCard(
                    block,
                    blockIndex
                )
            );
        }
    );
}

function shouldRemoveConsensusLine(index, totalLines) {
    /**
     * Remove linhas consenso e a linha final de separação.
     * Utilizado para simplificar a visualização dos alinhamentos.
     */
    // remove a última linha
    if (index === totalLines - 1) {
        return true;
    }

    // remove linhas da seq consenso: 4, 8, 12, 16, ...
    return (
        index >= 3 &&
        (index - 3) % 4 === 0
    );

}

function createAlignmentCard(block, blockIndex) {
    /**
     * Cria um card Bootstrap contendo um alinhamento completo.
     * O cabeçalho exibe o identificador da sequência.
     */

    const lines = block.lines;
    const referenceSequence = lines[2];
    const card = document.createElement('div');
    const columnColors =
        computeColumnColors(
            block
        );

    card.className = 'card mb-4';
    card.innerHTML = `
        <div class="card-header small">
            <span class="bg-dark text-info badge">${block.header}</span>
        </div>
        <div class="card-body">
            <div class="alignment-container">
            </div>
        </div>
    `;

    const container =
        card.querySelector(
            '.alignment-container'
        );

    // laço que grava as linhas
    lines.forEach((line, index) => {
        if (line.trim().startsWith('fragments chains:')) {
            container.appendChild(createAlignmentLine(line));
            return;
        }
        if (shouldRemoveConsensusLine(index, lines.length)) {
            const emptyLine = document.createElement('div');
            emptyLine.className = 'alignment-line';
            emptyLine.textContent = '';
            container.appendChild(emptyLine);
            return;
        }
        if (isSequenceLine(line)) {
            const startResidue = getStartResidue(lines, index);
            container.appendChild(
                createAlignmentLine(line, startResidue, index, blockIndex, referenceSequence, columnColors)
            );
        } else {
            container.appendChild(
                createAlignmentLine(line)
            );
        }
    });
    return card;
}

function createAlignmentLine(line, startResidue = null, lineNumber = null, blockNumber = null, referenceSequence = null, columnColors = null) {
    /**
     * Cria uma linha da visualização do alinhamento.
     * Sequências são formatadas; demais linhas são exibidas como texto.
     */
    const div = document.createElement('div');
    div.className = 'alignment-line';
    if (isSequenceLine(line) && startResidue !== null) {
        div.innerHTML = renderSequence(line, startResidue, lineNumber, blockNumber, referenceSequence, columnColors);
    } else {
        div.textContent = line;
    }
    return div;
}

function renderSequence(sequenceLine, startResidue, lineNumber, blockNumber, referenceSequence, columnColors) {
    /**
     * Renderiza uma sequência de aminoácidos com numeração de resíduos.
     * Cada aminoácido é convertido em um elemento HTML independente.
     */
    let residue = startResidue - 1;
    let residueIndex = 0;
    let columnIndex = -1;
    const isReferenceSequence = lineNumber === 2;

    return [...sequenceLine]
        .map(char => {
            columnIndex++;
            if (/[A-Za-z]/.test(char)) {
                residue++;
                residueIndex++;

                const referenceAA =
                    referenceSequence[
                    columnIndex
                    ];
                const color =
                    columnColors[
                    columnIndex
                    ];

                return createResidueHtml(
                    char,
                    color,
                    residue,
                    lineNumber,
                    residueIndex,
                    blockNumber,
                    columnIndex
                );
            }
            return char;
        })
        .join('');
}

function createResidueHtml(
    aa,
    color,
    residueNumber,
    lineNumber,
    residueIndex,
    blockNumber,
    columnIndex
) {
    /**
     * Gera o HTML de um aminoácido com cor e tooltip.
     * O tooltip exibe a posição do resíduo na sequência.
     */
    // const color =
    //     baaColors[
    //         aa.toUpperCase()
    //     ] || '#cccccc';

    const cssClass =
        aa === '-'
            ? 'aa gap'
            : 'aa';

    return `<span
        class="${cssClass}"
        data-position="${residueNumber}"
        data-residue="${aa}"
        data-line="${lineNumber}"
        data-index="${residueIndex}"
        data-bs-toggle="tooltip"
        data-bs-title="Residue ${residueNumber}"
        data-block="${blockNumber}"
        data-column="${columnIndex}"
        style="background-color:${color}"
    >${aa}</span>`;
}


function isSequenceLine(line) {
    /**
     * Verifica se uma linha representa uma sequência biológica.
     * Ignora cabeçalhos, coordenadas, fragmentos e separadores.
     */
    const trimmed = line.trim();

    if (!trimmed)
        return false;

    if (trimmed.startsWith('>'))
        return false;

    if (trimmed.startsWith('fragments chains:'))
        return false;

    if (/^[\d\s]+$/.test(trimmed))
        return false;

    if (/^#+$/.test(trimmed))
        return false;

    if (/<-.*->/.test(trimmed))
        return false;

    return /^[A-Za-z\-\+\.\s]+$/i.test(trimmed);
}

function isConsensusLine(lines, index) {
    /**
     * Identifica linhas consenso localizadas entre sequências.
     * Mantida para possíveis usos futuros.
     */
    if (index === 0)
        return false;

    if (index === lines.length - 1)
        return false;

    return (
        isSequenceLine(lines[index])
        &&
        isSequenceLine(lines[index - 1])
        &&
        isSequenceLine(lines[index + 1])
    );

}

function initializeTooltips() {
    /**
     * Ativa todos os tooltips Bootstrap presentes na página.
     * Deve ser executada após a renderização dos alinhamentos.
     */
    const tooltipTriggerList =
        document.querySelectorAll(
            '[data-bs-toggle="tooltip"]'
        );
    tooltipTriggerList.forEach(el => {
        new bootstrap.Tooltip(el);
    });
}

function getSequenceStart(coordinateLine) {
    /**
    * Extrai o primeiro número de uma linha de coordenadas.
    * Esse valor corresponde ao início da sequência alinhada.
    */
    const match = coordinateLine.match(/\d+/);
    if (!match)
        return 1;
    return parseInt(match[0]);
}

function getStartResidue(lines, index) {
    /**
     * Determina o resíduo inicial de uma sequência do alinhamento.
     * Usa coordenadas do arquivo ou retorna 1 para a sequência principal.
     */
    if (index === 2) { // sequência principal
        return 1;
    }
    const coordinateLine =
        lines[index + 1] || '';
    return getSequenceStart(
        coordinateLine
    );
}


function initializeSelection() {
    /* Responsável por permitir a seleção de seqs */
    document.addEventListener(
        'mousedown',
        startSelection
    );

    document.addEventListener(
        'mouseover',
        updateSelection
    );

    document.addEventListener(
        'mouseup',
        finishSelection
    );
}

function startSelection(event) {
    /* Responsável por iniciar a seleção de seqs */
    if (!event.target.classList.contains('aa')) {
        return;
    }

    clearSelection();

    isSelecting = true;

    selection.start = event.target;
    selection.end = event.target;

    redrawSelection();
}

function updateSelection(event) {
    /* Responsável por arrastar e manter a seleção de seqs */
    if (!isSelecting) {
        return;
    }
    if (!event.target.classList.contains('aa')) {
        return;
    }
    if (event.target.dataset.line !== selection.start.dataset.line) {
        return;
    }
    selection.end = event.target;
    redrawSelection();
}

function redrawSelection() {
    /* Responsável por atualizar borda vermelha */
    clearSelectionMarks();

    const block = selection.start.dataset.block;
    const line = selection.start.dataset.line;
    const start = parseInt(selection.start.dataset.index);
    const end = parseInt(selection.end.dataset.index);
    const min = Math.min(start, end);
    const max = Math.max(start, end);

    document
        .querySelectorAll(
            `.aa[data-block="${block}"][data-line="${line}"]`
        )
        .forEach(residue => {
            const idx = parseInt(residue.dataset.index);
            if (idx >= min && idx <= max) {
                residue.classList.add('selected');
            }
        });
}


function clearSelectionMarks() {
    /* Responsável por limpar as bordas */
    document
        .querySelectorAll(
            '.aa.selected'
        )
        .forEach(el => {
            el.classList.remove(
                'selected'
            );

        });
}

function finishSelection(event) {
    /* Responsável por finalizar seleção */
    if (!isSelecting) {
        return;
    }

    isSelecting = false;
    selection.residues = [...document.querySelectorAll('.aa.selected')];
    if (selection.residues.length === 0) {
        return;
    }

    showEditButton(event.pageX, event.pageY);
}

function showEditButton(x, y) {
    /* Responsável por mostrar o botão de edição */
    const menu = document.getElementById('selectionMenu');
    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
    menu.style.display = 'block';
}

function clearSelection() {
    /* Apaga as bordas vermelhas */
    clearSelectionMarks();

    selection.start = null;
    selection.end = null;
    selection.residues = [];

    const menu = document.getElementById('selectionMenu');

    if (menu) {
        menu.style.display = 'none';
    }
}


function openEditModal() {
    /* Carrega o modal editar */
    const oldSequence =
        selection.residues
            .map(r => r.dataset.residue)
            .join('');
    document
        .getElementById('oldSequence')
        .value = oldSequence;
    document
        .getElementById('newSequence')
        .value = oldSequence;
    const modal =
        new bootstrap.Modal(
            document.getElementById('editModal')
        );
    modal.show();
}


function saveEditedSequence() {
    const newSequence =
        document
            .getElementById('newSequence')
            .value
            .trim();
    const oldSequence =
        document
            .getElementById('oldSequence')
            .value
            .trim();
    const blockIndex =
        parseInt(
            selection.residues[0]
                .dataset.block
        );

    const lineIndex =
        parseInt(
            selection.residues[0]
                .dataset.line
        );
    const startColumn = parseInt(
        selection.residues[0]
            .dataset.column
    );
    const endColumn = parseInt(
        selection.residues[
            selection.residues.length - 1
        ].dataset.column
    );
    let sequenceLine =
        alignmentBlocks[
            blockIndex
        ].lines[
        lineIndex
        ];

    if (newSequence.length !== oldSequence.length) {
        alert(
            'The new sequence must have the same length.'
        );
        return;
    }
    const updatedLine =
        sequenceLine.substring(
            0,
            startColumn
        )
        +
        newSequence
        +
        sequenceLine.substring(
            endColumn + 1
        );


    alignmentBlocks[
        blockIndex
    ].lines[
        lineIndex
    ] = updatedLine;

    renderAlignments(
        alignmentBlocks
    );

    initializeTooltips();

    clearSelection();

    bootstrap.Modal
        .getInstance(
            document.getElementById(
                'editModal'
            )
        )
        .hide();
}


async function saveAlignmentsFile() {
    /* Responsável por salvar o alinhamento */

    let content = '';

    document
        .querySelectorAll('.alignment-line')
        .forEach(line => {
            content += line.textContent + '\n';
        });

    const response =
        await fetch(`/project/${projectId}/save-alignments`, {
            method: 'POST',
            headers: {
                'Content-Type':
                    'application/json'
            },
            body: JSON.stringify({
                content
            })
        }
        );
    const result = await response.json();
    alert(result.message);
}


function hideSelectionMenu(event) {
    /* Esta função esconde o menu Edit */
    const menu =
        document.getElementById(
            'selectionMenu'
        );

    if (!menu) {
        return;
    }

    const modal =
        document.getElementById(
            'editModal'
        );

    if (modal && modal.contains(event.target)) {
        return;
    }
    if (menu.contains(event.target)) {
        return;
    }
    if (event.target.classList.contains('aa')) {
        return;
    }
    menu.style.display = 'none';
    clearSelection();
}


function getResidueColor(referenceAA, currentAA) {

    referenceAA = referenceAA.toUpperCase();
    currentAA = currentAA.toUpperCase();

    // gaps
    if (referenceAA === '-' || currentAA === '-'){
        return '#FFFFFF';
    }

    // idênticos
    if (referenceAA === currentAA) {
        return '#008CA6';
    }

    // mesmo grupo químico
    for (
        const group of Object.values(
            aaGroups
        )
    ) {
        if (
            group.includes(
                referenceAA
            )
            &&
            group.includes(
                currentAA
            )
        ) {
            return '#CCCCCC';
        }
    }

    // diferentes
    return '#D54344';
}


function computeColumnColors(block){

    const reference = block.lines[2];

    const colors =
        Array(
            reference.length
        ).fill('#FFFFFF');

    const sequenceLines =
        block.lines.filter(
            isSequenceLine
        );

    for (let col = 0; col < reference.length; col++) {

        const refAA = reference[col];

        let identical = 0;
        let similar = 0;
        let different = 0;

        for (let i = 1; i < sequenceLines.length; i++) {
            const aa =
                sequenceLines[i][col];

            if (
                !aa ||
                aa === '-'
            ) {
                continue;
            }

            const color =
                getResidueColor(
                    refAA,
                    aa
                );

            if (color === '#008CA6'){
                identical++;
            }
            else if (color === '#CCCCCC'){
                similar++;
            }
            else {
                different++;
            }
        }

        if (identical > 0) {
            colors[col] =
                '#008CA6';
        }
        else if (
            similar > 0
        ) {
            colors[col] = '#CCCCCC';
        }
        else if (different > 0) {
            colors[col] = '#D54344';
        }
        else {
            colors[col] =
                '#FFFFFF';
        }
    }
    return colors;
}