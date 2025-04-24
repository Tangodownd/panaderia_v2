<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $client;
    protected $fromNumber;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->fromNumber = config('services.twilio.whatsapp_from');
    }

    public function sendOrderConfirmation($to, $orderNumber, $customerName, $total)
    {
        try {
            // Formato WhatsApp: whatsapp:+521234567890
            $to = 'whatsapp:+' . preg_replace('/[^0-9]/', '', $to);
            
            $message = $this->client->messages->create($to, [
                'from' => $this->fromNumber,
                'body' => "¡Hola $customerName! Tu orden #$orderNumber ha sido confirmada. Total: $total. Gracias por tu compra en Panaderia."
            ]);
            
            Log::info('Mensaje de WhatsApp enviado', [
                'to' => $to,
                'message_sid' => $message->sid
            ]);
            
            return $message->sid;
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje de WhatsApp', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}