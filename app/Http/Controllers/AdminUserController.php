<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminUserController extends Controller
{
    /**
     * Obtener todos los administradores
     */
    public function index()
    {
        $admins = User::where('role', 'admin')->get();
        return response()->json($admins);
    }

    /**
     * Registrar un nuevo administrador
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'Administrador creado con éxito',
            'user' => $user
        ], 201);
    }

    /**
     * Eliminar un administrador
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Evitar que el administrador se elimine a sí mismo
        if (auth()->id() == $user->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta de administrador'
            ], 403);
        }
        
        $user->delete();
        
        return response()->json([
            'message' => 'Administrador eliminado con éxito'
        ]);
    }
}

