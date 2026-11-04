# Panduan Langkah demi Langkah Pengujian & Demonstrasi (REPRODUCE.md)

Dokumen ini berisi alur kerja lengkap untuk menjalankan lab, memverifikasi konfigurasi memori & proses worker, serta menjalankan pengujian komparatif antara versi PHP **Unfixed** (`8082`–`8085`) dan **Fixed** (`8182`–`8185`) saat kuliah maupun perekaman video.

---

## 0. Persiapan Lingkungan Terisolasi (`mise` + `uv`)

Pastikan Anda berada di dalam direktori lab ini agar konfigurasi `mise.toml` dan `.venv` lokal aktif:

```bash
cd /Users/ybbond/puph/AKK-1951/video/php2shell-lab

# Pastikan mise mempercayai konfigurasi lokal dan menginstal Python + uv
mise trust
mise install

# Sinkronisasi virtualenv lokal (.venv) menggunakan uv
mise run setup
```

Verifikasi bahwa `python` dan `uv` mengarah ke instalasi terisolasi:

```bash
mise which python
mise which uv
```

---

## 1. Menyalakan Container Target (Unfixed & Fixed)

Anda dapat menyalakan ke-8 container sekaligus atau menyalakan per kelompok sesuai segmen video yang sedang direkam:

```bash
# Opsi A: Nyalakan semua container (4 unfixed + 4 fixed)
mise run up

# Opsi B: Nyalakan hanya versi belum dipatch (port 8082-8085)
mise run up-unfixed

# Opsi C: Nyalakan hanya versi sudah dipatch (port 8182-8185)
mise run up-fixed
```

Setelah container berjalan, jalankan pemeriksa otomatis untuk menampilkan tabel perbandingan seluruh container:

```bash
mise run check
```

Output tabel akan menampilkan:
- Nama container (`php85-unfixed` s/d `php82-fixed`)
- Status HTTP (`200`)
- Versi eksak PHP (`8.5.11` vs `8.5.12`, `8.4.26` vs `8.4.27`, `8.3.35` vs `8.3.36`, `8.2.34` vs `8.2.35`)
- Label status (`UNFIXED` vs `FIXED`)
- `Worker PID` yang melayani request tersebut

---

## 2. Verifikasi Determinisme Worker & Batas Memori (Untuk Pembukaan Video)

Sebelum menjalankan kode pengujian utama, Anda dapat menunjukkan dua karakteristik penting dari environment ini kepada audiens:

### A. Bukti Hanya Ada 1 Worker Aktif (PID Tetap Sama)
Karena `config/mpm_prefork.conf` mengatur `MaxRequestWorkers 1`, setiap request berurutan ke container yang sama akan selalu ditangani oleh **PID worker yang sama**:

```bash
# Panggil 3 kali berturut-turut ke php84-unfixed (port 8084)
curl -s "http://localhost:8084/?format=json"
curl -s "http://localhost:8084/?format=json"
curl -s "http://localhost:8084/?format=json"
```
Perhatikan nilai `"worker_pid"` dan `"memory_limit": "2M"` yang selalu konsisten.

### B. Bukti Isolasi Memori Container (`48MB` per Container)
Untuk memperlihatkan bahwa seluruh container berjalan dengan batas RAM minimal:

```bash
docker stats --no-stream --format "table {{.Name}}\t{{.MemUsage}}\t{{.MemPerc}}\t{{.PIDs}}"
```

---

## 3. Menambahkan & Menjalankan Kode Pengujian Sendiri

Seluruh container melakukan *bind-mount* direktori `./src` di host ke `/var/www/html` di dalam container. Artinya, **Anda tidak perlu melakukan `docker compose build` ulang** ketika menambahkan atau mengedit file PHP.

### Langkah 1 — Tempatkan Kode Target PHP di `src/`
Buat atau tambahkan file PHP yang ingin diuji di dalam folder `src/`, misalnya `src/test_target.php`. File tersebut langsung dapat diakses di seluruh versi PHP:

- **PHP 8.5 Unfixed:** `http://localhost:8085/test_target.php`
- **PHP 8.5 Fixed:** `http://localhost:8185/test_target.php`
- **PHP 8.4 Unfixed:** `http://localhost:8084/test_target.php`
- **PHP 8.4 Fixed:** `http://localhost:8184/test_target.php`
- **PHP 8.3 Unfixed:** `http://localhost:8083/test_target.php`
- **PHP 8.3 Fixed:** `http://localhost:8183/test_target.php`
- **PHP 8.2 Unfixed:** `http://localhost:8082/test_target.php`
- **PHP 8.2 Fixed:** `http://localhost:8182/test_target.php`

### Langkah 2 — Tempatkan Script Pengujian Klien di `scripts/`
Jika Anda menulis script otomatis dengan Python (misalnya menggunakan `httpx` yang sudah terpasang di `.venv` lokal), simpan di dalam `scripts/`, lalu jalankan melalui `mise` dan `uv`:

```bash
mise exec -- uv run python scripts/<nama_script_pengujian>.py
```

Jika membutuhkan library Python tambahan di tengah pengerjaan, gunakan `uv` melalui `mise` agar tetap terisolasi di `.venv` direktori ini:

```bash
mise exec -- uv add <nama_paket>
```

---

## 4. Pemantauan Log & Debugging Saat Rekaman Video

Untuk menampilkan apa yang terjadi di balik layar pada saat request diproses oleh PHP/Apache (misalnya pesan `Allowed memory size of 2097152 bytes exhausted` saat `zend_bailout` terjadi, atau pergantian PID jika worker crash), buka terminal kedua dan jalankan:

```bash
# Memantau log berdampingan antara 1 container unfixed dan 1 container fixed
docker compose logs -f php84-unfixed php84-fixed
```

Atau jika ingin memeriksa isi direktori session di dalam container secara langsung:

```bash
docker exec -it php84-unfixed ls -la /tmp/php_sessions
docker exec -it php84-fixed ls -la /tmp/php_sessions
```

---

## 5. Membersihkan Lingkungan Setelah Selesai

Setelah demonstrasi atau perekaman video selesai, matikan seluruh container dengan:

```bash
mise run down
```
