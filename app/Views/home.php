<!-- modelo para criação de views: copie este arquivo e apague os comentários -->
<?= $this->extend('template') ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('/js/home.js') ?>"></script>

<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>

<!-- ================= HERO ================= -->
<section class="container py-5 mb-5">
  <div class="row align-items-center g-5">
    <div class="col-lg-6">
      <span class="badge bg-primary-subtle text-primary mb-3">
        Cryo-EM • Protein-RNA Modeling
      </span>

      <h1 class="display-4 fw-bold lh-1 mb-4">
        Improve sequence assignments
        in cryo-EM complexes
      </h1>

      <p class="lead text-secondary mb-4">
        Vertigo assists the modeling and refinement
        of protein–RNA large complexes derived from
        cryo-electron microscopy. The platform
        matches the chain fragments of a modeled
        structure against protein and RNA sequence
        datasets, generating
        multiple sequence alignments that support
        structure correction and validation.
      </p>

      <div class="d-flex gap-3">

        <a href="#run"
          class="btn btn-primary btn-lg px-4">
          Run a Project
        </a>

        <a href="<?= base_url('/documentation') ?>"
          class="btn btn-outline-secondary btn-lg px-4">
          Documentation
        </a>

      </div>

    </div>

    <div class="col-lg-6">

      <img
        src="<?= base_url('/img/home.png') ?>"
        class="img-fluid rounded-4 shadow"
        alt="Vertigo illustration">
    </div>
  </div>
</section>

<!-- ================= PROJECT ================= -->
<section class="container" id="run">
  <div class="project-card">
    <h2>Run a New Project</h2>
    <p>
      Upload the FASTA files for proteins and (optionally) RNA sequences,
      together with the modeled structure (PDB or CIF).
    </p>

    <form
      id="uploadForm"
      action="<?= base_url('/run') ?>"
      method="POST"
      enctype="multipart/form-data">
      <div class="upload-grid">
        <!-- Protein -->
        <div class="dropzone" data-input="proteinInput">
          <h3>Protein FASTA Files</h3>
          <p>Drag & drop files or click below</p>

          <button type="button" class="btn btn-primary">
            Select Files
          </button>

          <input
            type="file"
            name="protein_files[]"
            id="proteinInput"
            accept=".fasta,.fa"
            multiple
            hidden>

          <div class="file-list" id="proteinList"></div>

          <div class="upload-actions">
            <button
              type="button"
              class="btn clear-btn btn-sm btn-outline-secondary">
              Clear
            </button>
          </div>
        </div>

        <!-- RNA -->
        <div class="dropzone" data-input="rnaInput">
          <h3>RNA FASTA Files</h3>
          <p>Drag & drop files or click below</p>

          <button type="button" class="btn btn-primary">
            Select Files
          </button>

          <input
            type="file"
            name="rna_files[]"
            id="rnaInput"
            accept=".fasta,.fa"
            multiple
            hidden>

          <div class="file-list" id="rnaList"></div>
          <div class="upload-actions">
            <button
              type="button"
              class="btn clear-btn btn-sm btn-outline-secondary">
              Clear
            </button>
          </div>
        </div>

        <!-- Structure -->
        <div class="dropzone" data-input="structureInput">
          <h3>Structure (PDB/CIF)</h3>
          <p>Drag & drop file or click below</p>

          <button type="button" class="btn btn-primary">
            Select File
          </button>

          <input
            type="file"
            name="structure_files[]"
            id="structureInput"
            accept=".pdb,.cif,.mmcif"
            hidden>

          <div class="file-list" id="structureList"></div>
          <div class="upload-actions">
            <button
              type="button"
              class="btn clear-btn btn-sm btn-outline-secondary">
              Clear
            </button>
          </div>
        </div>

      </div>

      <div class="form-check d-flex justify-content-center align-items-center gap-2 mt-3">
        <input class="form-check-input" type="checkbox" name="high_sensitivity" value="1" id="highSensitivity">
        <label class="form-check-label" for="highSensitivity">
          High sensitivity &mdash; recover more (weaker) matches; may include false positives you can filter by Identity/Coverage.
        </label>
      </div>

      <div class="submit-area">
        <button type="submit" class="submit-btn">
          Launch Project
        </button>
      </div>
    </form>
  </div>
</section>


<?= $this->endSection() ?>