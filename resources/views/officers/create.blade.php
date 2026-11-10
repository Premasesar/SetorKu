@extends('layouts.ui-staff')

@section('title', 'Tambah Petugas')

@section('content')
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.index') }}">Kelola Petugas</a></li>
        <li class="breadcrumb-item active">Tambah</li>
    </ol>
</nav>

<x-page-hero
    title="Tambah Akun Petugas"
    subtitle="Daftarkan akun petugas operasional baru untuk akses Google maupun password."
    icon="bi-person-plus"
    :breadcrumbs="[
        ['label' => 'Portal Admin'],
        ['label' => 'Kelola Petugas', 'url' => route('admin.petugas.index')],
        ['label' => 'Tambah'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('admin.petugas.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card sk-card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-person-plus text-success me-1"></i> Formulir Akun Petugas Baru</h5>
    </div>
    <div class="card-body p-3 p-md-4">
        <form method="POST" action="{{ route('admin.petugas.store') }}" novalidate>
            @csrf
            @include('officers._form')

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Petugas</button>
                <a href="{{ route('admin.petugas.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
