# VAPT Labs 2026 — contributor instructions

- Read `.specify/memory/constitution.md` and the active `specs/*/spec.md` before changing code.
- This is a public builder repository for three intentionally different lab apps. It is
  not a student handout. Student distribution contains only the assigned target package,
  common workbook, ROE, and that target's student card.
- Preserve the three bounded intended flaws only in each target's vulnerable build. Fix
  unrelated security defects. Keep a matching patched build and normal-flow checks.
- Use synthetic fixture data only. No live campus systems, real accounts, or real PII.
- Do not run network tests against anything outside a locally started assigned instance
  or an explicitly approved lab scope.
- Implement the shared harness in `shared/` and apps independently under `apps/`.
  Shared logic may cover distribution, seed/reset, health, and logging; domain flows and
  findings belong in the target app.
- Keep instructor answer keys, secret seeds, and private markers outside this public
  repository and outside student packages.
- Treat `docs/reference/` as a snapshot of the source planning documents; update the
  active feature spec when build decisions change.
- The first active Spec Kit feature is `specs/001-shared-lab-harness`. Switch the local
  `.specify/feature.json` pointer before running plan/tasks for a different target.

## OpenCode handoff

- Tiga target dan harness sudah diimplementasikan. Gunakan `docs/BUILD-STATUS.md` dan `docs/WINDOWS-SETUP.md` untuk menjalankan uji; kerjakan pilot buta manusia, kalibrasi, dan rilis peserta berikutnya. Pertahankan kalender 20 November–23 Desember 2026 dan pertanyaan PTES.
- OpenCode membaca file ini saat dijalankan dari root repo. Pada Windows tanpa Python, gunakan `.\scripts\select-feature.ps1 --list` dan pilih fitur sebelum perintah Spec Kit interaktif. `scripts/select_feature.py` hanya alternatif.
- Perubahan target harus tetap lulus alur normal, bukti terbatas, reset, retest patched, isolasi paket, dan pemeriksaan resource. Dry pilot agen tidak menggantikan pilot manusia.
