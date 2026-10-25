@extends('layouts.app')

@section('title', 'Ubah Petugas')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.index') }}">Kelola Petugas</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.show', $officer) }}">{{ $officer->name }}</a></li>
        <li class="breadcrumb-item active">Ubah</li>
    </ol>
</nav>

<h4 class="fw-bold mb-3"><i class="bi bi-pencil-square"></i> Ubah Data Petugas</h4>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.petugas.update', $officer) }}" novalidate>
            @csrf
            @method('PUT')
            @include('officers._form')

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="{{ route('admin.petugas.show', $officer) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
