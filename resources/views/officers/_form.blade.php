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
            <label for="password" class="form-label">Password Awal <span class="text-danger">*</span></label>
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
