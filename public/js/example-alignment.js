// alignments.js - variáveis
let selection = { start: null, end: null, residues: [] };
let isSelecting = false;
let originalAlignmentText = '';
let alignmentBlocks = [];

const baaColors = {
    'A': '#9798FF', 'I': '#9798FF', 'L': '#9798FF', 'M': '#9798FF', 'V': '#9798FF', 'F': '#F79B20', 'W': '#F79B20', 'Y': '#F79B20', 'K': '#FF9799', 'R': '#FF9799', 'D': '#98CB99', 'E': '#98CB99', 'N': '#FEDA96', 'Q': '#FEDA96', 'S': '#FEDA96', 'T': '#FEDA96', 'C': '#FEDA96', 'G': '#9798FF', 'P': '#9798FF', 'H': '#FF9799', '-': '#ffffff'
};

const aaGroups = {
    hydrophobic: ['A', 'V', 'I', 'L', 'M'],
    aromatic: ['F', 'W', 'Y'],
    positive: ['K', 'R', 'H'],
    negative: ['D', 'E'],
    polar: ['S', 'T', 'N', 'Q', 'C'],
    special: ['G', 'P']
};

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('mousedown', hideSelectionMenu);

document.getElementById('editSelectionBtn').addEventListener('click', openEditModal);
document.getElementById('saveEditBtn').addEventListener('click', saveEditedSequence);
document.getElementById('saveAlignmentsBtn').addEventListener('click', saveAlignmentsFile);
document.getElementById('insertSelectionBtn').addEventListener('click', openInsertModal);
document.getElementById('deleteSelectionBtn').addEventListener('click', deleteSelection);

document
    .querySelectorAll('.sequence-link')
    .forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            document.getElementById('sequenceTitle').textContent = id;
            document.getElementById('sequenceContent').textContent = fastaSequences[id] || 'Sequence not found';
            const modal = new bootstrap.Modal(document.getElementById('sequenceModal'));
            modal.show();
        });
    });

document.getElementById('deleteGapBtn').addEventListener('click', deleteGap);

document.addEventListener('click', event => {
    if (!event.target.classList.contains('close-sequence-popover')) return;
    const popoverElement = event.target.closest('.popover');
    if (!popoverElement) return;
    document.querySelectorAll('.chain-link').forEach(link => {
        const popover = bootstrap.Popover.getInstance(link);
        if (popover) popover.hide();
    });
});

async function init() {
    /* Carrega os alinhamentos do projeto, renderiza os blocos e inicializa os tooltips da interface. */
    const response = await fetch(`/project/${projectId}/alignments`);
    const text = await response.text();
    originalAlignmentText = text;
    alignmentBlocks = parseAlignments(text);

    const blocks = parseAlignments(text);
    document.querySelector('#mutations_found_title').textContent = blocks.length;

    renderAlignments(alignmentBlocks);
    initializeTooltips();initializeChainPopovers();
    initializeSelection();
    initializeChainPopovers();
}

function parseAlignments(text) {
    /** Divide o conteúdo do arquivo em blocos de alinhamento. Cada bloco inicia com '>'. */
    return text.split(/(?=^>)/m).filter(block => block.trim()).map(block => {
        const lines = block.trim().split('\n');
        return { header: lines[0], lines: lines };
    });
}

function renderAlignments(blocks) {
    /** Renderiza todos os blocos de alinhamento na página. Cada bloco é convertido em um card. */
    const container = document.getElementById('alignments');
    container.innerHTML = '';
    blocks.forEach((block, blockIndex) => {
        container.appendChild(createAlignmentCard(block, blockIndex));
    });
}

function shouldRemoveConsensusLine(index, totalLines) {
    /** Remove linhas consenso e a linha final de separação. */
    if (index === totalLines - 1) return true;
    return index >= 3 && (index - 3) % 4 === 0;
}

function getColorBar(x){
    if(x<30){ return 'danger' }
    else if(x<70){ return 'warning' }  
    else{ return 'success' }
}

function createAlignmentCard(block, blockIndex) {
    /** Cria um card Bootstrap contendo um alinhamento completo. */
    const lines = block.lines;
    const referenceSequence = lines[2];
    const card = document.createElement('div');
    const columnColors = computeColumnColors(block);
    const statistics = computeAlignmentStatistics(block);
    card.className = 'card mb-4';
    card.innerHTML = `<div class="card-header small" id="${block.header.replace(/\|/g, '_')}">
        <div class="row small">
            <div class="col-3"><span class="bg-dark text-info badge">${block.header}</span></div>
            <div class="col-3 text-end">Coverage: ${statistics.coverage.toFixed(1)}%
            <div class="progress" role="progressbar" aria-label="Example 1px high" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px">
                <div class="progress-bar bg-${getColorBar(statistics.coverage.toFixed(1))}" style="width: ${statistics.coverage.toFixed(1)}%"></div>
            </div>
            </div>
            <div class="col-3 text-end">Identity: ${statistics.identity.toFixed(1)}%
                <div class="progress" role="progressbar" aria-label="Example 1px high" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px">
                    <div class="progress-bar bg-${getColorBar(statistics.identity.toFixed(1))}" style="width: ${statistics.identity.toFixed(1)}%"></div>
                </div>
            </div>
            <div class="col-3 text-end">Positives: ${statistics.positives.toFixed(1)}%
                <div class="progress" role="progressbar" aria-label="Example 1px high" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px">
                    <div class="progress-bar bg-${getColorBar(statistics.positives.toFixed(1))}" style="width: ${statistics.positives.toFixed(1)}%"></div>
                </div>
            </div>
        </div>
    </div><div class="card-body"><div class="alignment-container"></div></div>`;
    const container = card.querySelector('.alignment-container');
    lines.forEach((line, index) => {
        if (line.trim().startsWith('fragments chains:')) {
            container.appendChild(createAlignmentLine(line));
            return;
        }
        if (shouldRemoveConsensusLine(index, lines.length)) {
            const emptyLine = document.createElement('div');
            emptyLine.className = 'alignment-line';
            container.appendChild(emptyLine);
            return;
        }
        if (isSequenceLine(line)) {
            const startResidue = getStartResidue(lines, index);
            container.appendChild(createAlignmentLine(line, startResidue, index, blockIndex, referenceSequence, columnColors));
        } else {
            container.appendChild(createAlignmentLine(line));
        }
    });
    return card;
}

function createAlignmentLine(line, startResidue = null, lineNumber = null, blockNumber = null, referenceSequence = null, columnColors = null) {
    /** Cria uma linha da visualização do alinhamento. */
    const div = document.createElement('div');
    div.className = 'alignment-line';
    if (isSequenceLine(line) && startResidue !== null) {
        div.innerHTML = renderSequence(line, startResidue, lineNumber, blockNumber, referenceSequence, columnColors);
    } else {
        if(isChainLine(line)){
            div.innerHTML = renderChainLine(line);
        }
        else{
            div.textContent = line;
        }
    }
    return div;
}

function renderSequence(sequenceLine, startResidue, lineNumber, blockNumber, referenceSequence, columnColors) {
    /** Renderiza uma sequência de aminoácidos com numeração de resíduos. */
    let residue = startResidue - 1, residueIndex = 0, columnIndex = -1;
    return [...sequenceLine].map(char => {
        columnIndex++;
        if (/[A-Za-z]/.test(char)) {
            residue++; residueIndex++;
            return createResidueHtml(char, columnColors[columnIndex], residue, lineNumber, residueIndex, blockNumber, columnIndex);
        }
        if (char === '-' || char === '.') {
            return createResidueHtml(char, '#FFFFFF', residue, lineNumber, residueIndex, blockNumber, columnIndex);
        }
        return char;
    }).join('');
}

function createResidueHtml(aa, color, residueNumber, lineNumber, residueIndex, blockNumber, columnIndex) {
    /** Gera o HTML de um aminoácido com cor e tooltip. */
    const cssClass = aa === '-' ? 'aa gap' : 'aa';
    return `<span class="${cssClass}" data-position="${residueNumber}" data-residue="${aa}" data-line="${lineNumber}" data-index="${residueIndex}" data-bs-toggle="tooltip" data-bs-title="Residue ${residueNumber}" data-block="${blockNumber}" data-column="${columnIndex}" style="background-color:${color}">${aa}</span>`;
}

function isSequenceLine(line) {
    /** Verifica se uma linha representa uma sequência biológica. */
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('>') || trimmed.startsWith('fragments chains:') || /^[\d\s]+$/.test(trimmed) || /^#+$/.test(trimmed) || /<-.*->/.test(trimmed)) return false;
    return /^[A-Za-z\-\+\.\s]+$/i.test(trimmed);
}

function initializeTooltips() {
    /** Ativa todos os tooltips Bootstrap. */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
}

function getSequenceStart(coordinateLine) {
    /** Extrai o primeiro número de uma linha de coordenadas. */
    const match = coordinateLine.match(/-?\d+/);
    return match ? parseInt(match[0]) : 1;
}

function getStartResidue(lines, index) {
    /** Determina o resíduo inicial de uma sequência. */
    return index === 2 ? 1 : getSequenceStart(lines[index + 1] || '');
}

function initializeSelection() {
    /* Responsável por permitir a seleção de seqs */
    document.addEventListener('mousedown', startSelection);
    document.addEventListener('mouseover', updateSelection);
    document.addEventListener('mouseup', finishSelection);
}

function startSelection(event) {
    /* Responsável por iniciar a seleção */
    if (!event.target.classList.contains('aa')) return;
    clearSelection();
    isSelecting = true;
    selection.start = event.target;
    selection.end = event.target;
    redrawSelection();
}

function updateSelection(event) {
    /* Responsável por arrastar e manter a seleção */
    if (!isSelecting || !event.target.classList.contains('aa') || event.target.dataset.line !== selection.start.dataset.line) return;
    selection.end = event.target;
    redrawSelection();
}

function redrawSelection() {
    /* Responsável por atualizar borda selecionada */
    clearSelectionMarks();
    const block = selection.start.dataset.block;
    const line = selection.start.dataset.line;
    const min = Math.min(parseInt(selection.start.dataset.column), parseInt(selection.end.dataset.column));
    const max = Math.max(parseInt(selection.start.dataset.column), parseInt(selection.end.dataset.column));
    document.querySelectorAll(`.aa[data-block="${block}"][data-line="${line}"]`).forEach(residue => {
        if (parseInt(residue.dataset.column) >= min && parseInt(residue.dataset.column) <= max) residue.classList.add('selected');
    });
}

function clearSelectionMarks() {
    /* Responsável por limpar as marcas de seleção */
    document.querySelectorAll('.aa.selected').forEach(el => el.classList.remove('selected'));
}

function finishSelection(event) {
    /* Finaliza a seleção e mostra botões de ação */
    if (!isSelecting) return;
    isSelecting = false;
    selection.residues = [...document.querySelectorAll('.aa.selected')];
    if (selection.residues.length === 0) return;
    const editBtn = document.getElementById('editSelectionBtn');
    const insertBtn = document.getElementById('insertSelectionBtn');
    const deleteBtn = document.getElementById('deleteSelectionBtn');
    const deleteGapBtn = document.getElementById('deleteGapBtn');

    editBtn.style.display = 'none';
    insertBtn.style.display = 'none';
    deleteBtn.style.display = 'none';
    deleteGapBtn.style.display = 'none';

    if(
        isInsertionSelection()
    ){
        insertBtn.style.display =
            'block';
    }
    else if(
        isGapDeletionSelection()
    ){
        deleteGapBtn.style.display =
            'block';
    }
    else if(
        isDeleteSelection()
    ){
        editBtn.style.display =
            'block';

        deleteBtn.style.display =
            'block';
    }
    else{
        editBtn.style.display =
            'block';
    }
    showEditButton(event.pageX, event.pageY);
}

function showEditButton(x, y) {
    /* Mostra menu de edição */
    const menu = document.getElementById('selectionMenu');
    menu.style.left = x + 'px'; menu.style.top = y + 'px'; menu.style.display = 'block';
}

function clearSelection() {
    /* Reseta as seleções */
    clearSelectionMarks();
    selection.start = null; selection.end = null; selection.residues = [];
    const menu = document.getElementById('selectionMenu');
    if (menu) menu.style.display = 'none';
}

function openEditModal() {
    /* Carrega o modal de edição */
    const oldSequence = selection.residues.map(r => r.dataset.residue).join('');
    document.getElementById('oldSequence').value = oldSequence;
    document.getElementById('newSequence').value = oldSequence;
    document.querySelector('#editModalLabel').textContent = 'Edit Sequence';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function saveEditedSequence() {
    /* Salva a sequência editada */
    const newSequence = document.getElementById('newSequence').value.trim();
    const oldSequence = document.getElementById('oldSequence').value.trim();
    const isInsertion = /^[.-]+$/.test(oldSequence);
    const blockIndex = parseInt(selection.residues[0].dataset.block);
    const lineIndex = parseInt(selection.residues[0].dataset.line);
    const startColumn = parseInt(selection.residues[0].dataset.column);
    const endColumn = parseInt(selection.residues[selection.residues.length - 1].dataset.column);
    let sequenceLine = alignmentBlocks[blockIndex].lines[lineIndex];
    if (newSequence.length !== oldSequence.length) {
        alert('The new sequence must have the same length.');
        return;
    }
    alignmentBlocks[blockIndex].lines[lineIndex] = sequenceLine.substring(0, startColumn) + newSequence + sequenceLine.substring(endColumn + 1);
    if (isInsertion){
        updateCoordinatesAfterInsertion(
            blockIndex,
            lineIndex,
            oldSequence,
            newSequence,
            startColumn,
            endColumn,
            sequenceLine
        );
        updateChainLine(
            blockIndex,
            lineIndex
        );
    }
    renderAlignments(alignmentBlocks);
    initializeTooltips();
    initializeChainPopovers();
    clearSelection();
    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
}

async function saveAlignmentsFile() {
    /* Salva o arquivo no servidor */
    let content = '';
    document.querySelectorAll('.alignment-line').forEach(line => content += line.textContent + '\n');
    const response = await fetch(`/project/${projectId}/save-alignments`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content })
    });
    const result = await response.json();
    alert(result.message);
}

function hideSelectionMenu(event) {
    /* Esconde o menu de edição */
    const menu = document.getElementById('selectionMenu'), modal = document.getElementById('editModal');
    if (!menu || (modal && modal.contains(event.target)) || menu.contains(event.target) || event.target.classList.contains('aa')) return;
    menu.style.display = 'none';
    clearSelection();
}

function getResidueColor(referenceAA, currentAA) {
    /* Calcula a cor baseada na similaridade química */
    referenceAA = referenceAA.toUpperCase(); currentAA = currentAA.toUpperCase();
    if (referenceAA === '-' || currentAA === '-') return '#FFFFFF';
    if (referenceAA === currentAA) return '#008CA6';
    for (const group of Object.values(aaGroups)) {
        if (group.includes(referenceAA) && group.includes(currentAA)) return '#CCCCCC';
    }
    return '#D54344';
}

function computeColumnColors(block) {
    /* Analisa colunas para definir cores de destaque */
    const reference = block.lines[2];
    const colors = Array(reference.length).fill('#FFFFFF');
    const sequenceLines = block.lines.filter(isSequenceLine);
    for (let col = 0; col < reference.length; col++) {
        const refAA = reference[col];
        let identical = 0, similar = 0, different = 0;
        for (let i = 1; i < sequenceLines.length; i++) {
            const aa = sequenceLines[i][col];
            if (!aa || aa === '-') continue;
            const color = getResidueColor(refAA, aa);
            if (color === '#008CA6') identical++;
            else if (color === '#CCCCCC') similar++;
            else different++;
        }
        if (identical > 0) colors[col] = '#008CA6';
        else if (similar > 0) colors[col] = '#CCCCCC';
        else if (different > 0) colors[col] = '#D54344';
    }
    return colors;
}

function updateCoordinatesAfterInsertion(blockIndex, lineIndex, oldSequence, newSequence, startColumn, endColumn, originalLine) {
    /* Ajusta coordenadas após inserção */
    const coordinateIndex = lineIndex + 1;
    const coordinateLine = alignmentBlocks[blockIndex].lines[coordinateIndex];
    const matches = coordinateLine.match(/-?\d+/g);

    if (!matches || matches.length < 2) return;

    let start = parseInt(matches[0]), end = parseInt(matches[1]);
    const insertedResidues = newSequence.replace(/[.-]/g, '').length;
    const sequence = alignmentBlocks[blockIndex].lines[lineIndex];
    const originalFirstAA = originalLine.search(/[A-Za-z]/);
    const originalLastAA = originalLine.split('').findLastIndex(c => /[A-Za-z]/.test(c));

    if (endColumn < originalFirstAA) start -= insertedResidues;
    if (startColumn > originalLastAA) end += insertedResidues;

    const currentFirstAA = sequence.search(/[A-Za-z]/);
    const currentLastAA = sequence.split('').findLastIndex(c => /[A-Za-z]/.test(c));
    let newCoordinateLine = Array(sequence.length).fill(' ');
    const startText = String(start);

    for (let i = 0; i < startText.length; i++){
        if (currentFirstAA + i >= 0 && currentFirstAA + i < newCoordinateLine.length){ 
            newCoordinateLine[currentFirstAA + i] = startText[i];
        }
    }

    const endText = String(end);
    for (let i = 0; i < endText.length; i++){ 
        if (currentLastAA - endText.length + 1 + i >= 0){ 
            newCoordinateLine[currentLastAA - endText.length + 1 + i] = endText[i]; 
        }
    }
    alignmentBlocks[blockIndex].lines[coordinateIndex] = newCoordinateLine.join('');
}

function isInsertionSelection() {
    /* Verifica se a seleção é válida para inserção */
    return selection.residues.length > 0 && selection.residues.every(r => r.dataset.residue === '-');
}

function openInsertModal() {
    /* Abre modal de inserção */
    const oldSequence = selection.residues.map(r => r.dataset.residue).join('');
    document.getElementById('oldSequence').value = oldSequence;
    document.getElementById('newSequence').value = '';
    document.querySelector('#editModalLabel').textContent = 'Insert Sequence';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}


function isDeleteSelection(){
    if(selection.residues.length === 0){
        return false;
    }
    const line = parseInt(selection.residues[0].dataset.line);
    if(line === 2){
        return false;
    }
    return selection.residues.every(
        residue => {
            const aa = residue.dataset.residue;
            return (/[A-Za-z]/.test(aa) || aa === '.');
        }
    );
}

function deleteSelection() {
    const blockIndex = parseInt(selection.residues[0].dataset.block);
    const lineIndex = parseInt(selection.residues[0].dataset.line);
    let sequence = alignmentBlocks[blockIndex].lines[lineIndex];
    const chars = sequence.split('');
    const selectedColumns = selection.residues.map(r => parseInt(r.dataset.column));
    const selectionStart = Math.min(...selectedColumns);
    const selectionEnd = Math.max(...selectedColumns);

    const leftTerminal = sequence.substring(0, selectionStart).replace(/[-.]/g, '').length === 0;
    const rightTerminal = sequence.substring(selectionEnd + 1).replace(/[-.]/g, '').length === 0;

    selection.residues.forEach(residue => {
        const col = parseInt(residue.dataset.column);
        const aa = residue.dataset.residue;
        if (aa === '.') { chars.splice(col, 1); chars.push('-'); }
        else if (leftTerminal || rightTerminal) { chars[col] = '-'; }
        else { chars[col] = '.'; }
    });

    alignmentBlocks[blockIndex].lines[lineIndex] = chars.join('');
    const deletedResidues =
        selection.residues.filter(
            r => /[A-Za-z]/.test(
                r.dataset.residue
            )
        ).length;

    updateCoordinatesAfterDeletion(
        blockIndex,
        lineIndex,
        leftTerminal,
        rightTerminal,
        deletedResidues
    );
    updateChainLine(
        blockIndex,
        lineIndex
    );
    renderAlignments(alignmentBlocks);
    initializeTooltips();initializeChainPopovers();
    clearSelection();
}

function isGapDeletionSelection() {
    if (selection.residues.length === 0) return false;
    const columns = selection.residues.map(r => parseInt(r.dataset.column)).sort((a, b) => a - b);
    const onlyDots = selection.residues.every(r => r.dataset.residue === '.');
    if (!onlyDots) return false;
    for (let i = 1; i < columns.length; i++) {
        if (columns[i] !== columns[i - 1] + 1) return false;
    }
    return true;
}

function deleteGap() {
    const blockIndex = parseInt(selection.residues[0].dataset.block);
    const lineIndex = parseInt(selection.residues[0].dataset.line);
    const selectedColumns = selection.residues.map(r => parseInt(r.dataset.column));
    const gapCount = selectedColumns.length;
    const minColumn = Math.min(...selectedColumns);
    const chars = alignmentBlocks[blockIndex].lines[lineIndex].split('');

    chars.splice(minColumn, gapCount);
    chars.push(...Array(gapCount).fill('-'));
    alignmentBlocks[blockIndex].lines[lineIndex] = chars.join('');
    updateChainLine(blockIndex, lineIndex);
    renderAlignments(alignmentBlocks);
    initializeTooltips();initializeChainPopovers();
    clearSelection();
}

function updateCoordinatesAfterDeletion(blockIndex, lineIndex, leftTerminal, rightTerminal, deletedResidues) {
    const coordinateIndex = lineIndex + 1;
    const coordinateLine = alignmentBlocks[blockIndex].lines[coordinateIndex];
    const matches = coordinateLine.match(/-?\d+/g);

    if (!matches || matches.length === 0) return;

    let start = parseInt(matches[0]);
    let end = parseInt(matches[matches.length - 1]);
    const sequence = alignmentBlocks[blockIndex].lines[lineIndex];
    const residueCount = (sequence.match(/[A-Za-z]/g) || []).length;

    if (residueCount === 0) {
        alignmentBlocks[blockIndex].lines[coordinateIndex] = '';
        return;
    }

    if (leftTerminal) start += deletedResidues;
    else end -= deletedResidues;

    const firstAA = sequence.search(/[A-Za-z]/);
    const lastAA = sequence.split('').findLastIndex(c => /[A-Za-z]/.test(c));
    let newCoordinateLine = Array(sequence.length).fill(' ');

    if (start === end) {
        const text = String(start);
        const pos = lastAA - text.length + 1;
        for (let i = 0; i < text.length; i++) newCoordinateLine[pos + i] = text[i];
    } else {
        const startText = String(start);
        for (let i = 0; i < startText.length; i++) newCoordinateLine[firstAA + i] = startText[i];
        const endText = String(end);
        const endPos = lastAA - endText.length + 1;
        for (let i = 0; i < endText.length; i++) newCoordinateLine[endPos + i] = endText[i];
    }
    alignmentBlocks[blockIndex].lines[coordinateIndex] = newCoordinateLine.join('');
}

function computeAlignmentStatistics(block) {
    const columnColors = computeColumnColors(block);
    let matches = 0, positives = 0, mismatches = 0;

    columnColors.forEach(color => {
        if (color === '#008CA6') matches++;
        else if (color === '#CCCCCC') positives++;
        else if (color === '#D54344') mismatches++;
    });

    const aligned = matches + positives + mismatches;
    const total = columnColors.length;

    return {
        coverage: total > 0 ? (aligned / total) * 100 : 0,
        identity: aligned > 0 ? (matches / aligned) * 100 : 0,
        positives: aligned > 0 ? ((matches + positives) / aligned) * 100 : 0
    };
}

function updateChainLine(blockIndex, lineIndex) {
    const chainIndex = lineIndex + 2;
    const originalBlocks = parseAlignments(originalAlignmentText);
    const originalChainLine = originalBlocks[blockIndex].lines[chainIndex];

    if (!originalChainLine) return;

    const leftMatch = originalChainLine.match(/([A-Za-z0-9_]+)<-/);
    const rightMatch = originalChainLine.match(/->([A-Za-z0-9_]+)/);
    if (!leftMatch || !rightMatch) return;

    const [leftName, rightName] = [leftMatch[1], rightMatch[1]];
    const sequence = alignmentBlocks[blockIndex].lines[lineIndex];
    const coordinateLine = alignmentBlocks[blockIndex].lines[lineIndex + 1];

    const firstNumberPos = coordinateLine.search(/-?\d/);
    let lastNumberPos = -1;
    for (let i = coordinateLine.length - 1; i >= 0; i--) {
        if (/\d/.test(coordinateLine[i])) { lastNumberPos = i; break; }
    }

    if (firstNumberPos < 0 || lastNumberPos < 0) return;

    let newChainLine = Array(sequence.length).fill(' ');

    const leftText = `${leftName}<-`;
    for (let i = 0; i < leftText.length; i++) {
        if (firstNumberPos + i < newChainLine.length) newChainLine[firstNumberPos + i] = leftText[i];
    }

    const rightText = `->${rightName}`;
    const rightStart = lastNumberPos - rightText.length + 1;
    for (let i = 0; i < rightText.length; i++) {
        if (rightStart + i >= 0 && rightStart + i < newChainLine.length) {
            newChainLine[rightStart + i] = rightText[i];
        }
    }

    alignmentBlocks[blockIndex].lines[chainIndex] = newChainLine.join('');
}

function renderChainLine(line) {
    return line
        .replace(/([A-Za-z0-9_]+)(<\-)/g, (match, chain, arrow) => 
            `<span class="chain-link" data-chain="${chain}">${chain}</span>${arrow}`
        )
        .replace(/(\-\>)([A-Za-z0-9_]+)/g, (match, arrow, chain) => 
            `${arrow}<span class="chain-link" data-chain="${chain}">${chain}</span>`
        );
}

function isChainLine(line){
    return (
        line.includes('<-') && line.includes('->')
    );
}

function initializeChainPopovers() {
    document.querySelectorAll('.chain-link').forEach(link => {
        const id = link.dataset.chain;
        const sequence = fastaSequences[id] || 'Sequence not found';
        
        new bootstrap.Popover(link, {
            trigger: 'click',
            html: true,
            placement: 'bottom',
            content: `<div style="max-width:700px; word-break:break-all; font-family:monospace;"><div class="sequence-popover text-end text-muted mb-2">
    <span class="close-sequence-popover">×</span>
    </div>${formatSequenceForPopover(sequence)}</div>`
        });
    });
}

function formatSequenceForPopover(sequence) {
    let numbers = '1', ruler = '|';
    for (let i = 10; i <= sequence.length; i += 10) {
        numbers += String(i).padStart(10, ' ');
        ruler += '.........|';
    }
    return `<pre style="margin:0; font-family:monospace; white-space:pre;">
${numbers}
${ruler}
${sequence}
</pre>`;
}