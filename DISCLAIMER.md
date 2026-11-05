# DISCLAIMER — Tujuan Pendidikan

> File ini mengikuti konvensi `DISCLAIMER.md` yang umum digunakan proyek open source.
> Berlaku untuk seluruh isi repository `php2shell-lab` (direktori `lab/` dan `video/`).

## 1. Tujuan: hanya untuk pendidikan

Repository ini dibuat sebagai **materi pembelajaran tugas kuliah** mata pelajaran
Keamanan Komputer dan Jaringan. Tujuannya menjelaskan pola dua kelas kerentanan
(OOM bailout tanpa rollback dan use-after-free lintas-request) melalui simulasi
yang aman, deterministik, dan mudah didemokan di kelas.

## 2. Bukan alat serangan

- Kode di `lab/src/` **tidak memuat eksploit nyata**. Kedua endpoint demo adalah
  simulasi: perilaku *unfixed* vs *fixed* diemulasi dari environment variable
  `LAB_PHP_STATUS`, dan "state internal" dimodelkan sebagai file JSON per worker PID
  (rincian teknis di [`PENJELASAN.md`](./PENJELASAN.md) §6).
- Script di `lab/scripts/` hanyalah klien HTTP untuk menjalankan demo terhadap
  container milik sendiri.
- Repository ini **tidak boleh digunakan** untuk menyerang, mengganggu, atau menguji
  sistem milik pihak lain tanpa izin tertulis pemilik sistem tersebut.

## 3. Jangan dijalankan di luar lab

Kode di `lab/src/` **hanya ditujukan untuk container Docker lab ini secara lokal**:

- Endpoint tidak memiliki autentikasi atau pembatasan akses.
- Konfigurasi sengaja memperlihatkan detail error (`display_errors = On`) dan
  membatasi resource (`memory_limit = 2M`, container 48 MB) untuk keperluan demo.

Menyalin file-file ini ke server, hosting, atau container lain yang dapat diakses
publik **melanggar asumsi keamanan lab ini** dan tidak didukung.

## 4. Akurasi materi

- Detail kerentanan pada `README.md`, `REPRODUCE.md`, dan `PENJELASAN.md` disederhanakan
  untuk tujuan pembelajaran. Perilaku *unfixed* vs *fixed* pada demo **diemulasi**, bukan
  hasil patch engine PHP sungguhan.
- Gunakan repository ini sebagai **model konsep** untuk memahami pola kerentanan, bukan
  sebagai rujukan analisis CVE primer. Untuk rujukan resmi, selalu periksa basis data
  CVE/NVD dan rilis keamanan resmi vendor terkait.

## 5. Tanpa jaminan

Seluruh kode dan materi disediakan "sebagaimana adanya", tanpa jaminan dalam bentuk
apa pun, sesuai ketentuan lisensi pada [`LICENSE`](./LICENSE) (MIT). Penulis tidak
bertanggung jawab atas penyalahgunaan materi di luar tujuan pendidikan di atas.
