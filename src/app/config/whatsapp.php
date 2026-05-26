<?php
/**
 * Configuración de WhatsApp (Green API)
 * Los Cedros
 * 
 * INSTRUCCIONES:
 * 1. Registrarse en https://green-api.com/en
 * 2. Crear una instancia (plan Developer - gratis)
 * 3. Escanear QR con WhatsApp
 * 4. Copiar ID_INSTANCE y API_TOKEN_INSTANCE aquí
 */

return [
    // Credenciales de Green API
    'id_instance' => '7103511479',           // Ej: '1101123456'
    'api_token' => 'aa265f433ed743bba3acf43bbe119dcf030ac2fbd9eb4d5eba',       // Ej: 'd75b3a66374942c5...'
    
    // Número del dueño/gerente (formato: código país + número sin espacios ni signos)
    // México: 52 + 10 dígitos. Ejemplo: '5219511234567'
    'numero_destino' => '5219512211406',
    
    // Host de Green API (no cambiar a menos que te lo indiquen)
    'api_host' => 'https://api.green-api.com',
    
    // Activar/desactivar envío automático al cerrar caja
    'envio_activo' => true,
    
    // Mensaje que acompaña al PDF
    'mensaje_corte' => '📊 *Corte de Caja - Los Cedros*' . "\n" .
                        '📅 Fecha: {fecha}' . "\n" .
                        '🕐 Hora cierre: {hora}' . "\n" .
                        '💰 Balance: {balance}' . "\n" .
                        '---' . "\n" .
                        '_Reporte generado automáticamente_'
];
