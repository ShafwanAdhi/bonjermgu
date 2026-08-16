<?php

namespace App\Support;

use App\Models\SprintOffering;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class SprintOfferingWorkbookImporter
{
    private const MAIN_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** @var array<string, array{channel: string|null}> */
    private const OFFERING_SHEETS = [
        'new REFERRAL' => ['channel' => 'Referral'],
        'new TELEMARKETING' => ['channel' => 'Telemarketing'],
        'new CAPTIVE' => ['channel' => null],
        'new WIRA BISNIS' => ['channel' => 'Wira Bisnis'],
        'new SHOWROOM' => ['channel' => 'Showroom'],
    ];

    /** @return array{imported: int, skipped: int} */
    public function import(string $path): array
    {
        $rows = $this->extract($path);

        DB::transaction(function () use ($rows): void {
            SprintOffering::query()->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                SprintOffering::query()->insert($chunk);
            }
        });

        return ['imported' => count($rows), 'skipped' => 0];
    }

    /** @return array<int, array<string, mixed>> */
    public function extract(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Workbook SPRINT tidak ditemukan: {$path}");
        }

        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException("Workbook SPRINT tidak dapat dibuka: {$path}");
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheets = $this->sheetTargets($zip);
            $workbook = basename($path);
            $rows = [];
            $seen = [];

            foreach (self::OFFERING_SHEETS as $sheetName => $meta) {
                if (! isset($sheets[$sheetName])) {
                    continue;
                }

                foreach ($this->rowsFromSheet($zip, $sheets[$sheetName], $sharedStrings) as $rowNumber => $cells) {
                    $row = $this->offeringRow($workbook, $sheetName, $rowNumber, $cells, $meta['channel']);

                    if ($row === null || isset($seen[$row['fingerprint']])) {
                        continue;
                    }

                    $seen[$row['fingerprint']] = true;
                    $rows[] = $row;
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /** @return array<string, string> */
    private function sheetTargets(ZipArchive $zip): array
    {
        $workbook = $this->xml($zip, 'xl/workbook.xml');
        $relations = $this->text($zip, 'xl/_rels/workbook.xml.rels');
        $relationTargets = [];

        preg_match_all('/<Relationship[^>]+Id="([^"]+)"[^>]+Target="([^"]+)"/', $relations, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $target = $match[2];
            $relationTargets[$match[1]] = str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
        }

        $workbook->registerXPathNamespace('x', self::MAIN_NAMESPACE);
        $targets = [];

        foreach ($workbook->xpath('//x:sheet') ?: [] as $sheet) {
            $attributes = $sheet->attributes();
            $relationshipAttributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $name = (string) $attributes['name'];
            $id = (string) $relationshipAttributes['id'];

            if ($name !== '' && isset($relationTargets[$id])) {
                $targets[$name] = $relationTargets[$id];
            }
        }

        return $targets;
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $entry = $zip->getFromName('xl/sharedStrings.xml');

        if ($entry === false) {
            return [];
        }

        $xml = $this->parseXml($entry);
        $xml->registerXPathNamespace('x', self::MAIN_NAMESPACE);
        $strings = [];

        foreach ($xml->xpath('//x:si') ?: [] as $item) {
            $parts = [];
            $item->registerXPathNamespace('x', self::MAIN_NAMESPACE);

            foreach ($item->xpath('.//x:t') ?: [] as $text) {
                $parts[] = (string) $text;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<string, string>>
     */
    private function rowsFromSheet(ZipArchive $zip, string $target, array $sharedStrings): array
    {
        $xml = $this->xml($zip, $target);
        $xml->registerXPathNamespace('x', self::MAIN_NAMESPACE);

        $rows = [];
        $header = [];

        foreach ($xml->xpath('//x:sheetData/x:row') ?: [] as $row) {
            $rowNumber = (int) $row['r'];
            $cells = [];
            $row->registerXPathNamespace('x', self::MAIN_NAMESPACE);

            foreach ($row->xpath('x:c') ?: [] as $cell) {
                $column = $this->columnName((string) $cell['r']);
                $value = $this->cellValue($cell, $sharedStrings);

                if ($value !== '') {
                    $cells[$column] = $value;
                }
            }

            if ($header === [] && $this->looksLikeHeader($cells)) {
                $header = $cells;

                continue;
            }

            if ($header === [] || $cells === []) {
                continue;
            }

            $mapped = [];

            foreach ($header as $column => $label) {
                $mapped[$this->normalizeHeader($label)] = trim($cells[$column] ?? '');
            }

            $rows[$rowNumber] = $mapped;
        }

        return $rows;
    }

    /** @param array<string, string> $cells */
    private function looksLikeHeader(array $cells): bool
    {
        $headers = array_map(fn (string $value): string => $this->normalizeHeader($value), $cells);

        return count(array_intersect($headers, ['product_id', 'product_description'])) > 0
            && count(array_intersect($headers, ['product_offering', 'offering_description'])) > 0;
    }

    private function normalizeHeader(string $value): string
    {
        return match (strtolower(trim($value))) {
            'type cust' => 'type_customer',
            'segment customer' => 'type_customer',
            'product id' => 'product_id',
            'product description' => 'product_description',
            'product offering' => 'product_offering',
            'offering description' => 'offering_description',
            'cluster' => 'cluster',
            default => strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($value)) ?: ''),
        };
    }

    /**
     * @param  array<string, string>  $cells
     * @return array<string, mixed>|null
     */
    private function offeringRow(
        string $workbook,
        string $sheet,
        int $rowNumber,
        array $cells,
        ?string $sheetChannel,
    ): ?array {
        // The description column is the catalogue and wins wherever both exist.
        // "new CAPTIVE" carries a second, 44-row "Product ID" column alongside
        // its 1,800-row "Product Description": that short column is the picker
        // list for the SPRINT form, not per-row data, and reading it dropped
        // every catalogue row whose picker cell was blank. Head office
        // confirmed the catalogue spelling is the one SPRINT expects
        // (client, 16 August 2026).
        $productId = trim($cells['product_description'] ?? $cells['product_id'] ?? '');
        $productOffering = trim($cells['offering_description'] ?? $cells['product_offering'] ?? '');

        if ($productId === '' || $productOffering === '' || $productId === '0' || $productOffering === '0') {
            return null;
        }

        $haystack = $productId.' '.$productOffering;
        $channel = $this->inferChannel($haystack, $sheetChannel);
        $now = now();

        return [
            'fingerprint' => hash('sha256', implode('|', [$sheet, $rowNumber, $productId, $productOffering])),
            'source_workbook' => $workbook,
            'source_sheet' => $sheet,
            'source_row' => $rowNumber,
            'source_channel' => $sheetChannel,
            'product_id' => $productId,
            'product_offering' => $productOffering,
            'product_category' => $this->inferProductCategory($haystack),
            'channel' => $channel,
            'region' => $this->inferRegion(($cells['cluster'] ?? '').' '.$productOffering),
            'unit' => $this->inferUnit($haystack),
            'brand' => $this->inferBrand($productOffering),
            'profile' => $this->inferProfile(($cells['type_customer'] ?? '').' '.$haystack),
            'debtor_type' => $this->inferDebtorType($haystack),
            'dp' => $this->inferDp($productOffering),
            'tenor' => $this->inferTenor($productOffering),
            'instalment' => $this->inferInstalment($haystack),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function inferProductCategory(string $value): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'INVESTASI PPSA') => 'C2C Investasi PPSA',
            str_contains($value, 'PPSA C2C') => 'C2C Multiguna PPSA',
            str_contains($value, 'SALES & LEASEBACK'), str_contains($value, 'S&L') => 'Sale & Leaseback',
            str_contains($value, 'MODAL USAHA'), str_contains($value, 'MDL USAHA') => 'Fasilitas Modal Usaha',
            str_contains($value, 'FASILITAS DANA'), str_contains($value, 'FLT DN') => 'Fasilitas Dana',
            default => null,
        };
    }

    private function inferChannel(string $value, ?string $fallback): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'CAPTIVE NJF SMGT') => 'Captive NJF Semangat',
            str_contains($value, 'CAPTIVE NJF TNGH') => 'Captive NJF Tangguh',
            str_contains($value, 'CAPTIVE NJF CUAN') => 'Captive NJF Cuan',
            str_contains($value, 'WIRA AGENT') => 'Wira Agent',
            str_contains($value, 'WIRA BISNIS') => 'Wira Bisnis',
            str_contains($value, 'TELEMARKETING') => 'Telemarketing',
            str_contains($value, 'REFERRAL') => 'Referral',
            str_contains($value, 'SHOWROOM') => 'Showroom',
            default => $fallback,
        };
    }

    private function inferRegion(string $value): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'NON-JAWA'), str_contains($value, 'NON JAWA') => 'Non Jawa',
            str_contains($value, 'NASIONAL') => 'Nasional',
            str_contains($value, 'JAWA') => 'Jawa',
            default => null,
        };
    }

    private function inferUnit(string $value): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'PICK UP') => 'Pick Up',
            str_contains($value, 'TRUCK') => 'Truck',
            str_contains($value, 'PASSENGER'), preg_match('/\bPASS\b/', $value) === 1 => 'Passenger',
            default => null,
        };
    }

    private function inferBrand(string $value): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'NON-JPN'), str_contains($value, 'NON JPN'), str_contains($value, 'NON JEPANG') => 'Non Japan',
            preg_match('/\bEV\b/', $value) === 1 => 'EV',
            preg_match('/\bJPN\b/', $value) === 1, str_contains($value, 'JEPANG') => 'Japan',
            default => null,
        };
    }

    private function inferProfile(string $value): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'B.USAHA'), str_contains($value, 'BADAN USAHA') => 'Badan Hukum Usaha',
            str_contains($value, 'PERORANGAN') => 'Perorangan',
            default => null,
        };
    }

    private function inferDebtorType(string $value): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'TOMI') => 'Additional Order',
            str_contains($value, 'NEW&ROMI'), str_contains($value, 'NEW CUST'), str_contains($value, 'ROMI') => 'New Customer / Repeat Order',
            default => null,
        };
    }

    private function inferDp(string $value): ?string
    {
        return preg_match('/\bDP\s*([0-9]{1,2})\b/i', $value, $matches) === 1
            ? 'DP'.$matches[1]
            : null;
    }

    private function inferTenor(string $value): ?string
    {
        return preg_match('/\b([1-7])\s*TH\b/i', $value, $matches) === 1
            ? $matches[1].'TH'
            : null;
    }

    private function inferInstalment(string $value): ?string
    {
        $value = mb_strtoupper($value);

        return match (true) {
            str_contains($value, 'ADDB') => 'ADDB',
            str_contains($value, 'ADDM') => 'ADDM',
            default => null,
        };
    }

    /** @param array<int, string> $sharedStrings */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $raw = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            return trim($sharedStrings[(int) $raw] ?? '');
        }

        if ($type === 'inlineStr') {
            $cell->registerXPathNamespace('x', self::MAIN_NAMESPACE);

            return trim(implode('', array_map(
                static fn (SimpleXMLElement $text): string => (string) $text,
                $cell->xpath('.//x:t') ?: [],
            )));
        }

        return trim($raw);
    }

    private function columnName(string $cellReference): string
    {
        return preg_replace('/[^A-Z]/', '', $cellReference) ?: '';
    }

    private function xml(ZipArchive $zip, string $name): SimpleXMLElement
    {
        return $this->parseXml($this->text($zip, $name));
    }

    private function text(ZipArchive $zip, string $name): string
    {
        $contents = $zip->getFromName($name);

        if ($contents === false) {
            throw new RuntimeException("Bagian workbook tidak ditemukan: {$name}");
        }

        return $contents;
    }

    private function parseXml(string $contents): SimpleXMLElement
    {
        $xml = simplexml_load_string($contents);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('XML workbook tidak dapat dibaca.');
        }

        return $xml;
    }
}
