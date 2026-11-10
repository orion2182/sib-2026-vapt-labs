# Kartu target VAPT — EventPass

**Untuk:** kelompok VAPT 3 saja. **Kerangka kerja:** [panduan PTES bersama](./Draft-Lab-VAPT-KampusMart-Mahasiswa.md).  
**Status:** skenario peserta; URL, akun, port, dan marker diisi instruktur pada saat pembagian setelah ROE disetujui.

## Cerita dan tujuan

EventPass adalah platform acara fiktif. Peserta memilih tiket, memberi ulasan acara, dan melakukan check-in. Penyelenggara melihat hasilnya. Pemilik meminta pemeriksaan apakah konten ulasan, properti tiket, dan aturan check-in dilindungi pada setiap jalur aplikasi.

| Alur normal yang perlu dipahami | Bukti dasar yang dicatat |
| --- | --- |
| Ulasan peserta ditampilkan kembali pada halaman acara | Akun penulis dan pembaca, lokasi tampilan |
| Peserta memperbarui detail tiket yang memang boleh diubah | Field yang boleh dan tidak boleh diubah |
| Satu tiket melakukan check-in satu kali | State tiket sebelum/sesudah scan simulasi |

## Pertanyaan khusus target

**Q8 — Analisis.** Pada bagian mana konten ulasan berubah menjadi output HTML bagi pembaca lain? Mengapa menyimpan teks saja belum membuktikan **stored XSS**? Jelaskan pembanding yang akan dipakai menurut [rujukan OWASP](https://community.owasp.org/attacks/xss/).

**Q9 — Ulasan.** Dapatkah satu ulasan sintetis menimbulkan **indikator DOM lokal yang tidak mengirim data keluar** ketika dibuka oleh akun uji kedua? Catat akun penulis/pembaca, respons normal, efek yang benar-benar terlihat, dan ID bukti. Tidak boleh mengambil cookie atau token.

**Q10 — Properti tiket.** Dapatkah akun peserta mengubah **satu properti tiket yang seharusnya ditentukan server/penyelenggara** melalui request pembaruan? Bandingkan field yang sah dan terlarang, state sebelum/sesudah, serta ID perubahan sintetis.

**Q11 — Check-in.** Dapatkah **tiket yang sama** tercatat check-in dua kali meskipun aturan menyatakan satu kali? Tulis invariant, kondisi awal, dua hasil scan simulasi, state akhir, dan ID bukti; reset sesudahnya.

<details><summary>Petunjuk konsep</summary>

Periksa **konteks output HTML**, **otorisasi setiap properti yang diterima API**, dan **aturan state check-in pada server**.

</details>

**Batas:** hanya dua akun browser uji dan tiket sintetis yang tercantum dalam ROE; tidak ada exfiltration, pengambilan cookie/token, atau scan fisik. Empat pertanyaan ini menjadi Q8–Q11 untuk kelompok Anda dalam panduan bersama.
