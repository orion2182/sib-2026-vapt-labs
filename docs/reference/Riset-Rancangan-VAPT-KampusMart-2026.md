# Riset dan rancangan final project VAPT berbasis PTES — tiga target

**Status:** rancangan instruktur, revisi 26 Oktober 2026  
**Periode mahasiswa:** 20 November–23 Desember 2026 (WIB)  
**Konteks:** 65 mahasiswa SIB terbagi ke lima tema; VAPT diikuti **tiga kelompok, masing-masing mendapat satu target yang berbeda**.  
**Dokumen pendamping:** `Draft-Lab-VAPT-KampusMart-Mahasiswa.md` (panduan bersama), tiga `Kartu-Target-VAPT-*.md`, `Form-Otorisasi-Mulai-VAPT-PTES.md`, dan `Arsitektur-Lab-VAPT-KampusMart-Lokal.md`.

**Distribusi:** dokumen riset ini dan dokumen arsitektur adalah **materi instruktur** karena memuat peta temuan. Kepada mahasiswa, bagikan panduan bersama, form ROE, dan **hanya kartu target kelompoknya**.

## 1. Rekomendasi operasional

Gunakan **PTES sebagai urutan kerja** dan OWASP WSTG sebagai rujukan teknis web/API. Tujuh fase PTES adalah pre-engagement interactions, intelligence gathering, threat modeling, vulnerability analysis, exploitation, post exploitation, dan reporting. Lab mahasiswa terdiri atas tujuh task sesuai fase tersebut, dengan 15 pertanyaan yang meminta hasil pengamatan dan bukti. Retest ditambahkan sebagai kebutuhan final project di fase reporting. [OWASP WSTG tentang PTES](https://wstg.owasp.org/v4.2/3-The_OWASP_Testing_Framework/1-Penetration_Testing_Methodologies/).

**Gerbang utama:** pada fase pre-engagement, setiap tim menyerahkan scope, Rules of Engagement (ROE), dan rencana uji. Mentor/pengajar yang diberi wewenang oleh pemilik lab menandatangani formulir sebelum kredensial target atau akses VPN tim diaktifkan. Status `Disetujui` dicatat bersama waktu, versi dokumen, host/port, dan masa berlaku. Perubahan scope memerlukan adendum bertanda tangan. NIST mendefinisikan ROE sebagai batas dan kewenangan yang ditetapkan sebelum tes dimulai; [CREST](https://www.crest-approved.org/wp-content/uploads/2023/04/A-Guide-to-Penetration-Testing-2022.pdf) juga merekomendasikan scope formal yang disetujui pihak terkait. [Definisi NIST](https://csrc.nist.gov/glossary/term/Rules_of_Engagement).

**Hosting yang disarankan setelah mempertimbangkan perangkat peserta:** bangun **tiga paket Docker Compose yang berbeda**, satu paket target untuk tiap kelompok; setiap laptop hanya menjalankan target kelompoknya. Sediakan instance jarak jauh dari instruktur bagi tim yang perangkatnya tidak lulus pilot. Tiap paket memakai satu aplikasi web/API ringan dan SQLite; hanya KampusMart memiliki sidecar Apache CVE opsional. **RAM 8 GB adalah target minimum bersyarat yang harus dibuktikan pada pilot; RAM 16 GB adalah rekomendasi nyaman.** Detail komponen, resource, dan fallback ada di dokumen arsitektur.

## 2. Dasar riset dan konsekuensi desain

| Rujukan primer | Temuan relevan | Konsekuensi untuk proyek |
| --- | --- | --- |
| [PTES tujuh fase menurut OWASP WSTG](https://wstg.owasp.org/v4.2/3-The_OWASP_Testing_Framework/1-Penetration_Testing_Methodologies/) | PTES mencakup engagement dari scope sampai laporan. | Task, artefak, dan gerbang mutu mengikuti tujuh fase. |
| [NIST SP 800-115, lampiran ROE](https://nvlpubs.nist.gov/nistpubs/legacy/sp/nistspecialpublication800-115.pdf) | Template ROE memuat scope, jadwal, personel, risiko, dan batasan. | Formulir persetujuan ditandatangani sebelum pengujian aktif. |
| [TryHackMe: Creating Your First Room](https://help.tryhackme.com/en/articles/6633511-creating-your-first-room) | Task yang baik menuntut penerapan, bukan kuis; panduan room menyarankan maksimum 15 pertanyaan. | Tujuh task PTES, 15 pertanyaan, hint bertahap, bukti lebih penting daripada marker. |
| [OWASP WSTG: entry points](https://wstg.owasp.org/v4.2/4-Web_Application_Security_Testing/01-Information_Gathering/06-Identify_Application_Entry_Points/) | Pemetaan request dan parameter mendahului uji kelemahan. | Intelligence gathering memproduksi peta endpoint, akun, dan objek. |
| [OWASP API1:2023 BOLA](https://api-security.owasp.org/editions/2023/en/0xa1-broken-object-level-authorization/), [API3:2023 BOPLA](https://api-security.owasp.org/editions/2023/en/0xa3-broken-object-property-level-authorization/), [API5:2023 BFLA](https://api-security.owasp.org/editions/2023/en/0xa5-broken-function-level-authorization/), dan [WSTG business logic](https://wstg.owasp.org/v4.2/4-Web_Application_Security_Testing/10-Business_Logic_Testing/06-Testing_for_the_Circumvention_of_Work_Flows/) | Kontrol berbeda pada objek, properti, fungsi, dan alur transaksi. | Tiga target menguji keluarga kelemahan berbeda dengan beban penilaian setara. |
| [OWASP SQL injection](https://community.owasp.org/attacks/SQL_Injection) dan [XSS](https://community.owasp.org/attacks/xss/) | Input yang masuk ke query atau output HTML memerlukan kontrol berbeda. | PerpusDigital dan EventPass menambah variasi analisis teknis; pembuktian dibatasi pada data sintetis dan efek lokal. |
| [Advisory Apache HTTP Server](https://httpd.apache.org/security/vulnerabilities_24.html) | CVE-2021-41773 memengaruhi 2.4.49 dengan syarat konfigurasi; perbaikan 2.4.50 belum cukup terhadap CVE-2021-42013. | Modul CVE wajib memeriksa versi dan konfigurasi; bukti hanya file marker sintetis. |
| [Dokumentasi Docker: security](https://docs.docker.com/engine/security/), [network](https://docs.docker.com/compose/how-tos/networking/), dan [resource limits](https://docs.docker.com/engine/containers/resource_constraints/) | Container memerlukan konfigurasi isolasi, pembatasan sumber daya, dan jaringan yang diuji. | Tiap tim diberi stack, akun, fixture, dan jaringan terpisah; host tidak membuka layanan rentan ke internet publik. |

## 3. Sasaran dan cakupan lab

Tiga kelompok VAPT memperoleh **tiga aplikasi yang benar-benar berbeda**, bukan tiga tema visual atau konfigurasi di atas aplikasi yang sama. Semua akun dan data sintetis. Satu kelompok hanya menguji targetnya sendiri.

| Kelompok | Aplikasi dan alur normal | Tiga kelas temuan yang disiapkan instruktur | Bukti minimum |
| --- | --- | --- | --- |
| 1 | **KampusMart:** katalog, pesanan, invoice, kupon | CVE komponen Apache terisolasi; otorisasi objek invoice; pemakaian kupon melewati aturan satu kali | Marker file sintetis, marker invoice akun uji lain, perubahan state kupon |
| 2 | **PerpusDigital:** katalog buku, peminjaman, denda dan pengembalian | SQL injection pada pencarian katalog terbatas; otorisasi fungsi staf pada pembaruan metadata buku; pembebasan denda melewati syarat pengembalian | Marker record sintetis, perubahan metadata terbatas, state denda |
| 3 | **EventPass:** daftar acara, tiket, check-in, ulasan | Stored XSS pada ulasan dengan efek DOM lokal; otorisasi properti tiket pada API pembaruan; check-in tiket yang semestinya satu kali | Indikator DOM lokal, perubahan properti tiket sintetis, state check-in |

Detail implementasi temuan, marker, dan expected response disimpan di **manifest instruktur**, bukan dalam kartu peserta. Tiga aplikasi punya alur, objek, endpoint, data, dan kontrol yang berbeda; hanya harness distribusi, reset, health check, serta format evidence yang boleh dibagi. Angka 13 tim pada PDF adalah asumsi lama ketika semua 65 mahasiswa VAPT; kapasitas sekarang dihitung untuk **tiga kelompok VAPT**. Kesetaraan harus diuji melalui blind pilot: waktu sampai bukti pertama, jumlah langkah, titik buntu, usaha retest, dan kemampuan peserta menjelaskan akar masalah.

Pembuktian dibatasi pada satu bukti minimum per kondisi di instance sendiri yang dapat direset. PTES mencantumkan post exploitation sebagai fase; **adaptasi lab ini** memakai fase itu untuk mengukur dampak pada data sintetis, memeriksa batas akses, menyimpan bukti, dan membersihkan state. Tidak ada persistence, lateral movement, pengambilan data nyata, atau eksekusi kode sebagai tugas.

## 4. Peta tujuh fase PTES ke lab dan pertanyaan

| Fase PTES | Tanggal utama | Hasil yang diserahkan | Pertanyaan |
| --- | --- | --- | --- |
| 1. Pre-engagement interactions | 20–23 Nov | Scope, ROE, rencana uji, **tanda tangan mulai** | Q1–Q2 |
| 2. Intelligence gathering | 24–25 Nov | Peta aset, endpoint, teknologi, peran | Q3–Q4 |
| 3. Threat modeling | 26–27 Nov | Aset prioritas, trust boundary, misuse case | Q5–Q6 |
| 4. Vulnerability analysis | 30 Nov–2 Des | Hipotesis dan triase tiga kondisi khusus target | Q7–Q8 |
| 5. Exploitation | 3–8 Des | Tiga bukti minimum khusus target | Q9–Q11 pada kartu target |
| 6. Post exploitation | 9–11 Des | Dampak terbatas, validasi ulang, cleanup | Q12–Q13 |
| 7. Reporting + retest kursus | 14–18 Des | Rekomendasi, retest, laporan dan presentasi | Q14–Q15 |

Format setiap task: deskripsi situasi, tujuan, aktivitas, pertanyaan, bentuk bukti, lalu hint konsep dan hint area target. Q1–Q7 dan Q12–Q15 memakai panduan bersama; **Q8–Q11 berbeda menurut kartu target**. Setiap target memiliki tiga bukti unik yang dapat dicocokkan dengan manifest instruktur. Bukti tanpa kondisi awal, hasil aktual/semestinya, dan penjelasan kontrol tidak menjadi temuan lengkap.

## 5. Tanda tangan sebagai gerbang akses

1. Pada 20 November tim menyusun **formulir ROE**: host/port dalam scope, akun, jendela waktu, teknik yang diizinkan, batas pembuktian, larangan, kontak penghentian, dan cara menangani bukti.
2. Pada hari mentorship 23 November, mentor memeriksa formulir dan rencana pengujian. Hanya pengajar/mentor yang namanya ditunjuk sebagai wakil pemilik lab boleh menandatangani otorisasi.
3. Setelah status `Disetujui`, instruktur mengaktifkan akses VPN/instance atau menyerahkan paket lokal serta mencatat waktu aktivasi. Tes aktif dimulai sesudahnya.
4. Jika scope berubah, tim berhenti pada aset baru, mengajukan adendum, dan menunggu persetujuan tertulis untuk perubahan itu.
5. Otorisasi berakhir sesuai jendela waktu di formulir. Presentasi dengan demo aktif pada 21–23 Desember harus tercakup dalam jendela tersebut atau menggunakan persetujuan demo yang dicatat instruktur.

Tim yang formulirnya dikembalikan untuk revisi tetap mengerjakan analisis brief secara offline. Mentor dapat meninjau ulang pada sesi 25 November; akses target tetap tertutup sampai status `Disetujui` tercatat. Tracker progres perlu menampilkan alasan revisi dan waktu tinjau ulang agar keterlambatan izin tidak disamakan dengan keterlambatan pengerjaan teknis.

Tanda tangan berfungsi sebagai **kontrol operasional** dan bukti bahwa peserta memahami mandat uji. Status progres mahasiswa dapat dilacak dengan ID tim, versi ROE, nama penanda tangan, waktu WIB, dan status akses, tanpa mencantumkan password dalam tracker.

## 6. VPS terpusat dan Docker lokal

| Aspek | VPS/lab server terpusat, target berupa Compose | Docker Compose pada laptop peserta |
| --- | --- | --- |
| Pengendalian akses sesudah tanda tangan | VPN, akun, dan routing diaktifkan per tim; dapat dihentikan instruktur | Paket/registry baru dirilis sesudah tanda tangan; salinan yang sudah diunduh tidak bisa ditarik kembali |
| Konsistensi target | Satu lingkungan yang dipatok, reset dan patch dikelola instruktur | Bergantung pada OS, arsitektur CPU, versi Docker, dan resource laptop |
| Integritas tantangan | Manifest serta marker dapat tetap di sisi server | Pemilik laptop dapat memeriksa image dan fixture; jawaban flag kurang kuat sebagai bukti |
| Pemantauan dan bantuan | Log, health check, snapshot, dan suspend terpusat | Perlu log/diagnostik yang dikirim tim; dukungan lebih bervariasi |
| Risiko jaringan | Kesalahan firewall/VPN dapat mengekspos target; isolasi antar tim wajib diuji | Kesalahan port binding dapat mengekspos target dari laptop; egress dan bridge perlu diuji |
| Biaya utama | Sewa host, storage, backup, VPN, operasi instruktur | Kapasitas laptop mahasiswa dan waktu dukungan teknis |

**Rekomendasi:** distribusikan **paket target yang ditugaskan saja** setelah tanda tangan, jika pilot pada laptop 8 GB berhasil. Sediakan host jarak jauh untuk perangkat 4 GB, virtualisasi tidak tersedia, arsitektur tidak didukung, atau modul CVE KampusMart yang gagal uji lokal. Penilaian tetap menekankan reproduksi dan analisis karena image lokal dapat diperiksa pemiliknya. Ini keputusan desain bersyarat, belum hasil benchmark.

### Spesifikasi awal host jarak jauh untuk stack ringan

**Setiap** target dibatasi sekitar **1,5 vCPU, 1 GB RAM container, dan <5 GB image+volume per kelompok** dengan SQLite tertanam; tidak ada laptop yang menjalankan ketiganya bersamaan. Untuk tiga kelompok saat ini, pilot VPS **8 vCPU, 16 GB RAM, 200 GB NVMe** sebagai titik awal, lalu ukur beban nyata. Ini **estimasi untuk stack ringan yang belum dibenchmark**, bukan ukuran pembelian final.

| Instance VAPT aktif | VPS awal yang layak dipilot | Storage/operasi |
| ---: | --- | --- |
| **3 target berbeda, satu instance per kelompok** | **8 vCPU, 16 GB RAM, 200 GB NVMe** | Backup di storage terpisah; ukur beban serentak sebelum menyewa |

Pilih Linux host terpelihara, uplink minimal 1 Gbps, firewall provider dan host, VPN/jaringan kelas, monitoring CPU/RAM/disk/koneksi, backup/snapshot terpisah, serta akses admin terpisah dari jalur peserta. Tidak ada target rentan yang mendengarkan langsung pada IP publik. Batasi CPU/RAM/PID tiap stack; database dan layanan CVE hanya pada jaringan yang diperlukan. Hindari container privileged, host networking, Docker socket, dan mount host yang tidak perlu. `internal: true` membantu memutus gateway keluar, tetapi layanan yang juga tersambung ke jaringan lain masih dapat egress; verifikasi jalur jaringan pada host. [Docker Compose networking](https://docs.docker.com/compose/how-tos/networking/).

Tabel mengasumsikan beberapa stack Compose ringan pada satu VPS, bukan VM per tim. Untuk batas antar tim yang lebih kuat, gunakan VM/VPS tersendiri dan hitung ulang overhead. Jika satu VPS berbagi kernel antartim, batasi layanan CVE pada baca marker, harden container, dan uji isolasi secara eksplisit. Ukur beban **ketiga target berbeda secara serentak** dan reset beberapa tim sebelum memilih paket sewa.

### Paket Docker lokal pada laptop peserta

**Minimum bersyarat:** CPU 4 core, RAM 8 GB, ruang kosong ≥20 GB, dan Docker/virtualisasi berfungsi. **Nyaman:** RAM 16 GB dan ruang kosong ≥30 GB. Batasi target gabungan sekitar **1,5 vCPU dan 1 GB RAM**, dengan image+volume <5 GB. Browser dan alat HTTP/proxy menggunakan RAM di luar target. Pada Windows/macOS, periksa kapasitas VM Docker Desktop; untuk perangkat ARM, sediakan image multi-arsitektur atau fallback jarak jauh. Syarat 8 GB baru ditetapkan setelah pilot pada perangkat representatif.

Paket **masing-masing target** perlu menyediakan image bertag dan berdigest tetap, `compose.yaml`, fixture sintetis khusus kelompok, lembar scope, health check, reset, versi patched untuk retest, serta petunjuk troubleshooting. Bind antarmuka target ke localhost saja, batasi egress, hindari `network_mode: host` dan mount sensitif, dan uji dari perangkat lain pada jaringan yang sama. Rahasia penilaian tidak boleh mengandalkan file yang tersembunyi di image lokal. Rilis paket/kunci akses hanya setelah tanda tangan ROE, lalu nilai reproduksi serta analisis sebagai bukti utama.

## 7. Rubrik 100 poin

| Komponen | Poin | Bukti |
| --- | ---: | --- |
| Pre-engagement dan kepatuhan ROE | 10 | Scope, rencana, tanda tangan, disiplin batas uji |
| Intelligence gathering | 10 | Peta endpoint, peran, teknologi, fakta vs asumsi |
| Threat modeling | 10 | Aset, trust boundary, misuse case dan prioritas |
| Vulnerability analysis | 15 | Hipotesis, advisory/prasyarat, triase false positive |
| Exploitation terbatas | 20 | Tiga bukti sintetis dengan kondisi dan hasil terukur |
| Post exploitation aman | 10 | Dampak proporsional, batas akses, cleanup/reset |
| Reporting dan retest | 15 | Laporan, akar masalah, rekomendasi, hasil sebelum/sesudah |
| Pemeriksaan individual | 10 | Setiap anggota menjelaskan bukti atau keputusan yang dikerjakan |
| **Total** | **100** |  |

**Gerbang wajib:** belum ada pengujian aktif tanpa form bertanda tangan. Temuan scanner baru dinilai setelah reproduksi dan validasi dampak. Jumlah request, jumlah payload, dan kecepatan menjawab bukan metrik nilai.

## 8. Timeline harian, 20 November–23 Desember 2026

Kolom mentorship mengikuti kalender yang diberikan. Tanggal dan hari telah diperiksa terhadap kalender 2026. Durasi sesi dan jumlah slot presentasi belum ditetapkan.

| Tanggal | Mentor | Fase dan pekerjaan peserta | Output |
| --- | --- | --- | --- |
| **Jumat, 20 Nov** | Ya | Pre-engagement: brief, scope, ROE, rencana uji; tanpa tes aktif | Draf form dan Q1–Q2 |
| **Senin, 23 Nov** | Ya | Review scope/ROE dan minta tanda tangan; akses dibuka setelah disetujui | **G0: izin mulai bertanda tangan** |
| Selasa, 24 Nov | Tidak | Intelligence gathering pada host yang disetujui | Peta aplikasi awal |
| Rabu, 25 Nov | Ya | Validasi peta endpoint, akun, teknologi bersama mentor | Q3–Q4 dan peta final |
| Kamis, 26 Nov | Tidak | Threat modeling: aset, alur, trust boundary | Draf model ancaman |
| Jumat, 27 Nov | Ya | Review misuse case dan prioritas uji | **G1: Q5–Q6/model ancaman** |
| Senin, 30 Nov | Ya | Vulnerability analysis: tiga hipotesis khusus target masing-masing | Daftar kandidat dan bukti awal |
| Selasa, 1 Des | Tidak | Periksa rujukan teknis, prasyarat, kontrol akses dan aturan bisnis target sendiri | Matriks validasi kandidat |
| Rabu, 2 Des | Ya | Tinjau false positive dan rencana bukti aman | **G2: Q7–Q8/rencana eksploitasi terbatas** |
| Kamis, 3 Des | Tidak | Exploitation: bukti pertama sesuai kartu target, efek minimum | Evidence kondisi 1 |
| Jumat, 4 Des | Ya | Mentor memeriksa reproduksi bukti pertama dan batasnya | Q9 dan draf temuan 1 |
| Senin, 7 Des | Ya | Exploitation: kondisi kedua dan ketiga sesuai kartu target | Evidence kondisi 2–3 |
| Selasa, 8 Des | Tidak | Ulang tiga kasus pada state bersih; sanitasi bukti | **G3: Q10–Q11/tiga bukti minimum** |
| Rabu, 9 Des | Ya | Post exploitation: ukur dampak sebatas data sintetis | Draf impact statement |
| Kamis, 10 Des | Tidak | Verifikasi batas akses, reset, cleanup dan keadaan instance | Q12–Q13 serta log cleanup |
| Jumat, 11 Des | Ya | Mentor memvalidasi temuan, dampak, dan rencana perbaikan | **G4: tiga temuan tervalidasi** |
| Senin, 14 Des | Ya | Reporting: rekomendasi dan retest pada patched build/config | Tabel sebelum/sesudah |
| Selasa, 15 Des | Tidak | Selesaikan laporan dan retest, catat keterbatasan | Q14–Q15 dan draf final |
| Rabu, 16 Des | Ya, gladi | Latihan presentasi teknis dan demo bukti aman | Koreksi demo/slide |
| Kamis, 17 Des | Ya, gladi | Latihan ringkasan manajemen dan tanya jawab individual | Revisi laporan |
| Jumat, 18 Des | Ya, gladi | Simulasi penuh, pembekuan bukti, pengumpulan akhir | **G5: laporan, evidence log, slide final** |
| Senin, 21 Des | Pengujian | Presentasi dan pengujian sesuai slot | Penilaian tim/individu |
| Selasa, 22 Des | Pengujian | Presentasi dan pengujian sesuai slot | Penilaian tim/individu |
| Rabu, 23 Des | Pengujian | Presentasi dan pengujian sesuai slot | Penilaian selesai |

Alokasi slot 21–23 Desember dibuat setelah jumlah tim VAPT diketahui. Untuk pilot jadwal, 30–40 menit per tim dapat dibagi ke paparan, demo retest, tanya jawab individual, dan pergantian; durasi akhir mengikuti waktu kelas nyata.

## 9. Kesiapan instruktur dan keputusan yang masih terbuka

Sebelum 20 November: survei OS/RAM/arsitektur laptop ketiga kelompok, siapkan host fallback, pihak yang boleh menandatangani, dan jendela uji. Bangun **tiga aplikasi terpisah** dengan alur normal dan temuan masing-masing; gunakan harness bersama hanya untuk distribusi, log, reset, dan health check. Pin image, siapkan marker khusus target dan patched build. Lakukan blind pilot **untuk tiap target pada laptop 8 GB yang representatif**; bandingkan tingkat kesulitan, uji isolasi, egress, reset, health check, ukuran image, serta pemeriksaan Q1–Q15. Layanan Apache rentan hanya milik KampusMart, tanpa CGI dan dengan bukti file marker saja. Bila pilot Apache atau isolasi lokal gagal, pindahkan modul itu ke host terpusat atau gunakan simulasi setara sebelum dibuka untuk peserta.

Jawaban literal Q8–Q11, URL, akun, dan marker belum dapat diisi sampai **ketiga aset** dibuat. Formulir tanda tangan adalah template kosong; ia baru sah setelah diisi dan ditandatangani oleh pihak berwenang. Minimum laptop 8 GB dan sizing host fallback perlu dikoreksi dengan benchmark ketiga stack final.

### Sumber lokal

- `Rencana-Proyek-Akhir-Cybersecurity-KampusMart.pdf` — draft 13 Oktober 2026 yang memberi skenario awal KampusMart dan estimasi lama; rancangan tiga varian A/B/C diganti oleh tiga aplikasi berbeda sesuai keputusan terbaru.
