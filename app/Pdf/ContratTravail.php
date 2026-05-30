<?php

namespace App\Pdf;

class ContratTravail extends BasePdf
{
    private const S_LABEL = 'bgcolor:#f1f5f9; font-color:#94a3b8; font-style:B; font-size:7; paddingY:2; paddingX:4; border:B; border-color:#e2e8f0;';
    private const S_VALUE = 'font-style:B; font-size:9.5; paddingY:2; paddingX:4; border:B; border-color:#e2e8f0;';
    private const S_VALUE_BLUE = self::S_VALUE . 'font-color:#1d4ed8;';

    public function __construct(array $data)
    {
        parent::__construct($data, autoPageBreak: false);
    }

    public function build(): static
    {
        $type = $this->data['type'];
        $this->drawHeader('RÉFÉRENCE', $this->data['reference']);
        $this->drawBanner(
            'CONTRAT DE TRAVAIL',
            $this->data['type_label'] . '  —  ' . $this->data['company_name']
        );
        $this->drawDate();

        $this->SetXY(self::ML, 56);

        $this->drawParties();
        $this->drawContractDetails();
        $this->drawFinancial();
        if (!empty($this->data['notes'])) {
            $this->drawNotes();
        }
        $this->drawSignatures();

        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    // SECTIONS
    // ─────────────────────────────────────────────────────────────

    private function drawParties(): void
    {
        $d       = $this->data;
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '%{50,50}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        // En-têtes des deux parties
        $table->rowStyle('min-height:10;');
        $table->easyCell(
            $this->e("L'EMPLOYEUR"),
            'bgcolor:#1d4ed8; font-color:#ffffff; font-style:B; font-size:8; paddingX:5; border:0;'
        );
        $table->easyCell(
            $this->e("L'EMPLOYÉ(E)"),
            'bgcolor:#1d4ed8; font-color:#ffffff; font-style:B; font-size:8; paddingX:5; border:0; border-left-color:#ffffff;'
        );
        $table->printRow();

        // Noms (valeur principale)
        $table->rowStyle('min-height:12;');
        $table->easyCell($this->e($d['company_name']),    'font-style:B; font-size:11; paddingX:5; paddingY:3; border:B; border-color:#e2e8f0; font-color:#1e293b;');
        $table->easyCell($this->e($d['employee_name']),   'font-style:B; font-size:11; paddingX:5; paddingY:3; border:B; border-color:#e2e8f0; font-color:#1e293b;');
        $table->printRow();

        // Ligne 2
        $table->rowStyle('min-height:9;');
        $table->easyCell($this->e($d['company_address']),   'font-size:8.5; paddingX:5; paddingY:2; border:B; border-color:#e2e8f0; font-color:#475569;');
        $table->easyCell($this->e('Matricule : ' . $d['employee_matricule']), 'font-size:8.5; paddingX:5; paddingY:2; border:B; border-color:#e2e8f0; font-color:#475569;');
        $table->printRow();

        // Ligne 3
        $table->rowStyle('min-height:9;');
        $table->easyCell($this->e($d['company_phone'] !== '' ? 'Tél : ' . $d['company_phone'] : '—'), 'font-size:8.5; paddingX:5; paddingY:2; border:0; font-color:#475569;');
        $table->easyCell($this->e($d['employee_email']),   'font-size:8.5; paddingX:5; paddingY:2; border:0; font-color:#475569;');
        $table->printRow();

        $table->endTable(0);

        $this->SetLineWidth(0.35);
        $this->dc(self::BORDER);
        $this->roundedRect(self::ML, $yBefore, self::UW, $this->GetY() - $yBefore, 3, 'D');

        // Séparateur vertical entre les deux colonnes
        $midX = self::ML + self::UW / 2;
        $this->SetLineWidth(0.2);
        $this->dc(self::BORDER);
        $this->Line($midX, $yBefore, $midX, $this->GetY());

        $this->SetY($this->GetY() + 5);
    }

    private function drawContractDetails(): void
    {
        $d       = $this->data;
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '%{22,28,22,28}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        // En-tête section
        $table->rowStyle('bgcolor:#f1f5f9; min-height:10;');
        $table->easyCell(
            $this->e('CONDITIONS DU CONTRAT'),
            'colspan:4; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;'
        );
        $table->printRow();

        // Type / N° contrat
        $table->rowStyle('min-height:11;');
        $table->easyCell($this->e('TYPE'),              self::S_LABEL);
        $table->easyCell($this->e($d['type_label']),    self::S_VALUE_BLUE);
        $table->easyCell($this->e('N° CONTRAT'),        self::S_LABEL);
        $table->easyCell($this->e($d['contract_number']), self::S_VALUE_BLUE);
        $table->printRow();

        // Poste / Département
        $table->rowStyle('min-height:11;');
        $table->easyCell($this->e('POSTE'),             self::S_LABEL);
        $table->easyCell($this->e($d['position']),      self::S_VALUE);
        $table->easyCell($this->e('DÉPARTEMENT'),       self::S_LABEL);
        $table->easyCell($this->e($d['department']),    self::S_VALUE);
        $table->printRow();

        // Date début / Date fin
        $table->rowStyle('min-height:11;');
        $table->easyCell($this->e('DATE DE DÉBUT'),     self::S_LABEL);
        $table->easyCell($this->e($d['start_date']),    self::S_VALUE);
        $table->easyCell($this->e('DATE DE FIN'),       self::S_LABEL);
        $table->easyCell($this->e($d['end_date']),      self::S_VALUE);
        $table->printRow();

        // Période d'essai / Grille salariale
        $table->rowStyle('min-height:11;');
        $table->easyCell($this->e("FIN PÉRIODE D'ESSAI"), self::S_LABEL);
        $table->easyCell($this->e($d['trial_end_date'] ?? '—'), self::S_VALUE);
        $table->easyCell($this->e('GRILLE SALARIALE'),  self::S_LABEL);
        $table->easyCell($this->e($d['salary_grid'] ?? '—'), self::S_VALUE);
        $table->printRow();

        // Date signature / Statut
        $table->rowStyle('min-height:11;');
        $table->easyCell($this->e('SIGNÉ LE'),          self::S_LABEL);
        $table->easyCell($this->e($d['signed_at']),     self::S_VALUE);
        $table->easyCell($this->e('STATUT'),            self::S_LABEL);
        $table->easyCell($this->e($d['status_label']),  self::S_VALUE);
        $table->printRow();

        $table->endTable(0);

        $this->SetLineWidth(0.35);
        $this->dc(self::BORDER);
        $this->roundedRect(self::ML, $yBefore, self::UW, $this->GetY() - $yBefore, 3, 'D');

        $this->SetY($this->GetY() + 5);
    }

    private function drawFinancial(): void
    {
        $d       = $this->data;
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '%{50,50}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        $table->rowStyle('bgcolor:#f1f5f9; min-height:10;');
        $table->easyCell(
            $this->e('CONDITIONS FINANCIÈRES'),
            'colspan:2; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;'
        );
        $table->printRow();

        $table->rowStyle('min-height:14;');
        $table->easyCell(
            $this->e('SALAIRE DE BASE MENSUEL BRUT'),
            self::S_LABEL . 'font-size:8.5;'
        );
        $table->easyCell(
            $this->e(number_format((float)$d['base_salary'], 0, ',', ' ') . ' FCFA'),
            'font-style:B; font-size:13; paddingY:2; paddingX:4; border:B; border-color:#e2e8f0; font-color:#1d4ed8; align:R;'
        );
        $table->printRow();

        $table->endTable(0);

        $this->SetLineWidth(0.35);
        $this->dc(self::BORDER);
        $this->roundedRect(self::ML, $yBefore, self::UW, $this->GetY() - $yBefore, 3, 'D');

        $this->SetY($this->GetY() + 5);
    }

    private function drawNotes(): void
    {
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '{180}',
            'width:180; font-family:Helvetica; border:0;');

        $table->rowStyle('bgcolor:#f8fafc; min-height:8;');
        $table->easyCell(
            $this->e('NOTES & CLAUSES PARTICULIÈRES'),
            'font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;'
        );
        $table->printRow();

        $table->rowStyle('');
        $table->easyCell(
            $this->e($this->data['notes']),
            'font-size:9; paddingX:5; paddingY:3; border:0; font-color:#334155;'
        );
        $table->printRow();

        $table->endTable(0);

        $this->SetLineWidth(0.35);
        $this->dc(self::BORDER);
        $this->roundedRect(self::ML, $yBefore, self::UW, $this->GetY() - $yBefore, 3, 'D');

        $this->SetY($this->GetY() + 5);
    }

    private function drawSignatures(): void
    {
        $yStart = $this->GetY();

        $titleSt = 'font-style:B; font-size:8.5; font-color:#475569; align:C; paddingY:3; border:T; border-color:#cbd5e1;';
        $spaceSt = 'min-height:22; border:0;';
        $nameSt  = 'font-style:B; font-size:9.5; align:C; paddingY:2; border:0;';
        $roleSt  = 'font-size:7.5; font-color:#94a3b8; align:C; paddingY:1; border:0;';

        $table = new \easyTable($this, '%{40,20,40}',
            'width:180; font-family:Helvetica; border:0;');

        $table->rowStyle('');
        $table->easyCell($this->e("L'EMPLOYÉ(E)"),   $titleSt);
        $table->easyCell('', 'border:T; border-color:#cbd5e1; paddingY:3;');
        $table->easyCell($this->e("L'EMPLOYEUR"),     $titleSt);
        $table->printRow();

        $table->rowStyle('');
        $table->easyCell('', $spaceSt);
        $table->easyCell('', $spaceSt);
        $table->easyCell('', $spaceSt);
        $table->printRow();

        $table->rowStyle('');
        $table->easyCell($this->e($this->data['employee_name']), $nameSt);
        $table->easyCell('', 'border:0; paddingY:2;');
        $table->easyCell($this->e($this->data['company_name']),  $nameSt);
        $table->printRow();

        $table->rowStyle('');
        $table->easyCell($this->e($this->data['employee_matricule']), $roleSt);
        $table->easyCell('', 'border:0; paddingY:1;');
        $table->easyCell($this->e('Direction Générale'), $roleSt);
        $table->printRow();

        $table->endTable(0);

        // Cachet central
        $cx = self::ML + self::UW / 2;
        $cy = $yStart + 8 + 11;
        $r  = 10.0;

        $this->SetLineWidth(0.7);
        $this->dc(self::BLUE);
        $this->ellipse($cx, $cy, $r, $r);
        $this->SetLineWidth(0.3);
        $this->ellipse($cx, $cy, $r - 2, $r - 2);

        $this->SetFont('Helvetica', 'B', 6.5);
        $this->tc(self::BLUE);
        $this->SetXY($cx - $r, $cy - 4);
        $this->Cell($r * 2, 4, 'CACHET',   0, 0, 'C');
        $this->SetXY($cx - $r, $cy + 0.5);
        $this->Cell($r * 2, 4, 'OFFICIEL', 0, 0, 'C');
    }
}
