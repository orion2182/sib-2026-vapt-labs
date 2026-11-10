# PerpusDigital — paket lab VAPT (Kelompok VAPT-2)

Perpustakaan digital fiktif. Semua anggota, buku, pinjaman, denda, dan marker
bersifat sintetis. Paket ini hanya untuk instance lokal kelompok VAPT-2.

> **Batas akses.** Mulai pengujian aktif hanya setelah ROE kelompok ditandatangani.
> Pengujian hanya pada `127.0.0.1` atau lingkup yang tertulis di ROE.

## Prasyarat

- Windows 10/11, PowerShell 5.1+.
- PHP 8.1+ (`pdo_sqlite`, `curl`, `json`, `hash`, `session`). XAMPP terdeteksi otomatis.
- Tidak perlu Docker, Python, Node.js, atau database server.

## Menjalankan

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action start -Port 8082
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action health -Port 8082
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action reset -Port 8082
powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 -Action stop
```

Buka `http://127.0.0.1:8082/`.

## Akun sintetis

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Anggota | `nabila@perpus.test` | `LabPass!2026` |
| Anggota | `raka@perpus.test` | `LabPass!2026` |
| Petugas | `dewi@perpus.test` | `StaffPass!2026` |

## Alur normal

1. Cari buku berdasarkan judul atau penulis.
2. Pinjam satu buku, lalu kembalikan.
3. Lihat daftar pinjaman dan denda sendiri.
4. Sebagai petugas, ubah metadata satu buku.
5. Sebagai petugas, bebaskan denda pada pinjaman yang **sudah dikembalikan**.

## Titik masuk

| Metode | Path | Keterangan |
| --- | --- | --- |
| GET | `/catalog?q=` | Pencarian katalog |
| GET | `/book?id=` | Detail buku |
| POST | `/login`, `/logout` | Sesi |
| POST | `/borrow`, `/return` | Pinjam dan kembali |
| GET | `/loans`, `/fines` | Pinjaman dan denda |
| POST | `/fines/waive` | Pembebasan denda |
| POST | `/staff/book/update` | Ubah metadata (petugas) |
| GET | `/staff/loans` | Semua pinjaman (petugas) |
| GET | `/healthz` | Health check |

## Bukti yang diserahkan

Untuk tiap kondisi: kondisi awal, satu tindakan minimum tersanitasi, hasil aktual
dan seharusnya, peran/objek sintetis, `request_id`/evidence ID, batas klaim, dan
hasil retest patched. Jangan menyerahkan kata sandi atau cookie sesi.

## Troubleshooting

| Gejala | Tindakan |
| --- | --- |
| `PHP 8.1+ not found` | Pasang XAMPP/PHP atau tambahkan ke `PATH`. |
| Port dipakai | Ganti `-Port 8083`. |
| `running scripts is disabled` | Gunakan `powershell -ExecutionPolicy Bypass -File ...`. |
| Health gagal | Lihat `run\server.err.log`. |
| State ingin diulang | `-Action reset`, lalu ulangi dari kondisi bersih. |
