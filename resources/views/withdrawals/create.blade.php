@extends('layouts.ui-staff')

@section('title', 'Catat Penarikan Saldo')

@section('content')
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('petugas.penarikan.index') }}">Riwayat Penarikan</a></li>
        <li class="breadcrumb-item active">Catat Penarikan</li>
    </ol>
</nav>

<x-page-hero
    title="Catat Penarikan Saldo"
    subtitle="Pencatatan pencairan saldo tabungan sampah nasabah dengan pemeriksaan kecukupan saldo."
    eyebrow="TRANSAKSI PENARIKAN SALDO"
    icon="bi-arrow-up-circle"
>
    <a href="{{ route('petugas.penarikan.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Riwayat Penarikan
    </a>
</x-page-hero>

{{-- Pencarian Cepat QR / Kode / Nama Nasabah --}}
@include('partials._qr_customer_lookup', [
    'actionRoute' => route('petugas.penarikan.create'),
    'customerSearch' => $customerSearch,
    'customers' => $customers,
])

<div class="card sk-card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-cash-stack text-success me-1"></i> Formulir Transaksi Penarikan</h5>
    </div>
    <div class="card-body p-3 p-md-4">
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> Penarikan gagal disimpan. Periksa kembali isian berikut.
            </div>
        @endif

        <form method="POST" action="{{ route('petugas.penarikan.store') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="customer_id" class="form-label fw-semibold">Nasabah Aktif <span class="text-danger">*</span></label>
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
                    <label for="amount" class="form-label fw-semibold">Nominal Penarikan (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" step="0.01" min="0.01" id="amount" name="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" placeholder="Contoh: 10000" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Perkiraan Sisa Saldo</label>
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
