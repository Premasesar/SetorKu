@extends('layouts.ui-staff')

@section('title', 'Jenis & Harga Sampah')

@section('content')
@php($isAdmin = auth()->user()->isAdmin())
@php($indexRoute = $isAdmin ? 'admin.jenis-sampah.index' : 'petugas.jenis-sampah.index')
@php($portalLabel = $isAdmin ? 'Portal Admin' : 'Portal Petugas')

<x-page-hero
    title="Jenis & Harga Sampah"
    subtitle="Daftar master jenis sampah daur ulang beserta harga beli per satuan."
    icon="bi-tags"
    :breadcrumbs="[$portalLabel, 'Jenis & Harga Sampah']"
>
    @if($isAdmin)
        <x-slot:actions>
            <a href="{{ route('admin.jenis-sampah.create') }}" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Tambah Jenis Sampah
            </a>
        </x-slot:actions>
    @endif
</x-page-hero>

<div class="card shadow-sm">
    <div class="card-body">
        <x-table-toolbar
            :action="route($indexRoute)"
            search-name="q"
            :search-value="$search"
            placeholder="Cari nama jenis sampah..."
            submit-label="Cari"
            :reset-url="$search !== '' ? route($indexRoute) : null"
            reset-label="Reset"
            class="mb-3"
        />

        @if($wasteTypes->isEmpty())
            <x-empty-state
                icon="bi-inbox"
                message="Belum ada data jenis sampah."
            />
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle sk-table">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Jenis Sampah</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga per Satuan</th>
                            <th>Status</th>
                            @if($isAdmin)
                                <th class="text-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($wasteTypes as $wasteType)
                            <tr>
                                <td>{{ $wasteTypes->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $wasteType->name }}</td>
                                <td>{{ $wasteType->unit }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($wasteType->price_per_unit, 2, ',', '.') }}</td>
                                <td>
                                    <x-status-pill :status="$wasteType->is_active ? 'aktif' : 'nonaktif'" />
                                </td>
                                @if($isAdmin)
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.jenis-sampah.edit', $wasteType) }}" class="btn btn-sm btn-outline-secondary" title="Ubah">
                                            <i class="bi bi-pencil"></i> Ubah
                                        </a>
                                        @if($wasteType->is_active)
                                            <form method="POST" action="{{ route('admin.jenis-sampah.deactivate', $wasteType) }}" class="d-inline"
                                                  onsubmit="return confirm('Nonaktifkan jenis sampah ini?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.jenis-sampah.activate', $wasteType) }}" class="d-inline"
                                                  onsubmit="return confirm('Aktifkan kembali jenis sampah ini?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success">Aktifkan</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sk-mobile-list d-md-none mb-3">
                @foreach($wasteTypes as $wasteType)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <div>
                                <span class="text-muted small me-1">#{{ $wasteTypes->firstItem() + $loop->index }}</span>
                                <span class="fw-semibold">{{ $wasteType->name }}</span>
                            </div>
                            <x-status-pill :status="$wasteType->is_active ? 'aktif' : 'nonaktif'" />
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Satuan</span>
                            <span class="sk-mobile-card__value">{{ $wasteType->unit }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Harga per Satuan</span>
                            <span class="sk-mobile-card__value fw-semibold text-success">Rp {{ number_format($wasteType->price_per_unit, 2, ',', '.') }}</span>
                        </div>
                        @if($isAdmin)
                            <div class="sk-mobile-card__actions">
                                <a href="{{ route('admin.jenis-sampah.edit', $wasteType) }}" class="btn btn-sm btn-outline-secondary flex-grow-1" title="Ubah">
                                    <i class="bi bi-pencil"></i> Ubah
                                </a>
                                @if($wasteType->is_active)
                                    <form method="POST" action="{{ route('admin.jenis-sampah.deactivate', $wasteType) }}" class="flex-grow-1"
                                          onsubmit="return confirm('Nonaktifkan jenis sampah ini?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Nonaktifkan</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.jenis-sampah.activate', $wasteType) }}" class="flex-grow-1"
                                          onsubmit="return confirm('Aktifkan kembali jenis sampah ini?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-success w-100">Aktifkan</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $wasteTypes->firstItem() }}–{{ $wasteTypes->lastItem() }} dari {{ $wasteTypes->total() }} jenis sampah
                </small>
                {{ $wasteTypes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
