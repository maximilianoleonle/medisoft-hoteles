<?php
/**
 * Legacy direct sync endpoint disabled for multi-hotel security.
 *
 * Modern controlled replacement: /api/sync
 */

http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode([
    'success' => false,
    'error' => 'legacy_sync_endpoint_disabled',
    'message' => 'Este endpoint legacy de sincronizacion fue deshabilitado por seguridad multi-hotel.',
    'replacement' => '/api/sync',
], JSON_UNESCAPED_SLASHES);
exit;
