<?php

namespace App\Controllers;

class Project extends BaseController
{
    public function index($id)
    {
        $dados['id'] = $id;
        $dados['ready'] = false;
        $dados['structure'] = null;
        $dados['structureFormat'] = null;

        if (file_exists('./data/' . $id . '/alignments.txt')) {
            $dados['ready'] = true;
            $dados['results'] = Project::loadResults($id);

            $structure = Project::findStructure(
                './data/' . $id . '/structure',
                '/data/' . $id . '/structure'
            );
            $dados['structure'] = $structure['path'];
            $dados['structureFormat'] = $structure['format'];
        }
        return view('project', $dados);
    }

    /**
     * Locate the uploaded structure file (PDB/CIF) inside $dir and return the
     * web path fragment (relative to base_url) plus the 3Dmol format string.
     */
    private function findStructure($dir, $urlBase)
    {
        $result = ['path' => null, 'format' => null];

        if (!is_dir($dir)) {
            return $result;
        }

        foreach (glob($dir . '/*') as $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['cif', 'mmcif'])) {
                $format = 'cif';
            } elseif (in_array($ext, ['pdb', 'ent'])) {
                $format = 'pdb';
            } else {
                continue;
            }
            $result['path'] = $urlBase . '/' . rawurlencode(basename($path));
            $result['format'] = $format;
            return $result;
        }

        return $result;
    }


    private function loadResults($id)
    {
        $result = [];

        $alignmentFile = "./data/$id/alignments.txt";
        $hitsFile      = "./data/$id/alignmentHits.csv";
        $fasta      = "./data/$id/hmm.fasta";

        if (file_exists($alignmentFile)) {
            $result['alignments'] = file_get_contents($alignmentFile);
        } else {
            $result['alignments'] = null;
        }

        if (file_exists($fasta)) {
            $fastaContent = file_get_contents($fasta);
            $sequences = [];
            $currentId = null;
            foreach (explode("\n", $fastaContent) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if ($line[0] === '>') {
                    $currentId = substr($line, 1);
                    $currentId = explode('_', $currentId)[0];
                    $sequences[$currentId] = '';
                } else {
                    $sequences[$currentId] .= $line;
                }
            }
            $result['fasta'] = $sequences;
        } else {
            $result['fasta'] = null;
        }

        $result['hits'] = [];
        if (file_exists($hitsFile)) {
            $handle = fopen($hitsFile, 'r');
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if ($row[1] != '-') {
                    $result['hits'][] = $row;
                }
            }
            fclose($handle);
        }

        return $result;
    }

    public function alignments($id)
    {
        $edited = "./data/$id/alignments_edited.txt";
        $original = "./data/$id/alignments.txt";

        if($id == "Example"){
            $edited = "./example/alignments_edited.txt";
            $original = "./example/alignments.txt";
        }

        if (file_exists($edited)) {
            $content = file_get_contents($edited);
        } else {
            $content = file_get_contents($original);
        }

        return $this->response
            ->setContentType('text/plain')
            ->setBody($content);
    }

    public function saveAlignments($projectId)
    {
        $json =
            $this->request->getJSON(true);

        file_put_contents(
            "./data/$projectId/alignments_edited.txt",
            $json['content']
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Alignment exported successfully.'
        ]);
    }

    public function example(): string
    {
        $dados['id'] = 'Example';
        $dados['ready'] = true;

        $structure = Project::findStructure('./example/structure', '/example/structure');
        $dados['structure'] = $structure['path'];
        $dados['structureFormat'] = $structure['format'];

        if (file_exists('./example/alignments.txt')) {

            $result = [];

            $alignmentFile = "./example/alignments.txt";
            $hitsFile      = "./example/alignmentHits.csv";
            $fasta      = "./example/hmm.fasta";

            if (file_exists($alignmentFile)) {
                $result['alignments'] = file_get_contents($alignmentFile);
            } else {
                $result['alignments'] = null;
            }

            if (file_exists($fasta)) {
                $fastaContent = file_get_contents($fasta);
                $sequences = [];
                $currentId = null;
                foreach (explode("\n", $fastaContent) as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    if ($line[0] === '>') {
                        $currentId = substr($line, 1);
                        $currentId = explode('_', $currentId)[0];
                        $sequences[$currentId] = '';
                    } else {
                        $sequences[$currentId] .= $line;
                    }
                }
                $result['fasta'] = $sequences;
            } else {
                $result['fasta'] = null;
            }

            $result['hits'] = [];
            if (file_exists($hitsFile)) {
                $handle = fopen($hitsFile, 'r');
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    if ($row[1] != '-') {
                        $result['hits'][] = $row;
                    }
                }
                fclose($handle);
            }

            $dados['results'] = $result;
        }
        return view('example', $dados);
    }

}
