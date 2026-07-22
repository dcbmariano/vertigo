<?php

namespace App\Controllers;

class Run extends BaseController
{
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
        mkdir($baseDir . '/structure');

        // recupera uploads
        $proteinFiles   = $this->request->getFiles()['protein_files'] ?? [];
        $rnaFiles       = $this->request->getFiles()['rna_files'] ?? [];
        $structureFiles = $this->request->getFiles()['structure_files'] ?? [];
        $proteinArgs = [];
        $rnaArgs = [];
        $structurePath = null;
 
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

        // salva estrutura (PDB/CIF) — usa o primeiro arquivo válido
        foreach ($structureFiles as $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                $destino = $baseDir . '/structure/' . $file->getName();
                $file->move(
                    $baseDir . '/structure',
                    $file->getName()
                );
                $structurePath = $destino;
                break;
            }
            else{
                echo "File size limit: 200MB. Formats allowed: .pdb, .cif or .mmcif";
            }
        }

        $proteinString = implode(' ', $proteinArgs);
        $rnaString = implode(' ', $rnaArgs);

        // executa pipeline
        #python hs.py -p data/protein.fasta -r data/rna.fasta -s data/complex.cif -o output
        $host = $_SERVER['HTTP_HOST'];
        if ($host === 'bioinfo.dcc.ufmg.br') {
            $python = '/home/liase/miniconda3/bin/python';
        } else {
            $python = 'python';
        }

        // RNA é opcional: só inclui -r quando há arquivos de RNA
        $rnaOption = $rnaString !== '' ? '-r ' . $rnaString . ' ' : '';

        // Alta sensibilidade (opcional): recupera fragmentos fracos/curtos
        $sensitivityOption = !empty($dados['high_sensitivity']) ? '--high-sensitivity ' : '';

        $command =
            'nohup '.$python.' ../app/ThirdParty/hs.py ' .
            '-p ' . $proteinString . ' ' .
            $rnaOption .
            $sensitivityOption .
            '-s ' . escapeshellarg($structurePath) . ' ' .
            '-o ' . escapeshellarg($baseDir) .
            ' > ' . escapeshellarg($baseDir . '/log.txt') .
            ' 2>&1 &';

        shell_exec($command);

        return view('running', $dados);
    }
}
