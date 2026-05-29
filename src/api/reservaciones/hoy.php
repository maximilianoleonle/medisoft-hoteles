<?php
/**
 * Legacy direct API endpoint disabled for multi-hotel security.
 *
 * Modern replacement: /api/reservaciones/hoy
 */

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode([
    'success' => false,
    'error' => 'legacy_endpoint_disabled',
    'message' => 'Este endpoint legacy fue deshabilitado por seguridad multi-hotel. Usa la ruta API moderna correspondiente.',
    'replacement' => '/api/reservaciones/hoy',
], JSON_UNESCAPED_SLASHES);
exit;
