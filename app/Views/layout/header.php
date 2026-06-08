<!DOCTYPE html>
<html lang="pt-br">

<head>
  <?php function filtra_url($i){ return $i; } ?>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php 
    if(isset($titulo)){ 
      echo $titulo.' | Vertigo'; 
    }else{ 
      echo 'Vertigo'; 
    }
  ?></title>

  <link rel="shortcut icon" type="image/png" href="<?=base_url('img/favicon.png')?>" >

  <!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">  
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">  <link rel="stylesheet" href="<?= filtra_url(base_url('/css/estilo.css')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="//cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css" rel="stylesheet">

  <!-- jQuery -->
<script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>  

</head>

<body>
<!-- ================= NAVBAR ================= -->

<header>
  <nav>
      <div class="container-fluid nav-content">

          <div class="logo">
              <!-- Placeholder logo -->
              <a href="<?= base_url('/') ?>"><img src="<?=base_url('/img/logo.png')?>" alt="Vertigo Logo"></a>
          </div>

          <div class="nav-links">
              
              <a href="#" data-bs-toggle="modal" data-bs-target="#about">About</a>
              <a href="<?= base_url('/documentation') ?>">Documentation</a>
              <a href="<?= base_url('/project-example') ?>">Example</a>
              <a href="<?= base_url('/#run') ?>">Run</a>
          </div>

      </div>
  </nav>
</header>
