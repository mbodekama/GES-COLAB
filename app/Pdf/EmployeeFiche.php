<?php

namespace App\Pdf;

class EmployeeFiche extends BasePdf
{
    public function __construct(array $data)
    {
        parent::__construct($data, autoPageBreak: true, autoPageBreakMargin: self::FOOTER_H + 2);
    }

    public function build(): static
    {
        $this->drawHeader('FICHE EMPLOYÉ');
        $this->drawIdentityBlock();
        $this->drawSectionTitle('Informations personnelles');
        $this->drawPersonalInfo();
        $this->drawSectionTitle('Informations professionnelles & Contrat');
        $this->drawProfessionalInfo();
        if (!empty($this->data['leaves'])) {
            $this->drawSectionTitle('Historique des congés');
            $this->drawLeaveHistory();
        }
        $this->drawSignatures();
        $this->drawFooter();
        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    // SECTIONS
    // ─────────────────────────────────────────────────────────────

    private function drawIdentityBlock(): void
    {
        $y = 28.0;

        $this->fc(self::GRAY_BG);
        $this->roundedRect(self::ML, $y, self::UW, 17, 3, 'F');

        // Cercle avatar avec initiales
        $cx = self::ML + 12;
        $cy = $y + 8.5;
        $this->fc([230, 241, 251]);
        $this->dc(self::BLUE);
        $this->SetLineWidth(0.5);
        $this->ellipse($cx, $cy, 7.5, 7.5, 'FD');
        $this->SetFont('Helvetica', 'B', 10);
        $this->tc(self::BLUE);
        $this->SetXY($cx - 7.5, $cy - 4);
        $this->Cell(15, 8, $this->e($this->data['initials']), 0, 0, 'C');

        $this->SetFont('Helvetica', 'B', 13);
        $this->tc(self::DARK);
        $this->SetXY(self::ML + 23, $y + 1.5);
        $this->Cell(100, 6, $this->e($this->data['full_name']), 0, 0, 'L');

        $this->SetFont('Helvetica', '', 9);
        $this->tc(self::MUTED);
        $this->SetXY(self::ML + 23, $y + 7.5);
        $this->Cell(100, 4.5, $this->e($this->data['position'] . ' — ' . $this->data['department']), 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->tc(self::BLUE);
        $this->SetXY(self::ML + 23, $y + 12.5);
        $this->Cell(60, 4, $this->e($this->data['matricule']), 0, 0, 'L');

        // Badge statut
        $statusColor = $this->data['status'] === 'active' ? self::GREEN : self::RED;
        $bw = 28; $bh = 6;
        $bx = self::ML + self::UW - $bw - 3;
        $by = $y + 5.5;
        $this->fc($statusColor);
        $this->roundedRect($bx, $by, $bw, $bh, 3, 'F');
        $this->SetFont('Helvetica', 'B', 8);
        $this->tc(self::WHITE);
        $this->SetXY($bx, $by + 0.8);
        $this->Cell($bw, $bh - 1, $this->e(strtoupper($this->data['status_label'])), 0, 0, 'C');
    }

    private function drawSectionTitle(string $title): void
    {
        $y = $this->GetY() + 4;

        $this->fc(self::BLUE);
        $this->Rect(self::ML, $y, 1.5, 7, 'F');

        $this->fc(self::GRAY_BG);
        $this->Rect(self::ML + 1.5, $y, self::UW - 1.5, 7, 'F');

        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(71, 85, 105);
        $this->SetXY(self::ML + 5, $y + 1.5);
        $this->Cell(self::UW - 5, 5, $this->e(strtoupper($title)), 0, 0, 'L');

        $this->SetY($y + 8);
    }

    private function drawPersonalInfo(): void
    {
        $table = new \easyTable(
            $this,
            '%{22,28,22,28}',
            'width:180; border:B; border-color:#e2e8f0; font-size:9; font-family:Helvetica;'
        );

        $d = $this->data;
        $labelStyle = 'bgcolor:#f8fafc; font-color:#94a3b8; font-style:B; font-size:8; paddingY:2;';
        $valStyle   = 'font-style:B; font-size:9.5; paddingY:2;';

        $rows = [
            [$this->e('DATE DE NAISSANCE'),   $labelStyle, $this->e($d['birth_date']),       $valStyle,
             $this->e('LIEU DE NAISSANCE'),   $labelStyle, $this->e($d['birth_place']),       $valStyle],
            [$this->e('NATIONALITÉ'),         $labelStyle, $this->e($d['nationality']),       $valStyle,
             $this->e('SITUATION FAM.'),      $labelStyle, $this->e($d['marital_status']),    $valStyle],
            [$this->e('ENFANTS'),             $labelStyle, $this->e($d['children_count']),    $valStyle,
             $this->e('N° CNPS'),             $labelStyle, $this->e($d['cnps_number']),       $valStyle],
            [$this->e('TÉLÉPHONE'),           $labelStyle, $this->e($d['phone']),             $valStyle,
             $this->e('EMAIL'),               $labelStyle, $this->e($d['email']),             $valStyle],
        ];

        foreach ($rows as $row) {
            $table->rowStyle('min-height:13;');
            $table->easyCell($row[0], $row[1]);
            $table->easyCell($row[2], $row[3]);
            $table->easyCell($row[4], $row[5]);
            $table->easyCell($row[6], $row[7]);
            $table->printRow();
        }

        $table->rowStyle('min-height:13;');
        $table->easyCell($this->e('ADRESSE'), $labelStyle);
        $table->easyCell($this->e($d['address']), $valStyle . 'colspan:3;');
        $table->printRow();

        $table->endTable(3);
    }

    private function drawProfessionalInfo(): void
    {
        $table = new \easyTable(
            $this,
            '%{22,28,22,28}',
            'width:180; border:B; border-color:#e2e8f0; font-size:9; font-family:Helvetica;'
        );

        $d = $this->data;
        $labelStyle = 'bgcolor:#f8fafc; font-color:#94a3b8; font-style:B; font-size:8; paddingY:2;';
        $valStyle   = 'font-style:B; font-size:9.5; paddingY:2;';
        $valBlue    = $valStyle . 'font-color:#1d4ed8;';

        $rows = [
            [$this->e("DATE D'EMBAUCHE"),   $labelStyle, $this->e($d['hire_date']),        $valBlue,
             $this->e('ANCIENNETÉ'),         $labelStyle, $this->e($d['seniority']),        $valStyle],
            [$this->e('SOLDE CONGÉS'),       $labelStyle, $this->e($d['leave_balance']),    $valBlue,
             $this->e('N° CONTRAT'),         $labelStyle, $this->e($d['contract_number']),  $valStyle],
            [$this->e('TYPE CONTRAT'),       $labelStyle, $this->e($d['contract_type']),    $valStyle,
             $this->e('SALAIRE DE BASE'),    $labelStyle, $this->e($d['base_salary']),      $valBlue],
            [$this->e('DÉBUT CONTRAT'),      $labelStyle, $this->e($d['contract_start']),   $valBlue,
             $this->e('FIN CONTRAT'),        $labelStyle, $this->e($d['contract_end']),     $valBlue],
        ];

        foreach ($rows as $row) {
            $table->rowStyle('min-height:13;');
            $table->easyCell($row[0], $row[1]);
            $table->easyCell($row[2], $row[3]);
            $table->easyCell($row[4], $row[5]);
            $table->easyCell($row[6], $row[7]);
            $table->printRow();
        }

        $table->endTable(3);
    }

    private function drawLeaveHistory(): void
    {
        $table = new \easyTable(
            $this,
            '{40,35,27,27,21,30}',
            'width:180; split-row:1; border:1; border-color:#e2e8f0; font-size:9; font-family:Helvetica;'
        );

        $headerStyle = 'bgcolor:#1d4ed8; font-color:#ffffff; font-style:B; font-size:8.5; min-height:9; align:C;';
        $table->rowStyle($headerStyle);
        foreach (['N° DEMANDE', 'TYPE', 'DÉBUT', 'FIN', 'JOURS', 'STATUT'] as $h) {
            $table->easyCell($this->e($h));
        }
        $table->printRow(true);

        foreach ($this->data['leaves'] as $i => $leave) {
            $rowBg = ($i % 2 === 0) ? 'bgcolor:#f8fafc;' : 'bgcolor:#ffffff;';
            $table->rowStyle($rowBg . 'min-height:8; align:{LLLLRR};');
            $table->easyCell($this->e($leave['number']));
            $table->easyCell($this->e($leave['type']));
            $table->easyCell($this->e($leave['start']));
            $table->easyCell($this->e($leave['end']));
            $table->easyCell($this->e($leave['days'] . 'j'));
            $table->easyCell($this->e($leave['status']));
            $table->printRow();
        }

        $table->endTable(4);
    }

    private function drawSignatures(): void
    {
        $y = $this->GetY() + 4;

        $this->SetLineWidth(0.3);
        $this->dc(self::BORDER);
        $this->Line(self::ML, $y, self::ML + self::UW, $y);

        $cw = self::UW / 2;
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetTextColor(71, 85, 105);

        $this->SetXY(self::ML, $y + 4);
        $this->Cell($cw, 5, $this->e("L'EMPLOYÉ(E)"), 0, 0, 'C');
        $this->SetXY(self::ML + $cw, $y + 4);
        $this->Cell($cw, 5, 'VISA RH / DIRECTION', 0, 0, 'C');

        $this->SetLineWidth(0.3);
        $this->SetDrawColor(180, 180, 180);
        $this->SetDash(1, 1);
        $this->roundedRect(self::ML + 2, $y + 11, $cw - 4, 18, 2, 'D');
        $this->roundedRect(self::ML + $cw + 2, $y + 11, $cw - 4, 18, 2, 'D');
        $this->SetDash();

        $this->SetFont('Helvetica', '', 8);
        $this->tc(self::MUTED);
        $this->SetXY(self::ML + 2, $y + 22);
        $this->Cell($cw - 4, 4, $this->e('Signature'), 0, 0, 'C');
        $this->SetXY(self::ML + $cw + 2, $y + 22);
        $this->Cell($cw - 4, 4, $this->e('Cachet & Signature'), 0, 0, 'C');
    }

    private function drawFooter(): void
    {
        $y = $this->GetY() + 6;
        $this->SetLineWidth(0.3);
        $this->dc(self::BORDER);
        $this->Line(self::ML, $y, self::ML + self::UW, $y);

        $this->SetFont('Helvetica', '', 8);
        $this->tc(self::MUTED);
        $this->SetXY(self::ML, $y + 3);
        $this->Cell(60, 4, $this->e('Généré le ' . $this->data['generated_at']), 0, 0, 'L');
        $this->SetXY(self::ML + 60, $y + 3);
        $this->Cell(60, 4, $this->e('Document confidentiel — Usage interne'), 0, 0, 'C');
        $this->SetXY(self::ML + 120, $y + 3);
        $this->Cell(60, 4, $this->e('GES-COLAB — Gestion RH'), 0, 0, 'R');
    }
}
