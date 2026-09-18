<?php

namespace App\Core;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Generic Excel/CSV export, downloadable template generation, and validated
 * import for any metadata-driven module (see app/Modules/ModuleRegistry).
 */
class ExportImport
{
    private static function exportableFields(array $module): array
    {
        return array_values(array_filter($module['fields'], fn ($f) => !in_array($f['type'], ['file'], true)));
    }

    public static function headers(array $module): array
    {
        return array_map(fn ($f) => $f['label'], self::exportableFields($module));
    }

    private static function rowToValues(array $module, array $row): array
    {
        $values = [];
        foreach (self::exportableFields($module) as $f) {
            $name = $f['name'];
            if ($f['type'] === 'relation') {
                $values[] = $row[$name . '_label'] ?? '';
            } elseif ($f['type'] === 'select' && isset($f['options'][$row[$name] ?? null])) {
                $values[] = $f['options'][$row[$name]];
            } elseif ($f['type'] === 'polymorphic_location') {
                $values[] = LocationResolver::resolve($row['location_type'] ?? null, isset($row[$name]) ? (int) $row[$name] : null) ?? '';
            } else {
                $values[] = $row[$name] ?? '';
            }
        }
        return $values;
    }

    public static function buildSpreadsheet(array $module, array $rows, bool $templateOnly = false): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($module['label'], 0, 30));
        $headers = self::headers($module);
        $sheet->fromArray($headers, null, 'A1');
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        if (!$templateOnly) {
            $r = 2;
            foreach ($rows as $row) {
                $sheet->fromArray(self::rowToValues($module, $row), null, 'A' . $r);
                $r++;
            }
        }
        return $spreadsheet;
    }

    public static function streamXlsx(array $module, array $rows, bool $templateOnly = false): void
    {
        $spreadsheet = self::buildSpreadsheet($module, $rows, $templateOnly);
        $filename = $module['key'] . ($templateOnly ? '_template' : '_export_' . date('Ymd_His')) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public static function streamCsv(array $module, array $rows): void
    {
        $filename = $module['key'] . '_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, self::headers($module), ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($out, self::rowToValues($module, $row), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /**
     * Parses an uploaded CSV/XLSX against the module definition, validates
     * each row, inserts the valid ones, and returns a structured report.
     */
    public static function import(array $module, string $filePath, string $originalName, int $importedBy): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rows = $ext === 'csv' ? self::readCsv($filePath) : self::readXlsx($filePath);

        if (!$rows) {
            return ['total' => 0, 'success' => 0, 'errors' => [['row' => 0, 'message' => 'The file is empty or could not be read.']]];
        }

        $headerRow = array_map('trim', array_shift($rows));
        $fields = self::exportableFields($module);
        $labelToField = [];
        foreach ($fields as $f) {
            $labelToField[$f['label']] = $f;
        }

        $pdo = Database::connection();
        $errors = [];
        $successCount = 0;
        $rowNum = 1;

        foreach ($rows as $rawRow) {
            $rowNum++;
            if (count(array_filter($rawRow, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // skip blank rows
            }
            $assoc = [];
            foreach ($headerRow as $i => $label) {
                $assoc[$label] = $rawRow[$i] ?? '';
            }

            [$data, $rowErrors] = self::mapAndValidateRow($module, $labelToField, $assoc, $pdo);
            if ($rowErrors) {
                $errors[] = ['row' => $rowNum, 'message' => implode('; ', $rowErrors)];
                continue;
            }

            if (!empty($module['code_field'])) {
                $data[$module['code_field']] = generate_code($module['code_prefix'], $pdo, $module['table'], $module['code_field']);
            }
            $data['created_by'] = $importedBy;

            $model = new Model($module['table'], 'id', $module['soft_deletes'] ?? true);
            $id = $model->insert($data);
            Audit::log('imported', $module['key'], $id, "Imported via {$originalName} (row {$rowNum})");
            $successCount++;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO import_history (module, filename, total_rows, success_count, error_count, errors_json, imported_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $module['key'], $originalName, count($rows), $successCount, count($errors),
            json_encode($errors, JSON_UNESCAPED_UNICODE), $importedBy,
        ]);

        return ['total' => count($rows), 'success' => $successCount, 'errors' => $errors];
    }

    private static function mapAndValidateRow(array $module, array $labelToField, array $assoc, \PDO $pdo): array
    {
        $data = [];
        $errors = [];
        foreach ($labelToField as $label => $f) {
            $raw = trim((string) ($assoc[$label] ?? ''));
            $name = $f['name'];

            if ($raw === '') {
                if (!empty($f['required'])) {
                    $errors[] = "{$label} is required";
                }
                $data[$name] = $f['default'] ?? null;
                if (($data[$name] ?? null) === 'today') {
                    $data[$name] = date('Y-m-d');
                }
                continue;
            }

            if ($f['type'] === 'relation') {
                $stmt = $pdo->prepare("SELECT id FROM {$f['relation_table']} WHERE {$f['relation_display']} = ? LIMIT 1");
                $stmt->execute([$raw]);
                $id = $stmt->fetchColumn();
                if (!$id) {
                    $errors[] = "{$label} \"{$raw}\" was not found";
                    continue;
                }
                $data[$name] = (int) $id;
            } elseif ($f['type'] === 'select') {
                $key = array_search($raw, $f['options'], true) ?: (array_key_exists($raw, $f['options']) ? $raw : false);
                if ($key === false) {
                    $errors[] = "{$label} \"{$raw}\" is not a valid option";
                    continue;
                }
                $data[$name] = $key;
            } elseif ($f['type'] === 'date') {
                $ts = strtotime($raw);
                if (!$ts) {
                    $errors[] = "{$label} \"{$raw}\" is not a valid date";
                    continue;
                }
                $data[$name] = date('Y-m-d', $ts);
            } elseif (in_array($f['type'], ['number'], true)) {
                if (!is_numeric($raw)) {
                    $errors[] = "{$label} must be numeric";
                    continue;
                }
                $data[$name] = (int) $raw;
            } elseif ($f['type'] === 'decimal') {
                if (!is_numeric($raw)) {
                    $errors[] = "{$label} must be numeric";
                    continue;
                }
                $data[$name] = (float) $raw;
            } elseif ($f['type'] === 'polymorphic_location') {
                $data[$name] = (int) $raw;
            } else {
                $data[$name] = $raw;
            }
        }
        return [$data, $errors];
    }

    private static function readCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            // Strip UTF-8 BOM if present.
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }
        return $rows;
    }

    private static function readXlsx(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }
}
