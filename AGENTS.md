# AGENTS.md — Konteks & Panduan untuk AI Agent

Repository `php2shell-lab` ini terbagi secara rapi menjadi **dua sub-proyek terisolasi** agar tidak saling mengotori:
1. **`lab/`** — Lingkungan Docker Compose multi-versi PHP (*Unfixed* vs *Fixed*) dan skrip pengujian Python (`uv`).
2. **`video/`** — Proyek video presentasi Remotion (mengikuti arsitektur SOLID & KISS dari `KKA-1429-video-project`).

Ikuti prinsip **SOLID** dan **KISS**. Utamakan perubahan kecil dan terfokus sesuai struktur di bawah ini.

---

## Bagian 1: Lingkungan Lab Keamanan (`./lab`)

### 1.1 Isolasi Toolchain (`mise` & `uv`)
- **Jangan gunakan Python atau `pip` global.** Seluruh pemanggilan `python` dan `uv` wajib melalui `mise`.
- Dari root repo:
  - `mise run lab:setup` — sinkronisasi `.venv` di `lab/.venv`
  - `mise run lab:up` / `mise run lab:up-unfixed` / `mise run lab:up-fixed` — menyalakan container
  - `mise run lab:check` — memeriksa status ke-8 container
  - `mise run lab:down` — mematikan container
- Menambah paket Python di `lab/`: `cd lab && mise exec -- uv add <paket>`.

### 1.2 Invarian Memori & Worker (Jangan Diubah Tanpa Instruksi)
Pengujian **CVE-2026-109618** (OOM bailout) dan **CVE-2026-109619** (worker state UAF) bergantung pada:
1. **PHP `memory_limit = 2M`** di `lab/config/php.ini` — memicu *Zend Memory Manager* OOM internal (`zend_bailout`) dengan alokasi minimal.
2. **Docker `mem_limit: 48m` & `memswap_limit: 48m`** di `lab/docker-compose.yml` — hemat RAM host namun di atas `2M` agar proses Apache tidak di-*kill* oleh Linux Kernel OOM Killer.
3. **Worker Tunggal (`MaxRequestWorkers 1`)** di `lab/config/mpm_prefork.conf` — memastikan setiap request berurutan masuk ke `worker_pid` yang sama.
4. **Jangan menimpa/menghapus** kode pengujian buatan pemilik di `lab/src/` atau `lab/scripts/` tanpa izin.

---

## Bagian 2: Proyek Video Presentasi Remotion (`./video`)

### 2.1 Perintah Utama
Dari root repo (atau jalankan `bun run ...` langsung di dalam folder `video/`):

```sh
mise run video:dev        # Studio preview (default localhost:3000)
mise run video:typecheck  # tsc --noEmit
mise run video:render     # merender video/out/main.mp4
```

Tool berasal dari `mise.toml` (`bun = "1.4.4"`, serta `node = "22"` karena CLI Remotion berjalan di atas Node). `video/out/` dan `video/node_modules/` di-ignore oleh git.

### 2.2 Struktur `video/`

| Path | Tanggung Jawab |
|---|---|
| `video/src/timeline.ts` | Urutan scene serta seluruh teks dan durasi caption. Memvalidasi total durasi dan kecepatan baca. |
| `video/src/Main.tsx` | Memutar scene secara berurutan dengan transisi fade dan overlay caption. |
| `video/src/Root.tsx` | Mendaftarkan komposisi `Main` (1920x1080, 30 fps) dengan durasi dari `timeline.ts`. |
| `video/src/scenes/*.tsx` | Satu file per scene, hanya berisi visual. |
| `video/src/components/Stage.tsx` | Backdrop dan kanvas SVG 1920x1080. Mengekspor `GROUND_Y` dan `Ground`. |
| `video/src/components/motion.tsx` | `useSeconds`, `ramp`, `Pop`. |
| `video/src/components/characters.tsx` | `Person`, `Robot`. |
| `video/src/components/labels.tsx` | `Tag`, `Bubble`, `Headline`. |
| `video/src/components/props.tsx` | `Desk`, `Bulb`. |
| `video/src/components/Captions.tsx` | Overlay teks caption di bagian bawah layar. |
| `video/src/theme.ts` | `COLORS`, `FONT`. |

### 2.3 Aturan Mengubah Video Remotion
- **Menambah scene:** buat `video/src/scenes/X.tsx` dan tambahkan satu entri pada `TIMELINE` di `video/src/timeline.ts`.
- **Total durasi:** `timeline.ts` akan melempar error kecuali jumlah durasi caption sama persis dengan `TARGET_SECONDS`. Jika menambah waktu, sesuaikan `TARGET_SECONDS` atau kurangi dari scene lain.
- **Kecepatan caption:** `timeline.ts` melempar error jika melebihi `18` karakter per detik (`MAX_CHARS_PER_SECOND`). `seconds` harus menghasilkan jumlah frame bulat pada 30 fps, dan teks caption di dalam satu scene harus unik karena menjadi React `key`.
- **Kanvas & Layout:** scene digambar menggunakan SVG pada kanvas `1920x1080`. Tempatkan karakter di `GROUND_Y` (titik origin berada di kaki). Caption menutupi area `y > 900`, jadi jaga elemen visual penting tetap di atas `y = 900`.
- **Animasi deterministik:** turunkan seluruh animasi dari frame saat ini melalui `useSeconds`, `ramp`, dan `Pop`. Gunakan `random(seed)` dari Remotion (bukan `Math.random`), dan hindari `setTimeout` atau animasi CSS.
- **Warna:** gunakan `COLORS` dari `video/src/theme.ts`.

### 2.4 Verifikasi Visual Setelah Perubahan Video
Lulus `typecheck` saja tidak cukup karena bug tata letak hanya terlihat pada frame hasil render. Render lalu periksa sampel frame:

```sh
mise run video:render
ffmpeg -y -v error -i video/out/main.mp4 -vf "fps=1/4,scale=640:-1,tile=4x4" -frames:v 1 <scratch-dir>/sheet.png
```

### 2.5 Studio Pitfalls
- **Pemilik mungkin sedang menjalankan Remotion Studio di port 3000.** Jangan melakukan `pkill` sembarangan pada proses Studio pemilik. Jika perlu menjalankan instance Studio sendiri untuk pengujian, gunakan `--port=<port-lain> --no-open` dan hentikan berdasarkan PID spesifik dari `lsof -nP -tiTCP:<port> -sTCP:LISTEN`.
- **Jangan membuat editan sementara/eksperimen di `video/src/`** jika Studio pemilik sedang terbuka karena akan langsung *hot-reload*.

---

## Bagian 3: Git
- Lakukan `git commit` hanya ketika diminta oleh pemilik.
