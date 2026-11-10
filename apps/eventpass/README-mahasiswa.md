# EventPass — paket lab VAPT (Kelompok VAPT-3)

Platform acara kampus fiktif. Semua peserta, penyelenggara, acara, tiket, ulasan,
dan marker bersifat sintetis. Paket ini hanya untuk instance lokal kelompok VAPT-3.

> **Batas akses.** Mulai pengujian aktif hanya setelah ROE ditandatangani.
> Pengujian hanya pada `127.0.0.1` atau lingkup yang tertulis di ROE.

## Prasyarat

- Windows 10/11, PowerShell 5.1+.
- PHP 8.1+ (`pdo_sqlite`, `curl`, `json`, `hash`, `session`). XAMPP terdeteksi otomatis.
- Tidak perlu Docker, Python, Node.js, atau browser otomatis.

## Menjalankan

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action start -Port 8083
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action health -Port 8083
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action reset -Port 8083
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action stop
```

Buka `http://127.0.0.1:8083/`.

## Akun sintetis

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Peserta | `laras@eventpass.test` | `LabPass!2026` |
| Peserta | `dimas@eventpass.test` | `LabPass!2026` |
| Penyelenggara | `panitia@eventpass.test` | `StaffPass!2026` |

## Alur normal

1. Lihat daftar acara dan detail acara.
2. Ambil tiket, lalu ubah nama tampilan pada tiket.
3. Tulis ulasan biasa.
4. Sebagai penyelenggara, lakukan check-in pertama dengan kode tiket.
5. Sebagai penyelenggara, lihat laporan acara.

## Titik masuk

| Metode | Path | Keterangan |
| --- | --- | --- |
| GET | `/events`, `/event?id=` | Acara dan ulasan |
| POST | `/login`, `/logout` | Sesi |
| GET | `/tickets` | Tiket sendiri |
| POST | `/tickets/create` | Ambil tiket (`event_id`) |
| POST | `/tickets/update` | Ubah field tiket (`ticket_id`, field) |
| POST | `/reviews` | Simpan ulasan (`event_id`, `body`) |
| POST | `/checkin` | Check-in (`ticket_code`, penyelenggara) |
| GET | `/organizer/event?id=` | Laporan acara |
| GET | `/healthz` | Health check |

## Bukti yang diserahkan

Untuk tiap kondisi: kondisi awal, satu tindakan minimum, hasil aktual dan
seharusnya, akun/objek sintetis, evidence ID, batas klaim, dan hasil retest.
Untuk indikator DOM, lampirkan cuplikan HTML tersanitasi dan pastikan tidak ada
permintaan jaringan keluar.

## Troubleshooting

| Gejala | Tindakan |
| --- | --- |
| `PHP 8.1+ not found` | Pasang XAMPP/PHP atau tambahkan ke `PATH`. |
| Port dipakai | Ganti `-Port 8084`. |
| `running scripts is disabled` | Gunakan `powershell -ExecutionPolicy Bypass -File ...`. |
| Health gagal | Lihat `run\server.err.log`. |
| State ingin diulang | `-Action reset`, lalu ulangi dari kondisi bersih. |
