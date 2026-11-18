<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Menampilkan form tambah pengguna untuk admin.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Menyimpan akun pengguna yang dibuat oleh admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],

            'role' => [
                'required',
                Rule::in(['karyawan', 'kasir', 'manajer']),
            ],
        ]);

        // Membuat akun pengguna
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Memberikan role kepada pengguna
        $user->assignRole($validated['role']);

        // Admin tetap login sebagai admin
        return redirect()
            ->route('admin.register.create')
            ->with('success', 'Akun pengguna berhasil dibuat.');
    }
}
