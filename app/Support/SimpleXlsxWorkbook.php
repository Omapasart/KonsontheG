<?php

namespace App\Support;

use ZipArchive;

class SimpleXlsxWorkbook
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @param  array<int, float>  $columnWidths
     */
    public function build(
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $columnWidths = [],
    ): string {
        $columnCount = count($headers);
        $headerRow = 3;
        $lastRow = max($headerRow, $headerRow + count($rows));
        $lastCol = $this->columnLetter($columnCount);
        $sheetXml = $this->sheetXml($title, $subtitle, $headers, $rows, $columnWidths, $lastCol, $lastRow, $headerRow);

        $temp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($temp === false || $zip->open($temp, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create the Excel workbook.');
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Confirmed Applicants" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);
        $zip->addFromString('xl/styles.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="16"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/></font>
  </fonts>
  <fills count="2">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
  </fills>
  <borders count="1">
    <border><left/><right/><top/><bottom/><diagonal/></border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="4">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
  </cellXfs>
</styleSheet>
XML);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $contents = file_get_contents($temp);
        @unlink($temp);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the generated Excel workbook.');
        }

        return $contents;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @param  array<int, float>  $columnWidths
     */
    private function sheetXml(
        string $title,
        string $subtitle,
        array $headers,
        array $rows,
        array $columnWidths,
        string $lastCol,
        int $lastRow,
        int $headerRow,
    ): string {
        $cols = '';
        foreach ($columnWidths as $index => $width) {
            $col = $index + 1;
            $cols .= '<col min="'.$col.'" max="'.$col.'" width="'.$width.'" customWidth="1"/>';
        }

        $sheetData = '<row r="1"><c r="A1" t="inlineStr" s="1"><is><t>'.$this->xml($title).'</t></is></c></row>';
        $sheetData .= '<row r="2"><c r="A2" t="inlineStr"><is><t>'.$this->xml($subtitle).'</t></is></c></row>';
        $sheetData .= $this->rowXml($headerRow, $headers, 2);

        foreach ($rows as $offset => $row) {
            $sheetData .= $this->rowXml($headerRow + 1 + $offset, $row, 3);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$headerRow.'" topLeftCell="A'.($headerRow + 1).'" activePane="bottomLeft" state="frozen"/>'
            .'<selection pane="bottomLeft"/></sheetView></sheetViews>'
            .($cols !== '' ? '<cols>'.$cols.'</cols>' : '')
            .'<sheetData>'.$sheetData.'</sheetData>'
            .'<autoFilter ref="A'.$headerRow.':'.$lastCol.$lastRow.'"/>'
            .'<mergeCells count="1"><mergeCell ref="A1:'.$lastCol.'1"/></mergeCells>'
            .'</worksheet>';
    }

    /**
     * @param  list<string>  $values
     */
    private function rowXml(int $rowNumber, array $values, int $style): string
    {
        $cells = '';

        foreach ($values as $index => $value) {
            $ref = $this->columnLetter($index + 1).$rowNumber;
            $cells .= '<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t>'.$this->xml((string) $value).'</t></is></c>';
        }

        return '<row r="'.$rowNumber.'">'.$cells.'</row>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private function xml(string $value): string
    {
        $clean = preg_replace('/[^\P{C}\t\n\r]/u', '', $value) ?? $value;

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
