# KampusMart — paket lab VAPT (Kelompok VAPT-1)

KampusMart adalah toko fiktif kebutuhan kampus. Semua akun, produk, pesanan,
invoice, kupon, dan marker bersifat sintetis. Paket ini **hanya** untuk instance
lokal kelompok VAPT-1.

> **Batas akses.** Jangan mulai pengujian aktif sebelum form Rules of Engagement
> (ROE) kelompok Anda ditandatangani pengajar. Pengujian hanya pada instance yang
> berjalan di laptop ini (`127.0.0.1`) atau lingkup yang tertulis di ROE.

## Prasyarat

- Windows 10/11 dengan PowerShell 5.1 atau lebih baru.
- PHP 8.1+ dengan ekstensi `pdo_sqlite`, `curl`, `json`, `hash`, `session`.
  XAMPP PHP (`C:\xampp\php\php.exe`) terdeteksi otomatis.
- Tidak perlu Docker, Python, Node.js, database server, atau koneksi internet.

## Isi paket

| Path | Fungsi |
| --- | --- |
| `app/` | Aplikasi web/API KampusMart |
| `shared/harness/` | Reset, health, sesi, dan log bukti bersama |
| `seed/fixture.php` | Data sintetis deterministik |
| `tools/lab.ps1` | Start, stop, reset, health, test |
| `build.json` | Identitas build (target, varian, commit, waktu build) |
| `manifest-sha256.txt` | Checksum seluruh berkas paket |
| `README-mahasiswa.md` | Berkas ini |
| `student-card.md` | Kartu target dan pertanyaan Q1–Q15 |

## Menjalankan

Jalankan dari root paket:

```powershell
# Mulai target pada localhost
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action start -Port 8081

# Cek kesehatan
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action health -Port 8081

# Hentikan
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action stop

# Reset ke fixture awal (akun, pesanan, kupon, marker, log bukti)
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action reset -Port 8081

# Uji fungsi normal dan batas pembuktian
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action test -Port 8081
```

`-Target kampusmart -Variant vulnerable` boleh ditambahkan secara eksplisit.
Hanya satu target yang boleh berjalan pada satu laptop.

Buka `http://127.0.0.1:8081/` di browser. Uji API dengan header
`Accept: application/json`.

## Akun sintetis

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Pelanggan A | `aurel@kampusmart.test` | `LabPass!2026` |
| Pelanggan B | `bima@kampusmart.test` | `LabPass!2026` |
| Staf | `sari@kampusmart.test` | `StaffPass!2026` |

Kredensial ini hanya berlaku pada instance lokal dan tidak membuka sistem nyata.

## Alur normal yang harus Anda kuasai lebih dulu

1. Masuk sebagai pelanggan A, buka katalog, tambahkan produk, buat pesanan.
2. Buka invoice pesanan sendiri dan catat marker sintetisnya.
3. Pakai kupon `HEMAT10` satu kali dan bandingkan diskon dengan aturan.
4. Masuk sebagai pelanggan B dan buka invoice milik B.
5. Masuk sebagai staf dan buka daftar seluruh pesanan.

## Titik masuk utama

| Metode | Path | Keterangan |
| --- | --- | --- |
| GET | `/catalog`, `/product?id=` | Katalog |
| POST | `/login`, `/logout` | Sesi |
| POST | `/cart/add`, `/order` | Pesanan |
| GET | `/orders`, `/invoice?id=` | Riwayat dan invoice |
| POST | `/coupon/apply` | Kupon (`code`, `order_id`) |
| GET | `/legacy/files?name=` | Layanan dokumen lama |
| GET | `/staff/orders` | Daftar pesanan (staf) |
| POST | `/staff/order/status` | Ubah status (staf) |
| GET | `/healthz` | Health check |

## Bukti yang harus diserahkan

Untuk setiap kondisi, kumpulkan:

- `request_id` dan `evidence id` dari respons atau `/staff/evidence`;
- kondisi awal (state sebelum tindakan) dan satu tindakan minimum;
- hasil aktual dan hasil yang seharusnya;
- peran/akun yang dipakai dan objek sintetis yang terlibat;
- batas klaim: apa yang **tidak** dapat disimpulkan dari bukti tersebut;
- hasil retest pada build patched dari instruktur.

Jangan menyerahkan kata sandi, cookie sesi, atau token. Jangan mengakses akun,
objek, atau file di luar yang tercantum di ROE.

## Troubleshooting

| Gejala | Tindakan |
| --- | --- |
| `PHP 8.1+ not found` | Pasang XAMPP/PHP, atau tambahkan folder `php` ke `PATH`. Lihat `docs/WINDOWS-SETUP.md` di repo pembangun. |
| `running scripts is disabled` | Gunakan `powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 ...`. |
| Port sudah dipakai | Ganti `-Port 8082` (atau port lain di atas 1024). |
| Health gagal | Lihat `run\server.err.log` dan `run\php-error.log`. |
| State ingin diulang | Jalankan `-Action reset`, lalu ulangi dari kondisi bersih. |
| Ingin memastikan paket utuh | Bandingkan dengan `manifest-sha256.txt`. |
