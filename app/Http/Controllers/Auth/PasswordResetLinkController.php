<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /** Langkah 1: konfirmasi email. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'string', 'email']],
            [
                'email.required' => 'Email harus diisi terlebih dahulu.',
                'email.email'    => 'Format email tidak valid.',
            ]
        );

        $user = User::where('email', strtolower($request->input('email')))->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => 'Email salah, silahkan coba lagi.']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Akun ini dinonaktifkan. Hubungi admin.']);
        }

        // izin sementara (10 menit) untuk membuka form sandi baru; disimpan di sesi server, bukan di URL
        $request->session()->put('reset_password', [
            'user_id'    => $user->id,
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ]);

        return redirect()->route('password.reset');
    }
}