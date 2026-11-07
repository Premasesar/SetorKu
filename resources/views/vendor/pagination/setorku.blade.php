@php
    $firstRecord = $paginator->first();
    $resolvedItemLabel = $itemLabel ?? match (true) {
        $firstRecord instanceof \App\Models\Customer => 'nasabah',
        $firstRecord instanceof \App\Models\WasteType => 'jenis sampah',
        $firstRecord instanceof \App\Models\Deposit => 'setoran',
        $firstRecord instanceof \App\Models\Withdrawal => 'penarikan',
        $firstRecord instanceof \App\Models\PickupRequest => 'penjemputan',
        $firstRecord instanceof \App\Models\User => 'petugas',
        request()->routeIs('*.nasabah.*') => 'nasabah',
        request()->routeIs('*.petugas.*') => 'petugas',
        request()->routeIs('*.jenis-sampah.*') => 'jenis sampah',
        request()->routeIs('*.setoran.*') => 'setoran',
        request()->routeIs('*.penarikan.*') => 'penarikan',
        request()->routeIs('*.penjemputan.*') => 'penjemputan',
        request()->routeIs('*.riwayat.*') => 'transaksi',
        default => 'data',
    };
@endphp

@if ($paginator->hasPages())
    <nav class="sk-pagination" role="navigation" aria-label="Navigasi Halaman">
        <p class="sk-pagination__info">
            Menampilkan
            <strong>{{ $paginator->firstItem() ?? 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }}</strong>
            dari
            <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong>
            {{ $resolvedItemLabel }}
        </p>

        <ul class="pagination mb-0">
            {{-- Tombol Halaman Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="Halaman sebelumnya">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&lsaquo;</a>
                </li>
            @endif

            {{-- Nomor Halaman --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Tombol Halaman Berikutnya --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="Halaman berikutnya">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
