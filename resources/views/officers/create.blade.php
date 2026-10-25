@extends('layouts.app')

@section('title', 'Tambah Petugas')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.index') }}">Kelola Petugas</a></li>
        <li class="breadcrumb-item active">Tambah</li>
    </ol>
</nav>

<h4 class="fw-bold mb-3"><i class="bi bi-person-plus"></i> Tambah Akun Petugas</h4>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.petugas.store') }}" novalidate>
            @csrf
            @include('officers._form')

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Petugas</button>
                <a href="{{ route('admin.petugas.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
