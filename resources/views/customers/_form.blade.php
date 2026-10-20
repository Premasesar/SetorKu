{{-- Form bersama tambah/ubah nasabah. Saldo sengaja tidak ada di form. --}}
@php($isEdit = isset($customer))

@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i> Data gagal disimpan. Periksa kembali isian berikut.
    </div>
@endif

<h6 class="text-muted mb-3">Data Nasabah</h6>

<div class="mb-3">
    <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
    <input type="text" id="name" name="name" maxlength="255"
           class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $customer->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="address" class="form-label">Alamat <span class="text-danger">*</span></label>
    <textarea id="address" name="address" rows="3" maxlength="1000"
              class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $customer->address ?? '') }}</textarea>
    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="latitude" class="form-label">Latitude <small class="text-muted">(opsional)</small></label>
        <input type="text" id="latitude" name="latitude" inputmode="decimal" placeholder="-6.2000000"
               class="form-control @error('latitude') is-invalid @enderror"
               value="{{ old('latitude', $customer->latitude ?? '') }}">
        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="longitude" class="form-label">Longitude <small class="text-muted">(opsional)</small></label>
        <input type="text" id="longitude" name="longitude" inputmode="decimal" placeholder="106.8166667"
               class="form-control @error('longitude') is-invalid @enderror"
               value="{{ old('longitude', $customer->longitude ?? '') }}">
        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<h6 class="text-muted mb-3 mt-4">Akun Login Nasabah</h6>

<div class="mb-3">
    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
    <input type="email" id="email" name="email" maxlength="255"
           class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email', $customer->user->email ?? '') }}" required>
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@unless($isEdit)
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" id="password" name="password" minlength="8"
                   class="form-control @error('password') is-invalid @enderror" required>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Minimal 8 karakter.</div>
        </div>
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
                   class="form-control" required>
        </div>
    </div>
@endunless

<div class="alert alert-light border small mb-0">
    <i class="bi bi-info-circle"></i> Saldo tidak dapat diisi atau diubah dari form ini. Saldo hanya berubah melalui transaksi.
</div>
