@extends('layouts.ui-staff')

@section('title', 'Ubah Nasabah')

@section('content')
<x-page-hero
    title="Ubah Data Nasabah"
    :subtitle="'Perbarui informasi profil dan akun nasabah ' . $customer->name . ' (' . $customer->code . ').'"
    icon="bi-pencil-square"
    :breadcrumbs="[
        ['label' => 'Data Nasabah', 'url' => route('admin.nasabah.index')],
        ['label' => $customer->name, 'url' => route('admin.nasabah.show', $customer)],
        ['label' => 'Ubah'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('admin.nasabah.show', $customer) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.nasabah.update', $customer) }}" novalidate>
            @csrf
            @method('PUT')
            @include('customers._form')

            <div class="mt-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="{{ route('admin.nasabah.show', $customer) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
