@extends('layouts.ui-staff')

@section('title', 'Kelola Penjemputan Sampah')

@section('content')
<x-page-hero
    title="Kelola Penjemputan Sampah"
    subtitle="Verifikasi pengajuan penjemputan sampah nasabah, penjadwalan petugas lapangan, dan pemantauan status."
    icon="bi-truck"
    :breadcrumbs="['Portal Admin', 'Kelola Penjemputan Sampah']"
>
    <x-slot:actions>
        <a href="{{ route('admin.rute.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-signpost-split"></i> Optimasi Rute
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card sk-card shadow-sm">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ route('admin.penjemputan.index') }}" class="row g-2 mb-3 align-items-center">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ $search }}" class="form-control"
                       placeholder="Cari kode JMP atau nama nasabah...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\PickupStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" value="{{ $date }}" class="form-control" title="Filter tanggal pengajuan/jadwal">
            </div>
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-funnel"></i> Filter</button>
                @if($status !== '' || $date !== '' || $search !== '')
                    <a href="{{ route('admin.penjemputan.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($pickups->isEmpty())
            <x-empty-state
                icon="bi-truck"
                message="Tidak ada data penjemputan yang ditemukan."
            />
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table sk-table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nasabah</th>
                            <th>Tgl Pengajuan</th>
                            <th>Tgl Jadwal</th>
                            <th>Petugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pickups as $pickup)
                            <tr>
                                <td><code>{{ $pickup->code }}</code></td>
                                <td>
                                    <span class="fw-semibold">{{ $pickup->customer?->name }}</span>
                                    <br><small class="text-muted">{{ $pickup->customer?->code }}</small>
                                </td>
                                <td>{{ $pickup->requested_date?->format('d-m-Y') }}</td>
                                <td>{{ $pickup->scheduled_date?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $pickup->officer?->name ?? '-' }}</td>
                                <td><span class="badge {{ $pickup->statusBadgeClass() }}">{{ $pickup->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.penjemputan.show', $pickup) }}" class="btn btn-sm btn-outline-primary">
                                        Detail & Kelola
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (<768px) --}}
            <div class="sk-mobile-list d-md-none mb-3">
                @foreach($pickups as $pickup)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <div>
                                <code>{{ $pickup->code }}</code>
                                <div class="fw-bold mt-1">{{ $pickup->customer?->name }}</div>
                                <small class="text-muted">{{ $pickup->customer?->code }}</small>
                            </div>
                            <span class="badge {{ $pickup->statusBadgeClass() }}">{{ $pickup->statusLabel() }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Tgl Pengajuan</span>
                            <span class="sk-mobile-card__value">{{ $pickup->requested_date?->format('d-m-Y') }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Tgl Jadwal</span>
                            <span class="sk-mobile-card__value">{{ $pickup->scheduled_date?->format('d-m-Y') ?? '-' }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Petugas</span>
                            <span class="sk-mobile-card__value">{{ $pickup->officer?->name ?? '-' }}</span>
                        </div>
                        <div class="sk-mobile-card__actions">
                            <a href="{{ route('admin.penjemputan.show', $pickup) }}" class="btn btn-sm btn-outline-primary w-100">
                                Detail & Kelola
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-2 border-top">
                <small class="text-muted">
                    Menampilkan {{ $pickups->firstItem() }}–{{ $pickups->lastItem() }} dari {{ $pickups->total() }} penjemputan
                </small>
                {{ $pickups->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
