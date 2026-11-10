# Feature Specification: Shared VAPT Lab Harness

**Feature Branch**: `main` (planning baseline)  
**Created**: 2026-10-26  
**Status**: Ready for planning  
**Input**: Three separate PTES lab targets need consistent packaging, access, reset, evidence, and retest.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Instructor assigns one target (Priority: P1)

An instructor prepares a package for one VAPT group and releases it only after the group's
ROE is signed. The group receives only its assigned application and student card.

**Why this priority**: Target separation and signed scope are core to the course design.

**Independent Test**: Prepare all three assignments and inspect each package and release
record; no package contains another group's application or instructor key.

**Acceptance Scenarios**:

1. **Given** an unsigned ROE, **When** the release checklist is reviewed, **Then** the
   package remains withheld.
2. **Given** an approved ROE for group 2, **When** its package is prepared, **Then** it
   contains PerpusDigital only, with the correct card and synthetic fixture.

### User Story 2 - Student runs and resets a lab (Priority: P1)

A student starts the assigned target, checks readiness, works with synthetic accounts, and
resets the instance to the same initial state without knowing internal implementation.

**Why this priority**: Reproducibility is required for evidence and mentor support.

**Independent Test**: Start, change a synthetic object, reset, and compare accounts,
markers, object state, and health with the initial fixture.

**Acceptance Scenarios**:

1. **Given** a fresh package, **When** the student starts it, **Then** the target becomes
   ready within the pilot threshold and normal login works.
2. **Given** an altered fixture, **When** reset completes, **Then** initial state and
   stable group markers return, and health is reported.

### User Story 3 - Instructor releases patched retest (Priority: P2)

After the proof phase, an instructor provides the matching patched build so the group can
repeat the same three scenarios and confirm legitimate flows still work.

**Why this priority**: Reporting requires retest evidence rather than a one-time flag.

**Independent Test**: Switch builds on equivalent seeded state and verify evidence can be
compared by target, account, object, and scenario ID.

**Acceptance Scenarios**:

1. **Given** the original fixture, **When** the patched build is run, **Then** its
normal flows work and its three designated minimum proofs no longer succeed.

### Edge Cases

- A target does not start, a reset is interrupted, or a port is already occupied.
- A laptop lacks required RAM, supported architecture, or container runtime.
- A package is copied to an unintended group or an old patched/vulnerable build is mixed.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The instructor MUST be able to produce one distinct package per target.
- **FR-002**: Package contents MUST exclude instructor keys and other target sources/images.
- **FR-003**: Every package MUST provide documented start, stop, reset, and health actions.
- **FR-004**: Reset MUST restore deterministic synthetic accounts, objects, and markers.
- **FR-005**: Every request or state-changing event MUST have an evidence ID without logging
  passwords, session tokens, or real personal data.
- **FR-006**: A vulnerable build and matching patched build MUST be identifiable and usable
  with equivalent fixtures.
- **FR-007**: Local service access MUST be limited to the host device; remote instances MUST
  require controlled access and separate group instances.
- **FR-008**: Release instructions MUST require recorded ROE approval before active access.
- **FR-009**: An instructor MUST have a remote fallback for a device that fails local pilot.

### Key Entities

- **Target package**: assigned target, version, checksums, student card, and run instructions.
- **Group fixture**: synthetic accounts, objects, stable markers, and reset baseline.
- **Release record**: group, target, ROE version/status, release time, and build version.
- **Evidence event**: request/state ID, time, role, result, with secrets excluded.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All three packages contain one target each and no instructor answer key.
- **SC-002**: On representative pilot devices, a target starts in under 3 minutes and
  resets in under 2 minutes.
- **SC-003**: Each running target stays below the pilot budget of 1 GB container RAM and
  1.5 vCPU; image, volume, and patched build together use under 5 GB.
- **SC-004**: A second device on the same LAN cannot reach a local target port.
- **SC-005**: Reset reproduces the same three synthetic proof identifiers and normal state.

## Assumptions

- Three VAPT groups each receive one target. The instructor controls package release.
- Eight GB host RAM is a conditional minimum pending a representative pilot.
- The approved ROE is a signed document; automatic signature collection is outside scope.
