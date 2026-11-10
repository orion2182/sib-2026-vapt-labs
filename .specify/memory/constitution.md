<!--
Sync Impact Report (review scratch; remove before first commit)
Version: template → 1.0.0
Principles: five placeholders replaced by target separation, bounded flaws, PTES traceability,
deterministic isolation, and lightweight reproducibility.
Sections: Lab Constraints and Delivery Gates added from template placeholders.
Deferred placeholders: none.
-->
# VAPT Labs 2026 Constitution

## Core Principles

### I. Three genuinely distinct targets

KampusMart, PerpusDigital, and EventPass MUST be separate applications with different domain
flows, data models, routes, and intended findings. They MUST NOT be cosmetic variants of one
application. Shared code is limited to distribution, seed/reset, health checks, and logging.
This keeps each group's assessment substantively different.

### II. Deliberate flaws are bounded and reversible

Every intended flaw MUST have an instructor-only definition, a synthetic minimum proof, a
clear stop condition, a reset path, and a patched counterpart. The vulnerable build MUST
preserve only the specified flaw; unrelated defects MUST be fixed. Tests MUST show the
vulnerable behavior exists in the lab build and is rejected by the patched build while the
legitimate workflow still succeeds. No lab task may require real data, host code execution,
credential theft, exfiltration, persistence, or access to another group's instance.

### III. PTES evidence drives the product

Each target MUST support the same seven PTES phases and the agreed 20 November–23 December
2026 schedule. The student path MUST provide normal behavior, Q1–Q15, three target-specific
proofs, sanitized evidence, reporting, and retest. Active testing MUST be gated by a signed
Rules of Engagement form. A marker alone MUST NOT count as a complete finding.

### IV. Deterministic, isolated instances

Each group MUST receive only its assigned target with synthetic accounts and stable markers.
Reset MUST restore the initial fixture, and patched builds MUST support an equivalent retest.
Target ports MUST bind to localhost for laptop deployment. Remote deployment MUST place
instances behind controlled access and verify isolation and egress. Instructor keys MUST
remain outside student packages.

### V. Lightweight and inspectable delivery

Each laptop MUST run only one target. The design budget is at most 1 GB RAM and 1.5 vCPU for
its containers, with under 5 GB for image, volume, and patched build. Eight GB host RAM is
a conditional minimum, not a promise; blind pilots on representative devices MUST confirm
it. Every target MUST provide pinned build inputs, a start/stop/reset/health-check procedure,
and a remote fallback when the local pilot fails.

## Lab Constraints

The source repository and instructor answer keys are private to builders. Participants
receive a target-specific image/fixture, their target card, the common PTES workbook, and
their approved ROE only. No production services, live campus systems, real credentials,
or personal data may be included in fixtures. Apache CVE behavior, if retained, is isolated
to KampusMart and proves access only to an approved synthetic marker.

The three target-specific weakness sets are recorded in the instructor architecture
reference. Changes to those sets MUST update the corresponding spec, student card,
instructor key, and patched acceptance scenarios together.

## Delivery Gates

Before implementation, each feature spec MUST define normal user journeys, the three
bounded conditions, measurable success criteria, and exclusions. Before release, a reviewer
MUST verify normal flows, each minimum proof, reset, patched retest, network isolation,
resource use, and the student-facing Q8–Q11. A blind pilot MUST compare time to proof,
points of confusion, and retest effort across all three targets. Release is blocked until
the ROE process and one-target-per-group distribution are verified.

## Governance

This constitution governs the shared harness and all three target features. An amendment
requires a documented reason, impact on existing specs/cards, and instructor review before
implementation. Use semantic versioning: MAJOR for incompatible principle changes, MINOR
for new or expanded principles, PATCH for clarifications. Review compliance at spec,
plan, task, and release gates. The instructor research and architecture documents in the
parent project are source context; this constitution is the active build contract.

**Version**: 1.0.0 | **Ratified**: 2026-10-26 | **Last Amended**: 2026-10-26
