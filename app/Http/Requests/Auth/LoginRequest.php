<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /** [REVISI #1] Pesan kolom kosong muncul di kolom masing-masing. */
    public function messages(): array
    {
        return [
            'email.required'    => 'Email harus diisi terlebih dahulu.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi harus diisi terlebih dahulu.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // [REVISI #1] Email tidak ada ATAU sandi salah -> satu pesan, ditempel di kolom PASSWORD.
            // Pesan sengaja digabung agar penyerang tidak bisa menebak email mana yang terdaftar.
            throw ValidationException::withMessages([
                'password' => 'Email atau Kata Sandi tidak cocok, harap dicoba lagi.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $detik = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'password' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}