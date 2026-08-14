<?php

namespace App\Support;

use Illuminate\Support\Str;

class CreditSimulationPdf
{
    private const PAGE_WIDTH = 595;

    private const PAGE_HEIGHT = 842;

    private const MARGIN = 42;

    /** @param array<int, array{tenor: string, disbursement: string, instalment: string, zero: bool}> $results */
    public function render(array $subject, array $results, string $disbursementHeading): string
    {
        $ops = [];

        $this->watermark($ops);

        $this->brandMark($ops, self::MARGIN, 775, 34);
        $this->text($ops, self::MARGIN + 44, 798, 'Kebon Jeruk Multiguna', 17, 'F2');
        $this->text($ops, self::MARGIN + 44, 776, 'Hasil Simulasi Kredit', 15, 'F2');
        $this->text($ops, 365, 798, (string) ($subject['printed_at'] ?? ''), 10, 'F1');
        $this->line($ops, self::MARGIN, 760, 553, 760);

        $y = 735;
        $this->sectionTitle($ops, self::MARGIN, $y, 'Identitas dan Simulasi');
        $y -= 22;
        $identityFields = [
            ['Nama Calon Debitur', $subject['debtor_name'] ?? '-'],
            ['Kode Referral', $subject['referral_code'] ?? '-'],
            ['Nama Referral', $subject['referral_name'] ?? '-'],
            ['Jenis Pembiayaan', $subject['product'] ?? '-'],
            ['Dasar Simulasi', $subject['mode'] ?? '-'],
        ];

        if (filled($subject['debtor_nik'] ?? null)) {
            array_splice($identityFields, 1, 0, [['NIK', $subject['debtor_nik']]]);
        }

        if (filled($subject['debtor_birth_date'] ?? null)) {
            array_splice($identityFields, 2, 0, [['Tanggal Lahir', $subject['debtor_birth_date']]]);
        }

        $y = $this->fieldGrid($ops, $y, $identityFields);

        $y -= 8;
        $this->sectionTitle($ops, self::MARGIN, $y, 'Data Kendaraan');
        $y -= 22;
        $vehicleFields = [
            ['Kendaraan', $subject['vehicle'] ?? '-'],
            ['Tahun', $subject['vehicle_year'] ?? '-'],
            ['Penggunaan Unit', $subject['usage'] ?? '-'],
            ['Type Angsuran', $subject['instalment_type'] ?? '-'],
            ['Asuransi', $subject['insurance'] ?? '-'],
            ['Domisili', $subject['domicile'] ?? '-'],
            ['Type Debitur', $subject['debtor_type'] ?? '-'],
        ];

        if (filled($subject['age_group'] ?? null)) {
            $vehicleFields[] = ['Usia Debitur', $subject['age_group']];
        }

        if (filled($subject['funding_purpose'] ?? null)) {
            $vehicleFields[] = ['Kebutuhan Dana', $subject['funding_purpose']];
        }

        $y = $this->fieldGrid($ops, $y, $vehicleFields);

        $y -= 8;
        $this->sectionTitle($ops, self::MARGIN, $y, 'Hasil Lima Tenor');
        $y -= 24;
        $y = $this->resultsTable($ops, $y, $results, $disbursementHeading);

        $y -= 22;
        $this->text($ops, self::MARGIN, $y, 'Nominal pembiayaan bersifat estimasi.', 10);
        $this->text($ops, self::MARGIN, $y - 15, 'Besarnya pembiayaan berdasarkan hasil verifikasi profil debitur dan kondisi kendaraan.', 10);
        $this->line($ops, self::MARGIN, 72, 553, 72);
        $this->text($ops, self::MARGIN, 52, 'Diunduh '.$subject['printed_at'].' - Kebon Jeruk Multiguna', 9);

        return $this->compile(implode("\n", $ops)."\n");
    }

    public function filename(array $subject): string
    {
        $debtorName = Str::slug((string) ($subject['debtor_name'] ?? 'calon-debitur'));

        if ($debtorName === '') {
            $debtorName = 'calon-debitur';
        }

        return 'simulasi-kredit-'.$debtorName.'-'.now()->format('Ymd-His').'.pdf';
    }

    /**
     * @param  list<array{0: string, 1: mixed}>  $fields
     */
    private function fieldGrid(array &$ops, int $startY, array $fields): int
    {
        $columns = [
            ['x' => self::MARGIN, 'width' => 238],
            ['x' => 315, 'width' => 238],
        ];
        $rowHeight = 34;

        foreach (array_values($fields) as $index => [$label, $value]) {
            $column = $columns[$index % 2];
            $y = $startY - (int) floor($index / 2) * $rowHeight;

            $this->text($ops, $column['x'], $y, $label, 8, 'F2', [0.43, 0.45, 0.5]);
            $this->text($ops, $column['x'], $y - 13, $this->fit((string) $value, 42), 10);
        }

        return $startY - ((int) ceil(count($fields) / 2) * $rowHeight);
    }

    /**
     * @param  array<int, array{tenor: string, disbursement: string, instalment: string, zero: bool}>  $results
     */
    private function resultsTable(array &$ops, int $startY, array $results, string $disbursementHeading): int
    {
        $x = self::MARGIN;
        $widths = [120, 205, 186];
        $rowHeight = 24;

        $this->rect($ops, $x, $startY - 17, array_sum($widths), 24, [0.95, 0.96, 0.97]);
        $this->text($ops, $x + 10, $startY - 10, 'Tenor', 9, 'F2');
        $this->text($ops, $x + $widths[0] + 10, $startY - 10, $disbursementHeading, 9, 'F2');
        $this->text($ops, $x + $widths[0] + $widths[1] + 10, $startY - 10, 'Angsuran', 9, 'F2');
        $this->line($ops, $x, $startY - 17, $x + array_sum($widths), $startY - 17);

        foreach (array_values($results) as $index => $row) {
            $y = $startY - 17 - (($index + 1) * $rowHeight);
            $color = $row['zero'] ? [0.58, 0.6, 0.65] : [0.1, 0.12, 0.16];

            $this->line($ops, $x, $y, $x + array_sum($widths), $y);
            $this->text($ops, $x + 10, $y + 8, $row['tenor'], 10, 'F1', $color);
            $this->textRight($ops, $x + $widths[0] + $widths[1] - 10, $y + 8, $row['disbursement'], 10, 'F1', $color);
            $this->textRight($ops, $x + array_sum($widths) - 10, $y + 8, $row['instalment'], 10, 'F1', $color);
        }

        return $startY - 17 - ((count($results) + 1) * $rowHeight);
    }

    private function sectionTitle(array &$ops, int $x, int $y, string $text): void
    {
        $this->text($ops, $x, $y, Str::upper($text), 9, 'F2', [0.43, 0.45, 0.5]);
    }

    private function watermark(array &$ops): void
    {
        for ($y = 28; $y < self::PAGE_HEIGHT; $y += 28) {
            for ($x = 0; $x < self::PAGE_WIDTH; $x += 112) {
                $this->text($ops, $x, $y, 'bonjemgu.com', 10, 'F1', [0.88, 0.89, 0.91]);
            }
        }
    }

    private function brandMark(array &$ops, int $x, int $y, int $size): void
    {
        $scale = $size / 100;
        $sx = fn (float $value): float => $x + ($value * $scale);
        $sy = fn (float $value): float => $y + ($value * $scale);
        $s = fn (float $value): float => $value * $scale;

        $dark = [0.478, 0.118, 0.02];
        $red = [0.725, 0.145, 0.0];
        $orange = [0.875, 0.298, 0.098];
        $peach = [0.886, 0.557, 0.286];
        $yellow = [0.969, 0.847, 0.353];
        $cream = [0.973, 0.906, 0.639];

        $this->rectFloat($ops, $sx(7), $sy(7), $s(19), $s(86), $dark);
        $this->circleClip($ops, $sx(26), $sy(71.5), $s(21.5), $yellow, $sx(26), $sy(71.5), $s(22), $s(21.5));
        $this->circleClip($ops, $sx(26), $sy(71.5), $s(21.5), $red, $sx(26), $sy(50), $s(22), $s(21.5));
        $this->circleClip($ops, $sx(26), $sy(28.5), $s(21.5), $orange, $sx(26), $sy(7), $s(22), $s(43));
        $this->filledCircle($ops, $sx(33), $sy(28.5), $s(10), $cream);
        $this->circleClip($ops, $sx(73), $sy(71.5), $s(21.5), $red, $sx(51.5), $sy(50), $s(21.5), $s(43));
        $this->circleClip($ops, $sx(73), $sy(71.5), $s(21.5), $peach, $sx(73), $sy(50), $s(21.5), $s(43));
        $this->rectFloat($ops, $sx(52), $sy(7), $s(41), $s(40), $yellow);
        $this->circleClip($ops, $sx(52), $sy(47), $s(20), $dark, $sx(52), $sy(27), $s(20), $s(20));
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function text(array &$ops, int $x, int $y, string $text, int $size, string $font = 'F1', array $color = [0.1, 0.12, 0.16]): void
    {
        $ops[] = sprintf(
            'BT /%s %d Tf %.3F %.3F %.3F rg %d %d Td (%s) Tj ET',
            $font,
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $this->pdfText($text),
        );
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function textRight(array &$ops, int $rightX, int $y, string $text, int $size, string $font = 'F1', array $color = [0.1, 0.12, 0.16]): void
    {
        $textWidth = (int) ceil(strlen($this->toWinAnsi($text)) * $size * 0.48);

        $this->text($ops, $rightX - $textWidth, $y, $text, $size, $font, $color);
    }

    private function line(array &$ops, int $x1, int $y1, int $x2, int $y2): void
    {
        $ops[] = sprintf('0.86 0.88 0.91 RG 0.5 w %d %d m %d %d l S', $x1, $y1, $x2, $y2);
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function rect(array &$ops, int $x, int $y, int $width, int $height, array $color): void
    {
        $ops[] = sprintf('%.3F %.3F %.3F rg %d %d %d %d re f', $color[0], $color[1], $color[2], $x, $y, $width, $height);
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function rectFloat(array &$ops, float $x, float $y, float $width, float $height, array $color): void
    {
        $ops[] = sprintf('%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f', $color[0], $color[1], $color[2], $x, $y, $width, $height);
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function filledCircle(array &$ops, float $cx, float $cy, float $radius, array $color): void
    {
        $ops[] = sprintf('q %.3F %.3F %.3F rg %s f Q', $color[0], $color[1], $color[2], $this->circlePath($cx, $cy, $radius));
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function circleClip(array &$ops, float $cx, float $cy, float $radius, array $color, float $clipX, float $clipY, float $clipWidth, float $clipHeight): void
    {
        $ops[] = sprintf(
            'q %.2F %.2F %.2F %.2F re W n %.3F %.3F %.3F rg %s f Q',
            $clipX,
            $clipY,
            $clipWidth,
            $clipHeight,
            $color[0],
            $color[1],
            $color[2],
            $this->circlePath($cx, $cy, $radius),
        );
    }

    private function circlePath(float $cx, float $cy, float $radius): string
    {
        $k = 0.552284749831 * $radius;

        return sprintf(
            '%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c h',
            $cx + $radius,
            $cy,
            $cx + $radius,
            $cy + $k,
            $cx + $k,
            $cy + $radius,
            $cx,
            $cy + $radius,
            $cx - $k,
            $cy + $radius,
            $cx - $radius,
            $cy + $k,
            $cx - $radius,
            $cy,
            $cx - $radius,
            $cy - $k,
            $cx - $k,
            $cy - $radius,
            $cx,
            $cy - $radius,
            $cx + $k,
            $cy - $radius,
            $cx + $radius,
            $cy - $k,
            $cx + $radius,
            $cy,
        );
    }

    private function fit(string $value, int $length): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

        return Str::length($value) > $length
            ? Str::limit($value, $length)
            : $value;
    }

    private function pdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->toWinAnsi($text));
    }

    private function toWinAnsi(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);

        if (function_exists('iconv')) {
            $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

            if ($encoded !== false) {
                return $encoded;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    private function compile(string $contentStream): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($contentStream)." >>\nstream\n".$contentStream.'endstream',
        ];

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }
}
