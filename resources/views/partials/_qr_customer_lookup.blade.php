{{-- Partial Pencarian Cepat Kode / QR Nasabah (Scanner Keyboard & Kamera via CDN) — PBI-12 --}}
<div class="card shadow-sm mb-4 sk-qr-panel">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-qr-code-scan"></i> Pemindai Kartu QR & Pencarian Cepat Nasabah
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-7 col-lg-6">
                <label for="qr-quick-code" class="form-label mb-1 fw-semibold">
                    <i class="bi bi-upc-scan"></i> Input Cepat Kode / Alat Scanner QR
                </label>
                <input type="text"
                       id="qr-quick-code"
                       class="form-control"
                       placeholder="Scan kartu QR atau ketik kode (mis. NSB-0001) lalu tekan Enter..."
                       autofocus
                       autocomplete="off">
            </div>
            <div class="col-auto d-flex flex-wrap gap-2">
                <button type="button" id="btn-scan-camera" class="btn btn-outline-primary">
                    <i class="bi bi-camera"></i> Scan dengan kamera
                </button>
                <button type="button" id="btn-stop-camera" class="btn btn-outline-danger d-none">
                    <i class="bi bi-stop-circle"></i> Tutup Kamera
                </button>
            </div>
        </div>

        <div id="camera-scan-alert" class="alert alert-warning small d-none mb-3" role="alert"></div>
        <div id="qr-camera-reader" class="border rounded mb-3 d-none" style="max-width: 420px;"></div>

        <form id="customer-search-form" method="GET" action="{{ $actionRoute }}" class="row g-2 align-items-end">
            <div class="col-md-8 col-lg-6">
                <label for="q" class="form-label mb-1">Cari Nasabah (Nama atau Kode mis. NSB-0001)</label>
                <input type="text" id="q" name="q" value="{{ $customerSearch }}" class="form-control"
                       placeholder="Ketik nama atau kode nasabah...">
            </div>
            <div class="col-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-search"></i> Cari</button>
                @if($customerSearch !== '')
                    <a href="{{ $actionRoute }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($customerSearch !== '' && $customers->isEmpty())
    <div class="alert alert-warning shadow-sm mb-3" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <strong>Nasabah tidak ditemukan</strong> untuk pencarian <code>{{ $customerSearch }}</code> (pastikan kode benar dan status akun nasabah aktif).
    </div>
@endif

@once
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const quickInput = document.getElementById('qr-quick-code');
    const searchForm = document.getElementById('customer-search-form');
    const searchInput = document.getElementById('q');
    const btnScan = document.getElementById('btn-scan-camera');
    const btnStop = document.getElementById('btn-stop-camera');
    const alertBox = document.getElementById('camera-scan-alert');
    const readerEl = document.getElementById('qr-camera-reader');

    let html5QrScanner = null;

    function submitDetectedCode(rawCode) {
        const cleaned = (rawCode || '').trim();
        if (!cleaned || !searchInput || !searchForm) {
            return;
        }
        searchInput.value = cleaned;
        searchForm.submit();
    }

    if (quickInput) {
        quickInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitDetectedCode(quickInput.value);
            }
        });
    }

    function showCameraMessage(message) {
        if (!alertBox) {
            return;
        }
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    async function stopScanner() {
        if (html5QrScanner) {
            try {
                await html5QrScanner.stop();
                html5QrScanner.clear();
            } catch (_) {
                // Abaikan jika kamera sudah berhenti
            }
            html5QrScanner = null;
        }
        readerEl?.classList.add('d-none');
        btnStop?.classList.add('d-none');
        btnScan?.classList.remove('d-none');
    }

    if (btnScan) {
        btnScan.addEventListener('click', function () {
            alertBox?.classList.add('d-none');

            const host = window.location.hostname;
            const isLocalhost = host === 'localhost' || host === '127.0.0.1' || host === '::1';
            const isSecure = window.isSecureContext || window.location.protocol === 'https:' || isLocalhost;

            if (!isSecure || !navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                showCameraMessage('Pemindaian kamera hanya dapat berjalan pada koneksi HTTPS atau localhost. Silakan gunakan kolom input kode manual di atas.');
                quickInput?.focus();
                return;
            }

            if (typeof Html5Qrcode === 'undefined') {
                showCameraMessage('Pustaka pemindai QR kamera belum termuat. Silakan gunakan kolom input kode manual di atas.');
                quickInput?.focus();
                return;
            }

            readerEl.classList.remove('d-none');
            btnScan.classList.add('d-none');
            btnStop.classList.remove('d-none');

            html5QrScanner = new Html5Qrcode('qr-camera-reader');
            html5QrScanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                function (decodedText) {
                    stopScanner().finally(function () {
                        submitDetectedCode(decodedText);
                    });
                },
                function () {}
            ).catch(function () {
                stopScanner();
                showCameraMessage('Kamera tidak tersedia atau izin kamera ditolak. Anda tetap dapat mengetik atau memindai kode secara manual.');
                quickInput?.focus();
            });
        });
    }

    if (btnStop) {
        btnStop.addEventListener('click', function () {
            stopScanner();
        });
    }
});
</script>
@endpush
@endonce
