<!-- modelo para criação de views: copie este arquivo e apague os comentários -->
<?= $this->extend('template') ?>

<?= $this->section('scripts') ?>
<!-- adicione links para scripts aqui -->
<script>
    const local = document.querySelector('#time');

    function contar(duracao, onde) {
        let timer = duracao, minutes, seconds;

        setInterval(function () {

            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            onde.textContent = minutes + ":" + seconds;
            if (--timer < 0) {
                timer = 0;
                window.location.href = "<?=base_url('/project/'.$id)?>";
            }
        }, 1000);
    }

    window.onload = function () {
        let duracao = 5 * 1; // Converter para segundos
        contar(duracao, local); // iniciando o timer
    };

</script>
<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<!-- adicione o conteúdo principal aqui -->
<div class="text-center text-muted my-5">

    <div class="alert alert-success">Project ID <a href="<?=base_url('/project/'.$id)?>"><?=$id?></a> created.</div>

    <h1 class="mt-5">Project created</h1>
    <h1 class="display-1" id="time">00:05</h1>
    <p class="mb-5 pb-5">Please, wait... redirecting...</p>

</div>

<?= $this->endSection() ?>
