@extends('layouts.app')

@section('title', 'Jenis & Harga Sampah')

@section('content')
@php($isAdmin = auth()->user()->isAdmin())
@php($indexRoute = $isAdmin ? 'admin.jenis-sampah.index' : 'petugas.jenis-sampah.index')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-tags"></i> Jenis & Harga Sampah</h4>
    @if($isAdmin)
        <a href="{{ route('admin.jenis-sampah.create') }}" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Tambah Jenis Sampah
        </a>
    @endif
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route($indexRoute) }}" class="row g-2 mb-3">
            <div class="col-md-8 col-lg-6">
                <input type="text" name="q" value="{{ $search }}" class="form-control"
                       placeholder="Cari nama jenis sampah...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i> Cari</button>
                @if($search !== '')
                    <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($wasteTypes->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2 mb-0">Belum ada data jenis sampah.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                                <td>{{ $wasteType->name }}</td>
                                <td>{{ $wasteType->unit }}</td>
                                <td class="text-end">Rp {{ number_format($wasteType->price_per_unit, 2, ',', '.') }}</td>
                                <td>
                                    @if($wasteType->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
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
