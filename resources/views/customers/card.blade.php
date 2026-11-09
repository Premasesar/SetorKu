@extends(auth()->user()?->isNasabah() ? 'layouts.ui-nasabah' : 'layouts.ui-staff')

@section('title', $pageTitle ?? 'Kartu QR Nasabah')

@push('styles')
<style>
    .qr-member-card {
        max-width: 420px;
        border: 2px solid #198754;
        border-radius: 16px;
        background: #ffffff;
        overflow: hidden;
    }
    #customer-qr-code img,
    #customer-qr-code canvas {
        margin: 0 auto;
        display: block;
    }

    @media print {
        body {
            background: #ffffff !important;
        }
        .sidebar,
        .navbar,
        .offcanvas,
        .sk-sidebar,
        .sk-topbar,
        .sk-bottom-bar,
        .sk-nasabah-header,
        .sk-footer,
        .sk-hero,
        .no-print,
        .alert {
            display: none !important;
        }
        .content-wrapper,
        .content-wrapper > .p-4,
        .sk-staff__main,
        .sk-staff__content,
        .sk-nasabah__main {
            padding: 0 !important;
            margin: 0 !important;
        }
        .qr-member-card {
            box-shadow: none !important;
            margin: 10mm auto !important;
            page-break-inside: avoid;
        }
    }
</style>
@endpush

@section('content')
@php
    $portalCrumb = auth()->user()?->isNasabah()
        ? 'Portal Nasabah'
        : (auth()->user()?->isAdmin() ? 'Portal Admin' : 'Portal Petugas');
@endphp

<x-page-hero
    :title="$pageTitle ?? 'Kartu QR Nasabah'"
    subtitle="Kartu identitas nasabah siap cetak. Kode QR hanya memuat kode nasabah tanpa saldo maupun data sensitif."
    icon="bi-qr-code"
    :breadcrumbs="[$portalCrumb, $pageTitle ?? 'Kartu QR Nasabah']"
    class="no-print"
>
    <x-slot:actions>
        <button type="button" onclick="window.print()" class="btn btn-success">
            <i class="bi bi-printer"></i> Cetak Kartu
        </button>
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </x-slot:actions>
</x-page-hero>

<div class="qr-member-card card shadow-sm mx-auto my-4">
    <div class="card-header bg-success text-white text-center py-3">
        <div class="fw-bold fs-5"><i class="bi bi-recycle"></i> SetorKu</div>
        <small>{{ config('setorku.bank_sampah.name', 'Bank Sampah SetorKu Yogyakarta') }}</small>
    </div>
    <div class="card-body text-center p-4">
        <div class="text-uppercase text-muted small mb-1">Kartu Anggota Nasabah</div>
        <h4 class="fw-bold mb-1">{{ $customer->name }}</h4>
        <div class="fs-5 fw-semibold text-success mb-3">
            <code class="text-success fs-5">{{ $customer->code }}</code>
        </div>

        <div id="customer-qr-code"
             class="p-3 border rounded d-inline-block bg-white"
             data-qr-payload="{{ $customer->code }}"></div>

        <div class="small text-muted mt-3">
            Tunjukkan kartu ini kepada petugas saat setoran atau penarikan.
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const qrContainer = document.getElementById('customer-qr-code');
    if (!qrContainer || typeof QRCode === 'undefined') {
        return;
    }

    const payload = qrContainer.dataset.qrPayload || '';
    new QRCode(qrContainer, {
        text: payload,
        width: 168,
        height: 168,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
});
</script>
@endpush
