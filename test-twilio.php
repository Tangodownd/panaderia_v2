<?php
// test-twilio-custom.php
require __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

// Configurar para ver todos los errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Credenciales de Twilio
$sid = 'AC7985e2ecbd409e77ed1c90f3f17bd372';
$token = '537878d42c4bcf1a79d466c2dff2b4ba';

try {
    echo "Iniciando prueba de envío de WhatsApp personalizado...\n";
    
    // Configurar opciones de cURL para deshabilitar la verificación SSL
    $curlOptions = [
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ];
    
    // Inicializar cliente de Twilio con las opciones personalizadas
    $client = new Client($sid, $token, null, null, new \Twilio\Http\CurlClient($curlOptions));
    echo "Cliente Twilio inicializado.\n";
    
    // Número de destino
    $to = '+584244423510';
    
    // Formatear el número
    $formattedNumber = preg_replace('/[^0-9]/', '', $to);
    $whatsappTo = 'whatsapp:+' . $formattedNumber;
    
    // Crear un mensaje personalizado para la orden
    $orderId = rand(1000, 9999);
    $total = rand(50, 200) + (rand(0, 99) / 100);
    
    $orderItems = "- 2x Pan Francés\n- 1x Torta de Chocolate\n- 3x Croissant";
    
    $message = "¡Gracias por tu compra en Panadería!\n\n" .
               "Orden #" . $orderId . "\n" .
               "Fecha: " . date('d/m/Y') . "\n" .
               "Total: $" . number_format($total, 2) . "\n\n" .
               "Productos:\n" . $orderItems . "\n\n" .
               "Tu pedido será procesado pronto. Para cualquier consulta, responde a este mensaje.";
    
    echo "Enviando mensaje a: " . $whatsappTo . "\n";
    echo "Mensaje: " . $message . "\n";
    
    // Enviar el mensaje
    $messageResponse = $client->messages->create(
        $whatsappTo,
        [
            'from' => 'whatsapp:+14155238886',
            'body' => $message
        ]
    );
    
    echo "¡Mensaje enviado exitosamente!\n";
    echo "SID: " . $messageResponse->sid . "\n";
    echo "Estado: " . $messageResponse->status . "\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}