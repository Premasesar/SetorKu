# Dokumentasi Optimasi Rute Penjemputan Sampah (PBI-11)

Dokumen ini menjelaskan metodologi perhitungan rute penjemputan tertutup pada aplikasi **SetorKu** yang diimplementasikan di class murni PHP `App\Services\RouteOptimizer`.

---

## 1. Definisi Masalah Rute Tertutup

Setiap jadwal penjemputan harian seorang petugas dimodelkan sebagai tur tertutup (*closed tour*):
- **Titik `0` (Depot Tetap)**: Lokasi Bank Sampah (`config('setorku.bank_sampah')`, default pusat Kota Yogyakarta `-7.7970680, 110.3705290`).
- **Titik `1..N`**: Titik koordinat penjemputan nasabah berstatus `terjadwal` pada tanggal dan petugas terpilih yang lolos validasi koordinat.
- Petugas **berangkat dari titik `0`**, mengunjungi setiap titik `1..N` **tepat satu kali**, dan **kembali ke titik `0`** (`[0, v_1, v_2, ..., v_N, 0]`).

---

## 2. Perhitungan Jarak Garis Lurus (Haversine)

Jarak antar dua koordinat lintang-bujur $(\phi_1, \lambda_1)$ dan $(\phi_2, \lambda_2)$ dihitung memakai rumus **Haversine** dengan jari-jari rata-rata bumi $R = 6371{,}0088\text{ km}$:

$$
a = \sin^2\left(\frac{\Delta\phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)
$$

$$
c = 2 \cdot \operatorname{atan2}\left(\sqrt{a}, \sqrt{1 - a}\right), \qquad d = R \cdot c
$$

Seluruh pasangan jarak disimpan dalam matriks jarak simetris $D$ berukuran $(N+1) \times (N+1)$ dengan kompleksitas waktu dan ruang $O(N^2)$.

---

## 3. Konstruksi Rute Awal: Nearest Neighbor (NN)

Algoritma *greedy* **Nearest Neighbor** membangun tur tertutup dari depot `0` dengan selalu memilih titik belum dikunjungi yang memiliki jarak terdekat dari posisi saat ini:

```text
FUNCTION NearestNeighbor(D, N):
    tour <- [0]
    visited[0] <- true; visited[1..N] <- false
    current <- 0

    FOR step <- 1 TO N:
        next <- argmin_{j in 1..N, not visited[j]} D[current][j]
        visited[next] <- true
        APPEND next TO tour
        current <- next

    APPEND 0 TO tour
    RETURN tour
```

- **Kompleksitas Waktu**: $O(N^2)$
- **Kompleksitas Ruang**: $O(N)$ (di luar matriks jarak $O(N^2)$)

---

## 4. Perbaikan Rute Lokal: 2-Opt

Karena sifat *greedy* Nearest Neighbor sering menyisakan persilangan sisi (*crossing edges*) atau lompatan di akhir tur, hasilnya diperbaiki menggunakan **2-Opt** pada segmen kunjungan `1..N` (titik awal `tour[0] = 0` dan titik kembali `tour[N+1] = 0` dijaga tetap):

```text
FUNCTION TwoOpt(tour, D, maxIterations = 1000):
    improved <- true
    iter <- 0

    WHILE improved AND iter < maxIterations:
        improved <- false
        iter <- iter + 1

        FOR i <- 1 TO N - 1:
            FOR k <- i + 1 TO N:
                oldCost <- D[tour[i-1]][tour[i]] + D[tour[k]][tour[k+1]]
                newCost <- D[tour[i-1]][tour[k]] + D[tour[i]][tour[k+1]]

                IF newCost + 1e-9 < oldCost:
                    REVERSE tour[i .. k]
                    improved <- true

    RETURN tour
```

- **Sifat Monoton**: Setiap pertukaran hanya diterima bila `newCost < oldCost`, sehingga panjang rute hasil `twoOpt` dijamin **tidak pernah lebih panjang** daripada rute masukan (`nearestNeighbor`).
- **Kompleksitas Waktu**: $O(N^2)$ per iterasi pemindaian, dibatasi maksimum `maxIterations` ($O(K \cdot N^2)$) agar selalu berhenti dalam waktu terukur.

---

## 5. Validasi Kualitas Koordinat

Sebelum matriks jarak dibangun, setiap titik diuji oleh `RouteOptimizer::validateCoordinate()`:
1. Nilai lintang/bujur kosong (`null` atau string kosong) -> **dikecualikan**.
2. Nilai bukan angka numerik berhingga -> **dikecualikan**.
3. Titik tepat `(0, 0)` -> **dikecualikan**.
4. Di luar batas geografis Indonesia (`lat` di luar `[-11, 6]` atau `lng` di luar `[95, 141]`) -> **dikecualikan**.

Titik yang dikecualikan ditampilkan pada tabel peringatan beserta alasannya dan tidak diberi `route_sequence`. Bila jumlah titik valid `< 2`, optimasi dinyatakan tidak diperlukan.

---

## 6. Contoh Hasil Nyata dari Data Seeder Yogyakarta

Depot Bank Sampah: **Bank Sampah SetorKu Yogyakarta** (`-7.7970680, 110.3705290`).

### A. Tugas Hari Ini — Petugas Dua (4 Titik Valid)

| Metode / Tahap | Urutan Kunjungan | Total Jarak Tertutup | Selisih vs Awal |
| --- | --- | ---: | ---: |
| **Urutan Awal** (`created_at`) | `Depot -> JMP-0005 -> JMP-0006 -> JMP-0007 -> JMP-0008 -> Depot` | **13,25 km** (`13.2458 km`) | — |
| **Nearest Neighbor (NN)** | `Depot -> JMP-0008 -> JMP-0007 -> JMP-0005 -> JMP-0006 -> Depot` | **13,05 km** (`13.0535 km`) | Hemat `0,19 km` (`1,45%`) |
| **Nearest Neighbor + 2-Opt** | `Depot -> JMP-0006 -> JMP-0005 -> JMP-0008 -> JMP-0007 -> Depot` | **12,35 km** (`12.3452 km`) | **Hemat `0,90 km` (`6,80%`)** |

Rincian segmen hasil akhir (`NN + 2-Opt`):
1. `0 (Depot)` -> `#1 JMP-0006` (Agus Setiawan, Sewon): `4,48 km` (kumulatif `4,48 km`)
2. `#1 JMP-0006` -> `#2 JMP-0005` (Joko Prasetyo, Prawirotaman): `2,41 km` (kumulatif `6,89 km`)
3. `#2 JMP-0005` -> `#3 JMP-0008` (Sri Wahyuni, Taman Siswa): `1,50 km` (kumulatif `8,39 km`)
4. `#3 JMP-0008` -> `#4 JMP-0007` (Rina Kusuma, Kusumanegara): `1,60 km` (kumulatif `9,99 km`)
5. `#4 JMP-0007` -> `0 (Depot)`: `2,36 km` (**Total: `12,35 km`**)

### B. Tugas Hari Ini — Petugas Satu (4 Titik Valid)

- **Urutan Awal**: `9,48 km` (`9.4814 km`)
- **Nearest Neighbor**: `9,70 km` (`9.6969 km`) — langkah *greedy* awal sempat menghasilkan urutan sedikit lebih panjang
- **Nearest Neighbor + 2-Opt**: **9,48 km** (`9.4814 km`) — 2-Opt mengoreksi langkah *greedy* NN kembali ke tur bebas persilangan (`0,00%` terhadap urutan awal, hemat `0,22 km` terhadap NN murni).

---

## 7. Keterbatasan

1. **Jarak Garis Lurus (*Crow-Flies*)**: Rumus Haversine menghitung jarak permukaan bola bumi, belum memperhitungkan jaringan jalan raya, jalan satu arah, putar balik (*U-turn*), rintangan sungai/rel kereta, maupun kondisi lalu lintas.
2. **Optimum Lokal (*Heuristic*)**: Kombinasi Nearest Neighbor + 2-Opt merupakan algoritma heuristik perbaikan lokal sehingga **tidak menjamin ditemukannya rute optimum global** untuk seluruh kemungkinan permutasi titik.
3. **Tanpa Kendala Kapasitas & Jendela Waktu**: Perhitungan mengasumsikan seluruh titik pada seorang petugas dapat diangkut dalam satu perjalanan tanpa batas kapasitas kendaraan (*CVRP*) atau jam khusus (*time windows*).
