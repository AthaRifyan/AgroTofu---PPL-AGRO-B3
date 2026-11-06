<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /** Langkah 2: tampilkan form sandi baru (hanya jika email sudah dikonfirmasi). */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->penggunaDariSesi($request);

        if (! $user) {
            return $this->sesiBerakhir();
        }

        return view('auth.reset-password', ['email' => $user->email]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->penggunaDariSesi($request);

        if (! $user) {
            return $this->sesiBerakhir();
        }

        // error "tidak sama" ditempel di kolom KONFIRMASI (password_confirmation)
        $request->validate([
            'password'              => ['required', 'string', 'min:8', 'max:72'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'password.required'              => 'Kata sandi baru harus diisi.',
            'password.min'                   => 'Kata sandi baru minimal 8 karakter.',
            'password.max'                   => 'Kata sandi baru maksimal 72 karakter.',
            'password_confirmation.required' => 'Konfirmasi kata sandi baru harus diisi.',
            'password_confirmation.same'     => 'Konfirmasi kata sandi tidak sama dengan kata sandi baru.',
        ]);

        DB::transaction(function () use ($user, $request) {
            // cast 'hashed' pada model User otomatis meng-hash
            $user->forceFill([
                'password'       => $request->input('password'),
                'remember_token' => Str::random(60),
            ])->save();

            // keluarkan semua sesi login lama milik akun ini (di perangkat lain)
            if (config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });

        event(new PasswordReset($user));
        Log::info('Kata sandi direset lewat konfirmasi email', ['user_id' => $user->id, 'ip' => $request->ip()]);

        $request->session()->forget('reset_password');

        // key 'password_diubah' dipakai frontend untuk memunculkan popup di halaman login
        return redirect()->route('login')->with('password_diubah', 'Kata sandi berhasil diubah');
    }

    private function penggunaDariSesi(Request $request): ?User
    {
        $data = $request->session()->get('reset_password');

        if (! $data || now()->getTimestamp() > $data['expires_at']) {
            $request->session()->forget('reset_password');

            return null;
        }

        return User::where('is_active', true)->find($data['user_id']);
    }

    private function sesiBerakhir(): RedirectResponse
    {
        return redirect()->route('password.request')
            ->withErrors(['email' => 'Sesi konfirmasi email berakhir. Silakan konfirmasi email kembali.']);
    }
}