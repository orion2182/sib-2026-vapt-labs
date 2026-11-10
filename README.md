# VAPT Labs 2026 — workspace pembangunan target

Repository publik ini adalah **kontrak pembangunan**, bukan paket mahasiswa. Final project SIB memiliki **3 kelompok VAPT**, masing-masing mendapat aplikasi berbeda: KampusMart, PerpusDigital, dan EventPass. Penilaian mengikuti **PTES**. Mahasiswa harus memperoleh tanda tangan pada Rules of Engagement (ROE) sebelum pengujian aktif. Kalender: 20 November–23 Desember 2026. Dokumen program lengkap ada di [repo perencanaan](https://github.com/orion2182/sib-2026-cybersecurity-final-project).

**Status per 10 November 2026:** Spec Kit 1.2.3, integrasi OpenCode/Codex, konstitusi, empat spesifikasi, dan referensi sudah ada. **Aplikasi, image Docker, fixture, build patched, dan pilot belum dibuat.** Jangan menganggap file `apps/*/README.md` sebagai aplikasi yang berjalan.

## Tiga target yang harus dibangun

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
| `apps/` | Lokasi implementasi tiga aplikasi |
| `.opencode/commands/` | Perintah Spec Kit untuk OpenCode |
| `scripts/select_feature.py` | Memilih fitur aktif di laptop lokal |

## Mulai di laptop lain

Prasyarat awal: Git, Python 3.11+, dan OpenCode. Docker/Compose baru diperlukan saat implementasi yang dipilih di tahap rencana memerlukannya. Jalankan dari root repo ini:

```bash
git clone https://github.com/orion2182/sib-2026-vapt-labs.git
cd sib-2026-vapt-labs
python3 scripts/select_feature.py 001-shared-lab-harness
opencode
```

Jika memakai Windows, `py -3 scripts/select_feature.py 001-shared-lab-harness` dapat menggantikan `python3`. Perintah Spec Kit sudah tersimpan di `.opencode/commands/`; tidak perlu menjalankan `specify init` lagi. `AGENTS.md` dimuat oleh OpenCode. Di chat OpenCode, jalankan secara bertahap:

```text
/speckit.plan
/speckit.checklist
/speckit.tasks
/speckit.analyze
/speckit.implement
/speckit.converge
```

Perintah di atas adalah perintah **chat OpenCode**, bukan shell. Spesifikasi fitur sudah ada, jadi jangan menjalankan `/speckit.specify` kecuali mengubah ruang lingkup. Untuk berpindah target, jalankan `python3 scripts/select_feature.py --list`, pilih nama fitur, lalu mulai sesi baru agar konteks fitur tepat. Urutan pembangunan: harness bersama → KampusMart → PerpusDigital → EventPass → pilot buta ketiganya. [Referensi integrasi OpenCode Spec Kit](https://github.github.com/spec-kit/integrations/opencode.html).

## Gerbang sebelum paket mahasiswa dirilis

1. **Fungsi normal:** setiap peran dan alur bisnis utama berfungsi pada build rentan maupun patched.
2. **Bukti terukur:** hanya kondisi yang sengaja dirancang dapat dibuktikan dengan akun dan data sintetis; tidak perlu eksekusi host, eksfiltrasi, atau akses antarkelompok.
3. **Reset dan retest:** seed/reset deterministik, build patched menolak tiga bukti, fungsi normal tetap lulus.
4. **Akses dan ROE:** paket dibuka hanya setelah ROE ditandatangani; port lokal bind ke localhost, instans jarak jauh dipisahkan per kelompok.
5. **Resource:** target awal satu stack di laptop 8 GB dengan puncak container sekitar 1 GB dan paket di bawah 5 GB; angka ini wajib diuji pada pilot, bukan dijanjikan sebelum pengukuran.
6. **Pembagian:** paket tiap kelompok memuat satu image/fixture, kartu target, workbook PTES, dan ROE yang disetujui. Kunci instruktur dan sumber repository ini tidak termasuk paket.

Repository publik ini mengungkap sebagian rancangan temuan. Jangan commit marker rahasia, kunci jawaban final, akun nyata, atau data pribadi. Simpan materi penilaian yang belum boleh dilihat peserta di luar GitHub publik.
