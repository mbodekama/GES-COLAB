<?php

namespace App\Pdf;

class BulletinPaie extends BasePdf
{
    // ── Styles easyTable ─────────────────────────────────────────
    private const S_LABEL   = 'bgcolor:#f1f5f9; font-color:#94a3b8; font-style:B; font-size:7; paddingY:2; paddingX:4; border:B; border-color:#e2e8f0;';
    private const S_VALUE   = 'font-style:B; font-size:9.5; paddingY:2; paddingX:4; border:B; border-color:#e2e8f0;';
    private const S_ROW     = 'font-size:9; paddingY:2; paddingX:4; border:B; border-color:#e2e8f0;';
    private const S_ROW_RED = self::S_ROW . 'font-color:#dc2626;';
    private const S_TOTAL   = 'font-style:B; font-size:9.5; paddingY:3; paddingX:4; bgcolor:#f1f5f9; border:B; border-color:#cbd5e1;';
    private const S_EMPTY   = 'border:B; border-color:#e2e8f0;';

    public function __construct(array $data)
    {
        // Bulletin toujours sur une seule page
        parent::__construct($data, autoPageBreak: false);
    }

    public function build(): static
    {
        $this->drawHeader('BULLETIN DE PAIE');
        $this->drawBanner(
            'BULLETIN DE PAIE',
            strtoupper($this->data['period_label']) . '  —  ' . $this->data['employee_name']
        );
        $this->drawDate();

        $this->SetXY(self::ML, 56);

        $this->drawEmployeeBlock();
        $this->drawRemuneration();
        $this->drawRetenues();
        $this->drawNet();
        $this->drawChargesEmployeur();

        return $this;
    }

    // ─────────────────────────────────────────────────────────────
    // SECTIONS
    // ─────────────────────────────────────────────────────────────

    private function drawEmployeeBlock(): void
    {
        $d = $this->data;

        $table = new \easyTable($this, '%{22,28,22,28}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        // En-tête
        $table->rowStyle('bgcolor:#f1f5f9; min-height:10;');
        $table->easyCell(
            $this->e('INFORMATIONS EMPLOYÉ'),
            'colspan:4; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;'
        );
        $table->printRow();

        // Ligne 1
        $table->rowStyle('min-height:10;');
        $table->easyCell($this->e('MATRICULE'),    self::S_LABEL);
        $table->easyCell($this->e($d['employee_matricule']), self::S_VALUE . 'font-color:#1d4ed8;');
        $table->easyCell($this->e('PÉRIODE'),      self::S_LABEL);
        $table->easyCell($this->e($d['period_label']),       self::S_VALUE . 'font-color:#1d4ed8;');
        $table->printRow();

        // Ligne 2
        $table->rowStyle('min-height:10;');
        $table->easyCell($this->e('POSTE'),        self::S_LABEL);
        $table->easyCell($this->e($d['employee_position']),  self::S_VALUE);
        $table->easyCell($this->e('DÉPARTEMENT'),  self::S_LABEL);
        $table->easyCell($this->e($d['employee_department']), self::S_VALUE);
        $table->printRow();

        // Ligne 3
        $table->rowStyle('min-height:10;');
        $table->easyCell($this->e('TYPE CONTRAT'), self::S_LABEL);
        $table->easyCell($this->e($d['contract_type']),      self::S_VALUE);
        $table->easyCell($this->e('ANCIENNETÉ'),   self::S_LABEL);
        $table->easyCell($this->e($d['employee_seniority']), self::S_VALUE);
        $table->printRow();

        // Ligne 4
        $table->rowStyle('min-height:10;');
        $table->easyCell($this->e('JOURS TRAVAILLÉS'), self::S_LABEL);
        $table->easyCell($this->e($d['worked_days'] . ' j'),  self::S_VALUE);
        $table->easyCell($this->e('JOURS DE CONGÉS'),  self::S_LABEL);
        $table->easyCell($this->e($d['leave_days'] . ' j'),   self::S_VALUE);
        $table->printRow();

        $table->endTable(0);

        $this->SetLineWidth(0.35);
        $this->dc(self::BORDER);
        $this->roundedRect(self::ML, 56, self::UW, $this->GetY() - 56, 3, 'D');

        $this->SetY($this->GetY() + 4);
    }

    private function drawRemuneration(): void
    {
        $d       = $this->data;
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '%{70,30}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        $this->drawSectionHeader($table, 'RÉMUNÉRATION', 'Montant (FCFA)');

        $this->addRow($table, 'Salaire de base', $this->fmt($d['base_salary']));

        if ((float)$d['seniority_bonus'] > 0) {
            $this->addRow($table,
                "Prime d'ancienneté ({$d['seniority_rate']} %)",
                $this->fmt($d['seniority_bonus'])
            );
        }
        if ((float)$d['transport_allowance'] > 0) {
            $this->addRow($table, 'Indemnité de transport', $this->fmt($d['transport_allowance']));
        }
        if ((float)$d['housing_allowance'] > 0) {
            $this->addRow($table, 'Indemnité de logement', $this->fmt($d['housing_allowance']));
        }
        if ((float)$d['meal_allowance'] > 0) {
            $this->addRow($table, 'Indemnité de repas', $this->fmt($d['meal_allowance']));
        }

        $this->addTotalRow($table, 'SALAIRE BRUT', $this->fmt($d['gross_salary']));

        $table->endTable(0);

        $this->drawSectionBorder($yBefore);
        $this->SetY($this->GetY() + 4);
    }

    private function drawRetenues(): void
    {
        $d       = $this->data;
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '%{70,30}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        $this->drawSectionHeader($table, 'COTISATIONS & RETENUES', 'Montant (FCFA)');

        $this->addRow($table,
            "CNPS salarié ({$d['cnps_employee_rate']} %)",
            '- ' . $this->fmt($d['cnps_employee']),
            red: true
        );
        $this->addRow($table, 'IGR (barème CI)', '- ' . $this->fmt($d['igr']), red: true);

        if ((float)($d['other_deductions'] ?? 0) > 0) {
            $this->addRow($table, 'Autres retenues', '- ' . $this->fmt($d['other_deductions']), red: true);
        }

        $total = (float)$d['cnps_employee'] + (float)$d['igr'] + (float)($d['other_deductions'] ?? 0);
        $this->addTotalRow($table, 'TOTAL RETENUES', '- ' . $this->fmt($total), red: true);

        $table->endTable(0);

        $this->drawSectionBorder($yBefore);
        $this->SetY($this->GetY() + 4);
    }

    private function drawNet(): void
    {
        $table = new \easyTable($this, '%{55,45}',
            'width:180; font-family:Helvetica; border:0;');

        $table->rowStyle('bgcolor:#1d4ed8; min-height:14;');
        $table->easyCell(
            $this->e('NET À PAYER'),
            'font-style:B; font-size:12; font-color:#ffffff; paddingX:6; paddingY:5; border:0; align:L;'
        );
        $table->easyCell(
            $this->e($this->fmt($this->data['net_salary']) . ' FCFA'),
            'font-style:B; font-size:14; font-color:#ffffff; paddingX:6; paddingY:5; border:0; align:R;'
        );
        $table->printRow();
        $table->endTable(4);
    }

    private function drawChargesEmployeur(): void
    {
        $d       = $this->data;
        $yBefore = $this->GetY();

        $table = new \easyTable($this, '%{70,30}',
            'width:180; font-family:Helvetica; border:0; split-row:0;');

        $table->rowStyle('bgcolor:#f8fafc; min-height:10;');
        $table->easyCell(
            $this->e('CHARGES EMPLOYEUR'),
            'colspan:2; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;'
        );
        $table->printRow();

        $this->addRow($table,
            "CNPS employeur ({$d['cnps_employer_rate']} %)",
            $this->fmt($d['cnps_employer']) . ' FCFA',
            size: 9
        );
        $this->addTotalRow($table,
            'Coût total employeur',
            $this->fmt($d['total_employer_cost']) . ' FCFA'
        );

        $table->endTable(0);

        $this->drawSectionBorder($yBefore);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS PRIVÉS
    // ─────────────────────────────────────────────────────────────

    private function drawSectionHeader(\easyTable $table, string $label, string $colHeader): void
    {
        $table->rowStyle('bgcolor:#1d4ed8; min-height:10;');
        $table->easyCell(
            $this->e($label),
            'font-style:B; font-size:8.5; font-color:#ffffff; paddingX:5; border:0; align:L;'
        );
        $table->easyCell(
            $this->e($colHeader),
            'font-style:B; font-size:8; font-color:#bfdbfe; paddingX:5; border:0; align:R;'
        );
        $table->printRow();
    }

    private function addRow(\easyTable $table, string $label, string $value, bool $red = false, int $size = 9): void
    {
        $valStyle = $red ? self::S_ROW_RED : self::S_ROW;
        $table->rowStyle('min-height:9;');
        $table->easyCell($this->e($label), self::S_ROW . "font-size:{$size};");
        $table->easyCell($this->e($value), $valStyle . "font-size:{$size}; align:R;");
        $table->printRow();
    }

    private function addTotalRow(\easyTable $table, string $label, string $value, bool $red = false): void
    {
        $valStyle = $red
            ? self::S_TOTAL . 'font-color:#dc2626; align:R;'
            : self::S_TOTAL . 'align:R;';
        $table->rowStyle('min-height:10;');
        $table->easyCell($this->e($label), self::S_TOTAL);
        $table->easyCell($this->e($value), $valStyle);
        $table->printRow();
    }

    private function drawSectionBorder(float $yBefore): void
    {
        $this->SetLineWidth(0.35);
        $this->dc(self::BORDER);
        $this->roundedRect(self::ML, $yBefore, self::UW, $this->GetY() - $yBefore, 3, 'D');
    }

    private function fmt(float|int|string $amount): string
    {
        return $this->e(number_format((float) $amount, 0, ',', ' '));
    }
}
