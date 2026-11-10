# Kartu target VAPT — PerpusDigital

**Untuk:** kelompok VAPT-2 saja. **Kerangka kerja:** PTES, Q1–Q15.
**Build:** lihat `build.json`. **Instance:** `127.0.0.1` pada port yang diberikan.

Semua data sintetis. Jangan menguji di luar instance kelompok Anda.

## Cerita dan tujuan

PerpusDigital adalah perpustakaan kampus fiktif. Anggota mencari buku, meminjam,
mengembalikan, dan melihat denda. Petugas mengubah metadata dan menyelesaikan
denda yang memenuhi syarat. Pengelola meminta tim memastikan pencarian tidak
membocorkan arsip internal, fungsi petugas benar-benar dibatasi di server, dan
pembebasan denda mengikuti prasyarat pengembalian.

| Alur normal | Bukti dasar |
| --- | --- |
| Anggota mencari dan meminjam buku | Hasil pencarian, status pinjaman |
| Anggota mengembalikan buku | Perubahan status dan stok |
| Petugas mengubah metadata | Peran, field, respons |
| Petugas membebaskan denda yang memenuhi syarat | Status pinjaman dan denda |

## Pertanyaan bersama (Q1–Q15)

**Q1.** Host/port, akun/peran, jendela waktu, dan jenis pengujian yang diminta
untuk disetujui?

**Q2.** Tiga kondisi yang mewajibkan tim berhenti dan siapa yang dihubungi?
Kapan persetujuan VAPT berlaku?

**Q3.** Tiga titik masuk relevan beserta metode HTTP, parameter/ID objek, dan peran.

**Q4.** Teknologi/versi yang teramati dan yang masih asumsi.

**Q5.** Tiga aset/proses terpenting untuk diuji dan alasan prioritas.

**Q6.** Satu trust boundary dan satu misuse case; kontrol yang seharusnya menegakkan.

**Q7.** Hipotesis kelemahan untuk tiga area, bukti awal, dan satu kemungkinan
false positive.

**Q8.** Jawab pertanyaan analisis khusus target di bawah.

**Q9–Q11.** Jawab tiga pertanyaan pembuktian; lampirkan kondisi awal, satu tindakan,
hasil aktual/seharusnya, bukti sintetis, dan evidence ID.

**Q12.** Dampak teramati dan batas yang tidak dapat disimpulkan.

**Q13.** Cara memverifikasi reset dan tidak ada akses tambahan yang tersisa.

**Q14.** Urutan prioritas tiga temuan, akar masalah, dampak, dan perubahan kontrol.

**Q15.** Hasil retest pada build patched dan bukti fungsi normal masih bekerja.

## Pertanyaan khusus target

**Q8 — Analisis pencarian.** Bagaimana input pencarian membentuk query? Apa
kondisi yang membuat filter visibilitas dapat dilewati, dan bukti apa yang
membedakan kandidat dari pembacaan record yang benar-benar terbukti? Batasi pada
satu record marker sintetis.

**Q9 — Pencarian.** Dapatkah tim memperoleh **satu record arsip sintetis** yang
seharusnya tidak terlihat oleh anggota? Catat input tersanitasi, jumlah hasil,
marker, dan alasan berhenti.

**Q10 — Fungsi petugas.** Apakah akun anggota dapat mengubah **satu field metadata
buku** melalui fungsi yang seharusnya hanya untuk petugas? Bandingkan kontrol UI
dengan penegakan di server.

**Q11 — Denda.** Dapatkah satu denda dibebaskan **sebelum** buku dikembalikan?
Tulis invariant, state awal, satu tindakan, state akhir, dan ID bukti.

<details><summary>Petunjuk konsep secukupnya</summary>

Periksa **pembentukan query dan parameterisasi**, **otorisasi fungsi di server
bukan hanya di UI**, serta **validasi state dan transaksi** pada alur denda.
Perubahan respons saja bukan bukti penuh.

</details>

## Batas pembuktian

- Hanya akun, objek, dan marker dalam ROE.
- Tidak ada akses tabel sistem, file host, atau perubahan data massal.
- Satu bukti minimum per kondisi; berhenti setelah terbukti.
- Tidak ada akses antarkelompok.

## Format penyerahan

| Kolom | Isi |
| --- | --- |
| ID kondisi | Q9 / Q10 / Q11 |
| Kondisi awal | State sebelum tindakan |
| Tindakan minimum | Satu request tersanitasi |
| Hasil aktual | Status, record, atau state |
| Hasil seharusnya | Kontrol yang menolak |
| Evidence ID | ID dari log bukti |
| Batas klaim | Yang tidak dapat disimpulkan |
| Retest | Hasil pada build patched |
