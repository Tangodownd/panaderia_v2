<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class CustomerAuthController extends Controller
{
    // Registro de CLIENTES (emite token como tu AuthController@login)
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'customer',
        ]);

        // Emitimos token tipo Bearer (Sanctum Personal Access Token), igual que /api/login
        $token = $user->createToken('shop')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ], 201);
    }

    // Perfil del cliente autenticado
    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }
}
