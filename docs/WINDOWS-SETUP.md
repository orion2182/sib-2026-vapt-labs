# Windows setup for VAPT labs (no Python required)

This machine and the participant laptops can run the labs with **PowerShell,
Git for Windows, and PHP 8.1+**. Python is not needed to bootstrap, build, run,
reset, or test the targets.

## Verified environment (2026-11-10)

| Component | Version | Location |
| --- | --- | --- |
| Windows | 11 Pro | - |
| PowerShell | 5.1.26100 | built in |
| Git for Windows | 2.56.0 | `C:\Program Files\Git\cmd\git.exe` |
| Git Bash | 5.3.15 | `C:\Program Files\Git\bin\bash.exe` |
| PHP (XAMPP) | 8.2.4 | `C:\xampp\php\php.exe` |
| Host RAM | 7.8 GB | representative 8 GB laptop |

Python was **not installed**. The 0-byte `python.exe` in `WindowsApps` is the
Microsoft Store alias, not a runtime. Spec Kit scripts are Bash and run under
Git Bash; the Python helper `scripts/select_feature.py` has a PowerShell
equivalent at `scripts/select-feature.ps1`.

## One-time setup

```powershell
# Git on PATH (User scope; reopen the terminal afterwards)
setx PATH "$env:PATH;C:\Program Files\Git\cmd"

# Allow locally created scripts to run
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
```

PHP is provided by XAMPP. The lab controller searches `php` on `PATH`, then
`C:\xampp\php\php.exe`, then `%ProgramFiles%\php\php.exe`.

## Build and run one target

Only one target runs per laptop. Ports bind to `127.0.0.1`.

```powershell
cd vapt-labs
.\tools\lab.ps1 -Action build  -Target kampusmart -Variant vulnerable
.\tools\lab.ps1 -Action start  -Target kampusmart -Variant vulnerable -Port 8081
.\tools\lab.ps1 -Action health -Port 8081
.\tools\lab.ps1 -Action test   -Target kampusmart -Variant vulnerable -Port 8081
.\tools\lab.ps1 -Action reset  -Target kampusmart -Port 8081
.\tools\lab.ps1 -Action stop
```

Patched retest:

```powershell
.\tools\lab.ps1 -Action stop
.\tools\lab.ps1 -Action build -Target kampusmart -Variant patched
.\tools\lab.ps1 -Action start -Target kampusmart -Variant patched -Port 8081
.\tools\lab.ps1 -Action test  -Target kampusmart -Variant patched -Port 8081
```

## Package one target

```powershell
.\tools\lab.ps1 -Action package -Target kampusmart -Variant vulnerable
# -> packages\kampusmart-vulnerable.zip and .sha256
```

The package contains one target, the student card, the fixture, the harness, and
the start/stop/reset/health/test tooling. It contains no instructor key and no
other target.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| `PHP 8.1+ not found` | Install XAMPP/PHP or add its folder to `PATH`. |
| `running scripts is disabled` | Use `powershell -ExecutionPolicy Bypass -File .\tools\lab.ps1 ...`. |
| Port occupied | Choose another port above 1024. |
| Health fails | Read `dist\<target>-<variant>\run\server.err.log`. |
| Reset fails | Stop the server, then run `-Action reset`. |
| Package will not start | Unpack it fully; `build.json` must sit at the package root. |

## Notes on Python

If a future feature genuinely needs Python, install it from the official source
with winget:

```powershell
winget install --id Python.Python.3.12 -e
python --version
```

Record the version and the reason in the feature notes. Nothing in the current
VAPT harness requires it.
