# Kartu target VAPT — EventPass

**Untuk:** kelompok VAPT-3 saja. **Kerangka kerja:** PTES, Q1–Q15.
**Build:** lihat `build.json`. **Instance:** `127.0.0.1` pada port yang diberikan.

Semua data sintetis. Indikator DOM yang diuji bersifat lokal dan tidak boleh
melakukan permintaan jaringan.

## Cerita dan tujuan

EventPass adalah platform acara kampus fiktif. Peserta melihat acara, mengambil
tiket, menulis ulasan, dan check-in sekali. Penyelenggara melihat laporan acara.
Pengelola meminta tim memastikan ulasan dirender aman, properti tiket yang
dikendalikan penyelenggara tidak dapat diubah peserta, dan check-in hanya sekali.

| Alur normal | Bukti dasar |
| --- | --- |
| Peserta mengambil tiket dan mengubah nama tampilan | Field yang boleh diubah |
| Peserta menulis ulasan biasa | Ulasan tampil untuk akun lain |
| Penyelenggara check-in pertama | Satu catatan check-in |
| Penyelenggara melihat laporan | Jumlah tiket, check-in, ulasan |

## Pertanyaan bersama (Q1–Q15)

**Q1.** Host/port, akun/peran, jendela waktu, dan jenis pengujian yang diminta?

**Q2.** Tiga kondisi berhenti dan kontak; kapan persetujuan VAPT berlaku?

**Q3.** Tiga titik masuk, metode HTTP, parameter/ID, dan peran.

**Q4.** Teknologi/versi teramati dan asumsi.

**Q5.** Tiga aset/proses prioritas dan alasannya.

**Q6.** Satu trust boundary dan satu misuse case; kontrol yang seharusnya.

**Q7.** Hipotesis tiga area, bukti awal, satu kemungkinan false positive.

**Q8.** Jawab pertanyaan analisis khusus target.

**Q9–Q11.** Tiga pembuktian dengan kondisi awal, tindakan, hasil, bukti, evidence ID.

**Q12.** Dampak teramati dan batas klaim.

**Q13.** Verifikasi reset dan tidak ada akses tersisa.

**Q14.** Prioritas temuan, akar masalah, dampak, perubahan kontrol.

**Q15.** Retest patched dan bukti fungsi normal.

## Pertanyaan khusus target

**Q8 — Analisis rendering.** Di konteks apa ulasan disimpan dan ditampilkan?
Apa yang membedakan encoding yang benar dari sekadar menghapus karakter, dan
bagaimana Anda membuktikan indikator hanya lokal?

**Q9 — Ulasan.** Dapatkah satu ulasan tersimpan menghasilkan **indikator DOM lokal**
di browser akun uji kedua? Indikator berarti perubahan DOM yang dapat diamati
(mis. elemen atau atribut baru) tanpa permintaan jaringan. Lampirkan cuplikan HTML
respons tersanitasi, akun, dan alasan struktural mengapa payload tidak dapat
menghubungi jaringan. Jika browser otomatis tidak tersedia, analisis HTML respons
dan isi payload adalah bukti yang sah; sebutkan batasnya.

**Q10 — Properti tiket.** Dapatkah akun peserta mengubah **satu properti tiket yang
dikendalikan penyelenggara**? Bandingkan field yang diizinkan dengan yang ditolak.

**Q11 — Check-in.** Dapatkah satu tiket dicatat check-in **dua kali**? Bedakan
`tickets.status` (`checked_in`) dari jumlah baris catatan check-in; invariant
satu-kali berarti maksimal **satu baris check-in** per tiket. Tulis state awal,
dua tindakan, state akhir, dan ID bukti.

<details><summary>Petunjuk konsep secukupnya</summary>

Periksa **encoding sesuai konteks output**, **allowlist field dan otorisasi
properti di server**, serta **transisi state check-in yang atomik**. Jangan
mengumpulkan cookie/token atau mengirim data ke luar instance.

</details>

## Batas pembuktian

- Hanya dua akun browser dan satu tiket uji yang disetujui.
- Indikator DOM harus inert dan lokal; tanpa exfiltrasi, cookie, atau token.
- Tidak ada akses antarkelompok atau eksekusi host.
- Satu bukti minimum per kondisi; berhenti setelah terbukti.

## Format penyerahan

| Kolom | Isi |
| --- | --- |
| ID kondisi | Q9 / Q10 / Q11 |
| Kondisi awal | State sebelum tindakan |
| Tindakan minimum | Satu request/aksi tersanitasi |
| Hasil aktual | Indikator, field, atau jumlah check-in |
| Hasil seharusnya | Kontrol yang menolak |
| Evidence ID | ID dari log bukti |
| Batas klaim | Yang tidak dapat disimpulkan |
| Retest | Hasil pada build patched |
