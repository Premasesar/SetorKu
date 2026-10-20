@extends('layouts.app')

@section('title', 'Tambah Nasabah')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.nasabah.index') }}">Data Nasabah</a></li>
        <li class="breadcrumb-item active">Tambah</li>
    </ol>
</nav>

<h4 class="fw-bold mb-3"><i class="bi bi-person-plus"></i> Tambah Nasabah</h4>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.nasabah.store') }}" novalidate>
            @csrf
            @include('customers._form')

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan</button>
                <a href="{{ route('admin.nasabah.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
