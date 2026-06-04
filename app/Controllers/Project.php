<?php

namespace App\Controllers;

class Project extends BaseController
{
    public function index($id)
    {
        $dados['id'] = $id;
        $dados['ready'] = false;

        if (file_exists('./data/' . $id . '/alignments.txt')) {
            $dados['ready'] = true;
            $dados['results'] = Project::loadResults($id);
        }
        return view('project', $dados);
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
                if($row[1] != '-'){
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
}
