@extends('layouts.ui-staff')

@section('title', 'Detail Jenis Sampah')

@section('content')
<x-page-hero
    :title="$wasteType->name"
    subtitle="Detail master jenis sampah dan status ketersediaan transaksi."
    icon="bi-tags"
    :breadcrumbs="[
        ['label' => 'Jenis & Harga Sampah', 'url' => route('admin.jenis-sampah.index')],
        ['label' => $wasteType->name],
    ]"
>
    <x-status-pill :status="$wasteType->is_active ? 'aktif' : 'nonaktif'" />

    <x-slot:actions>
        <a href="{{ route('admin.jenis-sampah.edit', $wasteType) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil"></i> Ubah
        </a>
        <a href="{{ route('admin.jenis-sampah.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="fw-bold mb-3">{{ $wasteType->name }}</h4>
        <dl class="row mb-0">
            <dt class="col-sm-4">Satuan</dt>
            <dd class="col-sm-8">{{ $wasteType->unit }}</dd>

            <dt class="col-sm-4">Harga per Satuan</dt>
            <dd class="col-sm-8 fw-semibold text-success">Rp {{ number_format($wasteType->price_per_unit, 2, ',', '.') }}</dd>

            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8 mb-0">
                <x-status-pill :status="$wasteType->is_active ? 'aktif' : 'nonaktif'" />
            </dd>
        </dl>
    </div>
</div>

<a href="{{ route('admin.jenis-sampah.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a>
@endsection
