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

## 5. Demo CVE-2026-109618 & CVE-2026-109619 (Siap Pakai)

Dua endpoint simulasi edukatif sudah tersedia di `lab/src/` (otomatis ter-mount ke semua container), plus satu script demo berdampingan di `lab/scripts/demo_cves.py`:

| File | Fungsi |
| :--- | :--- |
| `lab/src/cve109618_oom.php` | Simulasi OOM bailout (`zend_bailout`) yang meninggalkan state internal terinisialisasi sebagian tanpa rollback |
| `lab/src/cve109619_uaf.php` | Simulasi use-after-free: pointer usang di registry worker tingkat proses dibaca ulang oleh request berikutnya di PID yang sama |
| `lab/scripts/demo_cves.py` | Menjalankan urutan demo lengkap pada pasangan unfixed vs fixed, lalu menampilkan tabel perbandingan |

### 5.1 Jalankan Demo Otomatis (Disarankan untuk Presentasi)

```bash
# Nyalakan minimal satu pasangan container
mise run lab:up

# Demo penuh (default pasangan PHP 8.4: unfixed 8084 vs fixed 8184)
mise run lab:demo

# Demo dengan pasangan versi lain, mis. PHP 8.5
mise exec -- uv run python scripts/demo_cves.py --unfixed-port 8085 --fixed-port 8185
```

Script akan menjalankan dan membandingkan secara berurutan:

1. **Langkah 0 — Determinisme worker:** 3 request berturut-turut; `worker_pid` selalu sama (bukti `MaxRequestWorkers 1`), prerequisite CVE-2026-109619.
2. **CVE-2026-109618:** picu OOM di kedua container → keduanya mengalami fatal error `Allowed memory size ... exhausted` (zend_bailout), TAPI hanya versi unfixed yang menyisakan state `in_flight` (terinisialisasi sebagian, tanpa rollback); versi fixed menunjukkan `SAFE (rolled back)`.
3. **CVE-2026-109619:** stage & free record (request A), lalu baca ulang (request B) di PID worker yang sama → versi unfixed menunjukkan `USE AFTER FREE`; versi fixed menunjukkan `SAFE`.

### 5.2 Demo Manual via Browser (Interaktif)

Buka berdampingan, lalu klik tombol di halaman sesuai urutan narasi:

| Langkah | Unfixed (`8084`) | Fixed (`8184`) |
| :--- | :--- | :--- |
| Picu OOM → cek state | `http://localhost:8084/cve109618_oom.php?action=oom` lalu `?action=status` | `http://localhost:8184/cve109618_oom.php?action=oom` lalu `?action=status` |
| Stage & free → baca ulang | `http://localhost:8084/cve109619_uaf.php?action=stage` lalu `?action=read` | `http://localhost:8184/cve109619_uaf.php?action=stage` lalu `?action=read` |

Setiap halaman menampilkan badge **UNFIXED/FIXED**, worker PID, verdict, dan isi state/registry lengkap — cocok untuk ditampilkan ke proyektor. Tombol **Reset** membersihkan state agar demo bisa diulang.

> **Catatan jujur untuk kelas:** kedua endpoint adalah **simulasi edukatif**. Perilaku unfixed vs fixed diemulasi dari env `LAB_PHP_STATUS` (diset saat build Docker) karena image resmi `php:*-apache` tidak memuat perbedaan patch internal Zend. Simulasi ini memodelkan pola kedua CVE secara deterministik dan aman didemokan.

### 5.3 Reproduksi Minimal dengan `curl` (Tanpa Browser / Python)

Karena kedua endpoint murni HTTP, seluruh demo juga bisa direproduksi hanya dengan `curl` — berguna saat berdemo lewat terminal, atau untuk diketik langsung di kelas. Contoh untuk pasangan PHP 8.4 (ulangi dengan port `8184` untuk versi fixed):

```bash
# Langkah 0 — Determinisme worker: worker_pid harus sama di ketiga kali
for i in 1 2 3; do curl -s "http://localhost:8084/?format=json" | grep worker_pid; done

# CVE-2026-109618 — picu OOM, lalu lihat verdict state yang tertinggal
curl -s "http://localhost:8084/cve109618_oom.php?action=reset" > /dev/null
curl -s "http://localhost:8084/cve109618_oom.php?action=oom" \
    | grep -oE 'Allowed memory size[^<]*|VULNERABLE PATTERN|SAFE \(rolled back\)'
curl -s "http://localhost:8084/cve109618_oom.php?action=status" \
    | grep -oE 'VULNERABLE PATTERN|SAFE \(rolled back\)|CLEAN'

# CVE-2026-109619 — stage & free (request A), lalu baca ulang (request B)
curl -s "http://localhost:8084/cve109619_uaf.php?action=reset" > /dev/null
curl -s "http://localhost:8084/cve109619_uaf.php?action=stage" > /dev/null
curl -s "http://localhost:8084/cve109619_uaf.php?action=read" \
    | grep -oE 'USE AFTER FREE|SAFE|CLEAN'
```

Hasil yang diharapkan:

| Perintah | Unfixed (`8084`) | Fixed (`8184`) |
| :--- | :--- | :--- |
| `cve109618_oom.php?action=oom` | `Allowed memory size ...` + `VULNERABLE PATTERN` | `Allowed memory size ...` + `SAFE (rolled back)` |
| `cve109619_uaf.php?action=read` | `USE AFTER FREE` | `SAFE` |

Perbandingan berdampingan sekali jalan:

```bash
for port in 8084 8184; do
    echo "=== port $port ==="
    curl -s "http://localhost:$port/cve109618_oom.php?action=oom" | grep -oE 'VULNERABLE PATTERN|SAFE \(rolled back\)'
    curl -s "http://localhost:$port/cve109619_uaf.php?action=stage" > /dev/null
    curl -s "http://localhost:$port/cve109619_uaf.php?action=read" | grep -oE 'USE AFTER FREE|SAFE|CLEAN'
done
```

> Tip demo: kesalahan fatal OOM sengaja disembunyikan dari browser (`display_errors` off saat request OOM) tetapi pesannya tetap tertanam di halaman hasil — sehingga grep `Allowed memory size` di atas tetap menemukannya. Untuk melihat fatal error mentah, gunakan `docker compose -f lab/docker-compose.yml logs php84-unfixed`.

## 6. Mematikan Container Lab

```bash
mise run lab:down
```
