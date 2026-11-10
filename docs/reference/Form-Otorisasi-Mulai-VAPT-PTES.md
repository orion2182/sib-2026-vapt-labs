# Formulir scope, ROE, dan otorisasi mulai VAPT

**Tiga target berbeda — final project SIB 2026**  
**Fase PTES:** Pre-engagement interactions  
**Status:** template kosong; berlaku setelah diisi dan ditandatangani pihak berwenang.

## A. Identitas engagement

| Kolom | Isian |
| --- | --- |
| ID kelompok VAPT (1 / 2 / 3) |  |
| Nama target yang ditugaskan (KampusMart / PerpusDigital / EventPass) |  |
| ID formulir dan versi |  |
| Mode lab (Docker lokal / host jarak jauh) |  |
| Ketua tim / anggota (atau ID peserta) |  |
| Pemilik lab / pengajar penanggung jawab |  |
| Mentor peninjau yang diberi wewenang |  |
| Jendela pengujian aktif (tanggal, jam, WIB) |  |
| Jendela demo aktif 21–23 Desember, bila diperlukan |  |
| Kanal komunikasi dan kontak penghentian |  |

## B. Scope yang diminta

| Aset yang diizinkan | Host/port atau ID instance | Akun/peran uji tanpa password | Kegiatan yang diizinkan |
| --- | --- | --- | --- |
| Web/API target kelompok |  |  |  |
| Layanan tambahan, jika ada (KampusMart saja) |  |  |  |
| Data/objek sintetis kelompok |  |  |  |

**Aset di luar scope:** sistem kampus nyata, internet publik, instance/akun tim lain, data pribadi nyata, serta host/port yang tidak tercantum di atas.

**Teknik yang diizinkan:** pemetaan browser/proxy pada host tercantum, pemeriksaan rujukan teknis, dan tiga uji terbatas sesuai kartu target kelompok yang ditugaskan. Sebutkan eksplisit pada bagian B objek/akun, bukti minimum, reset, dan retest yang disediakan instruktur. Untuk KampusMart, pembacaan satu marker CVE sintetis; untuk PerpusDigital, satu record penanda katalog; untuk EventPass, satu indikator DOM lokal tanpa pengiriman data.

**Batas pengujian:** tidak ada brute force, denial of service, persistence, lateral movement, eksekusi kode pada host/modul CVE, pengambilan file selain marker yang ditentukan, exfiltration, atau perubahan data di luar fixture sintetis kelompok. Pembuktian berhenti setelah bukti minimum diperoleh.

## C. Rencana dan pengendalian risiko

| Item | Isian tim |
| --- | --- |
| Urutan fase PTES dan perkiraan tanggal |  |
| Alat dan intensitas/rate yang akan dipakai |  |
| Cara mencatat request/response tanpa token atau password |  |
| Cara reset dan memverifikasi health check |  |
| Kondisi yang mengharuskan penghentian segera |  |
| Cara melaporkan salah konfigurasi atau akses di luar scope |  |
| Lokasi penyimpanan evidence log/laporan yang disetujui |  |

**Pemicu berhenti minimum:** target mengarah keluar scope, muncul data nyata, trafik keluar atau gangguan pada tim lain, atau instance tidak stabil. Tim menghentikan tes dan menghubungi kontak pada bagian A.

## D. Pernyataan tim dan keputusan pengajar

Tim menyatakan telah membaca scope dan ROE, akan menguji hanya pada jendela yang disetujui, serta akan meminta adendum tertulis sebelum menguji aset atau teknik baru.

| Persetujuan | Nama/ID | Tanda tangan | Waktu WIB |
| --- | --- | --- | --- |
| Ketua tim |  |  |  |
| Perwakilan anggota tim / daftar pengakuan seluruh anggota |  |  |  |
| Mentor peninjau |  |  |  |
| **Pengajar/pemilik lab atau mentor yang didelegasikan untuk memberi izin** |  |  |  |

**Keputusan:** ☐ Disetujui untuk mulai VAPT ☐ Dikembalikan untuk revisi  
**Waktu keputusan (WIB):** __________  
**Versi scope yang disetujui:** __________  
**ID aktivasi akses/instance oleh instruktur:** __________  
**Catatan atau syarat tambahan:** __________________________________________

Persetujuan hanya berlaku untuk aset, teknik, dan jendela waktu yang tertulis. Jika scope atau jendela berubah, catat perubahan sebagai versi/adendum baru dan minta tanda tangan ulang sebelum kegiatan baru dimulai. Berkas ini disimpan instruktur bersama tracker progres tanpa mencantumkan kredensial.
