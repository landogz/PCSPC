<?php

namespace App\Services\Exports;

use RuntimeException;
use ZipArchive;

/**
 * Minimal XLSX builder (Office Open XML) without third-party packages.
 * Reusable across modules that need Excel downloads.
 */
final class XlsxWriter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|bool|null>>  $rows
     */
    public function toString(array $headers, array $rows, string $sheetName = 'Sheet1'): string
    {
        $sheetName = $this->sanitizeSheetName($sheetName);
        $shared = [];
        $sharedIndex = [];

        $addShared = function (string $value) use (&$shared, &$sharedIndex): int {
            if (array_key_exists($value, $sharedIndex)) {
                return $sharedIndex[$value];
            }
            $index = count($shared);
            $shared[] = $value;
            $sharedIndex[$value] = $index;

            return $index;
        };

        $sheetRowsXml = [];
        $allRows = array_values(array_merge([$headers], $rows));

        foreach ($allRows as $rowIndex => $row) {
            $r = $rowIndex + 1;
            $cells = [];
            foreach (array_values($row) as $colIndex => $value) {
                $ref = $this->cellRef($colIndex, $r);
                if ($value === null || $value === '') {
                    $cells[] = '<c r="'.$ref.'"/>';
                    continue;
                }
                if (is_int($value) || is_float($value)) {
                    $cells[] = '<c r="'.$ref.'" t="n"><v>'.$value.'</v></c>';
                    continue;
                }
                if (is_bool($value)) {
                    $cells[] = '<c r="'.$ref.'" t="b"><v>'.($value ? '1' : '0').'</v></c>';
                    continue;
                }
                $idx = $addShared((string) $value);
                $cells[] = '<c r="'.$ref.'" t="s"><v>'.$idx.'</v></c>';
            }
            $sheetRowsXml[] = '<row r="'.$r.'">'.implode('', $cells).'</row>';
        }

        $sharedXml = '';
        foreach ($shared as $value) {
            $sharedXml .= '<si><t>'.$this->escapeXml($value).'</t></si>';
        }

        $files = [
            '[Content_Types].xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML,
            '_rels/.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML,
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
                .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="'.$this->escapeXml($sheetName).'" sheetId="1" r:id="rId1"/></sheets>'
                .'</workbook>',
            'xl/_rels/workbook.xml.rels' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML,
            'xl/styles.xml' => <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/></border></borders>
  <cellStyleXfs count="1"><xf/></cellStyleXfs>
  <cellXfs count="1"><xf xfId="0"/></cellXfs>
</styleSheet>
XML,
            'xl/sharedStrings.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'
                .count($shared).'" uniqueCount="'.count($shared).'">'
                .$sharedXml
                .'</sst>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                .'<sheetData>'.implode('', $sheetRowsXml).'</sheetData>'
                .'</worksheet>',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new RuntimeException('Unable to create temporary export file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Unable to open ZIP archive for Excel export.');
        }

        foreach ($files as $path => $contents) {
            $zip->addFromString($path, $contents);
        }
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        if ($binary === false) {
            throw new RuntimeException('Unable to read Excel export file.');
        }

        return $binary;
    }

    private function cellRef(int $colIndex, int $row): string
    {
        $col = '';
        $n = $colIndex;
        do {
            $col = chr(65 + ($n % 26)).$col;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);

        return $col.$row;
    }

    private function sanitizeSheetName(string $name): string
    {
        $name = trim(preg_replace('/[\\\\\\/?*\\[\\]:]/', '', $name) ?? '');
        if ($name === '') {
            return 'Sheet1';
        }

        return mb_substr($name, 0, 31);
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
