# Panduan Langkah demi Langkah Pengujian & Demonstrasi (REPRODUCE.md)

Dokumen ini berisi alur kerja lengkap untuk menjalankan laboratorium Docker (`./lab`), memverifikasi konfigurasi memori & proses worker, serta menjalankan pengujian komparatif antara versi PHP **Unfixed** (`8082`–`8085`) dan **Fixed** (`8182`–`8185`).

---

## 0. Persiapan Lingkungan Terisolasi (`mise`)

Dari root submodule `php2shell-lab`, aktifkan `mise` dan inisialisasi environment Python (`uv`) serta proyek video (`bun`):

```bash
cd /Users/ybbond/puph/AKK-1951/php2shell-lab

mise trust
mise install

# Siapkan virtualenv Python di ./lab/.venv
mise run lab:setup

# Siapkan dependensi Remotion di ./video/node_modules
mise run video:install
```

---

## 1. Menyalakan Container Target (`./lab`)

Anda dapat menyalakan ke-8 container sekaligus atau menyalakan per kelompok sesuai segmen materi yang sedang didemonstrasikan:

```bash
# Opsi A: Nyalakan semua container (4 unfixed + 4 fixed)
mise run lab:up

# Opsi B: Nyalakan hanya versi belum dipatch (port 8082-8085)
mise run lab:up-unfixed

# Opsi C: Nyalakan hanya versi sudah dipatch (port 8182-8185)
mise run lab:up-fixed
```

Periksa kesiapan seluruh container:

```bash
mise run lab:check
```

---

## 2. Verifikasi Determinisme Worker & Batas Memori

### A. Bukti Hanya Ada 1 Worker Aktif (PID Tetap Sama)
Karena `lab/config/mpm_prefork.conf` mengatur `MaxRequestWorkers 1`, setiap request berurutan ke container yang sama akan selalu ditangani oleh **PID worker yang sama**:

```bash
curl -s "http://localhost:8084/?format=json"
curl -s "http://localhost:8084/?format=json"
curl -s "http://localhost:8084/?format=json"
```
Perhatikan nilai `"worker_pid"` dan `"memory_limit": "2M"` yang konsisten.

### B. Bukti Isolasi Memori Container (`48MB` per Container)
```bash
docker stats --no-stream --format "table {{.Name}}\t{{.MemUsage}}\t{{.MemPerc}}\t{{.PIDs}}"
```

---

## 3. Menambahkan & Menjalankan Kode Pengujian Sendiri

Seluruh container melakukan *bind-mount* direktori `./lab/src` di host ke `/var/www/html` di dalam container. Anda **tidak perlu** melakukan rebuild image ketika menambahkan atau mengedit file PHP.

### Langkah 1 — Tempatkan Kode Target PHP di `lab/src/`
Tambahkan file PHP yang ingin diuji di dalam folder `lab/src/`, misalnya `lab/src/test_target.php`. File tersebut langsung dapat diakses di seluruh versi PHP:

- **PHP 8.5 Unfixed:** `http://localhost:8085/test_target.php`
- **PHP 8.5 Fixed:** `http://localhost:8185/test_target.php`
- **PHP 8.4 Unfixed:** `http://localhost:8084/test_target.php`
- **PHP 8.4 Fixed:** `http://localhost:8184/test_target.php`
- **PHP 8.3 Unfixed:** `http://localhost:8083/test_target.php`
- **PHP 8.3 Fixed:** `http://localhost:8183/test_target.php`
- **PHP 8.2 Unfixed:** `http://localhost:8082/test_target.php`
- **PHP 8.2 Fixed:** `http://localhost:8182/test_target.php`

### Langkah 2 — Tempatkan Script Pengujian Klien di `lab/scripts/`
Simpan script pengujian Python Anda di `lab/scripts/`, lalu jalankan melalui `mise` dan `uv`:

```bash
cd /Users/ybbond/puph/AKK-1951/php2shell-lab/lab
mise exec -- uv run python scripts/<nama_script_pengujian>.py
```

---

## 4. Pemantauan Log & Sesi Saat Demonstrasi

```bash
# Memantau log berdampingan antara container unfixed dan fixed
docker compose -f lab/docker-compose.yml logs -f php84-unfixed php84-fixed

# Memeriksa direktori session di dalam container
docker exec -it php84-unfixed ls -la /tmp/php_sessions
docker exec -it php84-fixed ls -la /tmp/php_sessions
```

---

## 5. Mematikan Container Lab

```bash
mise run lab:down
```
