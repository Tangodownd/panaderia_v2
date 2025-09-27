<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ProcessIncomingWhatsApp;
use Illuminate\Support\Str;
use Normalizer;

class TwilioWebhookController extends Controller
{
    public function incoming(Request $request)
    {
        // 1) Sanitizar inputs críticos (evitar que textos rompan plantillas o logs)
        $payload = $this->sanitize($request->all());

        // 2) (Opcional) Persistir un log mínimo para trazabilidad
        // \Log::info('Twilio incoming', ['payload' => $payload]);

        // 3) Encolar para procesar fuera del request (rápida respuesta a Twilio)
        ProcessIncomingWhatsApp::dispatch($payload)
            ->onQueue('twilio-incoming');

        // 4) Twilio solo necesita 200 OK rápido. Sin TwiML es válido para WhatsApp.
        return response('OK', 200);
    }

    private function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Normalizar Unicode (requiere ext-intl)
                if (class_exists(\Normalizer::class)) {
                    $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?? $value;
                }

                // Remover chars de control (excepto \n\r\t si los quieres permitir)
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

                // Evitar que placeholders tipo {{...}} rompan Blade/Twig/Plantillas
                $value = str_replace(['{{', '}}'], ['{ {', '} }'], $value);

                // Limitar longitud (evitar payloads enormes)
                if (mb_strlen($value) > 2000) {
                    $value = mb_substr($value, 0, 2000) . '…';
                }

                // Podrías decidir quedarte solo con UTF-8 imprimible
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
