@extends('layouts.ui-staff')

@section('title', 'Data Nasabah')

@section('content')
@php($routePrefix = auth()->user()->isAdmin() ? 'admin.nasabah' : 'petugas.nasabah')
@php($portalLabel = auth()->user()->isAdmin() ? 'Portal Admin' : 'Portal Petugas')

<x-page-hero
    title="Data Nasabah"
    subtitle="Kelola direktori nasabah, pantau saldo tabungan sampah, dan periksa status akun."
    icon="bi-people"
    :breadcrumbs="[$portalLabel, 'Data Nasabah']"
>
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.create') }}" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Tambah Nasabah
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card shadow-sm">
    <div class="card-body">
        <x-table-toolbar
            :action="route($routePrefix . '.index')"
            search-name="q"
            :search-value="$search"
            placeholder="Cari nama atau alamat..."
            submit-label="Cari"
            :reset-url="$search !== '' ? route($routePrefix . '.index') : null"
            reset-label="Reset"
            class="mb-3"
        />

        @if($customers->isEmpty())
            <div class="sk-empty">
                <div class="sk-empty__icon" aria-hidden="true">
                    <i class="bi bi-inbox"></i>
                </div>
                @if($search !== '')
                    <p class="sk-empty__text">Tidak ada nasabah yang cocok dengan pencarian "<strong>{{ $search }}</strong>".</p>
                @else
                    <p class="sk-empty__text">Belum ada data nasabah.</p>
                @endif
            </div>
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle sk-table">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Alamat</th>
                            <th class="text-end">Saldo</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td>{{ $customers->firstItem() + $loop->index }}</td>
                                <td><code>{{ $customer->code }}</code></td>
                                <td class="fw-semibold">{{ $customer->name }}</td>
                                <td>{{ $customer->user?->email ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($customer->address, 40) }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($customer->balance, 0, ',', '.') }}</td>
                                <td>
                                    <x-status-pill :status="$customer->is_active ? 'aktif' : 'nonaktif'" />
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route($routePrefix . '.show', $customer) }}" class="btn btn-sm btn-outline-primary" title="Detail" aria-label="Detail">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.nasabah.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="Ubah" aria-label="Ubah">
                                            <i class="bi bi-pencil"></i> Ubah
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sk-mobile-list d-md-none mb-3">
                @foreach($customers as $customer)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <div>
                                <span class="text-muted small me-1">#{{ $customers->firstItem() + $loop->index }}</span>
                                <code>{{ $customer->code }}</code>
                            </div>
                            <x-status-pill :status="$customer->is_active ? 'aktif' : 'nonaktif'" />
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Nama</span>
                            <span class="sk-mobile-card__value fw-semibold">{{ $customer->name }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Email</span>
                            <span class="sk-mobile-card__value">{{ $customer->user?->email ?? '-' }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Alamat</span>
                            <span class="sk-mobile-card__value">{{ \Illuminate\Support\Str::limit($customer->address, 40) }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Saldo</span>
                            <span class="sk-mobile-card__value fw-semibold text-success">Rp {{ number_format($customer->balance, 0, ',', '.') }}</span>
                        </div>
                        <div class="sk-mobile-card__actions">
                            <a href="{{ route($routePrefix . '.show', $customer) }}" class="btn btn-sm btn-outline-primary flex-grow-1" title="Detail">
                                <i class="bi bi-eye"></i> Detail
                            </a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.nasabah.edit', $customer) }}" class="btn btn-sm btn-outline-secondary flex-grow-1" title="Ubah">
                                    <i class="bi bi-pencil"></i> Ubah
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $customers->firstItem() }}–{{ $customers->lastItem() }} dari {{ $customers->total() }} nasabah
                </small>
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
