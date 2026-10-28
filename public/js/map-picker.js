/**
 * SetorKu — Pemilih & Penampil Titik Lokasi (Leaflet + OpenStreetMap) — PBI-16 / PBI-17 / PBI-18.
 *
 * Dipakai oleh komponen Blade <x-map-picker>. Setiap elemen [data-map-picker]
 * diinisialisasi sekali berdasarkan data-map-picker-mode:
 *   - "view" : peta mode baca-saja (pin tampil tanpa bisa digeser, klik peta diabaikan,
 *              zoom +/- dan geser peta tetap aktif).
 *   - "edit" : peta mode ubah/daftar dengan pengaman dua keadaan (TERKUNCI & AKTIF),
 *              clickTolerance 10 piksel, tombol "Ubah Titik", "Gunakan Lokasi Saya",
 *              "Kembalikan Titik Semula", dan "Hapus Titik".
 *
 * Tanpa geocoding, tanpa API key, tanpa alert(). Geolocation hanya atas klik pengguna.
 */
(function () {
    'use strict';

    var CLICK_TOLERANCE_PX = 10;

    var MESSAGES = {
        activeMode: 'Mode ubah titik aktif: klik peta atau geser pin.',
        updated: 'Titik diperbarui (belum disimpan).',
        restored: 'Titik dikembalikan ke posisi semula.',
        restoredEmpty: 'Titik dikosongkan ke keadaan semula. Mode ubah titik aktif: klik peta atau geser pin.',
        locating: 'Sedang mencari lokasi Anda…',
        unsupported: 'Browser Anda tidak mendukung fitur lokasi. Silakan klik pada peta untuk memilih titik.',
        denied: 'Izin lokasi ditolak. Silakan klik pada peta untuk memilih titik rumah Anda.',
        unavailable: 'Lokasi Anda tidak dapat ditentukan saat ini. Silakan klik pada peta untuk memilih titik.',
        timeout: 'Waktu pencarian lokasi habis. Silakan coba lagi atau klik pada peta.',
        insecure: 'Fitur lokasi hanya tersedia melalui HTTPS atau localhost. Silakan klik pada peta untuk memilih titik.',
        cleared: 'Titik lokasi dihapus. Mode ubah titik aktif: klik peta atau geser pin.',
        leafletMissing: 'Peta gagal dimuat. Periksa koneksi internet Anda, atau lanjutkan tanpa peta.'
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

    function applyClickTolerance(map) {
        if (map && map.dragging && map.dragging._draggable && map.dragging._draggable.options) {
            map.dragging._draggable.options.clickTolerance = CLICK_TOLERANCE_PX;
        }
    }

    function attachSizeObservers(map, mapEl) {
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

    function addOsmTileLayer(L, map) {
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
        }).addTo(map);
    }

    function setStatusText(statusEl, message, tone) {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = message || '';
        statusEl.classList.remove('text-muted', 'text-danger', 'text-success', 'fw-semibold');
        if (tone === 'error') {
            statusEl.classList.add('text-danger');
        } else if (tone === 'success') {
            statusEl.classList.add('text-success', 'fw-semibold');
        } else {
            statusEl.classList.add('text-muted');
        }
    }

    function setElementVisible(el, visible) {
        if (!el) {
            return;
        }
        if (visible) {
            el.classList.remove('d-none');
            el.hidden = false;
        } else {
            el.classList.add('d-none');
            el.hidden = true;
        }
    }

    function initViewPicker(root) {
        var mapEl = root.querySelector('[data-map-picker-canvas]');
        var statusEl = root.querySelector('[data-map-picker-status]');
        if (!mapEl) {
            return;
        }

        if (typeof window.L === 'undefined') {
            setStatusText(statusEl, MESSAGES.leafletMissing, 'error');
            return;
        }

        var lat = parseCoord(root.getAttribute('data-init-lat'), -90, 90);
        var lng = parseCoord(root.getAttribute('data-init-lng'), -180, 180);
        if (lat === null || lng === null) {
            return;
        }

        var L = window.L;
        var selectedZoom = parseInt(root.getAttribute('data-selected-zoom'), 10) || 17;

        var map = L.map(mapEl, {
            clickTolerance: CLICK_TOLERANCE_PX,
            zoomControl: true,
            dragging: true
        }).setView([lat, lng], selectedZoom);

        applyClickTolerance(map);
        addOsmTileLayer(L, map);

        var marker = L.marker([lat, lng], {
            draggable: false,
            keyboard: false,
            title: 'Titik lokasi rumah'
        }).addTo(map);

        if (marker.dragging) {
            marker.dragging.disable();
        }

        attachSizeObservers(map, mapEl);
    }

    function initEditPicker(root) {
        var latInput = document.getElementById(root.getAttribute('data-lat-input'));
        var lngInput = document.getElementById(root.getAttribute('data-lng-input'));
        var mapEl = root.querySelector('[data-map-picker-canvas]');
        var statusEl = root.querySelector('[data-map-picker-status]');
        var lockHintEl = root.querySelector('[data-map-picker-lock-hint]');
        var unlockBtn = root.querySelector('[data-map-picker-unlock]');
        var locateBtn = root.querySelector('[data-map-picker-locate]');
        var resetBtn = root.querySelector('[data-map-picker-reset]');
        var clearBtn = root.querySelector('[data-map-picker-clear]');

        if (!latInput || !lngInput || !mapEl) {
            return;
        }

        if (typeof window.L === 'undefined') {
            setStatusText(statusEl, MESSAGES.leafletMissing, 'error');
            if (unlockBtn) { unlockBtn.disabled = true; }
            if (locateBtn) { locateBtn.disabled = true; }
            if (resetBtn) { resetBtn.disabled = true; }
            if (clearBtn) { clearBtn.disabled = true; }
            return;
        }

        var L = window.L;
        var defaultLat = parseFloat(root.getAttribute('data-default-lat')) || -7.797068;
        var defaultLng = parseFloat(root.getAttribute('data-default-lng')) || 110.370529;
        var defaultZoom = parseInt(root.getAttribute('data-default-zoom'), 10) || 13;
        var selectedZoom = parseInt(root.getAttribute('data-selected-zoom'), 10) || 17;

        var originLat = parseCoord(latInput.value, -90, 90);
        var originLng = parseCoord(lngInput.value, -180, 180);
        var hasOrigin = originLat !== null && originLng !== null;
        var originLatStr = hasOrigin ? originLat.toFixed(7) : '';
        var originLngStr = hasOrigin ? originLng.toFixed(7) : '';

        var state = hasOrigin ? 'locked' : 'active';
        root.setAttribute('data-map-picker-state', state);

        var map = L.map(mapEl, {
            clickTolerance: CLICK_TOLERANCE_PX
        }).setView(
            hasOrigin ? [originLat, originLng] : [defaultLat, defaultLng],
            hasOrigin ? selectedZoom : defaultZoom
        );

        applyClickTolerance(map);
        addOsmTileLayer(L, map);

        var marker = null;

        function isDifferentFromOrigin() {
            return latInput.value.trim() !== originLatStr || lngInput.value.trim() !== originLngStr;
        }

        function syncMarkerDraggable() {
            if (!marker || !marker.dragging) {
                return;
            }
            if (state === 'active') {
                marker.dragging.enable();
            } else {
                marker.dragging.disable();
            }
        }

        function syncControls() {
            var isLocked = (state === 'locked');
            root.setAttribute('data-map-picker-state', state);

            mapEl.classList.toggle('map-picker-canvas--active', !isLocked);
            setElementVisible(lockHintEl, isLocked);
            setElementVisible(unlockBtn, isLocked);
            setElementVisible(resetBtn, isDifferentFromOrigin());

            if (clearBtn) {
                clearBtn.disabled = (marker === null && latInput.value.trim() === '' && lngInput.value.trim() === '');
            }
            syncMarkerDraggable();
        }

        function setLockState(nextState) {
            state = nextState;
            syncControls();
        }

        function writeInputs(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            latInput.dispatchEvent(new Event('change', { bubbles: true }));
            lngInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function placeMarker(lat, lng) {
            if (marker === null) {
                marker = L.marker([lat, lng], {
                    draggable: true,
                    autoPan: true,
                    keyboard: true,
                    title: 'Titik lokasi rumah'
                }).addTo(map);

                marker.on('dragend', function () {
                    if (state !== 'active') {
                        return;
                    }
                    var pos = marker.getLatLng();
                    writeInputs(pos.lat, pos.lng);
                    setLockState('locked');
                    setStatusText(statusEl, MESSAGES.updated, 'success');
                });
            } else {
                marker.setLatLng([lat, lng]);
            }
            writeInputs(lat, lng);
            syncControls();
        }

        if (hasOrigin) {
            placeMarker(originLat, originLng);
            setLockState('locked');
            setStatusText(statusEl, '', 'muted');
        } else {
            setLockState('active');
            setStatusText(statusEl, MESSAGES.activeMode, 'success');
        }

        if (unlockBtn) {
            unlockBtn.addEventListener('click', function () {
                setLockState('active');
                setStatusText(statusEl, MESSAGES.activeMode, 'success');
            });
        }

        map.on('click', function (e) {
            if (state !== 'active') {
                return;
            }
            placeMarker(e.latlng.lat, e.latlng.lng);
            setLockState('locked');
            setStatusText(statusEl, MESSAGES.updated, 'success');
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (hasOrigin) {
                    placeMarker(originLat, originLng);
                    map.setView([originLat, originLng], selectedZoom);
                    setLockState('locked');
                    setStatusText(statusEl, MESSAGES.restored, 'muted');
                } else {
                    if (marker !== null) {
                        map.removeLayer(marker);
                        marker = null;
                    }
                    latInput.value = '';
                    lngInput.value = '';
                    latInput.dispatchEvent(new Event('change', { bubbles: true }));
                    lngInput.dispatchEvent(new Event('change', { bubbles: true }));
                    setLockState('active');
                    setStatusText(statusEl, MESSAGES.restoredEmpty, 'muted');
                }
            });
        }

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
                setLockState('active');
                setStatusText(statusEl, MESSAGES.cleared, 'muted');
            });
        }

        if (locateBtn) {
            locateBtn.addEventListener('click', function () {
                if (!('geolocation' in navigator) || !navigator.geolocation) {
                    setStatusText(statusEl, MESSAGES.unsupported, 'error');
                    return;
                }
                if (window.isSecureContext === false) {
                    setStatusText(statusEl, MESSAGES.insecure, 'error');
                    return;
                }

                locateBtn.disabled = true;
                setStatusText(statusEl, MESSAGES.locating, 'muted');

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        locateBtn.disabled = false;
                        var lat = position.coords.latitude;
                        var lng = position.coords.longitude;
                        placeMarker(lat, lng);
                        map.setView([lat, lng], selectedZoom);
                        setLockState('locked');
                        setStatusText(statusEl, MESSAGES.updated, 'success');
                    },
                    function (error) {
                        locateBtn.disabled = false;
                        var code = error && typeof error.code === 'number' ? error.code : 0;
                        if (code === 1) {
                            setStatusText(statusEl, MESSAGES.denied, 'error');
                        } else if (code === 3) {
                            setStatusText(statusEl, MESSAGES.timeout, 'error');
                        } else {
                            setStatusText(statusEl, MESSAGES.unavailable, 'error');
                        }
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            });
        }

        attachSizeObservers(map, mapEl);
    }

    function initPicker(root) {
        if (root.getAttribute('data-map-picker-ready') === '1') {
            return;
        }
        root.setAttribute('data-map-picker-ready', '1');

        var mode = root.getAttribute('data-map-picker-mode') || 'edit';
        if (mode === 'view') {
            initViewPicker(root);
        } else {
            initEditPicker(root);
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
