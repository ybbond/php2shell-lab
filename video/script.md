# Outline Video Presentasi — PHP2Shell (CVE-2026-109618 & CVE-2026-109619)

Mata Kuliah: Keamanan Komputer (AKK-1951)

## Struktur Segmen Awal (Dapat Dikembangkan)

1. **Intro (`Intro.tsx`)**
   - Pengenalan topik: Kerentanan manajemen memori pada runtime PHP (CVE-2026-109618 & CVE-2026-109619 / PHP2Shell).
2. **Model Memori & Worker (`MemoryModel.tsx`)**
   - Visualisasi siklus hidup memori per-request (*Zend Memory Manager* / `zend_bailout`) dan state proses worker yang melayani banyak request berurutan.
3. **Perbandingan Patch & Mitigasi (`PatchMatrix.tsx`)**
   - Matriks perbandingan perilaku versi *Unfixed* (`8.2.34`, `8.3.35`, `8.4.26`, `8.5.11`) vs *Fixed* (`8.2.35`, `8.3.36`, `8.4.27`, `8.5.12`) di dalam lab container.
