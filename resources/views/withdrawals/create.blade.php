@extends('layouts.app')

@section('title', 'Catat Penarikan Saldo')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('petugas.penarikan.index') }}">Riwayat Penarikan</a></li>
        <li class="breadcrumb-item active">Catat Penarikan</li>
    </ol>
</nav>

<h4 class="fw-bold mb-3"><i class="bi bi-arrow-up-circle"></i> Catat Penarikan Saldo</h4>

{{-- Pencarian Nasabah berdasarkan Nama atau Kode --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('petugas.penarikan.create') }}" class="row g-2 align-items-end">
            <div class="col-md-8 col-lg-6">
                <label for="q" class="form-label mb-1">Cari Nasabah (Nama atau Kode mis. NSB-0001)</label>
                <input type="text" id="q" name="q" value="{{ $customerSearch }}" class="form-control"
                       placeholder="Ketik nama atau kode nasabah...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i> Cari</button>
                @if($customerSearch !== '')
                    <a href="{{ route('petugas.penarikan.create') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> Penarikan gagal disimpan. Periksa kembali isian berikut.
            </div>
        @endif

        <form method="POST" action="{{ route('petugas.penarikan.store') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="customer_id" class="form-label">Nasabah Aktif <span class="text-danger">*</span></label>
                <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                    <option value="" data-balance="0">-- Pilih Nasabah --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}"
                                data-balance="{{ $customer->balance }}"
                                @selected((int) old('customer_id', $selectedCustomerId) === $customer->id)>
                            {{ $customer->code }} — {{ $customer->name }} (Saldo: Rp {{ number_format($customer->balance, 2, ',', '.') }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if($customers->isEmpty())
                    <div class="form-text text-danger">Tidak ada nasabah aktif yang cocok.</div>
                @endif
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="amount" class="form-label">Nominal Penarikan (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" step="0.01" min="0.01" id="amount" name="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" placeholder="Contoh: 10000" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Perkiraan Sisa Saldo</label>
                    <div class="form-control bg-light fw-bold" id="remaining-balance">Rp 0,00</div>
                    <div class="form-text">Validasi kecukupan saldo diputuskan sepenuhnya oleh server saat disimpan.</div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Penarikan</button>
                <a href="{{ route('petugas.penarikan.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const custSelect = document.getElementById('customer_id');
    const amountInput = document.getElementById('amount');
    const remainEl = document.getElementById('remaining-balance');

    function updateRemaining() {
        const opt = custSelect.options[custSelect.selectedIndex];
        const balance = parseFloat(opt?.dataset?.balance || '0');
        const amount = parseFloat(amountInput.value || '0');
        const remaining = balance - (isNaN(amount) ? 0 : amount);
        remainEl.textContent = 'Rp ' + remaining.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        remainEl.classList.toggle('text-danger', remaining < 0);
        remainEl.classList.toggle('text-success', remaining >= 0);
    }

    custSelect.addEventListener('change', updateRemaining);
    amountInput.addEventListener('input', updateRemaining);
    updateRemaining();
});
</script>
@endpush
