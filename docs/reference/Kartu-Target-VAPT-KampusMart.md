# Kartu target VAPT — KampusMart

**Untuk:** kelompok VAPT 1 saja. **Kerangka kerja:** [panduan PTES bersama](./Draft-Lab-VAPT-KampusMart-Mahasiswa.md).  
**Status:** skenario peserta; URL, akun, port, dan marker diisi instruktur pada saat pembagian setelah ROE disetujui.

## Cerita dan tujuan

KampusMart adalah toko fiktif kebutuhan kampus. Pelanggan dapat melihat katalog, membuat pesanan, memakai kupon, dan membuka invoice. Staf menangani pesanan melalui fungsi terpisah. Pemilik meminta tim memastikan transaksi dan data pelanggan lain terlindungi serta satu layanan web tambahan dikonfigurasi aman.

| Alur normal yang perlu dipahami | Bukti dasar yang dicatat |
| --- | --- |
| Dua pelanggan melihat pesanan dan invoice masing-masing | Peran, pemilik objek, respons normal |
| Pelanggan memakai kupon sesuai ketentuan | Aturan pemakaian dan state sebelum/sesudah |
| Layanan web tambahan menyajikan halaman publik | Versi/build dan konfigurasi yang dapat diamati |

## Pertanyaan khusus target

**Q8 — Analisis.** Menurut [advisory Apache resmi](https://httpd.apache.org/security/vulnerabilities_24.html), apa kombinasi versi dan konfigurasi yang relevan untuk layanan tambahan? Bukti apa yang membedakan kandidat dari temuan yang benar-benar terbukti? Batasi pembacaan pada marker sintetis yang disetujui.

**Q9 — Layanan.** Dapatkah tim memperoleh **satu marker file sintetis** dari lokasi yang semestinya tertutup? Catat versi/config, kondisi normal, request tersanitasi, hasil aktual, dan alasan berhenti setelah marker diperoleh.

**Q10 — Invoice.** Apakah akun pelanggan A dapat membaca **satu invoice sintetis milik pelanggan B** pada instance tim yang sama? Bandingkan respons invoice sendiri, invoice B, dan hasil yang seharusnya. Catat marker invoice dan ID bukti.

**Q11 — Kupon.** Apakah satu kupon dapat digunakan ketika aturan bisnis menyatakan penggunaannya sudah habis? Tulis invariant, state awal, satu tindakan, state akhir, dan ID perubahan sintetis. Reset setelah bukti dicatat.

<details><summary>Petunjuk konsep</summary>

Periksa **syarat konfigurasi**, **otorisasi pada objek yang diminta**, dan **validasi kupon di sisi server**. Tiga area ini memerlukan pembanding normal masing-masing.

</details>

**Batas:** hanya akun, objek, dan file marker yang tercantum dalam ROE; tidak ada eksekusi kode pada layanan tambahan. Empat pertanyaan ini menjadi Q8–Q11 untuk kelompok Anda dalam panduan bersama.
