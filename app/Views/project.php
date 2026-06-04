<?php helper('App\Helpers\filtra_url'); ?>

<!-- modelo para criação de views: copie este arquivo e apague os comentários -->
<?= $this->extend('template') ?>

<?= $this->section('scripts') ?>

<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.bootstrap5.js"></script>
<script src="<?= base_url('/js/datatables_project.js') ?>"></script>
<script src="<?= base_url('/js/alignments.js') ?>"></script>

<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<!-- adicione o conteúdo principal aqui -->
<?php if (!$ready): ?>
    <div class="container">
        <div class="text-center text-muted my-5">
            <div class="alert alert-info small">This is project ID <a href="<?= base_url('/project/' . $id) ?>"><?= $id ?></a>. When processing is complete, this page will automatically refresh.</div>
            <h1 class="mt-5 pt-5">HMM Search is running...</h1>
            <p class="mb-5">This page will be updated every 60 seconds. Please, wait...</p>
            <meta http-equiv="refresh" content="60"><!-- atualiza a cada 60 segundos -->
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
                            <li><a class="dropdown-item" href="<?= base_url('/data/' . $id . '/alignmentHits.csv') ?>">Hits list (csv)</a></li>
                            <li><a class="dropdown-item mt-2" href="<?= base_url('/data/' . $id . '/alignments.txt') ?>">Alignments (original)</a></li>
                            <li><a class="dropdown-item mt-2" href="<?= base_url('/data/' . $id . '/alignments_edited.txt') ?>">Alignments (updated)</a></li>
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="saveAlignmentsBtn">
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
    function renderAlignmentLine($line)
    {
        $html = '';
        foreach (str_split($line) as $char) {
            $html .= '<span class="aa" data-aa="' .
                htmlspecialchars($char) .
                '">' .
                htmlspecialchars($char) .
                '</span>';
        }
        return $html;
    }
    $blocks = preg_split(
        '/(?=^>)/m',
        trim($results['alignments'])
    );

    $header = $results['hits'][0] ?? [];
    $rows = array_slice($results['hits'], 1);
    ?>

    <script>
        const fastaSequences = <?= json_encode($results['fasta']) ?>;
    </script>

    <table
        id="hitsTable"
        class="table table-striped table-hover table-bordered table-sm small">
        <thead>
            <tr>
                <?php foreach ($header as $column): ?>
                    <th><?= esc($column) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $i => $cell): ?>
                        <td>
                            <?php if ($i === 0): ?>
                                <a href="#"
                                    class="sequence-link"
                                    data-id="<?= esc($cell) ?>">
                                    <?= esc($cell) ?>
                                </a>
                            <?php elseif ($i === 2): ?>
                                <?php $anchor = '>' . str_replace('|', '_', $cell); ?>
                                <a href="#<?= esc($anchor) ?>">
                                    <?= esc($cell) ?>
                                </a>
                            <?php else: ?>
                                <?= esc($cell) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>


    <h2>Alignments</h2>
    <script>
        const projectId = "<?= esc($id) ?>";
    </script>

    <div id="alignments" class="mb-5 pb-5"></div>
    
    <div
        id="selectionMenu"
        class="card"
        style="
        position:absolute;
        display:none;
        z-index:9999;
    ">
        <button
            id="editSelectionBtn"
            class="btn btn-sm btn-dark">
            Edit
        </button>
        <button
            id="insertSelectionBtn"
            class="btn btn-sm btn-success"
            style="display:none">
            Insert
        </button>
    </div>

    <?= $this->include('modal/edit_sequence') ?>
    <?= $this->include('modal/view_hmm_fasta') ?>

<?php endif; ?>

<?= $this->endSection() ?>