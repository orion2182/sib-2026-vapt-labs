# Feature Specification: PerpusDigital VAPT Target

**Feature Branch**: `main` (planning baseline)  
**Created**: 2026-10-26  
**Status**: Ready for planning  
**Input**: Group 2 needs a library lab with bounded query, staff-function, and fine proofs.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Use a digital library (Priority: P1)

Two synthetic members search the catalog, borrow and return books, and see fine status.
A staff account can update book metadata and resolve eligible fines.

**Why this priority**: Legitimate journeys provide the comparison for the lab findings.

**Independent Test**: Search by title, borrow/return a book, make an eligible fine
resolution, and update one book as staff.

**Acceptance Scenarios**:

1. **Given** a valid title term, **When** a member searches, **Then** matching public
books are returned without private marker records.
2. **Given** a returned overdue loan, **When** the staff resolves its fine, **Then** the
fine state changes and the loan remains returned.

### User Story 2 - Assess three bounded conditions (Priority: P1)

The VAPT group compares normal search with one input that exposes a synthetic record,
checks whether a member can invoke a staff-only metadata function, and tests whether a
fine can be waived before the associated return prerequisite is met.

**Why this priority**: The three cases require distinct reasoning about queries, roles,
and business state.

**Independent Test**: From a clean fixture, obtain one record marker, change one
synthetic metadata field, and record one improper fine transition, then reset.

**Acceptance Scenarios**:

1. **Given** the designated search input, **When** it is submitted to the vulnerable
build, **Then** one nonpublic synthetic marker record appears.
2. **Given** a member account, **When** the designated staff action is requested in
the vulnerable build, **Then** one book's synthetic metadata changes.
3. **Given** an unreturned loan with a fine, **When** the designated waiver action
is requested, **Then** the vulnerable build marks that fine resolved.

### User Story 3 - Retest corrections (Priority: P2)

The group repeats the same three actions on a patched copy of the fixture.

**Why this priority**: A retest must show both control enforcement and legitimate use.

**Independent Test**: The synthetic marker stays hidden, member metadata modification
and premature waiver are rejected, while normal search/staff/eligible waiver still work.

**Acceptance Scenarios**:

1. **Given** patched state, **When** all three bounded actions are repeated, **Then**
none produces the vulnerable result, with normal journeys unaffected.

### Edge Cases

- Search input has punctuation or no results; a response change alone may be a false positive.
- A member sees a staff control in UI but the server rejects the action.
- A loan is returned while a waiver request is in flight.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The app MUST provide catalog search, borrowing, return, fine, and staff
  metadata flows with at least two member accounts and one staff account.
- **FR-002**: The vulnerable search MUST expose only the designated nonpublic synthetic
  marker record through its bounded query condition.
- **FR-003**: The vulnerable staff function MUST allow one designated member-originated
  metadata change with a stable evidence ID.
- **FR-004**: The vulnerable fine workflow MUST allow one premature waiver transition
  with a stable evidence ID.
- **FR-005**: The patched build MUST reject those three conditions while normal flows pass.
- **FR-006**: Reset MUST restore catalog, loan, metadata, fine, and marker state.
- **FR-007**: No proof may require system-table access, host files, or bulk data changes.

### Key Entities

- **Member/staff**: synthetic identity and function permissions.
- **Book**: catalog metadata and visibility.
- **Loan**: member, book, due/return state.
- **Fine**: loan relationship, amount, eligibility, and resolution state.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A blind reviewer completes normal search, loan, return, and staff actions.
- **SC-002**: Exactly three bounded proof categories are reproducible from clean state.
- **SC-003**: The patched build rejects all three proofs and preserves corresponding
  normal behavior.
- **SC-004**: The target meets the shared harness resource and isolation gates.

## Assumptions

- This target is assigned only to group 2; all users and records are synthetic.
- The marker is a lab record, not a real member record or database system table.
