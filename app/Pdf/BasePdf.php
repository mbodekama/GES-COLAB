<?php

namespace App\Pdf;

abstract class BasePdf extends \exFPDF
{
    // ── Marges & largeur utile (mm) ──────────────────────────────
    protected const ML = 15.0;
    protected const UW = 180.0;

    // ── Palette [R, G, B] ────────────────────────────────────────
    protected const BLUE    = [29,  78,  216];
    protected const BLUE_LT = [191, 219, 254];
    protected const GRAY_BG = [241, 245, 249];
    protected const BORDER  = [203, 213, 225];
    protected const DARK    = [30,  41,  59];
    protected const MUTED   = [100, 116, 139];
    protected const WHITE   = [255, 255, 255];
    protected const GREEN   = [22,  163,  74];
    protected const RED     = [220,  38,  38];

    // Hauteur réservée pour le pied de page (mm)
    protected const FOOTER_H = 24.0;

    public function __construct(
        protected readonly array $data,
        bool  $autoPageBreak       = false,
        float $autoPageBreakMargin = self::FOOTER_H,
    ) {
        parent::__construct('P', 'mm', 'A4');
        $this->SetMargins(self::ML, 10.0, self::ML);
        $this->AliasNbPages();
        $this->SetAutoPageBreak($autoPageBreak, $autoPageBreakMargin);
        $this->AddPage();
    }

    abstract public function build(): static;

    // ── Pied de page (appelé automatiquement par FPDF) ───────────

    public function Footer(): void
    {
        $pageH  = $this->GetPageHeight();
        $yLine  = $pageH - self::FOOTER_H + 1;
        $yStart = $yLine + 2;
        $qrSize = self::FOOTER_H - 5;          // 19 mm

        // Séparateur
        $this->SetLineWidth(0.4);
        $this->dc(self::BLUE);
        $this->Line(self::ML, $yLine, self::ML + self::UW, $yLine);

        // QR code
        $qrContent = $this->data['verification_url']
            ?? $this->data['reference']
            ?? $this->data['company_website']
            ?? $this->data['company_name']
            ?? 'GES-COLAB';
        $this->drawQrCode($qrContent, self::ML, $yStart, $qrSize);

        // Message de vérification
        $msgX = self::ML + $qrSize + 3;
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->tc(self::DARK);
        $this->SetXY($msgX, $yStart + 2);
        $this->Cell(95, 4.5, $this->e('Vérifiez l\'authenticité de ce document'), 0, 0, 'L');

        $this->SetFont('Helvetica', '', 7);
        $this->tc(self::MUTED);
        $this->SetXY($msgX, $yStart + 7);
        $this->Cell(95, 4, $this->e('en scannant le QR code ci-contre.'), 0, 0, 'L');

        // Numéro de page
        $rightX  = self::ML + self::UW;
        $website = $this->data['company_website'] ?? '';
        $phone   = $this->data['company_phone']   ?? '';

        $this->SetFont('Helvetica', 'B', 8);
        $this->tc(self::BLUE);
        $this->SetXY(self::ML, $yStart);
        $this->Cell(self::UW, 4.5, 'Page ' . $this->PageNo() . ' / {nb}', 0, 0, 'R');

        $this->SetFont('Helvetica', '', 7);
        $this->tc(self::MUTED);
        if ($website !== '') {
            $this->SetXY(self::ML, $yStart + 6);
            $this->Cell(self::UW, 4, $this->e($website), 0, 0, 'R');
        }
        if ($phone !== '') {
            $this->SetXY(self::ML, $yStart + ($website !== '' ? 11 : 6));
            $this->Cell(self::UW, 4, $this->e('Tél : ' . $phone), 0, 0, 'R');
        }
    }

    // Dessine un QR code vectoriel via la matrice bacon/bacon-qr-code.
    // Aucune extension image requise — modules tracés avec Rect().
    protected function drawQrCode(string $content, float $x, float $y, float $size): void
    {
        $qr     = \BaconQrCode\Encoder\Encoder::encode($content, \BaconQrCode\Common\ErrorCorrectionLevel::M());
        $matrix = $qr->getMatrix();
        $n      = $matrix->getWidth();
        $cell   = $size / $n;

        // Zone blanche (quiet zone)
        $this->fc(self::WHITE);
        $this->Rect($x, $y, $size, $size, 'F');

        // Modules sombres
        $this->fc(self::DARK);
        for ($row = 0; $row < $n; $row++) {
            for ($col = 0; $col < $n; $col++) {
                if ($matrix->get($col, $row) & 1) {
                    $this->Rect(
                        $x + $col * $cell,
                        $y + $row * $cell,
                        $cell,
                        $cell,
                        'F'
                    );
                }
            }
        }
    }

    // ── En-tête commun ───────────────────────────────────────────
    //
    // La boîte de droite prend deux formes :
    //   - $rightValue fourni  → boîte bordée avec libellé + valeur (style référence)
    //   - $rightValue null    → boîte remplie bleue avec titre seul  (style document)
    protected function drawHeader(string $rightLabel, ?string $rightValue = null): void
    {
        $initials = $this->e($this->resolveInitials());
        $tagline  = $this->e($this->resolveTagline());

        // ── Badge initiales entreprise ────────────────────────
        $this->fc(self::BLUE);
        $this->roundedRect(self::ML, 10, 12, 12, 2.5, 'F');
        $this->SetFont('Helvetica', 'B', strlen($initials) > 2 ? 7 : 9);
        $this->tc(self::WHITE);
        $this->SetXY(self::ML, 13.5);
        $this->Cell(12, 5, $initials, 0, 0, 'C');

        // ── Raison sociale ────────────────────────────────────
        $this->SetFont('Helvetica', 'B', 17);
        $this->tc(self::DARK);
        $this->SetXY(self::ML + 15, 10);
        $this->Cell(95, 7, $this->e($this->data['company_name']), 0, 0, 'L');

        // ── Adresse & tagline ─────────────────────────────────
        $this->SetFont('Helvetica', '', 8);
        $this->tc(self::MUTED);
        $this->SetXY(self::ML + 15, 18);
        $this->Cell(95, 4, $tagline, 0, 0, 'L');

        // ── Boîte droite ──────────────────────────────────────
        $rx = 148.0;
        if ($rightValue !== null) {
            // Style référence : bordure bleue, libellé grisé + valeur en bleu
            $this->SetLineWidth(0.45);
            $this->dc(self::BLUE);
            $this->roundedRect($rx, 10, 47, 14, 2, 'D');

            $this->SetFont('Helvetica', '', 7);
            $this->tc(self::MUTED);
            $this->SetXY($rx, 12.5);
            $this->Cell(47, 4, $this->e($rightLabel), 0, 0, 'C');

            $this->SetFont('Helvetica', 'B', 12);
            $this->tc(self::BLUE);
            $this->SetXY($rx, 17.5);
            $this->Cell(47, 5, $this->e($rightValue), 0, 0, 'C');
        } else {
            // Style titre : fond bleu plein, libellé blanc centré
            $this->fc(self::BLUE);
            $this->roundedRect($rx, 10, 47, 14, 2, 'F');
            $this->SetFont('Helvetica', 'B', 10);
            $this->tc(self::WHITE);
            $this->SetXY($rx, 13.5);
            $this->Cell(47, 6, $this->e($rightLabel), 0, 0, 'C');
        }

        // ── Séparateur de fermeture du bloc en-tête ───────────
        $this->SetLineWidth(0.5);
        $this->dc(self::BLUE);
        $this->Line(self::ML, 25.5, self::ML + self::UW, 25.5);
    }

    // Retourne les initiales à afficher dans le badge.
    // Priorité : $data['company_initials'] → 2 premières initiales du nom.
    private function resolveInitials(): string
    {
        if (!empty($this->data['company_initials'])) {
            return mb_strtoupper(mb_substr($this->data['company_initials'], 0, 3));
        }

        $words = preg_split('/\s+/', trim($this->data['company_name'] ?? ''));
        $initials = implode('', array_map(
            fn($w) => mb_strtoupper(mb_substr($w, 0, 1)),
            array_slice($words, 0, 2)
        ));

        return $initials ?: 'GC';
    }

    // Retourne la ligne d'adresse/tagline sous la raison sociale.
    // Priorité : $data['company_tagline'] → adresse + "| Système de Gestion RH".
    private function resolveTagline(): string
    {
        if (!empty($this->data['company_tagline'])) {
            return $this->data['company_tagline'];
        }

        $address = $this->data['company_address'] ?? '';
        return $address !== '' ? $address . ' | Système de Gestion RH' : 'Système de Gestion RH';
    }

    // Bandeau coloré sous l'en-tête avec titre et sous-titre optionnel
    protected function drawBanner(string $title, string $subtitle = ''): void
    {
        $y = 28.0;
        $this->fc(self::BLUE);
        $this->Rect(self::ML, $y, self::UW, 17, 'F');

        $this->SetFont('Helvetica', 'B', 14);
        $this->tc(self::WHITE);
        $this->SetXY(self::ML, $y + 3);
        $this->Cell(self::UW, 6, $this->e($title), 0, 0, 'C');

        if ($subtitle !== '') {
            $this->SetFont('Helvetica', '', 7.5);
            $this->SetTextColor(...self::BLUE_LT);
            $this->SetXY(self::ML, $y + 10);
            $this->Cell(self::UW, 4, $this->e($subtitle), 0, 0, 'C');
        }
    }

    // Date alignée à droite, sous le bandeau
    protected function drawDate(string $city = 'Abidjan'): void
    {
        $this->SetFont('Helvetica', '', 10);
        $this->tc(self::MUTED);
        $this->SetXY(self::ML, 51);
        $this->Cell(self::UW, 5, $this->e($city . ', le ' . $this->data['generated_date']), 0, 0, 'R');
    }

    // ── Helpers couleur / fonte ──────────────────────────────────

    protected function fc(array $rgb): void { $this->SetFillColor(...$rgb); }
    protected function tc(array $rgb): void { $this->SetTextColor(...$rgb); }
    protected function dc(array $rgb): void { $this->SetDrawColor(...$rgb); }

    protected function n(int $size = 11): void
    {
        $this->SetFont('Helvetica', '', $size);
        $this->tc(self::DARK);
    }

    protected function b(int $size = 11): void
    {
        $this->SetFont('Helvetica', 'B', $size);
        $this->tc(self::BLUE);
    }

    protected function e(string $text): string
    {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
    }

    // ── Helpers formes géométriques ──────────────────────────────

    protected function roundedRect(float $x, float $y, float $w, float $h, float $r, string $style = ''): void
    {
        $op  = match ($style) { 'F' => 'f', 'FD', 'DF' => 'B', default => 'S' };
        $arc = 4 / 3 * (M_SQRT2 - 1);

        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $this->k, ($this->h - $y) * $this->k));

        $xc = $x + $w - $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $this->k, ($this->h - $y) * $this->k));
        $this->bezierArc($xc + $r * $arc, $yc - $r, $xc + $r, $yc - $r * $arc, $xc + $r, $yc);

        $xc = $x + $w - $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $this->k, ($this->h - $yc) * $this->k));
        $this->bezierArc($xc + $r, $yc + $r * $arc, $xc + $r * $arc, $yc + $r, $xc, $yc + $r);

        $xc = $x + $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $this->k, ($this->h - ($y + $h)) * $this->k));
        $this->bezierArc($xc - $r * $arc, $yc + $r, $xc - $r, $yc + $r * $arc, $xc - $r, $yc);

        $xc = $x + $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $this->k, ($this->h - $yc) * $this->k));
        $this->bezierArc($xc - $r, $yc - $r * $arc, $xc - $r * $arc, $yc - $r, $xc, $yc - $r);

        $this->_out($op);
    }

    protected function ellipse(float $cx, float $cy, float $rx, float $ry, string $style = ''): void
    {
        $op = match ($style) { 'F' => 'f', 'FD', 'DF' => 'B', default => 'S' };
        $lx = 4 / 3 * (M_SQRT2 - 1) * $rx;
        $ly = 4 / 3 * (M_SQRT2 - 1) * $ry;
        $k  = $this->k;
        $h  = $this->h;

        $this->_out(sprintf('%.2F %.2F m', ($cx + $rx) * $k, ($h - $cy) * $k));
        $this->bezierArc($cx + $rx, $cy - $ly, $cx + $lx, $cy - $ry, $cx,       $cy - $ry);
        $this->bezierArc($cx - $lx, $cy - $ry, $cx - $rx, $cy - $ly, $cx - $rx, $cy);
        $this->bezierArc($cx - $rx, $cy + $ly, $cx - $lx, $cy + $ry, $cx,       $cy + $ry);
        $this->bezierArc($cx + $lx, $cy + $ry, $cx + $rx, $cy + $ly, $cx + $rx, $cy);
        $this->_out($op);
    }

    protected function bezierArc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k, ($this->h - $y1) * $this->k,
            $x2 * $this->k, ($this->h - $y2) * $this->k,
            $x3 * $this->k, ($this->h - $y3) * $this->k,
        ));
    }

    protected function SetDash(float $black = 0, float $white = 0): void
    {
        if ($black > 0) {
            $this->_out(sprintf('[%.3F %.3F] 0 d', $black * $this->k, $white * $this->k));
        } else {
            $this->_out('[] 0 d');
        }
    }
}
