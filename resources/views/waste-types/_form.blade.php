@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i> Data gagal disimpan. Periksa kembali isian berikut.
    </div>
@endif

<div class="sk-form-section">
    <div class="sk-form-section__title">
        <i class="bi bi-tags" aria-hidden="true"></i> Informasi Jenis & Harga Sampah
    </div>

    <div class="mb-3">
        <label for="name" class="form-label">Nama Jenis Sampah <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" maxlength="255"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $wasteType->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="row g-3 mb-0">
        <div class="col-md-4">
            <label for="unit" class="form-label">Satuan</label>
            <input type="text" id="unit" name="unit" maxlength="20"
                   class="form-control @error('unit') is-invalid @enderror"
                   value="{{ old('unit', $wasteType->unit ?? 'kg') }}" placeholder="kg">
            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-8">
            <label for="price_per_unit" class="form-label">Harga per Satuan (Rp) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" id="price_per_unit" name="price_per_unit"
                   class="form-control @error('price_per_unit') is-invalid @enderror"
                   value="{{ old('price_per_unit', $wasteType->price_per_unit ?? '') }}" required>
            @error('price_per_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
