<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\TransientToken; // <— IMPORTANTE

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Esto crea sesión (cookie) si las credenciales son válidas
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Además, emitimos un token personal (Bearer) para clientes que lo usen
            $token = $user->createToken('admin-panel')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user'  => $user,
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['Las credenciales proporcionadas son incorrectas.'],
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Not authenticated'], 401);
            }

            // 1) Revocar el token actual SOLO si es un PersonalAccessToken (Bearer)
            $token = $user->currentAccessToken();

            if ($token instanceof TransientToken) {
                // Cookie-based (SPA): NO hay delete() aquí; no intentes borrarlo
                // Opcional: podrías borrar TODOS los tokens personales si tu UX lo requiere:
                // $user->tokens()->delete();
            } elseif ($token) {
                // PersonalAccessToken (Bearer): se puede borrar seguro
                $token->delete();
            } else {
                // Como plan B, intenta obtener el id desde el formato "id|random" del header
                $bearer = $request->bearerToken();
                if ($bearer && str_contains($bearer, '|')) {
                    [$tokenId] = explode('|', $bearer, 2);
                    $user->tokens()->where('id', $tokenId)->delete();
                }
            }

            // 2) Cerrar sesión del guard de sesión (web), si está activo
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
            }

            // 3) Invalidar sesión y regenerar CSRF si aplica
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->noContent(); // 204
        } catch (\Throwable $e) {
            \Log::error('Logout error', [
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'Logout failed'], 500);
        }
    }
}
