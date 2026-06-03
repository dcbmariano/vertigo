<?php helper('App\Helpers\filtra_url'); ?>

<!-- MODAL: SOBRE -->
<div class="modal fade" tabindex="-1" id="about" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <div class="">
          <img width="150" class="me-3" src="<?php echo filtra_url(base_url('/img/logo.png')); ?>">
        </div>
      </div>
      <div class="modal-body small">
        <div class="row">
        <p class="text-muted">
          Vertigo (Visual Exploration and Refinement of proTeIn sequence assiGnment in cryo-EM cOmplexes) assists the modeling and refinement of protein–RNA complexes derived from cryo-electron microscopy. The platform performs HMM-based searches against protein and RNA sequence datasets, generating multiple sequence alignments that support structure correction and validation.
</p>
        </div>
        <div class="row text-secondary">
          <div class="col-md-6 ">

          <?php 
            $autores = fopen('../AUTHORS.md', 'r'); 
            $cont = 0;
            while(!feof($autores)) {
              $linha = fgets($autores);

              if(substr($linha, 0, 1) == '#'){  // subtítulo
                echo '<strong>'.substr($linha, 2).'</strong><br>';
              }
              elseif(count(explode(' | ', $linha)) > 1){  // autor com link
                if(substr($linha, 0, 2) != '//'){
                  $autor = explode(' | ', $linha);
                  echo '<a href="'.$autor[1].'" target="_blank">'.$autor[0].'</a><br>';
                }
              }
              else{  // autor
                $autor = $linha;
                if(substr($linha, 0, 2) != '//'){
                  echo $autor.'<br>';
                }
              } 
              $cont++; if($cont == 100){ break; } // impede que mais do que 100 linhas sejam exibidas

            } ?>
          </div>
          <div class="col-6">
            <h5>Update log</h5>
            <ul class="small">
              <?php 
              $autores = fopen('../VERSION.md', 'r'); 
              $cont = 0;
              while(!feof($autores)) {
                $linha = fgets($autores);
                if(substr($linha, 0, 2) != '//' and substr($linha, 0, 2) != ''){
                  echo '<li>'.$linha.'</li>';
                }
                $cont++; if($cont == 100){ break; } // impede que mais do que 100 linhas sejam exibidas

              } ?>
            </ul>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <img height="50" class="me-3" src="<?php echo filtra_url(base_url('/img/dcc_b.svg')); ?>">
        <img height="50"  class="me-3" src="<?php echo filtra_url(base_url('/img/ufmg_b.svg')); ?>">

        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal SOBRE -->