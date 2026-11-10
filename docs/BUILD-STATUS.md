# Build status — VAPT labs

Updated 2026-11-10. This file records what actually runs, not what is planned.

## Implemented and verified

### Shared harness

- `shared/harness/lib.php`: state paths, SQLite, deterministic seed/reset,
  evidence log (no secrets), sessions, JSON helpers, request IDs.
- `tools/lab.ps1`: build, start, stop, reset, health, test, package, status.
- `tools/reset.php`: quoting-safe reset entry point.
- Build output includes `build.json` (target, variant, commit, PHP version) and
  `manifest-sha256.txt`.
- Local bind is `127.0.0.1`; packaging excludes `run/`.

### KampusMart (feature 002)

- Normal flows: catalog, cart, order, invoice, coupon, staff status, evidence.
- Three bounded conditions:
  1. legacy document service path traversal exposes one synthetic marker;
  2. cross-account invoice read (IDOR);
  3. coupon reuse beyond `max_uses`.
- Patched build rejects all three while normal flows pass.
- Tests: `apps/kampusmart/tests/e2e.php`, 29 checks per variant.
- Student package verified standalone on port 8085.
- Pilot numbers: `docs/PILOT-2026-11-10.md`.
- One-target-per-laptop is enforced by a port-in-use check and a build-id match
  on the health response.

### PerpusDigital (feature 003)

- Normal flows: search, borrow, return, fine list, staff metadata update,
  staff waiver of an eligible returned-loan fine.
- Three bounded conditions:
  1. search-term SQL injection exposes one private archive record and marker;
  2. a member can change book metadata through a staff-only route;
  3. a fine can be waived while its loan is still active.
- Patched build uses a prepared statement, enforces the staff role on the server,
  and requires a returned loan before an atomic waiver.
- Tests: `apps/perpusdigital/tests/e2e.php`, 24 checks (vulnerable) and 25
  (patched, including the member-forbidden check).
- Port-conflict handling was added to `tools/lab.ps1` after a stale server on the
  same port produced a false healthy reading.

### EventPass (feature 004)

- Normal flows: event list/detail, ticket creation, allowed display-name update,
  review posting, first check-in, organizer report.
- Three bounded conditions:
  1. a stored review renders an inert local DOM indicator in a second account;
  2. an attendee can set an organizer-controlled ticket tier and price;
  3. the same ticket can be checked in twice.
- Patched build encodes review text, rejects forbidden ticket fields, and performs
  a one-time atomic check-in transition.
- Tests: `apps/eventpass/tests/e2e.php`, 25 checks per variant.
- The DOM payload is inert (`onerror` sets a `data-` attribute); no network call.

## Theme status

All three VAPT targets are implemented, packaged, and tested against vulnerable
and patched builds. What remains is not implementation of the three targets:
a blind student pilot, cross-target difficulty calibration, and the formal
release checklist (ROE process, per-group distribution record, mentor dry run).

## Not built yet

- Blind pilot and difficulty calibration across the three targets.
- Remote fallback deployment guidance for devices that fail the local pilot.
- Optional Apache sidecar variant for KampusMart (see `docs/DECISIONS.md`).
- Cross-target difficulty calibration and blind pilot.
- Optional Apache sidecar for KampusMart: deliberately not used. The legacy file
  service reproduces the bounded file-read lesson without a privileged container
  or a real CVE, which the architecture document allows as an optional sidecar.
  See `docs/DECISIONS.md`.

## How to verify the implemented part

```powershell
.\tools\lab.ps1 -Action package -Target kampusmart -Variant vulnerable
.\tools\lab.ps1 -Action build   -Target kampusmart -Variant patched
.\tools\lab.ps1 -Action start   -Target kampusmart -Variant patched -Port 8081
.\tools\lab.ps1 -Action test    -Target kampusmart -Variant patched -Port 8081
```
