<?php

namespace App\Controllers;

class Run extends BaseController
{
    // private function gravar($texto, $projeto)
    // {
    //     // essa função grava os dados em um arquivo
    //     $arquivo = "./data/$projeto/input.inp";
    //     $fp = fopen($arquivo, "w");
    //     fwrite($fp, $texto);
    //     fclose($fp);
    // }

    // private function gravar_cnv($texto, $projeto)
    // {
    //     // essa função grava os dados em um arquivo
    //     $arquivo = "./data/$projeto/cnv.csv";
    //     $fp = fopen($arquivo, "w");
    //     fwrite($fp, $texto);
    //     fclose($fp);
    // }

    // private function gravar_model($texto, $projeto)
    // {
    //     // essa função grava os dados em um arquivo
    //     $arquivo = "./data/$projeto/model.csv";
    //     $fp = fopen($arquivo, "w");
    //     fwrite($fp, $texto);
    //     fclose($fp);
    // }

    private function name_project($length = 6)
    {
        // dá um nome aleatório para o projeto
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function index()
    {

        // recebe os dados em uma variável
        $dados = $this->request->getPost();

        // cria projeto
        $projeto = Run::name_project();
        $dados['id'] = $projeto;

        $baseDir = './data/' . $projeto;

        mkdir($baseDir);
        mkdir($baseDir . '/proteins');
        mkdir($baseDir . '/rna');
        mkdir($baseDir . '/hmm');

        // recupera uploads
        $proteinFiles = $this->request->getFiles()['protein_files'] ?? [];
        $rnaFiles     = $this->request->getFiles()['rna_files'] ?? [];
        $hmmFiles     = $this->request->getFiles()['hmm_files'] ?? [];
        $proteinArgs = [];
        $rnaArgs = [];
 
        // salva proteínas
        foreach ($proteinFiles as $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $destino = $baseDir . '/proteins/' . $file->getName();
                $file->move(
                    $baseDir . '/proteins',
                    $file->getName()
                );
                $proteinArgs[] = escapeshellarg($destino);
            }
            else{
                echo "File size limit: 200MB. Formats allowed: .fasta or .fa";
            }
        }

        // salva RNA
        foreach ($rnaFiles as $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $destino = $baseDir . '/rna/' . $file->getName();
                $file->move(
                    $baseDir . '/rna',
                    $file->getName()
                );
                $rnaArgs[] = escapeshellarg($destino);
            }
            else{
                echo "File size limit: 200MB. Formats allowed: .fasta or .fa";
            }
        }

        // salva HMM
        foreach ($hmmFiles as $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $file->move(
                    $baseDir . '/hmm',
                    $file->getName()
                );
            }
            else{
                echo "Maximum File Limit: 1000. File size limit: 200MB. Formats allowed: .hmm";
            }
        }

        $proteinString = implode(' ', $proteinArgs);
        $rnaString = implode(' ', $rnaArgs);

        // executa pipeline
        #python hs.py -p data/protein.fasta -r data/rna.fasta -d data/hmm_profiles -o output
        
        $host = $_SERVER['HTTP_HOST'];
        if ($host === 'bioinfo.dcc.ufmg.br') {
            $python = '/home/liase/miniconda3/bin/python';
        } else {
            $python = 'python';
        }

        $command =
            'nohup '.$python.' ../app/ThirdParty/hs.py ' .
            '-p ' . $proteinString . ' ' .
            '-r ' . $rnaString . ' ' .
            '-d ' . escapeshellarg($baseDir . '/hmm') . ' ' .
            '-o ' . escapeshellarg($baseDir) .
            ' > ' . escapeshellarg($baseDir . '/log.txt') .
            ' 2>&1 &';

        shell_exec($command);

        // // grava dados no arquivo "input.inp"
        // Run::gravar($dados['input'], $projeto);
        // Run::gravar_model($dados['model'], $projeto);

        // if (!empty($dados['cnvData'])) {
        //     Run::gravar_cnv($dados['cnvData'], $projeto);
        // }

        return view('running', $dados);
    }
}
