/**
 * SetorKu — Pemilih Titik Lokasi (Leaflet + OpenStreetMap) — PBI-16.
 *
 * Dipakai oleh komponen Blade <x-map-picker>. Setiap elemen dengan atribut
 * [data-map-picker] diinisialisasi sekali. Konfigurasi dibaca dari data-attribute:
 *   data-lat-input / data-lng-input : id input latitude & longitude (readonly)
 *   data-default-lat / data-default-lng / data-default-zoom : pusat awal
 *   data-selected-zoom : zoom ketika titik sudah dipilih
 *
 * Tanpa geocoding, tanpa API key, tanpa alert(). Geolocation hanya atas klik pengguna.
 */
(function () {
    'use strict';

    var MESSAGES = {
        locating: 'Sedang mencari lokasi Anda…',
        located: 'Lokasi Anda berhasil ditandai. Geser pin bila posisinya kurang tepat.',
        unsupported: 'Browser Anda tidak mendukung fitur lokasi. Silakan klik pada peta untuk memilih titik.',
        denied: 'Izin lokasi ditolak. Silakan klik pada peta untuk memilih titik rumah Anda.',
        unavailable: 'Lokasi Anda tidak dapat ditentukan saat ini. Silakan klik pada peta untuk memilih titik.',
        timeout: 'Waktu pencarian lokasi habis. Silakan coba lagi atau klik pada peta.',
        insecure: 'Fitur lokasi hanya tersedia melalui HTTPS atau localhost. Silakan klik pada peta untuk memilih titik.',
        cleared: 'Titik lokasi dihapus. Kolom koordinat dikosongkan.',
        leafletMissing: 'Peta gagal dimuat. Periksa koneksi internet Anda, atau lanjutkan pendaftaran tanpa titik lokasi.'
    };

    function parseCoord(value, min, max) {
        if (value === null || value === undefined) {
            return null;
        }
        var trimmed = String(value).trim();
        if (trimmed === '') {
            return null;
        }
        var num = Number(trimmed);
        if (!isFinite(num) || num < min || num > max) {
            return null;
        }
        return num;
    }

    function initPicker(root) {
        if (root.getAttribute('data-map-picker-ready') === '1') {
            return;
        }
        root.setAttribute('data-map-picker-ready', '1');

        var latInput = document.getElementById(root.getAttribute('data-lat-input'));
        var lngInput = document.getElementById(root.getAttribute('data-lng-input'));
        var mapEl = root.querySelector('[data-map-picker-canvas]');
        var statusEl = root.querySelector('[data-map-picker-status]');
        var locateBtn = root.querySelector('[data-map-picker-locate]');
        var clearBtn = root.querySelector('[data-map-picker-clear]');

        function setStatus(message, tone) {
            if (!statusEl) {
                return;
            }
            statusEl.textContent = message || '';
            statusEl.classList.remove('text-muted', 'text-danger', 'text-success');
            statusEl.classList.add(tone === 'error' ? 'text-danger' : (tone === 'success' ? 'text-success' : 'text-muted'));
        }

        if (!latInput || !lngInput || !mapEl) {
            return;
        }

        if (typeof window.L === 'undefined') {
            setStatus(MESSAGES.leafletMissing, 'error');
            if (locateBtn) { locateBtn.disabled = true; }
            if (clearBtn) { clearBtn.disabled = true; }
            return;
        }

        var L = window.L;
        var defaultLat = parseFloat(root.getAttribute('data-default-lat')) || -7.797068;
        var defaultLng = parseFloat(root.getAttribute('data-default-lng')) || 110.370529;
        var defaultZoom = parseInt(root.getAttribute('data-default-zoom'), 10) || 13;
        var selectedZoom = parseInt(root.getAttribute('data-selected-zoom'), 10) || 17;

        var initLat = parseCoord(latInput.value, -90, 90);
        var initLng = parseCoord(lngInput.value, -180, 180);
        var hasInitial = initLat !== null && initLng !== null;

        var map = L.map(mapEl, { tap: true }).setView(
            hasInitial ? [initLat, initLng] : [defaultLat, defaultLng],
            hasInitial ? selectedZoom : defaultZoom
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
        }).addTo(map);

        var marker = null;

        function writeInputs(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            latInput.dispatchEvent(new Event('change', { bubbles: true }));
            lngInput.dispatchEvent(new Event('change', { bubbles: true }));
            if (clearBtn) { clearBtn.disabled = false; }
        }

        function placeMarker(lat, lng) {
            if (marker === null) {
                marker = L.marker([lat, lng], { draggable: true, autoPan: true, keyboard: true, title: 'Titik lokasi rumah' }).addTo(map);
                marker.on('dragend', function () {
                    var pos = marker.getLatLng();
                    writeInputs(pos.lat, pos.lng);
                });
            } else {
                marker.setLatLng([lat, lng]);
            }
            writeInputs(lat, lng);
        }

        if (hasInitial) {
            placeMarker(initLat, initLng);
        } else if (clearBtn) {
            clearBtn.disabled = true;
        }

        map.on('click', function (e) {
            placeMarker(e.latlng.lat, e.latlng.lng);
            setStatus('', 'muted');
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (marker !== null) {
                    map.removeLayer(marker);
                    marker = null;
                }
                latInput.value = '';
                lngInput.value = '';
                latInput.dispatchEvent(new Event('change', { bubbles: true }));
                lngInput.dispatchEvent(new Event('change', { bubbles: true }));
                clearBtn.disabled = true;
                setStatus(MESSAGES.cleared, 'muted');
            });
        }

        if (locateBtn) {
            locateBtn.addEventListener('click', function () {
                if (!('geolocation' in navigator) || !navigator.geolocation) {
                    setStatus(MESSAGES.unsupported, 'error');
                    return;
                }
                if (window.isSecureContext === false) {
                    setStatus(MESSAGES.insecure, 'error');
                    return;
                }

                locateBtn.disabled = true;
                setStatus(MESSAGES.locating, 'muted');

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        locateBtn.disabled = false;
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;
                        placeMarker(lat, lng);
                        map.setView([lat, lng], selectedZoom);
                        setStatus(MESSAGES.located, 'success');
                    },
                    function (error) {
                        locateBtn.disabled = false;
                        var code = error && typeof error.code === 'number' ? error.code : 0;
                        if (code === 1) {
                            setStatus(MESSAGES.denied, 'error');
                        } else if (code === 3) {
                            setStatus(MESSAGES.timeout, 'error');
                        } else {
                            setStatus(MESSAGES.unavailable, 'error');
                        }
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            });
        }

        // Pastikan ukuran peta benar (mencegah area abu-abu/terpotong) setelah layout final & saat resize/rotasi.
        function refreshSize() {
            map.invalidateSize();
        }
        window.setTimeout(refreshSize, 0);
        window.setTimeout(refreshSize, 300);
        window.addEventListener('load', refreshSize);
        window.addEventListener('resize', refreshSize);
        window.addEventListener('orientationchange', refreshSize);
        if (typeof window.ResizeObserver === 'function') {
            new window.ResizeObserver(refreshSize).observe(mapEl);
        }
    }

    function initAll() {
        var pickers = document.querySelectorAll('[data-map-picker]');
        for (var i = 0; i < pickers.length; i++) {
            initPicker(pickers[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
