# VAPT Labs 2026 — Spec Kit workspace

This local, private Git repository is the **builder workspace** for three separate
student VAPT targets: KampusMart, PerpusDigital, and EventPass. It contains Spec Kit
1.2.3 scaffolding with the Codex integration, a project constitution, four feature
specifications, and snapshots of the agreed planning documents. **The target apps are
not implemented yet.**

## Layout

| Path | Purpose |
| --- | --- |
| `.specify/memory/constitution.md` | Project rules and release gates |
| `specs/001-shared-lab-harness/spec.md` | Packaging, ROE release, reset, logs, retest |
| `specs/002-kampusmart-target/spec.md` | Retail target contract |
| `specs/003-perpusdigital-target/spec.md` | Library target contract |
| `specs/004-eventpass-target/spec.md` | Event target contract |
| `docs/reference/` | Instructor planning snapshot and student card drafts |
| `apps/` | Separate app directories for future implementation |
| `.agents/skills/speckit-*/` | Installed Codex Spec Kit skills |

## Continue with Spec Kit

Open Codex **from this directory**. Spec Kit commands are Codex skills, so type them
in Codex chat, not in the shell. The active feature starts at the shared harness.
For the active feature, run these in order, reviewing each output:

1. `$speckit-plan` — decide the implementation architecture against the existing spec.
2. `$speckit-checklist` — review specification quality.
3. `$speckit-tasks` — generate dependency-ordered implementation tasks.
4. `$speckit-analyze` — check spec, plan, and task consistency.
5. `$speckit-implement` — build the feature.
6. `$speckit-converge` — find and close remaining gaps.

To work on another existing spec, change the local `.specify/feature.json` to exactly
one of the four `specs/...` paths, then run the same sequence. This file is ignored by
Git because active feature selection is local to each developer. Do not rerun
`$speckit-specify` for these existing specs unless you are intentionally creating a
new feature. The order recommended for this project is shared harness, KampusMart,
PerpusDigital, then EventPass. Pilot and compare all three before student release.

## Delivery rules

Only distribute the group's assigned image/fixture, its target card, the common PTES
workbook, and the approved ROE. Do not give students this source repository or the
instructor research/architecture snapshot. Each target must pass normal-flow,
bounded-proof, reset, patched-retest, isolation, and resource checks before release.

The planning snapshot came from the parent directory on 26 October 2026. The active
build contract is the constitution plus the four specs in this repository.
