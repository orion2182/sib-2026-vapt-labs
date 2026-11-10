# VAPT Labs 2026 — contributor instructions

- Read `.specify/memory/constitution.md` and the active `specs/*/spec.md` before changing code.
- This is an instructor-only source repository for three intentionally different lab apps.
  Student distribution contains only the assigned target package, common workbook, ROE,
  and that target's student card.
- Preserve the three bounded intended flaws only in each target's vulnerable build. Fix
  unrelated security defects. Keep a matching patched build and normal-flow checks.
- Use synthetic fixture data only. No live campus systems, real accounts, or real PII.
- Do not run network tests against anything outside a locally started assigned instance
  or an explicitly approved lab scope.
- Implement the shared harness in `shared/` and apps independently under `apps/`.
  Shared logic may cover distribution, seed/reset, health, and logging; domain flows and
  findings belong in the target app.
- Keep instructor answer keys outside student packages. Do not put secrets or markers that
  are intended to stay private in public README files.
- Treat `docs/reference/` as a snapshot of the source planning documents; update the
  active feature spec when build decisions change.
- The first active Spec Kit feature is `specs/001-shared-lab-harness`. Switch the local
  `.specify/feature.json` pointer before running plan/tasks for a different target.
