# Kartu target VAPT — PerpusDigital

**Untuk:** kelompok VAPT 2 saja. **Kerangka kerja:** [panduan PTES bersama](./Draft-Lab-VAPT-KampusMart-Mahasiswa.md).  
**Status:** skenario peserta; URL, akun, port, dan marker diisi instruktur pada saat pembagian setelah ROE disetujui.

## Cerita dan tujuan

PerpusDigital adalah perpustakaan fiktif. Anggota mencari buku, meminjam, mengembalikan, dan melihat denda simulasi. Petugas mengelola metadata katalog dan menyelesaikan transaksi. Pemilik meminta pemeriksaan apakah input pencarian, fungsi petugas, dan aturan pembebasan denda bekerja sesuai batasnya.

| Alur normal yang perlu dipahami | Bukti dasar yang dicatat |
| --- | --- |
| Pencarian katalog mengembalikan buku yang sesuai | Kata kunci, jumlah/jenis hasil normal |
| Petugas mengubah metadata satu buku sintetis | Perbedaan hak anggota dan petugas |
| Denda dibebaskan setelah prasyarat dipenuhi | State peminjaman dan denda sebelum/sesudah |

## Pertanyaan khusus target

**Q8 — Analisis.** Bagaimana membedakan respons pencarian yang memang berubah karena input biasa dari indikasi **SQL injection**? Rancang pembanding aman dan jelaskan mengapa satu pesan galat saja belum cukup. Gunakan [rujukan OWASP](https://community.owasp.org/attacks/SQL_Injection).

**Q9 — Pencarian.** Dapatkah input pencarian menampilkan **satu record penanda sintetis** yang tidak muncul dalam pencarian normal? Catat pasangan request pembanding, hasil, batas data yang dibaca, dan ID bukti. Hentikan setelah satu record penanda.

**Q10 — Fungsi petugas.** Dengan akun anggota, apakah tim dapat menjalankan **satu perubahan metadata buku** yang hanya boleh dilakukan petugas? Bandingkan perilaku petugas dan anggota, lalu catat state sebelum/sesudah dan ID perubahan sintetis.

**Q11 — Denda.** Dapatkah **satu denda sintetis** dibebaskan sebelum prasyarat pengembalian terpenuhi? Nyatakan invariant, kondisi awal, satu transisi, state akhir, dan ID bukti; reset sesudahnya.

<details><summary>Petunjuk konsep</summary>

Periksa **pemisahan input dan query**, **otorisasi fungsi di server**, dan **urutan state peminjaman → pengembalian → pembebasan denda**.

</details>

**Batas:** hanya katalog dan record sintetis yang tercantum dalam ROE; tidak ada pembacaan tabel sistem, file host, atau perubahan massal. Empat pertanyaan ini menjadi Q8–Q11 untuk kelompok Anda dalam panduan bersama.
