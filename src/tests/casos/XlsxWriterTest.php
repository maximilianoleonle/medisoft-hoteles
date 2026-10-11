<?php
/**
 * XlsxWriter: los exports "Excel" (Reservaciones, Caja) deben ser .xlsx reales.
 * Antes eran HTML con extensión .xls y Excel moderno los rechazaba con
 * "El formato y la extensión del archivo no coinciden".
 *  1. El ZIP es válido y trae las partes OOXML obligatorias.
 *  2. Todo el XML es bien formado (un carácter de control lo rompería entero).
 *  3. Números como número, textos como inlineStr literal (sin inyección de fórmula).
 *  4. Estilos, merges y acentos sobreviven.
 */

require_once __DIR__ . '/../lib.php';
require_once __DIR__ . '/../../app/services/XlsxWriter.php';

echo "XlsxWriterTest\n";

$x = new XlsxWriter('Reservaciones');
$x->setColumnWidths([30, 12]);
$x->defineStyle('th', ['bold' => true, 'color' => '#FFFFFF', 'fill' => '#2E7D32', 'align' => 'center', 'border' => true]);
$x->defineStyle('monto', ['numFmt' => '"$"#,##0', 'fill' => '#E1BEE7', 'border' => true]);
$x->addRow([['v' => 'sábado, 10 de octubre de 2026', 's' => 'th']]);
$x->merge('A1:H1');
$x->addRow(['HUÉSPED', 'COSTO'], 'th');
$x->addRow(['=HYPERLINK("http://x")', ['v' => 1200.0, 's' => 'monto']]);
$x->addRow(["Ñoño \x01<&>\"", 'Cortesía']);
$x->addRow([]);

$bytes = $x->toString();
t_eq('PK', substr($bytes, 0, 2), 'arranca con firma ZIP');

$tmp = tempnam(sys_get_temp_dir(), 'xlsx');
file_put_contents($tmp, $bytes);

if (!class_exists('ZipArchive')) {
    t_ok(true, 'ZipArchive no disponible: se omite la inspección interna');
    unlink($tmp);
    t_fin();
}

$zip = new ZipArchive();
t_eq(true, $zip->open($tmp) === true, 'ZipArchive abre el archivo');

foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $parte) {
    $xml = $zip->getFromName($parte);
    t_ok($xml !== false, "parte presente: $parte");
    $dom = new DOMDocument();
    t_ok($xml !== false && @$dom->loadXML($xml), "XML bien formado: $parte");
}

$sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
$styles = $zip->getFromName('xl/styles.xml');
$zip->close();
unlink($tmp);

t_ok(strpos($sheet, '<f>') === false, 'ninguna celda es fórmula');
t_ok(strpos($sheet, 't="inlineStr"><is><t xml:space="preserve">=HYPERLINK(&quot;http://x&quot;)</t>') !== false, 'texto con = queda literal');
t_ok(preg_match('/<c r="B3" s="\d+"><v>1200<\/v><\/c>/', $sheet) === 1, 'monto viaja como número');
t_ok(strpos($sheet, 'Ñoño &lt;&amp;&gt;&quot;') !== false, 'acentos y escapes; control char eliminado');
t_ok(strpos($sheet, 'sábado, 10 de octubre') !== false, 'UTF-8 intacto');
t_ok(strpos($sheet, '<mergeCell ref="A1:H1"/>') !== false, 'merge registrado');
t_ok(strpos($sheet, '<row r="5"></row>') !== false, 'fila vacía de separador');
t_ok(strpos($styles, 'FF2E7D32') !== false && strpos($styles, 'formatCode="&quot;$&quot;#,##0"') !== false, 'relleno y formato de moneda en styles');

t_eq('A', XlsxWriter::colLetter(0), 'columna 0 = A');
t_eq('Z', XlsxWriter::colLetter(25), 'columna 25 = Z');
t_eq('AA', XlsxWriter::colLetter(26), 'columna 26 = AA');

t_fin();
