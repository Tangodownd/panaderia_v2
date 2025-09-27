<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client as TwilioClient;

class TwilioServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TwilioClient::class, function () {
            $sid   = config('services.twilio.sid');
            $token = config('services.twilio.token');

            // En local/desarrollo: desactivar verificación SSL para evitar errores con certs
            if (app()->environment('local')) {
                $curl = new \Twilio\Http\CurlClient([
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ]);

                return new TwilioClient($sid, $token, null, null, $curl);
            }

            // En producción: SSL verificado
            return new TwilioClient($sid, $token);
        });
    }

    public function boot()
    {
        //
    }
}
