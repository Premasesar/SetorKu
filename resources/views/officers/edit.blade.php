@extends('layouts.ui-staff')

@section('title', 'Ubah Petugas')

@section('content')
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.index') }}">Kelola Petugas</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.show', $officer) }}">{{ $officer->name }}</a></li>
        <li class="breadcrumb-item active">Ubah</li>
    </ol>
</nav>

<x-page-hero
    title="Ubah Data Petugas"
    :subtitle="'Perbarui identitas dan kontak akun petugas ' . $officer->name . ' (' . $officer->officer_code . ').'"
    icon="bi-pencil-square"
    :breadcrumbs="[
        ['label' => 'Portal Admin'],
        ['label' => 'Kelola Petugas', 'url' => route('admin.petugas.index')],
        ['label' => $officer->name, 'url' => route('admin.petugas.show', $officer)],
        ['label' => 'Ubah'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('admin.petugas.show', $officer) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Detail Petugas
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card sk-card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square text-success me-1"></i> Formulir Perubahan Data Petugas</h5>
    </div>
    <div class="card-body p-3 p-md-4">
        <form method="POST" action="{{ route('admin.petugas.update', $officer) }}" novalidate>
            @csrf
            @method('PUT')
            @include('officers._form')

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="{{ route('admin.petugas.show', $officer) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
