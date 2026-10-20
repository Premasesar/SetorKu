@extends('layouts.app')

@section('title', 'Ubah Nasabah')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.nasabah.index') }}">Data Nasabah</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.nasabah.show', $customer) }}">{{ $customer->name }}</a></li>
        <li class="breadcrumb-item active">Ubah</li>
    </ol>
</nav>

<h4 class="fw-bold mb-3"><i class="bi bi-pencil-square"></i> Ubah Data Nasabah</h4>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.nasabah.update', $customer) }}" novalidate>
            @csrf
            @method('PUT')
            @include('customers._form')

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="{{ route('admin.nasabah.show', $customer) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
