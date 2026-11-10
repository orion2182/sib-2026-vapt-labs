# Final Project VAPT — panduan bersama tiga target berbasis PTES

**Status:** draft materi mahasiswa; URL, akun, dan marker diisi instruktur per tim.  
**Periode:** 20 November–23 Desember 2026 (WIB)  
**Format:** tujuh fase PTES, 15 pertanyaan per kelompok, evidence log, laporan, retest, dan presentasi. Setiap kelompok mendapat **satu** kartu target: KampusMart, PerpusDigital, atau EventPass.

## Aturan mulai

Tim Anda bertugas menilai **hanya aplikasi target yang ditetapkan pengajar**. Ketiga kelompok mempunyai aplikasi, alur, dan pertanyaan teknis yang berbeda. Seluruh akun dan data adalah sintetis. **Pengujian aktif baru boleh dimulai setelah formulir scope/Rules of Engagement (ROE) tim berstatus `Disetujui` dan ditandatangani mentor/pengajar yang berwenang.** Pengajar kemudian membuka akses target atau membagikan paket lab.

Gunakan hanya host, port, akun, waktu, teknik, dan objek uji yang tertulis pada form. Hentikan pembuktian setelah satu marker atau perubahan sintetis minimum per kondisi diperoleh. Jangan menyimpan password, token sesi, atau data di luar instance tim dalam bukti. Jika target tidak sesuai form, hentikan interaksi dan hubungi mentor.

Jawaban Q1–Q15 harus merujuk **ID bukti** ketika menyatakan hasil pengujian. **Q8–Q11 diambil dari kartu target kelompok Anda**, bukan dari kartu kelompok lain. Tiga bukti minimum instance hanya menunjukkan progres; temuan lengkap tetap memerlukan kondisi awal, hasil aktual, hasil seharusnya, analisis, dan retest.

### Format evidence log

| ID | Waktu WIB | Fase PTES | Akun/peran | Kondisi awal | Tindakan/request tersanitasi | Hasil aktual | Hasil semestinya | Reset/cleanup |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| E01 | … | … | … | … | … | … | … | … |

---

## Task 1 — Pre-engagement interactions: mandat sebelum tes

**Jadwal:** 20 dan 23 November.  
**Cerita:** pemilik aplikasi pada kartu target Anda meminta asesmen terbatas. Tim harus menyepakati apa yang diuji, kapan, oleh siapa, dan bagaimana menangani kondisi tak terduga. Sebuah temuan di luar mandat tidak boleh dikejar tanpa perubahan scope yang disetujui.

**Tujuan:** membuat scope, ROE, dan rencana uji yang bisa diperiksa mentor. Isi [formulir otorisasi VAPT](./Form-Otorisasi-Mulai-VAPT-PTES.md) dan ajukan tanda tangan pada sesi mentorship. Sampai disetujui, gunakan brief dan dokumen yang diberikan pengajar; jangan mengirim request uji ke target.

### Jawab pertanyaan

**Q1.** Host/port, akun/peran, jendela waktu, dan jenis pengujian apa yang tim Anda minta untuk disetujui?  
**Format:** tabel scope dari form ROE; beri versi dokumen.

**Q2.** Apa tiga kondisi yang mewajibkan tim berhenti dan siapa yang dihubungi? Kapan persetujuan mulai VAPT berlaku?  
**Format:** tiga kondisi + kontak/kanal + status/waktu tanda tangan.

<details><summary>Petunjuk 1</summary>

Pisahkan **scope** (aset dan kegiatan yang dicakup) dari **ROE** (cara dan batas pelaksanaan).

</details>

**Serahkan:** form ROE, rencana uji, Q1–Q2. **Gerbang G0:** mentor/pengajar berwenang menandatangani sebelum Task 2 dimulai.

Jika form dikembalikan untuk revisi, perbaiki dari brief secara offline dan ajukan ulang pada sesi mentor berikutnya. Akses target diberikan setelah status `Disetujui` tercatat.

---

## Task 2 — Intelligence gathering: pahami target yang disetujui

**Jadwal:** 24–25 November.  
**Cerita:** satu halaman web dapat mengirim beberapa request API. Tim membutuhkan peta fungsi dan data sebelum menyimpulkan adanya kelemahan.

**Tujuan:** memetakan aset, layanan, endpoint, parameter objek, teknologi yang terlihat, dan hak akses berdasarkan perilaku normal. Semua interaksi tetap dalam scope yang ditandatangani.

### Jawab pertanyaan

**Q3.** Sebutkan sedikitnya tiga titik masuk yang relevan beserta metode HTTP, parameter/ID objek, dan peran akun yang memakainya.  
**Format:** tabel dan ID bukti request/response tersanitasi.

**Q4.** Teknologi atau versi apa yang benar-benar teramati, dan apa yang masih merupakan asumsi?  
**Format:** daftar `fakta → bukti` dan `asumsi → cara memverifikasi`.

<details><summary>Petunjuk 1</summary>

Telusuri alur normal yang tercantum pada kartu target kelompok Anda. Catat request yang muncul saat akun mengakses objeknya sendiri.

</details>

**Serahkan:** peta endpoint, matriks peran, Q3–Q4.

---

## Task 3 — Threat modeling: pilih risiko yang masuk akal

**Jadwal:** 26–27 November.  
**Cerita:** aplikasi yang Anda terima memiliki beberapa jenis akun dan proses bisnis. Tim perlu menjelaskan mengapa tiga area tertentu layak diuji lebih dahulu.

**Tujuan:** menautkan aset penting, aktor, trust boundary, jalur penyalahgunaan, dan dampak yang masuk akal. Bedakan risiko yang didukung peta aplikasi dari dugaan awal.

### Jawab pertanyaan

**Q5.** Sebutkan tiga aset atau proses yang paling penting untuk diuji pada instance tim dan alasan prioritasnya.  
**Format:** aset/proses, pelaku berkemampuan rendah yang relevan, dampak sintetis maksimum, sumber observasi.

**Q6.** Gambarkan satu trust boundary dan satu misuse case untuk objek atau alur transaksi yang akan diuji. Kontrol apa yang seharusnya menegakkan batas itu?  
**Format:** diagram/tabel singkat, plus satu hipotesis yang dapat dibuktikan.

<details><summary>Petunjuk 1</summary>

Perhatikan perpindahan dari browser ke API, dari akun ke objek/properti/fungsi, dan dari satu state proses bisnis ke state berikutnya.

</details>

**Serahkan:** model ancaman dan Q5–Q6. **Gerbang G1:** mentor meninjau prioritas uji.

---

## Task 4 — Vulnerability analysis: bedakan kandidat dan temuan

**Jadwal:** 30 November–2 Desember.  
**Cerita:** hasil scanner atau nomor versi dapat menunjukkan area yang perlu diperiksa. Tim belum dapat menyatakan temuan sebelum prasyarat dan perilaku target divalidasi.

**Tujuan:** menyiapkan rencana pembuktian aman untuk **tiga area pada kartu target sendiri**. Gunakan rujukan resmi yang ditunjuk kartu itu untuk menilai prasyarat dan kemungkinan false positive.

### Jawab pertanyaan

**Q7.** Apa hipotesis kelemahan untuk tiga area pada kartu target Anda, bukti awal masing-masing, dan satu kemungkinan false positive?  
**Format:** tabel `hipotesis → bukti awal → verifikasi aman → batas bukti`.

**Q8.** Jawab pertanyaan analisis khusus target pada kartu kelompok Anda.  
**Format:** rujukan teknis yang relevan, prasyarat, pembeda fakta/asumsi, dan ID bukti awal.

<details><summary>Petunjuk 1</summary>

Bandingkan perilaku normal dan kandidat temuan. Catat prasyarat yang benar-benar ada pada instance, lalu tetapkan batas pembuktian sebelum uji aktif.

</details>

**Serahkan:** matriks kandidat, rencana uji terbatas, Q7–Q8. **Gerbang G2:** mentor menyetujui batas pembuktian.

---

## Task 5 — Exploitation: bukti minimum pada instance sendiri

**Jadwal:** 3–8 Desember.  
**Cerita:** tim sekarang menguji tiga hipotesis pada data sintetis. Tujuan fase ini adalah membuktikan kontrol yang gagal dengan perubahan sekecil mungkin, lalu berhenti.

**Tujuan:** memperoleh tiga bukti yang dapat direproduksi **sesuai kartu target kelompok**. Catat respons normal sebagai pembanding. Tidak ada eksekusi kode pada host, brute force, denial of service, akses ke tim lain, atau pengambilan data di luar fixture sintetis.

### Jawab pertanyaan

**Q9–Q11.** Jawab tiga pertanyaan pembuktian pada kartu target kelompok Anda. Untuk tiap jawaban, lampirkan kondisi awal, satu tindakan minimum, hasil semestinya/aktual, bukti sintetis, dan ID evidence log.

<details><summary>Petunjuk 1</summary>

Gunakan peta endpoint dan trust boundary dari Task 2–3. Lakukan satu perubahan pada satu waktu.

</details>

<details><summary>Petunjuk 2</summary>

Ulangi dari fixture bersih bila hasil pertama tidak jelas. Hentikan setelah marker/ID bukti minimum muncul.

</details>

**Serahkan:** tiga catatan pembuktian tersanitasi dan Q9–Q11. **Gerbang G3:** ketiga bukti dapat diulang pada kondisi bersih.

---

## Task 6 — Post exploitation: dampak, batas, dan cleanup

**Jadwal:** 9–11 Desember.  
**Cerita:** keberhasilan memperoleh bukti minimum tidak otomatis berarti dampak tak terbatas. Pemilik aplikasi membutuhkan penjelasan tentang apa yang terbukti, apa yang belum diuji, dan apakah lab kembali ke keadaan semula.

**Tujuan:** mengukur dampak dengan record sintetis, menguji ulang klaim pada kondisi bersih, lalu mereset perubahan. Fase ini tidak mencakup persistence atau perpindahan ke host lain.

### Jawab pertanyaan

**Q12.** Untuk setiap temuan, dampak apa yang benar-benar teramati dan batas apa yang tidak dapat disimpulkan dari bukti tersebut?  
**Format:** tabel tiga baris `bukti → dampak terbukti → klaim yang belum terbukti`.

**Q13.** Bagaimana tim memverifikasi bahwa state uji sudah direset dan tidak ada data/akses tambahan yang tersisa dari pembuktian?  
**Format:** langkah cleanup, kondisi sebelum/sesudah, ID bukti health check atau reset.

<details><summary>Petunjuk 1</summary>

Pisahkan kemampuan yang dibuktikan oleh satu marker dari dampak hipotetis pada sistem nyata.

</details>

**Serahkan:** impact statement, log cleanup, Q12–Q13. **Gerbang G4:** mentor memvalidasi tiga temuan dan dampaknya.

---

## Task 7 — Reporting dan retest

**Jadwal:** 14–18 Desember; presentasi 21–23 Desember.  
**Cerita:** pemilik aplikasi membutuhkan laporan yang dapat dipakai untuk memperbaiki kontrol, serta bukti bahwa kondisi rentan sudah berubah setelah patch. Retest adalah kebutuhan final project yang melengkapi fase reporting PTES.

**Tujuan:** memberi prioritas temuan, mengusulkan kontrol spesifik, dan mengulang kasus yang sama pada build/config patched. Setiap anggota harus siap menjelaskan kontribusi teknisnya.

### Jawab pertanyaan

**Q14.** Urutkan tiga temuan menurut prioritas pemilik target Anda. Apa akar masalah, dampak terukur, dan perubahan kontrol yang disarankan untuk masing-masing?  
**Format:** tabel tiga baris dan alasan prioritas singkat.

**Q15.** Pada build/config patched, apa hasil retest setiap temuan dan bukti bahwa fungsi normal masih bekerja? Apa keterbatasan pengujian akhir?  
**Format:** tabel sebelum/sesudah berisi versi/config, akun, request/tindakan, respons/state, ID bukti, lalu dua batasan.

<details><summary>Petunjuk 1</summary>

Gunakan kondisi awal dan langkah pengujian yang sama pada versi rentan dan patched. Periksa state objek selain status HTTP.

</details>

**Serahkan paling lambat 18 Desember:** Q1–Q15, form ROE bertanda tangan, evidence log, laporan akhir, tabel retest, dan slide. Presentasi/pengujian berlangsung 21–23 Desember sesuai slot pengajar.

---

## Checklist sebelum pengumpulan

- [ ] Form ROE bertanda tangan mendahului seluruh pengujian aktif.
- [ ] Semua host, akun, dan objek yang diuji tercantum dalam scope.
- [ ] Q1–Q15 terjawab; klaim teknis merujuk ID bukti.
- [ ] Tiga temuan memiliki kondisi awal, hasil aktual, hasil semestinya, akar masalah, dan dampak sintetis.
- [ ] Bukti hanya berasal dari instance sendiri; state telah direset.
- [ ] Retest menyebut versi/config rentan dan patched, serta hasil fungsi normal.
- [ ] Bukti bebas password, token sesi, dan data di luar lab.
- [ ] Setiap anggota siap menjelaskan satu bukti, keputusan, atau retest yang dikerjakannya.
