@extends('layouts.ui-guest')

@section('title', 'Login')

@section('content')
    <div class="card shadow">
        <div class="card-body">
            {{-- Logo & Branding --}}
            <div class="text-center mb-4">
                <h3 class="text-success fw-bold mb-1">
                    <i class="bi bi-recycle"></i> SetorKu
                </h3>
                <p class="text-muted mb-0">Sistem Informasi Bank Sampah</p>
            </div>

            {{-- Error & Flash Messages --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @elseif (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <x-form-field
                    name="email"
                    id="email"
                    type="email"
                    label="Email"
                    placeholder="contoh@banksampah.test"
                    prefix-icon="bi-envelope"
                    :required="true"
                    autofocus
                />

                <div class="mb-3">
                    <x-password-input
                        name="password"
                        id="password"
                        label="Password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        prefix-icon="bi-lock"
                        :required="true"
                    />
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Masuk
                </button>
            </form>

            <div class="d-flex align-items-center my-3">
                <hr class="flex-grow-1 m-0">
                <span class="px-2 text-muted small">atau</span>
                <hr class="flex-grow-1 m-0">
            </div>

            <a href="{{ route('google.redirect') }}"
               class="btn btn-outline-dark w-100 d-flex align-items-center justify-content-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.657 29.223 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                    <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                </svg>
                <span>Masuk dengan Google</span>
            </a>

            <p class="text-center small text-muted mt-3 mb-0">
                Belum punya akun?
                <a href="{{ route('google.redirect') }}" class="text-success fw-semibold text-decoration-none">Daftar dengan Google</a>
            </p>
        </div>
    </div>
@endsection
