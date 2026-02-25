<?php

class PurchaseImport
{
    public function parseUploadedFile(array $file): array
    {
        if (!isset($file['tmp_name']) || !is_file($file['tmp_name'])) {
            throw new RuntimeException('Uploaded file not found.');
        }

        $name = strtolower((string)($file['name'] ?? ''));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseCsv($file['tmp_name']);
        }

        if ($ext === 'xlsx') {
            return $this->parseXlsx($file['tmp_name']);
        }

        if (in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp'], true)) {
            $text = $this->extractText($file['tmp_name'], $ext);
            if (trim($text) === '') {
                throw new RuntimeException('Unable to read text from PDF/Image. Please upload CSV/XLSX or install OCR tools on server.');
            }
            return $this->parseTextRows($text);
        }

        throw new RuntimeException('Unsupported file type. Use CSV, XLSX, PDF, PNG, JPG, JPEG or WEBP.');
    }

    private function parseCsv(string $path): array
    {
        $fp = fopen($path, 'rb');
        if ($fp === false) {
            throw new RuntimeException('Cannot open CSV file.');
        }

        $header = fgetcsv($fp) ?: [];
        $keys = $this->normalizeHeaders($header);
        $rows = [];

        while (($line = fgetcsv($fp)) !== false) {
            $row = $this->mapRow($keys, $line);
            if ($this->isMeaningfulRow($row)) {
                $rows[] = $row;
            }
        }

        fclose($fp);

        if (count($rows) === 0) {
            throw new RuntimeException('No valid rows found in file.');
        }

        return $rows;
    }

    private function parseXlsx(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('XLSX import needs ZipArchive extension enabled.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Cannot open XLSX file.');
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = simplexml_load_string($sharedXml);
            if ($sx) {
                foreach ($sx->si as $si) {
                    if (isset($si->t)) {
                        $shared[] = (string)$si->t;
                    } else {
                        $txt = '';
                        if (isset($si->r)) {
                            foreach ($si->r as $r) {
                                $txt .= (string)$r->t;
                            }
                        }
                        $shared[] = $txt;
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('sheet1 not found in XLSX.');
        }

        $sx = simplexml_load_string($sheetXml);
        if (!$sx) {
            throw new RuntimeException('Unable to parse XLSX sheet.');
        }

        $ns = $sx->getNamespaces(true);
        if (isset($ns[''])) {
            $sx->registerXPathNamespace('x', $ns['']);
        }

        $rowsRaw = $sx->xpath('//x:sheetData/x:row');
        if (!$rowsRaw || count($rowsRaw) === 0) {
            throw new RuntimeException('No rows found in XLSX.');
        }

        $rows = [];
        $header = [];
        $lineNo = 0;
        foreach ($rowsRaw as $rowNode) {
            $lineNo++;
            $cells = [];
            $cellNodes = $rowNode->xpath('x:c');
            if (!$cellNodes) {
                continue;
            }

            foreach ($cellNodes as $c) {
                $type = (string)($c['t'] ?? '');
                $v = (string)($c->v ?? '');
                $value = $v;
                if ($type === 's') {
                    $idx = (int)$v;
                    $value = $shared[$idx] ?? '';
                }
                $cells[] = trim((string)$value);
            }

            if ($lineNo === 1) {
                $header = $this->normalizeHeaders($cells);
                continue;
            }

            $row = $this->mapRow($header, $cells);
            if ($this->isMeaningfulRow($row)) {
                $rows[] = $row;
            }
        }

        if (count($rows) === 0) {
            throw new RuntimeException('No valid rows found in XLSX.');
        }

        return $rows;
    }

    private function extractText(string $path, string $ext): string
    {
        if ($ext === 'pdf') {
            $cmd = 'command -v pdftotext >/dev/null 2>&1 && pdftotext ' . escapeshellarg($path) . ' -';
            $out = shell_exec($cmd);
            return is_string($out) ? $out : '';
        }

        $cmd = 'command -v tesseract >/dev/null 2>&1 && tesseract ' . escapeshellarg($path) . ' stdout 2>/dev/null';
        $out = shell_exec($cmd);
        return is_string($out) ? $out : '';
    }

    private function parseTextRows(string $text): array
    {
        $lines = preg_split('/\R+/', $text) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strlen($line) < 4) {
                continue;
            }

            // Simple heuristic: item name + qty + rate + gst in each line.
            if (preg_match('/^(.*?)[\s,]+(\d+(?:\.\d+)?)[\s,]+(\d+(?:\.\d+)?)[\s,]+(5|12|18)(?:\.0+)?$/', $line, $m)) {
                $rows[] = [
                    'product_name' => trim($m[1]),
                    'quantity' => (float)$m[2],
                    'rate' => (float)$m[3],
                    'gst_percent' => (float)$m[4],
                    'sku' => '',
                ];
            }
        }

        if (count($rows) === 0) {
            throw new RuntimeException('Could not parse lines from PDF/Image text. Prefer CSV/XLSX with columns: product_name/sku, quantity, rate, gst_percent.');
        }

        return $rows;
    }

    private function normalizeHeaders(array $header): array
    {
        $norm = [];
        foreach ($header as $h) {
            $key = strtolower(trim((string)$h));
            $key = str_replace([' ', '-', '.'], '_', $key);

            if (in_array($key, ['product', 'item', 'product_name', 'item_name', 'name'], true)) {
                $norm[] = 'product_name';
            } elseif ($key === 'sku' || $key === 'product_code') {
                $norm[] = 'sku';
            } elseif (in_array($key, ['qty', 'quantity'], true)) {
                $norm[] = 'quantity';
            } elseif (in_array($key, ['rate', 'price', 'unit_price'], true)) {
                $norm[] = 'rate';
            } elseif (in_array($key, ['gst', 'gst_percent', 'gst_rate'], true)) {
                $norm[] = 'gst_percent';
            } else {
                $norm[] = $key;
            }
        }
        return $norm;
    }

    private function mapRow(array $keys, array $line): array
    {
        $row = [
            'product_name' => '',
            'sku' => '',
            'quantity' => 0,
            'rate' => 0,
            'gst_percent' => 0,
        ];

        foreach ($keys as $i => $k) {
            $value = trim((string)($line[$i] ?? ''));
            if ($k === 'quantity' || $k === 'rate' || $k === 'gst_percent') {
                $row[$k] = (float)$value;
            } elseif (isset($row[$k])) {
                $row[$k] = $value;
            }
        }

        return $row;
    }

    private function isMeaningfulRow(array $row): bool
    {
        return (($row['product_name'] ?? '') !== '' || ($row['sku'] ?? '') !== '')
            && (float)($row['quantity'] ?? 0) > 0
            && (float)($row['rate'] ?? 0) > 0;
    }
}
