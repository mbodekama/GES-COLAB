<?php

namespace App\Pdf;

class TestRapport extends BasePdf
{
    public function __construct(array $data)
    {
        parent::__construct($data, autoPageBreak: true);
    }

    public function build(): static
    {
        $this->drawHeader('RAPPORT DE TESTS');
        $this->drawBanner('RAPPORT D\'EXÉCUTION DES TESTS', 'GES-COLAB — Tests de bout en bout');
        $this->drawDate();
        $this->SetY(56);

        $this->drawMeta();
        $this->drawSummary();
        $this->drawDetails();

        return $this;
    }

    private function drawMeta(): void
    {
        $table = new \easyTable($this, '%{30,70}', 'width:180; font-family:Helvetica; border:0;');

        $table->rowStyle('bgcolor:#f1f5f9; min-height:10;');
        $table->easyCell('INFORMATIONS DU RAPPORT',
            'colspan:2; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;');
        $table->printRow();

        foreach ([
            ['Testeur',       $this->e($this->data['tester'])],
            ['Date',          $this->e($this->data['generated_date'])],
            ['Application',   'GES-COLAB'],
            ['Environnement', 'Local / http://localhost:8000'],
        ] as [$label, $value]) {
            $table->rowStyle('min-height:10;');
            $table->easyCell($this->e($label),
                'bgcolor:#f8fafc; font-color:#94a3b8; font-style:B; font-size:7.5; paddingY:3; paddingX:5; border:B; border-color:#e2e8f0;');
            $table->easyCell($value,
                'font-size:9; paddingY:3; paddingX:5; border:B; border-color:#e2e8f0;');
            $table->printRow();
        }

        $table->endTable(0);
        $this->SetY($this->GetY() + 6);
    }

    private function drawSummary(): void
    {
        $scenarios = $this->data['scenarios'];
        $results   = $this->data['results'];
        $total     = count($scenarios);

        $pass    = 0;
        $fail    = 0;
        $skipped = 0;

        foreach ($scenarios as $s) {
            $r = $results[$s['id']] ?? 'none';
            if ($r === 'pass')  $pass++;
            elseif ($r === 'fail') $fail++;
            else $skipped++;
        }

        $rate = $total > 0 ? round($pass / $total * 100) : 0;

        $table = new \easyTable($this, '%{25,25,25,25}', 'width:180; font-family:Helvetica; border:0;');

        $table->rowStyle('bgcolor:#f1f5f9; min-height:10;');
        $table->easyCell('RÉSUMÉ',
            'colspan:4; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;');
        $table->printRow();

        $table->rowStyle('min-height:16;');
        foreach ([
            ['Total', $total,   '#475569', '#f8fafc'],
            ['Réussis', $pass,  '#15803d', '#f0fdf4'],
            ['Échoués', $fail,  '#dc2626', '#fef2f2'],
            ['Non testés', $skipped, '#92400e', '#fffbeb'],
        ] as [$label, $count, $color, $bg]) {
            $table->easyCell(
                $this->e($label) . "\n" . $count,
                "bgcolor:{$bg}; font-color:{$color}; font-style:B; font-size:9; paddingY:4; paddingX:5; border:1; border-color:#e2e8f0; align:C;"
            );
        }
        $table->printRow();

        $table->rowStyle('bgcolor:#eff6ff; min-height:10;');
        $table->easyCell($this->e("Taux de réussite : {$rate}%"),
            'colspan:4; font-style:B; font-size:9; font-color:#1e40af; paddingX:5; paddingY:4; border:0; align:C;');
        $table->printRow();

        $table->endTable(0);
        $this->SetY($this->GetY() + 8);
    }

    private function drawDetails(): void
    {
        $results = $this->data['results'];
        $modules = [];
        foreach ($this->data['scenarios'] as $s) {
            $modules[$s['module']][] = $s;
        }

        foreach ($modules as $moduleName => $scenarios) {
            if ($this->GetY() > 240) {
                $this->AddPage();
            }

            $table = new \easyTable($this, '%{15,55,15,15}', 'width:180; font-family:Helvetica; border:0;');

            // Module header
            $table->rowStyle('bgcolor:#1e3a5f; min-height:10;');
            $table->easyCell($this->e(mb_strtoupper($moduleName)),
                'colspan:4; font-style:B; font-size:8; font-color:#ffffff; paddingX:5; border:0;');
            $table->printRow();

            // Column headers
            $table->rowStyle('bgcolor:#f1f5f9; min-height:8;');
            foreach (['ID', 'Scénario', 'Résultat', 'Statut'] as $col) {
                $table->easyCell($this->e($col),
                    'font-style:B; font-size:7; font-color:#64748b; paddingX:5; paddingY:2; border:B; border-color:#e2e8f0;');
            }
            $table->printRow();

            foreach ($scenarios as $s) {
                $r = $results[$s['id']] ?? 'none';

                if ($r === 'pass') {
                    $label = 'Réussi';
                    $color = '#15803d';
                    $bg    = '#f0fdf4';
                } elseif ($r === 'fail') {
                    $label = 'Échoué';
                    $color = '#dc2626';
                    $bg    = '#fef2f2';
                } else {
                    $label = 'Non testé';
                    $color = '#92400e';
                    $bg    = '#fffbeb';
                }

                $table->rowStyle('min-height:9;');
                $table->easyCell($this->e($s['id']),
                    'font-size:7.5; font-style:B; font-color:#334155; paddingX:5; paddingY:2; border:B; border-color:#e2e8f0;');
                $table->easyCell($this->e($s['name']),
                    'font-size:7.5; paddingX:5; paddingY:2; border:B; border-color:#e2e8f0;');
                $table->easyCell('',
                    'border:B; border-color:#e2e8f0;');
                $table->easyCell($this->e($label),
                    "bgcolor:{$bg}; font-color:{$color}; font-style:B; font-size:7; paddingX:3; paddingY:2; border:B; border-color:#e2e8f0; align:C;");
                $table->printRow();
            }

            $table->endTable(0);
            $this->SetY($this->GetY() + 6);
        }
    }
}