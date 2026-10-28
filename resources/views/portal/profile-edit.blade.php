@extends('layouts.app')

@section('title', 'Ubah Profil Saya')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('nasabah.profil.edit') }}">Profil Saya</a></li>
        <li class="breadcrumb-item active" aria-current="page">Ubah Profil</li>
    </ol>
</nav>

<h4 class="fw-bold mb-3"><i class="bi bi-pencil-square"></i> Ubah Profil Saya</h4>

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
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-semibold mb-1">
                            <i class="bi bi-exclamation-triangle-fill"></i> Profil gagal disimpan. Periksa kembali isian berikut:
                        </div>
                        <ul class="mb-0 ps-3 small">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="profile-edit-form" method="POST" action="{{ route('nasabah.profil.update') }}" novalidate>
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
                        <label for="phone" class="form-label">No. HP <small class="text-muted">(opsional)</small></label>
                        <input type="text" id="phone" name="phone" maxlength="30" placeholder="081234567890"
                               class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $customer->phone) }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea id="address" name="address" rows="3" maxlength="1000"
                                  class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $customer->address) }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <x-map-picker
                        id="profile-location-map"
                        :lat-value="old('latitude', $customer->latitude)"
                        :lng-value="old('longitude', $customer->longitude)"
                    />

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success" id="profile-submit-btn" data-submit-once>
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('nasabah.profil.edit') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('profile-edit-form');
    var submitBtn = document.getElementById('profile-submit-btn');
    if (!form || !submitBtn) {
        return;
    }
    var submitting = false;
    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }
        submitting = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Menyimpan...';
    });
});
</script>
@endpush
