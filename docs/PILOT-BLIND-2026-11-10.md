# VAPT blind dry pilot — 10 November 2026

## Method

- Three independent reviewers, one per target. Each received **only** the
  participant package (extracted outside the builder repository) and the student
  card. They were told not to read the builder repo, other packages, or test
  files.
- This is an **agent-assisted grey-box dry pilot**, not a human student pilot.
  PHP packages ship source, so a reviewer could inspect it; the reviewers were
  asked to use the running instance and to report when they inspected source.
- Environment: Windows 11, 7.8 GB RAM, PHP 8.2.4, PowerShell 5.1.
- Ports: 8201 (KampusMart), 8202 (PerpusDigital), 8203 (EventPass).
- Each reviewer reset and re-ran one proof, recorded evidence IDs, steps,
  confusion points, and difficulty 1–5.

## Results

| Target | Wall clock | HTTP actions | False starts | Proofs found | Difficulty (Q8/Q9/Q10/Q11) |
| --- | ---: | ---: | ---: | ---: | --- |
| KampusMart | 3 m 30 s | 40 | 3 | 3/3 | 3 / 3 / 2 / 2 |
| PerpusDigital | 10 m 30 s | ~86 | 5 | 3/3 | 4 / 4 / 2 / 2 |
| EventPass | 6 m 00 s | 26 | 3 | 3/3 | 2 / 3 / 3 / 1 |

All three proofs were reproducible after reset.

## Defects found and fixed

| # | Finding | Fix |
| --- | --- | --- |
| 1 | `start`/`reset` failed with "Another lab instance answered" when the package folder was renamed; health compared `build_id` to the folder name | Compare against `build.json` `build_id` instead |
| 2 | Evidence IDs became unverifiable after reset | `lab_reset()` archives and restores evidence rows and records a `lab.reset` event; verified: old ID still present after reset |
| 3 | Q9 marker was undiscoverable from participant docs; `welcome.txt` listed a missing document | Added `/legacy/info`, added the missing guide, corrected the listing |
| 4 | Evidence log labelled anomalies (`cross_owner: true`, `private_rows`) | Removed those fields; only reason/result remain |
| 5 | PerpusDigital Q10 had no staff metadata UI | Added `/staff/books` + staff-only nav; members get 403 server-side |
| 6 | Unknown metadata fields returned `ok:true` but changed nothing | Unknown fields now return 400 `unknown_metadata_field` |
| 7 | EventPass had no ticket-edit UI and no operational DOM definition | Added display-name form on `/tickets`; clarified DOM indicator and check-in invariant in the card |
| 8 | Evidence endpoints were undocumented | Added `/staff/evidence` and `/organizer/evidence` to the READMEs |

## Calibration

- **PerpusDigital is the hardest target** (10.5 min, Q8/Q9 = 4). The search
  validation rejects odd quote counts, so a naive payload fails first. This is
  intentional; keep one generic hint in the card ("if the search rejects input,
  inspect the syntax validation and use a syntactically valid form") rather than
  revealing the bypass.
- **KampusMart is medium** (3.5 min, Q8/Q9 = 3). After adding `/legacy/info` and
  the real guide, Q8/Q9 should drop; re-measure in the human pilot.
- **EventPass is the easiest** (6 min, Q11 = 1). Q11 is a straightforward state
  transition; keep it as the confidence-building proof. Q10/Q9 remain 3.
- Spread (1–4) is acceptable for three groups, but only a human pilot can confirm
  the perceived difficulty and time budget.

## Resource measurements (same host)

| Target | Start | Reset | RSS | Private | Dist | DB |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| KampusMart | 1.83 s | 1.27 s | 21.2 MB | 9.2 MB | 0.14 MB | 60 KB |
| PerpusDigital | 1.08 s | 1.18 s | 21.0 MB | 9.2 MB | 0.10 MB | 36 KB |
| EventPass | 1.09 s | 1.15 s | 21.0 MB | 9.3 MB | 0.11 MB | 48 KB |

Idle CPU was below the sampling resolution (0% of one core over 1.5 s). The design
budget is 768 MB RAM and 1.5 vCPU; the measured footprint is far below it.

## Limits

- Agent reviewers share the builder's model and may overperform humans on
  structured HTTP tasks.
- VAPT packages are grey-box; source inspection was allowed.
- No browser-based DOM execution was observed for EventPass; the proof is
  structural (unescaped HTML in the response).
- A human student pilot is still required before release.
