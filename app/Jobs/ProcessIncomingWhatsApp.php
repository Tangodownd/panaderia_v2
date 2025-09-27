<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessIncomingWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;
    public $tries = 3;
    public $timeout = 30;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->onQueue('twilio-incoming');
    }

public function handle(): void
{
    try {
        $from = $this->payload['From'] ?? '';
        $to   = $this->payload['To'] ?? '';
        $body = $this->payload['Body'] ?? '';

        // (Opcional) Log de entrada
        Log::info('WA IN', compact('from','to','body'));

        // Guarda log/DB si quieres…
        // \DB::table('twilio_incoming_logs')->insert([...]);

        // Responder por WhatsApp (si quieres respuesta automática de prueba)
        // Requiere .env: TWILIO_SID, TWILIO_AUTH_TOKEN, TWILIO_WHATSAPP_FROM (ej: whatsapp:+14155238886)
        if ($from && $body) {
$certPath = 'C:/wamp64/bin/php/php8.3.14/extras/ssl/cacert.pem';
Log::info('CA CHECK', ['exists' => false, 'realpath' => realpath($certPath)]);

$response = Http::withOptions(['verify' => false]) // <-- SOLO LOCAL/DEV
    ->withBasicAuth(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'))
    ->asForm()
    ->post(
        'https://api.twilio.com/2010-04-01/Accounts/'.env('TWILIO_SID').'/Messages.json',
        [
            'From' => env('TWILIO_WHATSAPP_FROM'),   // ej: whatsapp:+14155238886
            'To'   => $from,                         // ej: whatsapp:+5842...
            'Body' => "¡Recibimos tu mensaje: {$body}",
        ]
    );

Log::info('TWILIO RESP', [
    'status' => $response->status(),
    'body'   => $response->body(),
]);
$response->throw(); // para que el worker marque fail si Twilio devuelve 4xx/5xx

}

}
    catch (\Exception $e) {
        Log::error('Error procesando WhatsApp', ['error' => $e->getMessage(), 'payload' => $this->payload]);
        // Opcional: re-throw para que el job falle y se reintente según $tries
        throw $e;
    }
}   

    public function failed(\Throwable $exception): void
    {
        // Manejo de fallos, notificaciones, etc.
        Log::error('Job ProcessIncomingWhatsApp falló', ['error' => $exception->getMessage(), 'payload' => $this->payload]);
    }
}   

