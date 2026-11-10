# VAPT Labs 2026 — workspace pembangunan target

Repository publik ini adalah **kontrak pembangunan**, bukan paket mahasiswa. Final project SIB memiliki **3 kelompok VAPT**, masing-masing mendapat aplikasi berbeda: KampusMart, PerpusDigital, dan EventPass. Penilaian mengikuti **PTES**. Mahasiswa harus memperoleh tanda tangan pada Rules of Engagement (ROE) sebelum pengujian aktif. Kalender: 20 November–23 Desember 2026. Dokumen program lengkap ada di [repo perencanaan](https://github.com/orion2182/sib-2026-cybersecurity-final-project).

**Status per 10 November 2026:** 3 target VAPT dan harness PHP/PowerShell telah dibangun, diuji pada build rentan dan patched, dikemas, serta menjalani dry pilot berbantuan agen. Pilot buta manusia dan pemeriksaan rilis masih diperlukan. Lihat `docs/BUILD-STATUS.md` dan `../README-TRANSFER.md` pada salinan lengkap.

## Tiga target yang tersedia

| Target | Alur normal | Fokus pengujian yang dirancang | Kriteria retest |
| --- | --- | --- | --- |
| KampusMart | Katalog, pesanan, invoice, kupon untuk akun sintetis | Kondisi komponen Apache yang dibatasi, otorisasi invoice, penggunaan kupon berulang | Bukti minimum ditolak pada build patched; belanja normal tetap berhasil |
| PerpusDigital | Cari buku, pinjam, kembalikan, kelola denda | Query pencarian, fungsi metadata petugas, transisi denda | Tiga kondisi ditolak pada build patched; alur perpustakaan tetap berhasil |
| EventPass | Lihat acara, pesan tiket, tulis ulasan, check-in | Tampilan ulasan, properti tiket, check-in berulang | Tiga kondisi ditolak pada build patched; alur acara tetap berhasil |

Target harus berbeda dalam domain, data, route, dan temuan. Satu kelompok menerima satu target saja. Setiap target mempunyai build rentan yang dibatasi dan build patched setara, data sintetis, start/stop/reset, health check, log bukti, dan instruksi peserta. Deskripsi detail ada di spesifikasi; `docs/reference/Arsitektur-Lab-VAPT-KampusMart-Lokal.md` menjelaskan keputusan arsitektur dan anggaran resource.

## Peta file untuk OpenCode

| File/direktori | Fungsi |
| --- | --- |
| `AGENTS.md` | Instruksi proyek yang dibaca OpenCode |
| `.specify/memory/constitution.md` | Aturan bersama dan gerbang rilis |
| `specs/001-shared-lab-harness/spec.md` | Distribusi per kelompok, ROE, reset, logging, patched retest |
| `specs/002-kampusmart-target/spec.md` | Kontrak KampusMart |
| `specs/003-perpusdigital-target/spec.md` | Kontrak PerpusDigital |
| `specs/004-eventpass-target/spec.md` | Kontrak EventPass |
| `docs/reference/` | Snapshot riset, draft pertanyaan, kartu target, PDF awal; **materi pembangun** |
| `apps/` | Implementasi tiga aplikasi |
| `.opencode/commands/` | Perintah Spec Kit untuk OpenCode |
| `scripts/select-feature.ps1` | Pemilih fitur di Windows tanpa Python; `scripts/select_feature.py` opsional |

## Menjalankan dan mengembangkan di Windows

Salinan lengkap dari flashdisk memiliki `README-TRANSFER.md` di direktori induk. Jalankan lab dari disk lokal Windows dengan PowerShell 5.1+ dan PHP 8.1+; **Python dan Docker tidak diperlukan** untuk implementasi sekarang. Instruksi menjalankan, menguji, dan mengemas kasus ada di `docs/WINDOWS-SETUP.md` dan `docs/BUILD-STATUS.md`.

Untuk perubahan spesifikasi di OpenCode, jalankan dari root repo ini:

```powershell
.\scripts\select-feature.ps1 --list
.\scripts\select-feature.ps1 002-kampusmart-target
opencode
```

Perintah `/speckit.plan`, `/speckit.checklist`, `/speckit.tasks`, `/speckit.analyze`, `/speckit.implement`, dan `/speckit.converge` dipakai **di chat OpenCode** ketika mengubah fitur, bukan untuk menjalankan lab. `scripts/select_feature.py` tersedia hanya sebagai alternatif jika Python memang terpasang. Paket peserta harus dibuat melalui perintah packaging dan diperiksa sebelum dibagikan; direktori builder ini memuat bahan instruktur.

## Gerbang sebelum paket mahasiswa dirilis

1. **Fungsi normal:** setiap peran dan alur bisnis utama berfungsi pada build rentan maupun patched.
2. **Bukti terukur:** hanya kondisi yang sengaja dirancang dapat dibuktikan dengan akun dan data sintetis; tidak perlu eksekusi host, eksfiltrasi, atau akses antarkelompok.
3. **Reset dan retest:** seed/reset deterministik, build patched menolak tiga bukti, fungsi normal tetap lulus.
4. **Akses dan ROE:** paket dibuka hanya setelah ROE ditandatangani; port lokal bind ke localhost, instans jarak jauh dipisahkan per kelompok.
5. **Resource:** target awal satu stack di laptop 8 GB dengan puncak container sekitar 1 GB dan paket di bawah 5 GB; angka ini wajib diuji pada pilot, bukan dijanjikan sebelum pengukuran.
6. **Pembagian:** paket tiap kelompok memuat satu image/fixture, kartu target, workbook PTES, dan ROE yang disetujui. Kunci instruktur dan sumber repository ini tidak termasuk paket.

Repository publik ini mengungkap sebagian rancangan temuan. Jangan commit marker rahasia, kunci jawaban final, akun nyata, atau data pribadi. Simpan materi penilaian yang belum boleh dilihat peserta di luar GitHub publik.
