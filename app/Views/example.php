<?php helper('App\Helpers\filtra_url'); ?>

<!-- modelo para criação de views: copie este arquivo e apague os comentários -->
<?= $this->extend('template') ?>

<?= $this->section('scripts') ?>
<!-- adicione links para scripts aqui -->

<?= $this->endSection() ?>

<?= $this->section('conteudo') ?>
<!-- adicione o conteúdo principal aqui -->

<h1>Project example</h1>

<div class="row mt-4">
    <div class="col-12 col-md-9 small">
        <div class="table-responsive">

            <table class="table table-hover table-striped" id="resultado">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID</th>
                        <th>Haplotype #1</th>
                        <th>Allele Functional #1</th>
                        <th>Allele #1</th>
                        <th>Activity Value #1</th>
                        <th>Haplotype #2</th>
                        <th>Allele Functional #2</th>
                        <th>Allele #2</th>
                        <th>Activity Value #2</th>
                        <th>Activity Score</th>
                        <th>Phenotype</th>
                        <th>CNV</th>
                         <th>Diplotype</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="col-12 col-md-3 p-4 bg-light">
        <p class="text-muted">Download files:</p>
        <ul>
            <li><a href="<?= filtra_url(base_url('/example/final_table.csv')) ?>">final_table.csv</a></li>
            <li><a href="<?= filtra_url(base_url('/example/final_cnv.csv')) ?>">final_cnv.csv</a></li>


            <li><a href="<?= filtra_url(base_url('/example/halelos.csv')) ?>">haplotypes.csv</a></li>
<!--            <li><a href="<?= filtra_url(base_url('/example/pacientes.csv')) ?>">pacientes.csv</a></li>
    -->        <li><a href="<?= filtra_url(base_url('/example/output')) ?>">output</a></li>
            <li><a href="<?= filtra_url(base_url('/example/output_freqs')) ?>">output_freqs</a></li>
            <li><a href="<?= filtra_url(base_url('/example/output_hbg')) ?>">output_hbg</a></li>
            <li><a href="<?= filtra_url(base_url('/example/output_monitor')) ?>">output_monitor</a></li>
            <li><a href="<?= filtra_url(base_url('/example/output_pairs')) ?>">output_pairs</a></li>
            <li><a href="<?= filtra_url(base_url('/example/output_probs')) ?>">output_probs</a></li>
            <li><a href="<?= filtra_url(base_url('/example/output_recom')) ?>">output_recom</a></li>
            <li><a href="<?= filtra_url(base_url('/example/log.txt')) ?>">log.txt</a></li>
        </ul>
    </div>
</div>


<!-- gráficos -->
<div class="container">
    <div class="row py-5">
        <div class="col">
            <h2>Allele frequency</h2>
            <canvas id="g1" style="max-width: auto"></canvas>
        </div>

        <div class="col">
            <h2>Diplotype frequency</h2>
            <canvas id="g2" style="max-width: auto"></canvas>
        </div>
        
    </div>
    <div class="row pb-5">
        <div class="col">
            <h2>Activity score frequency</h2>
            <canvas id="g4" style="max-width: auto"></canvas>
        </div>

        <div class="col">
            <h2>Phenotype frequency</h2>
            <canvas id="g3" style="max-width: auto"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js"></script>

<script>
    // Função para contar a frequência dos alelos
function countAlleles(data) {
    const counts = {};
    data.forEach(row => {
        // Processar a coluna 1_allele
        if (row['1_allele']){
            const alleles1 = row['1_allele'].includes('/') ? row['1_allele'].split('/') : [row['1_allele']];

            alleles1.forEach(allele => {
                if (allele) {
                    counts[allele] = (counts[allele] || 0) + 1;
                }
            });

            // Processar a coluna 2_allele
            const alleles2 = row['2_allele'].includes('/') ? row['2_allele'].split('/') : [row['2_allele']];
            alleles2.forEach(allele => {
                if (allele) {
                    counts[allele] = (counts[allele] || 0) + 1;
                }
            });
        }
    });
    return counts;
}

// Leitura do CSV e criação do gráfico
Papa.parse('<?= filtra_url(base_url("/example/final_cnv.csv")) ?>', {
    download: true,
    header: true,
    complete: function(results) {
        const data = results.data;
        
        // Contar a frequência dos alelos
        const alleleCounts = countAlleles(data);
        
        // Preparar dados para o gráfico
        const labels = Object.keys(alleleCounts);
        const values = Object.values(alleleCounts);

        // Configuração do gráfico
        const ctx = document.getElementById('g1').getContext('2d');
        const alleleChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Frequency',
                    data: values,
                    backgroundColor: 'rgba(00, 108, 255)',
                    borderColor: 'rgba(0, 0, 0, 0)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                        x: {
                            grid: {
                                display: false // Remove as linhas de grade do eixo X
                            }
                        },
                        y: {
                            grid: {
                                display: false, // Remove as linhas de grade do eixo y
                                beginAtZero: true
                            }
                        }
                    }
            }
        });
    }
});

</script>

<script>
    // Função para contar a frequência dos haplótipos
    function countHaplotypes(data, column) {
        const counts = {};
        data.forEach(row => {
            // const haplotype = row[column];
            // if (haplotype) {
            //     counts[haplotype] = (counts[haplotype] || 0) + 1;
            // }

            const totalDiplotype = row['1_allele'] + '/' + row['2_allele'];
            if (totalDiplotype) { counts[totalDiplotype] = (counts[totalDiplotype] || 0) + 1; }
        });
if ("undefined/undefined" in counts) {
    delete counts["undefined/undefined"];
}
	//console.log(counts);
        return counts;
    }

    // Leitura do CSV e criação do gráfico
    Papa.parse('<?= filtra_url(base_url("/example/final_cnv.csv")) ?>', {
        download: true,
        header: true,
        complete: function(results) {
            const data2 = results.data;

            // Contar a frequência dos haplótipos
            const haplotype1Counts = countHaplotypes(data2, '1_enzymatic_activity');
            const haplotype2Counts = countHaplotypes(data2, '2_enzymatic_activity');

            // Combinar os haplótipos e suas frequências
            const allHaplotypes = haplotype1Counts;
            //for (const haplotype in haplotype1Counts) {
            //    allHaplotypes[haplotype] = (allHaplotypes[haplotype] || 0) + haplotype1Counts[haplotype];
            //}
            //for (const haplotype in haplotype2Counts) {
            //    allHaplotypes[haplotype] = (allHaplotypes[haplotype] || 0) + haplotype2Counts[haplotype];
            //}

            // Preparar dados para o gráfico
            const labels = Object.keys(allHaplotypes);
            const values = Object.values(allHaplotypes);

            // Configuração do gráfico
            const ctx2 = document.getElementById('g2').getContext('2d');
            const haplotypeChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Frequency',
                        data: values,
                        backgroundColor: 'rgba(100, 100, 192)',
                        borderWidth: 1
                    }]
                },
                options: {
//                    indexAxis: 'y', // Isto transforma o gráfico de barras em horizontal
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                display: false // Remove as linhas de grade do eixo X
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1, // força incrementos inteiros
                                callback: function(value) {
                                    if (Number.isInteger(value)) {
                                        return value;
                                    }
                                }
                            },grid: {
                                display: false // Remove as linhas de grade do eixo y
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<script>
    // Função para contar a frequência dos fenótipos
    function countPhenotypes(data) {
        const counts = {};
        data.forEach(row => {
            const phenotype = row['phenotype'];
            if (phenotype) {
                counts[phenotype] = (counts[phenotype] || 0) + 1;
            }
        });
        return counts;
    }

    // Leitura do CSV e criação do gráfico
    Papa.parse('<?= filtra_url(base_url("/example/final_cnv.csv")) ?>', {
        download: true,
        header: true,
        complete: function(results) {
            const data = results.data;

            // Contar a frequência dos fenótipos
            const phenotypeCounts = countPhenotypes(data);

            // Preparar dados para o gráfico
            const labels = Object.keys(phenotypeCounts);
            const values = Object.values(phenotypeCounts);

            // Configuração do gráfico
            const ctx = document.getElementById('g3').getContext('2d');
            const phenotypeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Frequency',
                        data: values,
                        backgroundColor: 'rgba(75, 192, 100)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            grid: {
                                display: false // Remove as linhas de grade do eixo X
                            }
                        },
                        y: {
                            grid: {
                                display: false, // Remove as linhas de grade do eixo y
                                beginAtZero: true
                            }
                        }
                    }
                }
            });
        }
    });
</script>
<script>
    // Função para contar a frequência dos valores de total_score
    function countTotalScores(data) {
        const counts = {};
        data.forEach(row => {
            const totalScore = row['total_score'];
            if (totalScore) { counts[totalScore] = (counts[totalScore] || 0) + 1; }
        });
        return counts;
    }

    // Leitura do CSV e criação do gráfico
    Papa.parse('<?= filtra_url(base_url("/example/final_cnv.csv")) ?>', {
        download: true,
        header: true,
        complete: function(results) {
            const data = results.data;

            // Contar a frequência dos valores de total_score
            const totalScoreCounts = countTotalScores(data);

            // Preparar dados para o gráfico
            const labels = Object.keys(totalScoreCounts);
            const values = Object.values(totalScoreCounts);

            // Configuração do gráfico
            const ctx = document.getElementById('g4').getContext('2d');
            const totalScoreChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Frequency',
                        data: values,
                        backgroundColor: 'rgba(75, 100, 100)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            grid: {
                                display: false // Remove as linhas de grade do eixo X
                            }
                        },
                        y: {
                            grid: {
                                display: false, // Remove as linhas de grade do eixo y
                                beginAtZero: true
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<!-- fim/gráficos -->

<script>
    $(() => {
        fetch('<?= filtra_url(base_url("/example/final_cnv.csv")) ?>')
            .then(response => response.text())
            .then(dados => {

                // console.log('datatable:', dados)
                $('#resultado').DataTable({
                    data: dados.split('\n')
                        .filter(j => {
                            if (j.substr(0, 1) != ',') {
                                return j
                            }
                        })
                        .map(i => {
                            itens = i.split(',')
                            diplotype = itens[4] + '/' + itens[8]
                            itens = itens.concat([diplotype])
                            return itens
                        }),
                    pageLength: 50,
                })
            })

    })
</script>

<?= $this->endSection() ?>

