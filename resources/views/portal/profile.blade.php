@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<h4 class="fw-bold mb-3"><i class="bi bi-person-gear"></i> Profil Saya</h4>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-3">Informasi Akun (Tidak Dapat Diubah)</h6>
                <dl class="row mb-0">
                    <dt class="col-5">Kode</dt>
                    <dd class="col-7"><code>{{ $customer->code }}</code></dd>

                    <dt class="col-5">Peran</dt>
                    <dd class="col-7"><span class="badge badge-nasabah">Nasabah</span></dd>

                    <dt class="col-5">Email</dt>
                    <dd class="col-7">{{ auth()->user()->email }}</dd>

                    <dt class="col-5">Saldo</dt>
                    <dd class="col-7 fw-bold text-success mb-0">Rp {{ number_format($customer->balance, 2, ',', '.') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Profil gagal disimpan. Periksa kembali isian berikut.
                    </div>
                @endif

                <form method="POST" action="{{ route('nasabah.profil.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" maxlength="255"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $customer->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea id="address" name="address" rows="3" maxlength="1000"
                                  class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $customer->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">No. HP <small class="text-muted">(opsional)</small></label>
                        <input type="text" id="phone" name="phone" maxlength="30" placeholder="081234567890"
                               class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $customer->phone) }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude <small class="text-muted">(opsional)</small></label>
                            <input type="text" id="latitude" name="latitude" inputmode="decimal" placeholder="-7.7970680"
                                   class="form-control @error('latitude') is-invalid @enderror"
                                   value="{{ old('latitude', $customer->latitude) }}">
                            @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude <small class="text-muted">(opsional)</small></label>
                            <input type="text" id="longitude" name="longitude" inputmode="decimal" placeholder="110.3705290"
                                   class="form-control @error('longitude') is-invalid @enderror"
                                   value="{{ old('longitude', $customer->longitude) }}">
                            @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Profil</button>
                        <a href="{{ route('nasabah.dashboard') }}" class="btn btn-outline-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
