@extends('layouts.ui-staff')

@section('title', 'Catat Setoran Sampah')

@section('content')
<x-page-hero
    title="Catat Setoran Sampah"
    subtitle="Pencatatan setoran sampah nasabah dengan kalkulasi otomatis harga snapshot dan saldo."
    icon="bi-plus-circle"
    :breadcrumbs="[
        ['label' => 'Riwayat Setoran', 'url' => route('petugas.setoran.index')],
        ['label' => 'Catat Setoran'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('petugas.setoran.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Riwayat Setoran
        </a>
    </x-slot:actions>
</x-page-hero>

{{-- Pencarian Cepat QR / Kode / Nama Nasabah --}}
@include('partials._qr_customer_lookup', [
    'actionRoute' => route('petugas.setoran.create'),
    'customerSearch' => $customerSearch,
    'customers' => $customers,
])

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clipboard-plus"></i> Formulir Transaksi Setoran
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> Setoran gagal disimpan. Periksa kembali isian berikut.
            </div>
        @endif

        <form method="POST" action="{{ route('petugas.setoran.store') }}" novalidate>
            @csrf

            <div class="mb-3">
                <label for="customer_id" class="form-label">Nasabah Aktif <span class="text-danger">*</span></label>
                <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Nasabah --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}"
                            @selected((int) old('customer_id', $selectedCustomerId) === $customer->id)>
                            {{ $customer->code }} — {{ $customer->name }} ({{ \Illuminate\Support\Str::limit($customer->address, 45) }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if($customers->isEmpty())
                    <div class="form-text text-danger">Tidak ada nasabah aktif yang cocok.</div>
                @endif
            </div>

            <div class="mb-3">
                <label for="waste_type_id" class="form-label">Jenis Sampah Aktif <span class="text-danger">*</span></label>
                <select id="waste_type_id" name="waste_type_id" class="form-select @error('waste_type_id') is-invalid @enderror" required>
                    <option value="" data-price="0" data-unit="kg">-- Pilih Jenis Sampah --</option>
                    @foreach($wasteTypes as $wt)
                        <option value="{{ $wt->id }}"
                                data-price="{{ $wt->price_per_unit }}"
                                data-unit="{{ $wt->unit }}"
                                @selected((int) old('waste_type_id') === $wt->id)>
                            {{ $wt->name }} — Rp {{ number_format($wt->price_per_unit, 2, ',', '.') }} / {{ $wt->unit }}
                        </option>
                    @endforeach
                </select>
                @error('waste_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="weight" class="form-label">Berat Setoran <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.001" min="0.001" id="weight" name="weight"
                               class="form-control @error('weight') is-invalid @enderror"
                               value="{{ old('weight') }}" placeholder="Contoh: 2.500" required>
                        <span class="input-group-text" id="unit-label">kg</span>
                        @error('weight') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Perkiraan Nilai Setoran</label>
                    <div class="form-control bg-light fw-bold text-success" id="estimated-total">Rp 0</div>
                    <div class="form-text">Nilai resmi dihitung ulang oleh server dari harga master saat disimpan.</div>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Setoran</button>
                <a href="{{ route('petugas.setoran.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wtSelect = document.getElementById('waste_type_id');
    const weightInput = document.getElementById('weight');
    const unitLabel = document.getElementById('unit-label');
    const estimateEl = document.getElementById('estimated-total');

    function updateEstimate() {
        const opt = wtSelect.options[wtSelect.selectedIndex];
        const price = parseFloat(opt?.dataset?.price || '0');
        const unit = opt?.dataset?.unit || 'kg';
        const weight = parseFloat(weightInput.value || '0');
        unitLabel.textContent = unit;
        const total = weight > 0 && price > 0 ? Math.round(weight * price * 100) / 100 : 0;
        estimateEl.textContent = 'Rp ' + total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    wtSelect.addEventListener('change', updateEstimate);
    weightInput.addEventListener('input', updateEstimate);
    updateEstimate();
});
</script>
@endpush
