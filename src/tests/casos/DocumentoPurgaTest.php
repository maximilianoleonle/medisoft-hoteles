<?php
/**
 * Retencion + purga del centro documental: dar de baja ('eliminado') marca
 * eliminado_en y arranca una ventana de retencion (el archivo sigue en
 * disco); el cron de purga solo toca documentos cuya ventana ya vencio y
 * hace unlink fisico real + purgado_en. bytes_total del resumen excluye
 * 'eliminado' desde el momento de la baja (la cuota se libera de inmediato
 * aunque el archivo siga en disco durante la retencion).
 */

require_once __DIR__ . '/../bootstrap.php';

echo "DocumentoPurgaTest\n";

t_reset_db();
$base = t_seed_base('doc-purga');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();
$documentoModel = new Documento();

function t_doc_crear_fisico(int $hotelId, string $nombreArchivo, int $sizeBytes = 12): array
{
    $relativeDir = 'documentos/hotel_' . $hotelId . '/2026/07';
    $absoluteDir = STORAGE_PATH . DIRECTORY_SEPARATOR . $relativeDir;
    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0750, true);
    }

    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $nombreArchivo;
    file_put_contents($absolutePath, str_repeat('x', $sizeBytes));

    return [
        'storage_path' => $relativeDir . '/' . $nombreArchivo,
        'absolute_path' => $absolutePath,
    ];
}

function t_doc_insertar(Database $db, int $hotelId, string $storagePath, int $sizeBytes, ?int $usuarioId): int
{
    $db->query(
        "INSERT INTO documentos
            (hotel_id, documento_tipo_id, nombre_original, nombre_archivo,
             storage_path, mime_type, size_bytes, sha256, titulo,
             descripcion, etiquetas, estado, subido_por_usuario_id,
             created_at, updated_at)
         VALUES
            (?, NULL, 'prueba.pdf', ?, ?, 'application/pdf', ?, NULL, NULL,
             NULL, NULL, 'activo', ?, NOW(), NOW())",
        [$hotelId, basename($storagePath), $storagePath, $sizeBytes, $usuarioId]
    );

    return (int) $db->lastInsertId();
}

// ── Dar de baja marca eliminado_en y el archivo sigue en disco ──
$archivo1 = t_doc_crear_fisico($hotelId, 'doc_purga_1.pdf', 100);
$doc1Id = t_doc_insertar($db, $hotelId, $archivo1['storage_path'], 100, $usuarioId);

$documentoModel->actualizarEstado($doc1Id, $hotelId, 'eliminado', $usuarioId);

$fila = $db->query("SELECT estado, eliminado_en, purgado_en FROM documentos WHERE id = ?", [$doc1Id])->fetch();
t_eq('eliminado', $fila['estado'], 'dar de baja cambia el estado');
t_ok($fila['eliminado_en'] !== null, 'dar de baja marca eliminado_en');
t_ok($fila['purgado_en'] === null, 'dar de baja NO purga de inmediato');
t_ok(is_file($archivo1['absolute_path']), 'el archivo fisico sigue en disco tras la baja (dentro de la retencion)');

// ── El resumen ya no cuenta el eliminado en bytes_total, pero sí en pendientes de purga ──
$resumen = $documentoModel->resumenPorHotel($hotelId);
t_eq(0, $resumen['bytes_total'], 'bytes_total excluye documentos eliminados de inmediato');
t_eq(100, $resumen['bytes_pendientes_purga'], 'bytes_pendientes_purga refleja el archivo aun en disco');

// ── Dentro de la ventana de retencion: no es candidato a purga ──
$candidatos = $documentoModel->documentosElegiblesParaPurga(30);
t_eq(0, count($candidatos), 'documento recien dado de baja no es candidato a purga (ventana de 30 dias)');

// ── Vence la ventana: ahora sí es candidato y se purga ──
$db->query("UPDATE documentos SET eliminado_en = DATE_SUB(NOW(), INTERVAL 31 DAY) WHERE id = ?", [$doc1Id]);

$candidatos = $documentoModel->documentosElegiblesParaPurga(30);
t_eq(1, count($candidatos), 'documento con retencion vencida es candidato a purga');
t_eq($doc1Id, (int) $candidatos[0]['id'], 'el candidato es el documento correcto');

$purgado = $documentoModel->purgarDocumento($doc1Id, $hotelId);
t_ok($purgado, 'purgarDocumento devuelve true la primera vez');
t_ok(!is_file($archivo1['absolute_path']), 'el cron de purga SI borra el archivo fisico del servidor');

$fila = $db->query("SELECT purgado_en FROM documentos WHERE id = ?", [$doc1Id])->fetch();
t_ok($fila['purgado_en'] !== null, 'purgarDocumento marca purgado_en');

// ── Idempotencia: purgar dos veces no truena, la segunda no hace nada ──
$purgadoOtraVez = $documentoModel->purgarDocumento($doc1Id, $hotelId);
t_ok(!$purgadoOtraVez, 'purgar un documento ya purgado devuelve false (idempotente)');

// ── El resumen ya no cuenta bytes pendientes de purga tras purgar ──
$resumen = $documentoModel->resumenPorHotel($hotelId);
t_eq(0, $resumen['bytes_pendientes_purga'], 'bytes_pendientes_purga baja a 0 tras la purga real');

// ── Aislamiento de tenant: purgar con el hotel equivocado no toca nada ──
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B Purga', 'doc-purga-b', 1, NOW())");
$hotelBId = (int) $db->lastInsertId();

$archivo2 = t_doc_crear_fisico($hotelId, 'doc_purga_2.pdf', 50);
$doc2Id = t_doc_insertar($db, $hotelId, $archivo2['storage_path'], 50, $usuarioId);
$documentoModel->actualizarEstado($doc2Id, $hotelId, 'eliminado', $usuarioId);
$db->query("UPDATE documentos SET eliminado_en = DATE_SUB(NOW(), INTERVAL 31 DAY) WHERE id = ?", [$doc2Id]);

$purgadoCruzado = $documentoModel->purgarDocumento($doc2Id, $hotelBId);
t_ok(!$purgadoCruzado, 'purgar un documento con el hotel_id de otro hotel no purga nada');
t_ok(is_file($archivo2['absolute_path']), 'el archivo del documento ajeno sigue intacto');

// ── purgarElegibles() en lote: procesa lo vencido, ignora lo que aun esta en ventana ──
$archivo3 = t_doc_crear_fisico($hotelId, 'doc_purga_3.pdf', 30);
$doc3Id = t_doc_insertar($db, $hotelId, $archivo3['storage_path'], 30, $usuarioId);
$documentoModel->actualizarEstado($doc3Id, $hotelId, 'eliminado', $usuarioId);
// doc3 se queda DENTRO de la ventana (recien dado de baja); doc2 sigue vencido y sin purgar.

$resultadoLote = $documentoModel->purgarElegibles(30);
t_eq(1, $resultadoLote['purgados'], 'purgarElegibles solo purga lo vencido (doc2), no lo reciente (doc3)');
t_eq(50, $resultadoLote['bytes_liberados'], 'bytes_liberados refleja el tamano del documento purgado');
t_ok(!is_file($archivo2['absolute_path']), 'doc2 (vencido) SI se purgo por el lote');
t_ok(is_file($archivo3['absolute_path']), 'doc3 (reciente) sigue en disco, no se purgo');

t_fin();
