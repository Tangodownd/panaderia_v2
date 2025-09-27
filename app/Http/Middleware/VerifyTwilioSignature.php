<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class VerifyTwilioSignature
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('services.twilio.auth_token');
        if (!$token) {
            return response('Twilio token not configured', 500);
        }

        $twilioSignature = $request->header('X-Twilio-Signature');
        if (!$twilioSignature) {
            return response('Missing X-Twilio-Signature', 403);
        }

        $request->setTrustedProxies([$request->getClientIp()], \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);

        if ($request->headers->get('x-forwarded-proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        $url = $request->fullUrl(); // Debe coincidir con la URL configurada en Twilio

        // Si Twilio manda x-www-form-urlencoded:
        $params = $request->post();
        ksort($params); // Twilio concatena en orden lexicográfico de keys
        $data = $url;
        foreach ($params as $k => $v) {
            $data .= $k . $v;
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $token, true));

        if (!hash_equals($expected, $twilioSignature)) {
            return response('Invalid signature', 403);
        }

        return $next($request);
    }
}
