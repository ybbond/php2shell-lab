# AGENTS.md — Konteks & Panduan untuk AI Agent

Dokumen ini menyediakan konteks arsitektur, batasan konfigurasi, dan aturan kerja bagi AI Agent yang beroperasi di dalam direktori `video/php2shell-lab/`.

---

## 1. Tujuan Direktori Ini

Direktori ini adalah laboratorium demonstrasi untuk materi kuliah dan pembuatan video Keamanan Komputer yang membahas **CVE-2026-109618** (Out-of-Memory improper state handling) dan **CVE-2026-109619** (Use-After-Free pada worker state), dikenal sebagai **PHP2Shell**.

Pengguna menulis kode reproduksi/pengujian keamanannya sendiri. Peran agen di direktori ini adalah membantu pengelolaan infrastruktur container, konfigurasi PHP/Apache, otomasi pengujian/observabilitas, serta dokumentasi.

---

## 2. Aturan Wajib Isolasi Toolchain (`mise` & `uv`)

1. **Jangan gunakan Python atau `pip` global.**
   Seluruh eksekusi tool (`python`, `uv`, dll.) di direktori ini **wajib** dijalankan melalui `mise` dan `uv` yang sudah dikonfigurasi pada [`mise.toml`](./mise.toml).
2. **Cara menjalankan perintah Python:**
   - Gunakan `mise exec -- uv run python <script.py>` atau tambahkan task pada `mise.toml` lalu panggil `mise run <nama_task>`.
3. **Cara menambah dependensi Python:**
   - Gunakan `mise exec -- uv add <nama_paket>` agar tercatat rapi di [`pyproject.toml`](./pyproject.toml) dan `uv.lock` serta terinstal di `.venv/` lokal direktori ini.
4. **Jika perlu memanggil binary `php` di sisi host (di luar Docker):**
   - Tambahkan `php = "<versi>"` ke dalam `[tools]` di `mise.toml` alih-alih mengandalkan instalasi PHP global di macOS.

---

## 3. Invarian Arsitektur Container & Memori (Jangan Diubah Tanpa Instruksi)

Pengujian CVE-2026-109618 dan CVE-2026-109619 sangat bergantung pada perilaku memori dan *worker process reuse*. Pastikan invarian berikut tetap terjaga kecuali diminta secara eksplisit oleh pengguna:

1. **Dua Lapisan Batas Memori:**
   - **PHP `memory_limit = 2M`** di [`config/php.ini`](./config/php.ini): Sengaja dibuat sangat kecil agar *Zend Memory Manager* (ZMM) memicu kondisi Out-of-Memory internal (`zend_bailout` / `longjmp`) dengan alokasi minimal.
   - **Docker `mem_limit: 48m` & `memswap_limit: 48m`** di [`docker-compose.yml`](./docker-compose.yml): Sengaja dibuat hemat RAM namun tetap memiliki ruang cukup di atas `2M` agar proses Apache tidak dibunuh oleh *Linux Kernel OOM Killer* (`SIGKILL`) ketika PHP mengalami OOM internal.
2. **Worker Tunggal (`MaxRequestWorkers 1`):**
   - Diatur pada [`config/mpm_prefork.conf`](./config/mpm_prefork.conf) (`StartServers 1`, `MinSpareServers 1`, `MaxSpareServers 1`, `MaxRequestWorkers 1`, `MaxConnectionsPerChild 0`).
   - Konfigurasi ini memastikan setiap HTTP request berurutan masuk ke `worker_pid` yang sama, sehingga state antar-request pada satu worker dapat diamati secara deterministik.
3. **Pemetaan Port & Versi:**
   - **Unfixed (Port `808x`):**
     - `php85-unfixed` (`8.5.11-apache`) -> `8085`
     - `php84-unfixed` (`8.4.26-apache`) -> `8084`
     - `php83-unfixed` (`8.3.35-apache`) -> `8083`
     - `php82-unfixed` (`8.2.34-apache`) -> `8082`
   - **Fixed (Port `818x`):**
     - `php85-fixed` (`8.5.12-apache`) -> `8185`
     - `php84-fixed` (`8.4.27-apache`) -> `8184`
     - `php83-fixed` (`8.3.36-apache`) -> `8183`
     - `php82-fixed` (`8.2.35-apache`) -> `8182`

---

## 4. Konvensi Penempatan File

- **`src/`** — Di-mount ke `/var/www/html` pada ke-8 container. Semua endpoint/target PHP disimpan di sini. Jangan menghapus atau menimpa file pengujian yang dibuat pengguna di dalam `src/` tanpa izin.
- **`scripts/`** — Tempat menyimpan script pengujian, *benchmarking*, atau *health-check* berbasis Python.
- **`config/`** — Tempat menyimpan konfigurasi `php.ini` dan `mpm_prefork.conf` yang di-mount secara *read-only* ke seluruh container.
