<!-- modelo para criação de views: copie este arquivo e apague os comentários -->
<?= $this->extend('template') ?>

<?= $this->section('scripts') ?>
<!-- adicione links para scripts aqui -->
<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>

<div class="container col-xxl-10 px-2 py-0">

    <h1 class="mt-5 mb-4">Documentation</h1>


    <h2 class="text-muted mb-3 mt-3">What is Vertigo?</h2>

    <p class="text-muted">
        Vertigo assists the modeling and refinement of protein–RNA large complexes derived from cryo-electron microscopy. The platform performs HMM-based searches against protein and RNA sequence datasets, generating multiple sequence alignments that support structure correction and validation.
    </p>

        <center><img src="<?= base_url('/img/home.png') ?>" width="400px"></center>


    <h2 class="mt-5 mb-3 text-muted">What output does Vertigo provide?</h2>
    <p class="text-muted">
        An alignment file.
    </p>

</div>

<br class="py-5 my-5">

<?= $this->endSection() ?>