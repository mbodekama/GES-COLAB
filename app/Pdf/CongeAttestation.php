<?php

namespace App\Pdf;

class CongeAttestation extends BasePdf
{
    // ── Styles easyTable réutilisés ───────────────────────────────
    private const S_LABEL = 'bgcolor:#f1f5f9; font-color:#94a3b8; font-style:B; font-size:7.5; paddingY:3; paddingX:4; border:B; border-color:#e2e8f0;';
    private const S_VALUE = 'font-style:B; font-size:10.5; paddingY:3; paddingX:4; border:B; border-color:#e2e8f0;';
    private const S_VALUE_BLUE  = self::S_VALUE  . 'font-color:#1d4ed8;';
    private const S_VALUE_BADGE = 'bgcolor:#1d4ed8; font-color:#ffffff; font-style:B; font-size:9; paddingY:3; paddingX:4; border:B; border-color:#1d4ed8; align:C;';
    private const S_EMPTY = 'border:B; border-color:#e2e8f0;';

    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    public function build(): static
    {
        $this->drawHeader('RÉFÉRENCE', $this->data['reference']);
        $this->drawBanner('ATTESTATION DE CONGÉ', 'DOCUMENT OFFICIEL  —  RESSOURCES HUMAINES');
        $this->drawDate();

        $this->SetXY(self::ML, 58);   // début de la zone contenu

        $this->drawIntro();
        $this->drawNarrative();
        $this->drawInfoSection();
        $this->drawLegal();
        $this->drawSignatures();

        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    // SECTIONS
    // ─────────────────────────────────────────────────────────────

    private function drawIntro(): void
    {
        $table = new \easyTable($this, '{180}',
            'width:180; font-family:Helvetica; font-size:11; border:0;');
        $table->easyCell(
            $this->e('Nous soussignés, ' . $this->data['company_name'] . ', certifions que :'),
            'paddingY:2;'
        );
        $table->printRow();
        $table->endTable(5);
    }

    private function drawNarrative(): void
    {
        $d = $this->data;

        $text = sprintf(
            "Monsieur / Madame %s, occupant le poste de %s au sein du département %s, ".
            "matricule %s, bénéficie d'une %s du %s au %s inclus, soit une durée de %s jour(s).",
            $d['employee_name'],
            $d['employee_position'],
            $d['employee_department'],
            $d['employee_matricule'],
            $d['type_label'],
            $d['start_iso'],
            $d['end_iso'],
            $d['duration_days']
        );

        $table = new \easyTable($this, '{180}',
            'width:180; font-family:Helvetica; font-size:11; border:0;');
        $table->easyCell($this->e($text), 'paddingY:2; min-height:14;');
        $table->printRow();
        $table->endTable(7);
    }

    private function drawInfoSection(): void
    {
        $d       = $this->data;
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '%{22,28,22,28}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        // ── En-tête de section ──────────────────────────────────
        $table->rowStyle('bgcolor:#f1f5f9; min-height:11;');
        $table->easyCell(
            $this->e('RÉCAPITULATIF DE LA DEMANDE'),
            'colspan:4; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;'
        );
        $table->printRow();

        // ── Ligne 1 : Employé / Matricule ───────────────────────
        $table->rowStyle('min-height:13;');
        $table->easyCell($this->e('EMPLOYÉ(E)'),               self::S_LABEL);
        $table->easyCell($this->e($d['employee_name']),        self::S_VALUE);
        $table->easyCell($this->e('MATRICULE'),                self::S_LABEL);
        $table->easyCell($this->e($d['employee_matricule']),   self::S_VALUE_BLUE);
        $table->printRow();

        // ── Ligne 2 : Poste / Type ──────────────────────────────
        $table->rowStyle('min-height:13;');
        $table->easyCell($this->e('POSTE'),                    self::S_LABEL);
        $table->easyCell($this->e($d['employee_position']),    self::S_VALUE);
        $table->easyCell($this->e('TYPE DE CONGÉ'),            self::S_LABEL);
        $table->easyCell($this->e($d['type_label']),           self::S_VALUE);
        $table->printRow();

        // ── Ligne 3 : Dates ─────────────────────────────────────
        $table->rowStyle('min-height:13;');
        $table->easyCell($this->e('DATE DE DÉBUT'),            self::S_LABEL);
        $table->easyCell($this->e($d['start_date']),           self::S_VALUE_BLUE);
        $table->easyCell($this->e('DATE DE FIN'),              self::S_LABEL);
        $table->easyCell($this->e($d['end_date']),             self::S_VALUE_BLUE);
        $table->printRow();

        // ── Ligne 4 : Durée (badge) / Approuvé par ──────────────
        $table->rowStyle('min-height:13;');
        $table->easyCell($this->e('DURÉE'),                    self::S_LABEL);
        $table->easyCell($this->e($d['duration_days'] . ' jour(s)'), self::S_VALUE_BADGE);
        $table->easyCell($this->e('APPROUVÉ PAR'),             self::S_LABEL);
        $table->easyCell($this->e($d['approved_by']),          self::S_VALUE);
        $table->printRow();

        // ── Ligne 5 : Date approbation / (vide) ─────────────────
        $table->rowStyle('min-height:13;');
        $table->easyCell($this->e("DATE D'APPROBATION"),       self::S_LABEL);
        $table->easyCell($this->e($d['approved_at']),          self::S_VALUE_BLUE);
        $table->easyCell('', self::S_EMPTY . 'colspan:2;');
        $table->printRow();

        $table->endTable(0);

        // Bordure extérieure arrondie sur l'ensemble du tableau
        $this->SetLineWidth(0.35);
        $this->dc(self::BORDER);
        $this->roundedRect(self::ML, $yBefore, self::UW, $this->GetY() - $yBefore, 3.0, 'D');

        $this->SetY($this->GetY() + 7);
    }

    private function drawLegal(): void
    {
        $table = new \easyTable($this, '{180}',
            'width:180; font-family:Helvetica; font-size:10; border:T; border-color:#cbd5e1;');
        $table->easyCell(
            $this->e('La présente attestation est délivrée pour servir et valoir ce que de droit.'),
            'font-style:I; align:C; paddingY:5;'
        );
        $table->printRow();
        $table->endTable(10);
    }

    private function drawSignatures(): void
    {
        $yStart = $this->GetY();

        // ── Titres des colonnes ──────────────────────────────────
        $titleSt = 'font-style:B; font-size:8.5; font-color:#475569; align:C; paddingY:3; border:T; border-color:#cbd5e1;';
        $spaceSt = 'min-height:24; border:0;';
        $nameSt  = 'font-style:B; font-size:10.5; align:C; paddingY:2; border:0;';
        $roleSt  = 'font-size:7.5; font-color:#94a3b8; align:C; paddingY:2; border:0;';

        $table = new \easyTable($this, '%{33,34,33}',
            'width:180; font-family:Helvetica; border:0;');

        $table->rowStyle('');
        $table->easyCell($this->e("L'EMPLOYÉ(E)"),        $titleSt);
        $table->easyCell('',                               'border:T; border-color:#cbd5e1; paddingY:3;');
        $table->easyCell($this->e('LE RESPONSABLE RH'),   $titleSt);
        $table->printRow();

        // Espace de signature (le cachet sera dessiné par-dessus)
        $table->rowStyle('');
        $table->easyCell('', $spaceSt);
        $table->easyCell('', $spaceSt);
        $table->easyCell('', $spaceSt);
        $table->printRow();

        $table->rowStyle('');
        $table->easyCell($this->e($this->data['employee_name']), $nameSt);
        $table->easyCell('',                                      'border:0; paddingY:2;');
        $table->easyCell($this->e($this->data['approved_by']),   $nameSt);
        $table->printRow();

        $table->rowStyle('');
        $table->easyCell($this->e('Signature'),                             $roleSt);
        $table->easyCell('',                                                'border:0; paddingY:2;');
        $table->easyCell($this->e('Responsable des ressources humaines'),   $roleSt);
        $table->printRow();

        $table->endTable(0);

        // ── Cachet circulaire (dessiné sur l'espace libre central) ──
        // Titre : ~8mm, espace signature : 24mm → centre espace à +20mm
        $cx = self::ML + self::UW / 2;
        $cy = $yStart + 8 + 12;
        $r  = 12.0;

        $this->SetLineWidth(0.8);
        $this->dc(self::BLUE);
        $this->ellipse($cx, $cy, $r, $r);
        $this->SetLineWidth(0.3);
        $this->ellipse($cx, $cy, $r - 2.5, $r - 2.5);

        $this->SetFont('Helvetica', 'B', 7.5);
        $this->tc(self::BLUE);
        $this->SetXY($cx - $r, $cy - 4.5);
        $this->Cell($r * 2, 4, 'CACHET',   0, 0, 'C');
        $this->SetXY($cx - $r, $cy + 0.5);
        $this->Cell($r * 2, 4, 'OFFICIEL', 0, 0, 'C');
    }
}
