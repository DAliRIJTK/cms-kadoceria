<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pengelola;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $pengelola = Pengelola::where('username', $request->username)->first();

        if (!$pengelola || !Hash::check($request->password, $pengelola->password)) {
            return response()->json([
                'message' => 'Username atau Password salah!'
            ], 401);
        }

        $token = $pengelola->createToken('admin-kadoceria-token')->plainTextToken;

        return response()->json([
            'message' => 'Login Berhasil',
            'data' => $pengelola,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil dilakukan'
        ], 200);
    }
}
