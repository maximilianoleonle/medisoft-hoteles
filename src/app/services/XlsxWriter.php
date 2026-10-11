<?php
/**
 * Generador mínimo de .xlsx (Office Open XML) sin dependencias.
 *
 * Existe porque los exports "Excel" eran HTML con extensión .xls y Excel moderno
 * los rechaza con "El formato y la extensión del archivo no coinciden".
 * El ZIP se arma a mano (método stored, solo crc32) para no depender de ext-zip.
 *
 * Uso:
 *   $x = new XlsxWriter('Hoja');
 *   $x->defineStyle('th', ['bold' => true, 'fill' => '#2E7D32', 'color' => '#FFFFFF', 'align' => 'center', 'border' => true]);
 *   $x->addRow([['v' => 'Título', 's' => 'th'], 'texto', 1200.5]);
 *   $x->merge('A1:H1');
 *   $x->download('archivo.xlsx');
 *
 * Celda = escalar (string → texto literal, int/float → número) o ['v' => valor, 's' => estilo].
 * Los textos se escriben como inlineStr: nunca se interpretan como fórmula.
 */
class XlsxWriter
{
    private $sheetName;
    private $rows = [];
    private $merges = [];
    private $colWidths = [];
    private $styles = ['__default' => []];

    public function __construct($sheetName = 'Hoja1')
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) $sheetName);
        $this->sheetName = mb_substr(trim($name) !== '' ? trim($name) : 'Hoja1', 0, 31);
    }

    /**
     * Opciones: bold, size, color (#hex), fill (#hex), align (left|center|right), border (bool), numFmt (código Excel), wrap (bool).
     */
    public function defineStyle($name, array $opts)
    {
        $this->styles[$name] = $opts;
        return $this;
    }

    public function addRow(array $cells, $defaultStyle = null)
    {
        $this->rows[] = ['cells' => array_values($cells), 'style' => $defaultStyle];
        return $this;
    }

    /** Fila actual (1-based) — útil para calcular rangos de merge. */
    public function rowCount()
    {
        return count($this->rows);
    }

    public function merge($range)
    {
        $this->merges[] = $range;
        return $this;
    }

    /** Anchos en "caracteres" de Excel, en orden de columna. */
    public function setColumnWidths(array $widths)
    {
        $this->colWidths = array_values($widths);
        return $this;
    }

    public function download($filename)
    {
        $bytes = $this->toString();

        $level = ob_get_level();
        while ($level-- > 0) {
            @ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: max-age=0, no-cache, must-revalidate');
        header('Pragma: public');
        echo $bytes;
    }

    public function toString()
    {
        $styleIndex = [];
        $i = 0;
        foreach (array_keys($this->styles) as $name) {
            $styleIndex[$name] = $i++;
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="' . self::esc($this->sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
                . '</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>',
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $this->sheetXml($styleIndex),
        ];

        return self::zip($files);
    }

    private function sheetXml(array $styleIndex)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if (!empty($this->colWidths)) {
            $xml .= '<cols>';
            foreach ($this->colWidths as $idx => $w) {
                $n = $idx + 1;
                $xml .= '<col min="' . $n . '" max="' . $n . '" width="' . (float) $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach ($this->rows as $r => $row) {
            $rowNum = $r + 1;
            $xml .= '<row r="' . $rowNum . '">';
            foreach ($row['cells'] as $c => $cell) {
                $value = is_array($cell) ? ($cell['v'] ?? '') : $cell;
                $style = is_array($cell) && isset($cell['s']) ? $cell['s'] : $row['style'];
                $sAttr = ($style !== null && isset($styleIndex[$style])) ? ' s="' . $styleIndex[$style] . '"' : '';
                $ref = self::colLetter($c) . $rowNum;

                if ($value === null || $value === '') {
                    $xml .= '<c r="' . $ref . '"' . $sAttr . '/>';
                } elseif (is_int($value) || is_float($value)) {
                    $xml .= '<c r="' . $ref . '"' . $sAttr . '><v>' . (is_finite((float) $value) ? $value : 0) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '"' . $sAttr . ' t="inlineStr"><is><t xml:space="preserve">'
                        . self::esc((string) $value) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if (!empty($this->merges)) {
            $xml .= '<mergeCells count="' . count($this->merges) . '">';
            foreach ($this->merges as $range) {
                $xml .= '<mergeCell ref="' . self::esc($range) . '"/>';
            }
            $xml .= '</mergeCells>';
        }

        return $xml . '</worksheet>';
    }

    private function stylesXml()
    {
        $fonts = ['<font><sz val="10"/><name val="Arial"/></font>'];
        $fills = ['<fill><patternFill patternType="none"/></fill>', '<fill><patternFill patternType="gray125"/></fill>'];
        $borders = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
            '<border><left style="thin"><color rgb="FF9E9E9E"/></left><right style="thin"><color rgb="FF9E9E9E"/></right>'
                . '<top style="thin"><color rgb="FF9E9E9E"/></top><bottom style="thin"><color rgb="FF9E9E9E"/></bottom><diagonal/></border>',
        ];
        $numFmts = [];
        $xfs = [];

        foreach ($this->styles as $opts) {
            $fontXml = '<font>' . (!empty($opts['bold']) ? '<b/>' : '')
                . '<sz val="' . (float) ($opts['size'] ?? 10) . '"/>'
                . (!empty($opts['color']) ? '<color rgb="' . self::argb($opts['color']) . '"/>' : '')
                . '<name val="Arial"/></font>';
            $fontId = array_search($fontXml, $fonts, true);
            if ($fontId === false) {
                $fonts[] = $fontXml;
                $fontId = count($fonts) - 1;
            }

            $fillId = 0;
            if (!empty($opts['fill'])) {
                $fillXml = '<fill><patternFill patternType="solid"><fgColor rgb="' . self::argb($opts['fill']) . '"/><bgColor indexed="64"/></patternFill></fill>';
                $fillId = array_search($fillXml, $fills, true);
                if ($fillId === false) {
                    $fills[] = $fillXml;
                    $fillId = count($fills) - 1;
                }
            }

            $numFmtId = 0;
            if (!empty($opts['numFmt'])) {
                $code = (string) $opts['numFmt'];
                if (!isset($numFmts[$code])) {
                    $numFmts[$code] = 164 + count($numFmts);
                }
                $numFmtId = $numFmts[$code];
            }

            $borderId = !empty($opts['border']) ? 1 : 0;
            $align = '';
            if (!empty($opts['align']) || !empty($opts['wrap'])) {
                $align = '<alignment vertical="center"'
                    . (!empty($opts['align']) ? ' horizontal="' . self::esc($opts['align']) . '"' : '')
                    . (!empty($opts['wrap']) ? ' wrapText="1"' : '') . '/>';
            }

            $xfs[] = '<xf numFmtId="' . $numFmtId . '" fontId="' . $fontId . '" fillId="' . $fillId . '" borderId="' . $borderId . '" xfId="0"'
                . ($numFmtId ? ' applyNumberFormat="1"' : '')
                . ($fontId ? ' applyFont="1"' : '')
                . ($fillId ? ' applyFill="1"' : '')
                . ($borderId ? ' applyBorder="1"' : '')
                . ($align !== '' ? ' applyAlignment="1">' . $align . '</xf>' : '/>');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if (!empty($numFmts)) {
            $xml .= '<numFmts count="' . count($numFmts) . '">';
            foreach ($numFmts as $code => $id) {
                $xml .= '<numFmt numFmtId="' . $id . '" formatCode="' . self::esc($code) . '"/>';
            }
            $xml .= '</numFmts>';
        }
        $xml .= '<fonts count="' . count($fonts) . '">' . implode('', $fonts) . '</fonts>'
            . '<fills count="' . count($fills) . '">' . implode('', $fills) . '</fills>'
            . '<borders count="' . count($borders) . '">' . implode('', $borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($xfs) . '">' . implode('', $xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        return $xml;
    }

    public static function colLetter($index)
    {
        $s = '';
        $n = (int) $index + 1;
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    }

    private static function argb($hex)
    {
        $hex = strtoupper(ltrim((string) $hex, '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return preg_match('/^[0-9A-F]{6}$/', $hex) ? 'FF' . $hex : 'FF000000';
    }

    private static function esc($text)
    {
        // Quita caracteres de control inválidos en XML 1.0 (romperían el archivo entero).
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $text) ?? '';
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** ZIP mínimo (método 0 = stored). Suficiente para OOXML y sin ext-zip. */
    private static function zip(array $files)
    {
        $data = '';
        $central = '';
        $offset = 0;
        $t = getdate();
        $dosTime = ($t['hours'] << 11) | ($t['minutes'] << 5) | intdiv($t['seconds'], 2);
        $dosDate = (max(0, $t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'];

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $nameLen = strlen($name);

            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLen, 0) . $name;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLen, 0, 0, 0, 0, 0, $offset) . $name;

            $data .= $local . $content;
            $offset += strlen($local) + $size;
        }

        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), $offset, 0);

        return $data . $central . $end;
    }
}
