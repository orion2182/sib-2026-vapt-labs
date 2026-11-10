# Build decisions — VAPT labs

## D-001: PHP built-in server instead of Docker for the first release

**Date:** 2026-11-10
**Status:** accepted for the first milestone

**Context.** The specification allows Docker/Compose but does not require it
before a feature needs it. The available machine has PHP 8.2.4 and no Docker.
The target laptop class is 8 GB.

**Decision.** Each target is a PHP 8.1+ application with embedded SQLite, served
by the PHP built-in server bound to `127.0.0.1`. Start, stop, reset, health, and
test are provided by `tools/lab.ps1`.

**Consequences.** Runtime footprint is far below the 1 GB budget (22 MB measured).
No container isolation is claimed; isolation is by loopback binding, one target
per laptop, and separate packages. If a future requirement needs container-level
isolation, a Compose wrapper can be added without changing domain code.

## D-003: The PHP packages are grey-box, not black-box

**Date:** 2026-11-10
**Status:** accepted for the first release

**Context.** PHP ships as source. A participant can unzip the package and read
`app/`, including the fixture and the flawed branch. A container image would hide
the filesystem layout but not the behaviour, and the architecture document already
notes that a local image can be inspected.

**Decision.** Treat every VAPT package as grey-box. Grading requires a reproducible
request, the resulting state, and the `evidence id` from the lab log, not only a
marker value. The marker is synthetic evidence, not the answer key. Instructor
e2e tests are excluded from packages.

**Consequences.** Source review is a legitimate route to a hypothesis, but a
finding without a demonstrated request and evidence id does not count. If a future
cohort needs black-box conditions, add a container build without changing domain
code.

## D-002: KampusMart uses a bounded legacy document service, not an Apache CVE

**Date:** 2026-11-10
**Status:** accepted for the first milestone

**Context.** The architecture document marks the Apache sidecar as optional and
says the exact affected component is selected after a safety pilot. Running a
real vulnerable Apache build adds a privileged component and a real CVE to a
student laptop.

**Decision.** The first bounded condition is a path-traversal read of exactly one
synthetic marker from a legacy PHP document service. The patched build confines
requests to the public directory.

**Consequences.** Students still reason about configuration, path normalisation,
and a bounded file read. The lesson is not a real CVE reproduction. The optional
Apache sidecar remains a future variant if a pilot shows it is safe and useful.
