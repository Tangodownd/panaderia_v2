<?php

namespace App\Services;

use Twilio\Rest\Client;
use App\Models\Order;
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
    public function sendCustomMessage(string $to, string $body)
{
    try {
        $to = 'whatsapp:+' . preg_replace('/[^0-9]/', '', $to);

        $message = $this->client->messages->create($to, [
            'from' => $this->fromNumber,
            'body' => $body,
        ]);

        Log::info('WhatsApp enviado', [
            'to' => $to,
            'sid' => $message->sid,
        ]);

        return $message->sid;
    } catch (\Exception $e) {
        Log::error('Error WhatsApp', ['msg' => $e->getMessage()]);
        return null;
    }
}

public function sendPaymentVerifiedUnified(?string $phone, Order $order, string $method, ?string $reference, string $invoiceUrl): void
    {
        $pretty = match ($method) {
            'pago_movil','pago movil','pago_móvil' => 'pago móvil',
            'zelle'  => 'Zelle',
            'transfer'  => 'transferencia'
        };
        $refTxt = $reference ? " (Ref: {$reference})" : '';

        // Resumen rápido
        $items = $order->items ?? $order->orderItems ?? []; // usa la rel que tengas
        $lines = [];
        foreach ($items as $it) {
            $nm = $it->product->name ?? $it->name ?? ('Producto #'.$it->product_id);
            $lines[] = "{$it->quantity} x {$nm}";
        }
        $summary = $lines ? implode(', ', $lines) : 'Sin detalle de ítems';
        $total   = number_format((float)$order->total, 2, '.', ',');

        $msg = "Hemos verificado tu {$pretty}{$refTxt}. ✅\n\n"
             . "Resumen: {$summary}. Total: \${$total}.\n"
             . "Nombre: {$order->name}\n"
             . "Teléfono: {$order->phone}\n"
             . "Dirección: {$order->shipping_address}\n"
             . "Pago: {$pretty}{$refTxt}\n\n"
             . "¡Pedido #{$order->id} creado! Tienes alguna duda?";

        $this->sendCustomMessage($phone, $msg);
    }

}