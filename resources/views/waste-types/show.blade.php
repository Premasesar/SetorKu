@extends('layouts.app')

@section('title', 'Detail Jenis Sampah')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.jenis-sampah.index') }}">Jenis & Harga Sampah</a></li>
        <li class="breadcrumb-item active">{{ $wasteType->name }}</li>
    </ol>
</nav>

<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="fw-bold mb-3">{{ $wasteType->name }}</h4>
        <dl class="row mb-0">
            <dt class="col-sm-4">Satuan</dt>
            <dd class="col-sm-8">{{ $wasteType->unit }}</dd>

            <dt class="col-sm-4">Harga per Satuan</dt>
            <dd class="col-sm-8">Rp {{ number_format($wasteType->price_per_unit, 2, ',', '.') }}</dd>

            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8 mb-0">
                @if($wasteType->is_active)
                    <span class="badge bg-success">Aktif</span>
                @else
                    <span class="badge bg-secondary">Nonaktif</span>
                @endif
            </dd>
        </dl>
    </div>
</div>

<a href="{{ route('admin.jenis-sampah.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a>
@endsection
