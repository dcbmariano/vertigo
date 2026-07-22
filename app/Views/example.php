<!-- Example page: same structure as a project page; only the data source
     (/public/example) and the read-only Save button differ. -->
<?= $this->extend('template') ?>

<?= $this->section('scripts') ?>

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap5.js"></script>
<script src="https://3dmol.org/build/3Dmol-min.js"></script>
<script src="<?= base_url('/js/molviewer.js') ?>"></script>
<script src="<?= base_url('/js/datatables_project.js') ?>"></script>
<script src="<?= base_url('/js/alignments.js') ?>"></script>

<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<?php if (!$ready): ?>
    <div class="container">
        <div class="text-center text-muted my-5">
            <div class="alert alert-info small">This is project ID <a href="<?= base_url('/project/' . $id) ?>"><?= $id ?></a>. When processing is complete, this page will automatically refresh.</div>
            <h1 class="mt-5 pt-5">Search is running...</h1>
            <p class="mb-5">This page will be updated every 60 seconds. Please, wait...</p>
            <meta http-equiv="refresh" content="60">
            <img src="<?= base_url('/img/loading.gif') ?>" class="text-center mb-5">
        </div>
    </div>
<?php else: ?>
    <section class="row mb-4" style="height: 180px; margin-top:-20px; background-color:#e4e4e4">
        <div class="col-9 py-5 px-4">
            <h2>
                <strong><?= $id ?></strong>
                <div class="dropdown d-inline ms-2" title="Export files">
                    <div class="dropdown d-inline">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Download
                        </button>
                        <ul class="dropdown-menu">
                            <li><b class="ms-3">Export</b></li>
                            <hr>
                            <li><a class="dropdown-item" href="<?= base_url('/example/alignmentHits.csv') ?>">Hits list (csv)</a></li>
                            <li><a class="dropdown-item mt-2" href="<?= base_url('/example/alignments.txt') ?>">Alignments (original)</a></li>
                            <li><a class="dropdown-item mt-2" href="<?= base_url('/example/alignments_edited.txt') ?>">Alignments (updated)</a></li>
                        </ul>
                    </div>
                </div>
                <button
                    id="expandSequencesBtn"
                    class="btn btn-dark">
                    <i class="bi bi-arrows-expand-vertical"></i>
                    Expand Sequences
                </button>
                <button type="button" class="btn btn-primary" id="saveAlignmentsBtn" disabled title="You cannot save in the example page">
                    <i class="bi bi-floppy"></i> Save alignment
                </button>
            </h2>
        </div>
        <div class="col-3 text-light" style="background-color: #00bc9e;">
            <p style="text-align: center; font-size: 90px; padding-top:10px">
                <strong id="mutations_found_title"></ /?=count($results['hits']) ?></strong>
            </p>
            <p style="font-size: 14px; text-align:center; margin-top: -30px">
                alignments
                <a href="#" data-toggle="modal" data-target="#help" style="color:#fff"><span class="glyphicon glyphicon-info-sign"></span></a>
            </p>
        </div>
    </section>

    <?php
    $rows = array_slice($results['hits'], 1);
    $hasStructure = !empty($structure);
    ?>

    <script>
        const fastaSequences = <?= json_encode($results['fasta']) ?>;
        const structureUrl = <?= $hasStructure ? json_encode(base_url($structure)) : 'null' ?>;
        const structureFormat = <?= $hasStructure ? json_encode($structureFormat) : 'null' ?>;
    </script>

    <div class="row g-3">
        <div class="<?= $hasStructure ? 'col-xl-8' : 'col-12' ?>">
            <table
                id="hitsTable"
                class="table table-striped table-hover table-bordered table-sm small">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Chain</th>
                        <th>Reference</th>
                        <th>Score</th>
                        <th>Identity</th>
                        <th>Positives</th>
                        <th>Coverage</th>
                        <th>Type</th>
                        <?php if ($hasStructure): ?><th class="text-center">View</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        [$chain, $ref, $score, $identity, $positives, $coverage, $ftype] = array_pad($row, 7, '');
                        $anchor = '>' . str_replace('|', '_', $ref);
                        ?>
                        <tr data-chain="<?= esc($chain) ?>">
                            <td class="dt-block-id"></td>
                            <td>
                                <a href="#" class="sequence-link" data-id="<?= esc($chain) ?>">
                                    <?= esc($chain) ?>
                                </a>
                            </td>
                            <td>
                                <a href="#<?= esc($anchor) ?>"><?= esc($ref) ?></a>
                            </td>
                            <td><?= esc($score) ?></td>
                            <td><?= esc($identity) ?></td>
                            <td><?= esc($positives) ?></td>
                            <td><?= esc($coverage) ?></td>
                            <td><?= esc($ftype) ?></td>
                            <?php if ($hasStructure): ?>
                            <td class="text-center">
                                <button type="button"
                                    class="btn btn-link p-0 view-chain-btn"
                                    style="color:#00bc9e; line-height:1;"
                                    data-chain="<?= esc($chain) ?>"
                                    data-ref="<?= esc($anchor) ?>"
                                    title="Visualize chain in 3D">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($hasStructure): ?>
        <div class="col-xl-4">
            <div class="card position-sticky" style="top: 1rem; background: var(--bg); border-color: var(--border);">
                <div class="card-header py-1 small d-flex justify-content-between align-items-center bg-transparent">
                    <span class="d-flex align-items-center gap-2">
                        <a href="#" id="show3DBtn" title="Back to the 3D view">
                            <i class="bi bi-box"></i> Draft 3D Structure
                        </a>
                        <button type="button" id="showGraphBtn" class="btn btn-outline-secondary btn-sm py-0"
                            style="font-size:12px; line-height:1.6;" title="Select a chain first (click the eye button)" disabled>
                            <i class="bi bi-diagram-3"></i> Graph
                        </button>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <span class="badge" id="molChainLabel" style="display:none; background:#00bc9e; color:#fff;"></span>
                        <button type="button" id="goToAlignmentBtn" class="btn btn-sm py-0"
                            style="display:none; background:#4f46e5; color:#fff; font-size:12px; line-height:1.6;"
                            title="Go to this chain's alignment">
                            <i class="bi bi-box-arrow-down"></i> Go to alignment
                        </button>
                    </span>
                </div>
                <div id="molviewer" style="height: 478px; position: relative;"></div>
                <div id="molgraph" style="height: 478px; position: relative; display: none;"></div>
                <div class="card-footer py-1 text-muted bg-transparent" style="font-size: 12px;">
                    Click <i class="bi bi-eye"></i> to highlight a chain (click an atom for residue info),
                    or <i class="bi bi-diagram-3"></i> Graph to see its neighbouring chains.
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row mt-5">
        <div class="col"><h2 class="pt-4 pb-1"><strong>Alignments</strong></h2></div>
        <div class="col text-end mt-4 pb-2"><button class="btn btn-outline-primary" id="createAlignmentBlockBtn"><i class="bi bi-plus-circle-fill"></i> Add alignment block</button></div>
    </div>

    <script>
        const projectId = "<?= esc($id) ?>";
        const base_url = "<?= base_url() ?>";
    </script>

    <div id="alignments" class="mb-5 pb-5"></div>

    <div id="selectionMenu" class="card bg-dark p-2" style="position:absolute; display:none; z-index:9999;">
        <button id="moveLeftBtn" class="btn btn-sm btn-dark">
            ←
        </button>
        <button id="moveRightBtn" class="btn btn-sm btn-dark">
            →
        </button>
        <button id="editSelectionBtn" class="btn btn-sm btn-dark">
            Edit
        </button>
        <button id="deleteSelectionBtn" class="btn btn-sm btn-dark" style="display:none">
            Delete
        </button>
        <button id="deleteGapBtn" class="btn btn-sm btn-dark" style="display:none">
            Delete Gap
        </button>
        <button id="insertSelectionBtn" class="btn btn-sm btn-dark" style="display:none">
            Insert
        </button>
    </div>

    <button onclick="window.scrollTo({top:0,behavior:'smooth'})" class="btn btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Back to top"
    style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"><i class="bi bi-arrow-up"></i></button>

    <?= $this->include('modal/edit_sequence') ?>
    <?= $this->include('modal/view_hmm_fasta') ?>
    <?= $this->include('modal/add_alignment') ?>
    <?= $this->include('modal/remove_alignment') ?>
    <?= $this->include('modal/create_block') ?>
    <?= $this->include('modal/delete_block') ?>

<?php endif; ?>

<?= $this->endSection() ?>
