# Kartu target VAPT — KampusMart

**Untuk:** kelompok VAPT-1 saja. **Kerangka kerja:** PTES, Q1–Q15.
**Build:** lihat `build.json`. **Instance:** `127.0.0.1` pada port yang diberikan instruktur.

Semua akun, produk, pesanan, invoice, kupon, dan marker adalah data sintetis.
Jangan menguji di luar instance kelompok Anda.

## Cerita dan tujuan

KampusMart adalah toko fiktif kebutuhan kampus. Pelanggan melihat katalog,
membuat pesanan, memakai kupon, dan membuka invoice. Staf menangani pesanan
melalui fungsi terpisah. Pemilik meminta tim memastikan transaksi dan data
pelanggan lain terlindungi, aturan kupon ditegakkan, dan layanan dokumen lama
dikonfigurasi aman.

| Alur normal yang perlu dipahami | Bukti dasar yang dicatat |
| --- | --- |
| Dua pelanggan melihat pesanan dan invoice masing-masing | Peran, pemilik objek, respons normal |
| Pelanggan memakai kupon sesuai ketentuan | Aturan pemakaian dan state sebelum/sesudah |
| Layanan dokumen lama menyajikan dokumen publik | Build/konfigurasi yang dapat diamati |

## Pertanyaan bersama (Q1–Q15)

**Q1.** Host/port, akun/peran, jendela waktu, dan jenis pengujian apa yang tim
Anda minta untuk disetujui?

**Q2.** Apa tiga kondisi yang mewajibkan tim berhenti dan siapa yang dihubungi?
Kapan persetujuan mulai VAPT berlaku?

**Q3.** Sebutkan sedikitnya tiga titik masuk yang relevan beserta metode HTTP,
parameter/ID objek, dan peran akun yang memakainya.

**Q4.** Teknologi atau versi apa yang benar-benar teramati, dan apa yang masih
merupakan asumsi?

**Q5.** Sebutkan tiga aset atau proses yang paling penting untuk diuji pada
instance tim dan alasan prioritasnya.

**Q6.** Gambarkan satu trust boundary dan satu misuse case untuk objek atau alur
transaksi yang akan diuji. Kontrol apa yang seharusnya menegakkan batas itu?

**Q7.** Apa hipotesis kelemahan untuk tiga area pada kartu target Anda, bukti awal
masing-masing, dan satu kemungkinan false positive?

**Q8.** Jawab pertanyaan analisis khusus target di bawah.

**Q9–Q11.** Jawab tiga pertanyaan pembuktian khusus target di bawah. Untuk tiap
jawaban, lampirkan kondisi awal, satu tindakan minimum, hasil semestinya/aktual,
bukti sintetis, dan ID evidence log.

**Q12.** Untuk setiap temuan, dampak apa yang benar-benar teramati dan batas apa
yang tidak dapat disimpulkan dari bukti tersebut?

**Q13.** Bagaimana tim memverifikasi bahwa state uji sudah direset dan tidak ada
data/akses tambahan yang tersisa dari pembuktian?

**Q14.** Urutkan tiga temuan menurut prioritas pemilik target. Apa akar masalah,
dampak terukur, dan perubahan kontrol yang disarankan untuk masing-masing?

**Q15.** Pada build patched, apa hasil retest setiap temuan dan bukti bahwa
fungsi normal masih bekerja? Apa keterbatasan pengujian akhir?

## Pertanyaan khusus target

**Q8 — Analisis layanan dokumen.** Layanan dokumen lama menyajikan berkas dari
satu direktori publik. Konfigurasi yang dapat diamati tersedia pada
`/legacy/info`; dokumen publik yang benar-benar ada tercantum di `welcome.txt`.
Apa janji konfigurasinya, kondisi apa yang membuat permintaan keluar dari
direktori itu, dan bukti apa yang membedakan kandidat dari pembacaan yang
benar-benar terbukti? Batasi pembacaan pada satu marker sintetis yang disetujui.

**Q9 — Layanan.** Dapatkah tim memperoleh **satu marker file sintetis** dari
lokasi yang semestinya tertutup? Catat kondisi normal, request tersanitasi, hasil
aktual, dan alasan berhenti setelah marker diperoleh.

**Q10 — Invoice.** Apakah akun pelanggan B dapat membaca **satu invoice sintetis
milik pelanggan A** pada instance tim yang sama? Gunakan invoice yang **sudah
ada** milik pelanggan lain, bukan invoice yang Anda buat sendiri. Bandingkan
respons invoice sendiri, invoice pelanggan lain, dan hasil yang seharusnya.
Catat marker invoice dan ID bukti.

**Q11 — Kupon.** Apakah satu kupon dapat digunakan ketika aturan bisnis
menyatakan penggunaannya sudah habis? Uji **aplikasi berulang pada pesanan
pending yang sama**; jangan membuat pesanan baru untuk membuktikan kondisi ini.
Tulis invariant, state awal, satu tindakan, state akhir, dan ID perubahan
sintetis. Ekspor atau catat ID bukti sebelum reset.

<details><summary>Petunjuk konsep secukupnya</summary>

Periksa **normalisasi path dan batas direktori**, **otorisasi pada objek yang
diminta**, dan **validasi kupon di sisi server yang atomik**. Tiga area ini
memerlukan pembanding normal masing-masing. Marker atau pesan galat saja bukan
temuan lengkap.

</details>

## Batas pembuktian

- Hanya akun, objek, dan marker yang tercantum dalam ROE.
- Tidak ada eksekusi kode pada layanan dokumen, eskalasi host, atau akses
  antarkelompok.
- Tidak ada ekstraksi data massal; cukup satu bukti minimum per kondisi.
- Berhenti setelah marker/objek terbukti, catat bukti, lalu reset.

## Format penyerahan

| Kolom | Isi |
| --- | --- |
| ID kondisi | Q9 / Q10 / Q11 |
| Kondisi awal | State sebelum tindakan |
| Tindakan minimum | Satu request/aksi tersanitasi |
| Hasil aktual | Status, marker, atau state yang teramati |
| Hasil seharusnya | Kontrol yang semestinya menolak |
| Evidence ID | ID dari log bukti |
| Batas klaim | Yang tidak dapat disimpulkan |
| Retest | Hasil pada build patched |
