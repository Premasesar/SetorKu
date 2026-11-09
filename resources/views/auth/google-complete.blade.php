@extends('layouts.ui-guest')

@section('title', 'Lengkapi Pendaftaran')

@section('card_class', 'sk-guest__card--wide')

@section('aside_title', 'Satu langkah lagi untuk mulai menabung sampah.')

@section('aside_subtitle', 'Lengkapi data diri dan titik lokasi rumah Anda agar petugas dapat melayani setoran serta penjemputan sampah dengan tepat.')

@section('content')
    <div class="card shadow">
        <div class="card-body">
            <div class="text-center mb-4">
                <h3 class="text-success fw-bold mb-1">
                    <i class="bi bi-recycle"></i> SetorKu
                </h3>
                <h5 class="fw-semibold mb-1">Lengkapi Pendaftaran</h5>
                <p class="text-muted small mb-0">
                    Lengkapi data diri Anda untuk membuat akun <strong>Nasabah</strong> Bank Sampah.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Data pendaftaran belum valid. Periksa kembali kolom di bawah ini.
                </div>
            @endif

            <form method="POST" action="{{ route('google.register.complete') }}" novalidate>
                @csrf

                <div class="sk-form-section">
                    <div class="sk-form-section__title">
                        <i class="bi bi-person-vcard" aria-hidden="true"></i> Data Diri
                    </div>

                    <div class="mb-3">
                        <label for="google_email_display" class="form-label">Email Akun Google</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-google" aria-hidden="true"></i></span>
                            <input type="email"
                                   id="google_email_display"
                                   class="form-control bg-light"
                                   value="{{ $pending['email'] }}"
                                   readonly
                                   disabled
                                   tabindex="-1">
                        </div>
                        <div class="form-text">Email berasal dari akun Google terverifikasi dan tidak dapat diubah.</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text"
                               id="name"
                               name="name"
                               maxlength="255"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $pending['name']) }}"
                               required
                               autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">No. HP <span class="text-danger">*</span></label>
                        <input type="text"
                               id="phone"
                               name="phone"
                               maxlength="30"
                               placeholder="081234567890"
                               class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}"
                               required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label for="address" class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                        <textarea id="address"
                                  name="address"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Contoh: Jl. Malioboro No. 12, Gedongtengen, Yogyakarta"
                                  class="form-control @error('address') is-invalid @enderror"
                                  required>{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="sk-form-section">
                    <x-map-picker
                        id="home-location-map"
                        :lat-value="old('latitude')"
                        :lng-value="old('longitude')"
                    />
                </div>

                <div class="alert alert-light border small mb-3">
                    <i class="bi bi-info-circle"></i>
                    Akun Anda akan didaftarkan sebagai <strong>Nasabah</strong> dengan saldo awal <strong>Rp 0</strong>.
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-check2-circle"></i> Selesaikan Pendaftaran
                </button>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-muted small text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Kembali ke halaman login
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
