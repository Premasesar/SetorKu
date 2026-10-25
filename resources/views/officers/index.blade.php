@extends('layouts.app')

@section('title', 'Kelola Petugas')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-person-badge"></i> Kelola Petugas</h4>
    <a href="{{ route('admin.petugas.create') }}" class="btn btn-success">
        <i class="bi bi-person-plus"></i> Tambah Petugas
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.petugas.index') }}" class="row g-2 mb-3">
            <div class="col-md-8 col-lg-6">
                <input type="text" name="q" value="{{ $search }}" class="form-control"
                       placeholder="Cari nama, email, nomor HP, atau kode petugas...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i> Cari</button>
                @if($search !== '')
                    <a href="{{ route('admin.petugas.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($officers->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2 mb-0">
                    {{ $search !== '' ? 'Tidak ada petugas yang cocok dengan pencarian.' : 'Belum ada data petugas.' }}
                </p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $officers->firstItem() }}–{{ $officers->lastItem() }} dari {{ $officers->total() }} petugas
                </small>
                {{ $officers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
