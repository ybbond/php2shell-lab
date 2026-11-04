# Lab Container Multi-Versi PHP — Materi Kuliah & Video Keamanan Komputer

Lingkungan laboratorium berbasis **Docker Compose** serta isolasi toolchain **`mise` + `uv`** untuk materi kuliah dan pembuatan video pembelajaran Keamanan Komputer mengenai **CVE-2026-109618** dan **CVE-2026-109619** (*PHP2Shell*).

---

## 1. Konteks Materi & Latar Belakang Teknis

Lab ini dirancang untuk mendemonstrasikan perbandingan perilaku runtime PHP pada cabang **PHP 8.2, 8.3, 8.4, dan 8.5** antara versi **sebelum patch (unfixed)** dan **sesudah patch (fixed)** yang dirilis pada Oktober 2026:

1. **CVE-2026-109618 (Out-of-Memory / Improper State Handling)**
   - Berkaitan dengan penanganan kondisi *Out-of-Memory* (OOM) di dalam *Zend Memory Manager* (ZMM) yang menginterupsi eksekusi melalui mekanisme `zend_bailout()` (`longjmp`).
   - Pada versi *unfixed*, interupsi OOM pada titik tertentu dapat menyisakan struktur data internal request yang baru terinisialisasi sebagian dan tidak di-*rollback* secara utuh saat prosedur *cleanup*.
2. **CVE-2026-109619 (Use-After-Free pada State Worker / Global)**
   - Berkaitan dengan state di tingkat proses worker yang masih memegang referensi ke memori milik request sebelumnya yang telah dibebaskan (*freed*).
   - Ketika proses worker yang sama menangani request berikutnya, state usang (*stale reference*) tersebut dapat diakses kembali sebelum diinisialisasi ulang.

---

## 2. Desain Arsitektur Lab & Optimasi Memori

Karena skenario pengujian berkaitan erat dengan manajemen memori dan penggunaan ulang (*reuse*) proses worker, lab ini dikonfigurasi secara khusus pada dua lapisan:

### A. Batas Memori Internal PHP (`memory_limit = 2M`)
Diatur pada [`config/php.ini`](./config/php.ini):
- `memory_limit = 2M` (batas terendah yang diizinkan oleh Zend Memory Manager).
- Tujuan: Memungkinkan pengujian kondisi OOM **internal PHP** (`zend_bailout`) dengan ukuran data sekecil mungkin tanpa menghabiskan RAM sistem operasi.

### B. Batas Memori Container (`48m`) & Worker Tunggal (`MaxRequestWorkers 1`)
Diatur pada [`docker-compose.yml`](./docker-compose.yml) dan [`config/mpm_prefork.conf`](./config/mpm_prefork.conf):
- **Single Worker (`MaxRequestWorkers 1`):** Memastikan hanya ada **1 proses worker Apache** yang melayani HTTP request di dalam tiap container. Dengan demikian, setiap request berurutan dijamin ditangani oleh `worker_pid` yang sama (deterministik saat rekaman video/demo).
- **Container Cgroup Limit (`mem_limit: 48m`, `memswap_limit: 48m`, `pids_limit: 16`):** Menjaga konsumsi RAM host tetap sangat kecil meskipun ke-8 container dijalankan bersamaan, namun tetap berada di atas `memory_limit = 2M` milik PHP agar *Linux Kernel OOM Killer* tidak mematikan proses Apache (`SIGKILL`).

---

## 3. Struktur Direktori

```text
video/php2shell-lab/
├── AGENTS.md               # Konteks & panduan aturan kerja untuk sesi AI Agent berikutnya
├── README.md               # Dokumentasi utama proyek & konteks arsitektur lab
├── REPRODUCE.md            # Panduan langkah demi langkah alur pengujian & perekaman demo
├── Dockerfile              # Image builder berbasis php:<tag>-apache
├── docker-compose.yml      # Matriks 8 container PHP (4 unfixed di port 808x, 4 fixed di port 818x)
├── mise.toml               # Isolasi versi Python (3.13) & uv beserta daftar task (`mise run ...`)
├── pyproject.toml          # Definisi dependensi Python lokal (.venv dikelola oleh uv)
├── config/
│   ├── php.ini             # Override konfigurasi PHP (memory_limit=2M, session upload progress)
│   └── mpm_prefork.conf    # Konfigurasi 1 worker Apache Prefork untuk determinisme PID
├── src/
│   └── index.php           # Endpoint diagnostik (HTML & JSON) + tempat menaruh kode PHP uji
└── scripts/
    └── check_lab.py        # Script pemeriksa status ke-8 container secara serentak
```

---

## 4. Matriks Container & Pemetaan Port

| Cabang PHP | Container (Belum Fixed) | Image Tag | Port Host | Container (Sudah Fixed) | Image Tag | Port Host |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **PHP 8.5** | `php85-unfixed` | `8.5.11-apache` | `http://localhost:8085` | `php85-fixed` | `8.5.12-apache` | `http://localhost:8185` |
| **PHP 8.4** | `php84-unfixed` | `8.4.26-apache` | `http://localhost:8084` | `php84-fixed` | `8.4.27-apache` | `http://localhost:8184` |
| **PHP 8.3** | `php83-unfixed` | `8.3.35-apache` | `http://localhost:8083` | `php83-fixed` | `8.3.36-apache` | `http://localhost:8183` |
| **PHP 8.2** | `php82-unfixed` | `8.2.34-apache` | `http://localhost:8082` | `php82-fixed` | `8.2.35-apache` | `http://localhost:8182` |

---

## 5. Daftar Perintah Cepat (`mise` & `uv`)

Semua pemanggilan tool di direktori ini diisolasi menggunakan `mise` dan `uv`:

| Perintah | Fungsi |
| :--- | :--- |
| `mise install` | Memastikan versi `python` dan `uv` lokal terpasang sesuai `mise.toml` |
| `mise run setup` | Membuat `.venv` lokal dan menyinkronkan paket Python via `uv sync` |
| `mise run up` | Menjalankan seluruh 8 container PHP (*unfixed* & *fixed*) |
| `mise run up-unfixed` | Menjalankan hanya 4 container versi *unfixed* (port `8082`–`8085`) |
| `mise run up-fixed` | Menjalankan hanya 4 container versi *fixed* (port `8182`–`8185`) |
| `mise run check` | Menampilkan tabel status HTTP, versi PHP, status patch, dan `worker_pid` |
| `mise run down` | Mematikan dan menghapus seluruh container lab |

Untuk panduan langkah demi langkah saat demonstrasi kuliah atau rekaman video, lihat **[`REPRODUCE.md`](./REPRODUCE.md)**.
