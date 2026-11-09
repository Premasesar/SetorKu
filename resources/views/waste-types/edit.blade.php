@extends('layouts.ui-staff')

@section('title', 'Ubah Jenis Sampah')

@section('content')
<x-page-hero
    title="Ubah Jenis Sampah"
    :subtitle="'Perbarui data dan harga per satuan untuk ' . $wasteType->name . '.'"
    icon="bi-pencil-square"
    :breadcrumbs="[
        ['label' => 'Jenis & Harga Sampah', 'url' => route('admin.jenis-sampah.index')],
        ['label' => 'Ubah'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('admin.jenis-sampah.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.jenis-sampah.update', $wasteType) }}" novalidate>
            @csrf
            @method('PUT')
            @include('waste-types._form')

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="{{ route('admin.jenis-sampah.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
