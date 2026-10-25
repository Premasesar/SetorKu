@extends('layouts.app')

@section('title', 'Data Nasabah')

@section('content')
@php($routePrefix = auth()->user()->isAdmin() ? 'admin.nasabah' : 'petugas.nasabah')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-people"></i> Data Nasabah</h4>
    <a href="{{ route($routePrefix . '.create') }}" class="btn btn-success">
        <i class="bi bi-person-plus"></i> Tambah Nasabah
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route($routePrefix . '.index') }}" class="row g-2 mb-3">
            <div class="col-md-8 col-lg-6">
                <input type="text" name="q" value="{{ $search }}" class="form-control"
                       placeholder="Cari nama atau alamat...">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i> Cari</button>
                @if($search !== '')
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($customers->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1"></i>
                @if($search !== '')
                    <p class="mt-2 mb-0">Tidak ada nasabah yang cocok dengan pencarian "<strong>{{ $search }}</strong>".</p>
                @else
                    <p class="mt-2 mb-0">Belum ada data nasabah.</p>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->user?->email ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($customer->address, 40) }}</td>
                                <td class="text-end">Rp {{ number_format($customer->balance, 0, ',', '.') }}</td>
                                <td>
                                    @if($customer->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route($routePrefix . '.show', $customer) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.nasabah.edit', $customer) }}" class="btn btn-sm btn-outline-secondary" title="Ubah">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
