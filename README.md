# PHP2Shell Lab & Video Presentation (CVE-2026-109618 & CVE-2026-109619)

Repository ini (terpasang sebagai Git Submodule di root `AKK-1951`) memuat **dua bagian utama yang dipisahkan secara rapi** untuk kebutuhan materi kuliah dan pembuatan video Keamanan Komputer:

1. **[`lab/`](./lab)** — Lingkungan Docker Compose multi-versi PHP (*Unfixed* vs *Fixed*) dengan batas memori minimal (`memory_limit = 2M`, container `48m`, 1 worker Apache) beserta skrip pengujian Python terisolasi (`uv`).
2. **[`video/`](./video)** — Proyek video presentasi berbasis **Remotion + React 19 + Bun** (mengikuti arsitektur `KKA-1429-video-project`).

---

## 1. Konteks Teknis: CVE-2026-109618 & CVE-2026-109619 (PHP2Shell)

Kedua celah keamanan ini berkaitan dengan manajemen memori internal pada runtime PHP (versi 5.4 hingga 8.5) yang diperbaiki pada rilis patch Oktober 2026:

1. **CVE-2026-109618 (Out-of-Memory / Improper State Handling)**
   - Terkait penanganan kondisi *Out-of-Memory* (OOM) di *Zend Memory Manager* (ZMM) melalui `zend_bailout()` (`longjmp`).
   - Pada versi *unfixed*, interupsi OOM pada titik tertentu dapat meninggalkan struktur data internal request dalam kondisi terinisialisasi sebagian tanpa *rollback* utuh saat prosedur *cleanup*.
2. **CVE-2026-109619 (Use-After-Free pada State Worker / Global)**
   - Terkait state di tingkat proses worker yang masih menyimpan referensi ke memori milik request sebelumnya yang sudah dibebaskan (*freed*).
   - Request berikutnya yang dilayani oleh **proses worker yang sama** dapat membaca atau mengeksekusi referensi usang tersebut.

---

## 2. Struktur Direktori

```text
php2shell-lab/                  # Root Git Submodule
├── AGENTS.md                   # Konteks & aturan kerja lengkap untuk AI Agent (Lab + Video)
├── README.md                   # Dokumentasi utama proyek
├── REPRODUCE.md                # Panduan langkah demi langkah menjalankan lab Docker & reproduksi
├── mise.toml                   # Konfigurasi terpadu (python, uv, bun, node) & task runner
├── lab/                        # [Bagian 1] Lingkungan Docker PHP & Skrip Pengujian Python
│   ├── Dockerfile              # Image builder berbasis php:<tag>-apache
│   ├── docker-compose.yml      # Matriks 8 container PHP (4 unfixed: 808x, 4 fixed: 818x)
│   ├── mise.toml               # Konfigurasi lokal lab
│   ├── pyproject.toml          # Dependensi Python (httpx, rich) dikelola oleh uv
│   ├── config/
│   │   ├── php.ini             # memory_limit = 2M & session upload progress
│   │   └── mpm_prefork.conf    # MaxRequestWorkers = 1 (determinisme PID worker & hemat RAM)
│   ├── src/                    # Di-mount ke /var/www/html di semua container
│   │   └── index.php           # Endpoint diagnostik (HTML & ?format=json)
│   └── scripts/
│       └── check_lab.py        # Pemeriksa status ke-8 container secara serentak
└── video/                      # [Bagian 2] Proyek Video Presentasi Remotion
    ├── mise.toml               # Isolasi bun = "1.4.4" & node = "22"
    ├── package.json            # Skrip dev, typecheck, render (Remotion 4 + React 19)
    ├── remotion.config.ts      # Konfigurasi output frame Remotion
    ├── tsconfig.json           # Konfigurasi TypeScript ketat
    ├── script.md               # Outline naskah video presentasi
    └── src/
        ├── index.ts            # Entrypoint Remotion
        ├── Root.tsx            # Registrasi Composition 1920x1080 @ 30fps
        ├── Main.tsx            # Urutan scene dengan transisi Fade & Captions
        ├── theme.ts            # Palet warna (COLORS) & tipografi (FONT)
        ├── timeline.ts         # Daftar scene, teks caption, durasi, & validasi kecepatan baca
        ├── components/         # Komponen kanvas SVG reusable (Stage, Pop, Tag, Bubble, dll.)
        └── scenes/             # Visual per segmen (Intro, MemoryModel, PatchMatrix)
```

---

## 3. Matriks Container Lab (`./lab`)

| Cabang PHP | Container (Belum Fixed) | Image Tag | Port Host | Container (Sudah Fixed) | Image Tag | Port Host |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **PHP 8.5** | `php85-unfixed` | `8.5.11-apache` | `http://localhost:8085` | `php85-fixed` | `8.5.12-apache` | `http://localhost:8185` |
| **PHP 8.4** | `php84-unfixed` | `8.4.26-apache` | `http://localhost:8084` | `php84-fixed` | `8.4.27-apache` | `http://localhost:8184` |
| **PHP 8.3** | `php83-unfixed` | `8.3.35-apache` | `http://localhost:8083` | `php83-fixed` | `8.3.36-apache` | `http://localhost:8183` |
| **PHP 8.2** | `php82-unfixed` | `8.2.34-apache` | `http://localhost:8082` | `php82-fixed` | `8.2.35-apache` | `http://localhost:8182` |

---

## 4. Daftar Perintah (`mise`)

Dari root direktori `/Users/ybbond/puph/AKK-1951/php2shell-lab`:

### Perintah Lab Keamanan (`./lab`)
| Perintah | Fungsi |
| :--- | :--- |
| `mise run lab:setup` | Sinkronisasi `.venv` Python di `lab/.venv` dengan `uv` |
| `mise run lab:up` | Menjalankan seluruh 8 container PHP (*unfixed* & *fixed*) |
| `mise run lab:up-unfixed` | Menjalankan hanya 4 container *unfixed* (`8082`–`8085`) |
| `mise run lab:up-fixed` | Menjalankan hanya 4 container *fixed* (`8182`–`8185`) |
| `mise run lab:check` | Mengecek status HTTP, versi PHP, status patch, dan `worker_pid` |
| `mise run lab:down` | Mematikan dan menghapus seluruh container lab |

### Perintah Video Presentasi Remotion (`./video`)
| Perintah | Fungsi |
| :--- | :--- |
| `mise run video:install` | Menginstal paket npm/bun untuk Remotion di `video/` |
| `mise run video:dev` | Membuka Remotion Studio preview (`http://localhost:3000`) |
| `mise run video:typecheck` | Menjalankan `tsc --noEmit` pada kode video |
| `mise run video:render` | Merender video presentasi ke `video/out/main.mp4` |

Untuk detail langkah demi langkah pengujian container, baca **[`REPRODUCE.md`](./REPRODUCE.md)**.
