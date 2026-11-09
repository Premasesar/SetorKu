@extends('layouts.ui-staff')

@section('title', 'Tambah Nasabah')

@section('content')
@php($routePrefix = auth()->user()->isAdmin() ? 'admin.nasabah' : 'petugas.nasabah')

<x-page-hero
    title="Tambah Nasabah"
    subtitle="Daftarkan nasabah baru beserta akun login Portal Nasabah."
    icon="bi-person-plus"
    :breadcrumbs="[
        ['label' => 'Data Nasabah', 'url' => route($routePrefix . '.index')],
        ['label' => 'Tambah'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route($routePrefix . '.store') }}" novalidate>
            @csrf
            @include('customers._form')

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan</button>
                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
