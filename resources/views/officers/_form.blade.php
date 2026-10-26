@php($isEdit = isset($officer))

@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i> Data gagal disimpan. Periksa kembali isian berikut.
    </div>
@endif

<div class="mb-3">
    <label for="name" class="form-label">Nama Petugas <span class="text-danger">*</span></label>
    <input type="text" id="name" name="name" maxlength="255"
           class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $officer->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
    <input type="email" id="email" name="email" maxlength="255"
           class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email', $officer->email ?? '') }}" required>
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">No. HP <small class="text-muted">(opsional)</small></label>
    <input type="text" id="phone" name="phone" maxlength="30" placeholder="081234567890"
           class="form-control @error('phone') is-invalid @enderror"
           value="{{ old('phone', $officer->phone ?? '') }}">
    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@unless($isEdit)
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <x-password-input
                name="password"
                id="password"
                label="Password Awal"
                autocomplete="new-password"
                help-text="Minimal 8 karakter."
                minlength="8"
                :required="true"
                :required-indicator="true"
            />
        </div>
        <div class="col-md-6">
            <x-password-input
                name="password_confirmation"
                id="password_confirmation"
                label="Konfirmasi Password"
                autocomplete="new-password"
                minlength="8"
                :required="true"
                :required-indicator="true"
            />
        </div>
    </div>
@endunless
