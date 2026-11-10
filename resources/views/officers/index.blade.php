@extends('layouts.ui-staff')

@section('title', 'Kelola Petugas')

@section('content')
<x-page-hero
    title="Kelola Petugas"
    subtitle="Manajemen akun petugas operasional, status keaktifan, serta akses masuk Google dan password."
    icon="bi-person-badge"
    :breadcrumbs="['Portal Admin', 'Kelola Petugas']"
>
    <x-slot:actions>
        <a href="{{ route('admin.petugas.create') }}" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Tambah Petugas
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card sk-card shadow-sm">
    <div class="card-body p-3 p-md-4">
        <x-table-toolbar
            :action="route('admin.petugas.index')"
            search-name="q"
            :search-value="$search"
            placeholder="Cari nama, email, nomor HP, atau kode petugas..."
            submit-label="Cari"
            :reset-url="$search !== '' ? route('admin.petugas.index') : null"
            reset-label="Reset"
            class="mb-3"
        />

        @if($officers->isEmpty())
            <x-empty-state
                icon="bi-inbox"
                :message="$search !== '' ? 'Tidak ada petugas yang cocok dengan pencarian.' : 'Belum ada data petugas.'"
            />
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table sk-table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. HP</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($officers as $officer)
                            <tr>
                                <td><code>{{ $officer->officer_code }}</code></td>
                                <td class="fw-semibold">{{ $officer->name }}</td>
                                <td>{{ $officer->email }}</td>
                                <td>{{ $officer->phone ?: '-' }}</td>
                                <td>
                                    @if($officer->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.petugas.show', $officer) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                    <a href="{{ route('admin.petugas.edit', $officer) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (<768px) --}}
            <div class="sk-mobile-list d-md-none mb-3">
                @foreach($officers as $officer)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <div>
                                <code>{{ $officer->officer_code }}</code>
                                <div class="fw-bold mt-1">{{ $officer->name }}</div>
                            </div>
                            @if($officer->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Email</span>
                            <span class="sk-mobile-card__value">{{ $officer->email }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">No. HP</span>
                            <span class="sk-mobile-card__value">{{ $officer->phone ?: '-' }}</span>
                        </div>
                        <div class="sk-mobile-card__actions">
                            <a href="{{ route('admin.petugas.show', $officer) }}" class="btn btn-sm btn-outline-primary flex-fill">Detail</a>
                            <a href="{{ route('admin.petugas.edit', $officer) }}" class="btn btn-sm btn-outline-secondary flex-fill">Ubah</a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-2 border-top">
                <small class="text-muted">
                    Menampilkan {{ $officers->firstItem() }}–{{ $officers->lastItem() }} dari {{ $officers->total() }} petugas
                </small>
                {{ $officers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
