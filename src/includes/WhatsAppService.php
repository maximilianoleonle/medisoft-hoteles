<?php
/**
 * Servicio de WhatsApp usando Green API
 * Los Cedros
 * 
 * Envía archivos (PDF) y mensajes por WhatsApp usando Green API
 * Plan Developer (gratis): máx 3 contactos/mes
 */

class WhatsAppService {
    private $idInstance;
    private $apiToken;
    private $apiHost;
    private $numeroDestino;
    private $activo;
    
    public function __construct() {
        $config = require ROOT_PATH . '/config/whatsapp.php';
        
        $this->idInstance = $config['id_instance'];
        $this->apiToken = $config['api_token'];
        $this->apiHost = $config['api_host'];
        $this->numeroDestino = $config['numero_destino'];
        $this->activo = $config['envio_activo'] ?? true;
    }
    
    /**
     * Verificar si el servicio está configurado correctamente
     */
    public function estaConfigurado() {
        return $this->activo 
            && !empty($this->idInstance) 
            && $this->idInstance !== 'TU_ID_INSTANCE'
            && !empty($this->apiToken) 
            && $this->apiToken !== 'TU_API_TOKEN_INSTANCE'
            && !empty($this->numeroDestino)
            && $this->numeroDestino !== '521XXXXXXXXXX';
    }
    
    /**
     * Enviar archivo PDF por WhatsApp
     * 
     * @param string $rutaArchivo Ruta completa del archivo PDF
     * @param string $nombreArchivo Nombre que verá el destinatario
     * @param string $caption Texto que acompaña al archivo
     * @return array ['success' => bool, 'message' => string]
     */
    public function enviarPDF($rutaArchivo, $nombreArchivo, $caption = '') {
        if (!$this->estaConfigurado()) {
            error_log("WhatsApp: Servicio no configurado. Verifica config/whatsapp.php");
            return [
                'success' => false, 
                'message' => 'WhatsApp no configurado. Verifica config/whatsapp.php'
            ];
        }
        
        if (!file_exists($rutaArchivo)) {
            error_log("WhatsApp: Archivo no encontrado: $rutaArchivo");
            return ['success' => false, 'message' => 'Archivo no encontrado'];
        }
        
        try {
            $chatId = $this->numeroDestino . '@c.us';
            
            $url = sprintf(
                '%s/waInstance%s/sendFileByUpload/%s',
                $this->apiHost,
                $this->idInstance,
                $this->apiToken
            );
            
            // Preparar el archivo para upload con cURL
            $cfile = new CURLFile($rutaArchivo, 'application/pdf', $nombreArchivo);
            
            $postData = [
                'chatId' => $chatId,
                'file' => $cfile,
                'fileName' => $nombreArchivo,
                'caption' => $caption
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER => ['Accept: application/json']
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("WhatsApp cURL Error: $curlError");
                return ['success' => false, 'message' => 'Error de conexión: ' . $curlError];
            }
            
            $result = json_decode($response, true);
            
            error_log("WhatsApp Response [HTTP $httpCode]: $response");
            
            if ($httpCode == 200 && isset($result['idMessage'])) {
                return [
                    'success' => true, 
                    'message' => 'PDF enviado por WhatsApp',
                    'idMessage' => $result['idMessage']
                ];
            }
            
            return [
                'success' => false, 
                'message' => 'Error al enviar: ' . ($result['message'] ?? $response)
            ];
            
        } catch (Exception $e) {
            error_log("WhatsApp Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar mensaje de texto por WhatsApp
     */
    public function enviarMensaje($mensaje) {
        if (!$this->estaConfigurado()) {
            return ['success' => false, 'message' => 'WhatsApp no configurado'];
        }
        
        try {
            $url = sprintf(
                '%s/waInstance%s/sendMessage/%s',
                $this->apiHost,
                $this->idInstance,
                $this->apiToken
            );
            
            $data = json_encode([
                'chatId' => $this->numeroDestino . '@c.us',
                'message' => $mensaje
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode == 200 && isset($result['idMessage'])) {
                return ['success' => true, 'message' => 'Mensaje enviado'];
            }
            
            return ['success' => false, 'message' => 'Error: ' . ($result['message'] ?? $response)];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Preparar mensaje de corte con variables reemplazadas
     */
    public function prepararMensajeCorte($datos) {
        $config = require ROOT_PATH . '/config/whatsapp.php';
        $mensaje = $config['mensaje_corte'];
        
        $reemplazos = [
            '{fecha}' => $datos['fecha'] ?? date('d/m/Y'),
            '{hora}' => $datos['hora'] ?? date('H:i'),
            '{balance}' => '$' . number_format($datos['balance'] ?? 0, 2)
        ];
        
        return str_replace(array_keys($reemplazos), array_values($reemplazos), $mensaje);
    }
}
