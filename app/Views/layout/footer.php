<footer class=" bg-dark text-light py-5 ">
    <!-- <div class="row px-4">
        <div class="col-2"><a href="#">
            <img src="<?= filtra_url(base_url('/img/1.png')) ?>" style="height:100px">
        </a></div>
        <div class="col-2"> <a href="#">
            <img src="<?= filtra_url(base_url('/img/3.png')) ?>" style="height:100px">
          </a></div>

      
  
        <div class="col-2"><a href="#">
            <img src="<?= filtra_url(base_url('/img/dcc_w.svg')) ?>" style="height:90px">
          </a></div>
        <div class="col-2"><a href="#">
              <img src="<?= filtra_url(base_url('/img/ufmg_w.svg')) ?>" style="height:85px">
          </a></div>

          <div class="col-2 ps-5 ms-5"><a href="#">
            <img src="<?= filtra_url(base_url('/img/2.png')) ?>" style="height:100px">
          </a></div>

      </div>
    </div> -->

    <div class="px-4 d-flex flex-column flex-sm-row justify-content-between py-2 text-secondary ">
      <p class="px-4 text-center w-100 small">© <?=date('Y')?> | Vertigo v0.26.722 - Developed by <a href="#" data-bs-toggle="modal" data-bs-target="#about" class="link-light">Hashem's Lab</a>.</p>
      <ul class="list-unstyled d-flex">
        <img src="<?= base_url('/img/hamburg.svg') ?>" width="300px">
      </ul>
    </div>

  <!-- Scripts -->
     <!-- <script src="<?=base_url('/js/main.js')?>"></script> -->
<!-- 
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.5/dist/umd/popper.min.js"
  integrity="sha384-Xe+8cL9oJa6tN/veChSP7q+mnSPaj5Bcu9mPX5F5xIGE0DVittaqT5lorf0EI7Vk" crossorigin="anonymous">
  </script> -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <!-- <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/rowgroup/1.1.2/js/dataTables.rowGroup.min.js"></script> -->

  <!-- Lista de modals -->
  <?= $this->include('modal/autores') ?>
  <!-- fim / Lista de modals -->

  <?= $this->renderSection('scripts') ?> 
  <!-- FIM Scripts -->

</footer>
</body>
</html>