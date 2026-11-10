# Feature Specification: EventPass VAPT Target

**Feature Branch**: `main` (planning baseline)  
**Created**: 2026-10-26  
**Status**: Ready for planning  
**Input**: Group 3 needs an event lab with bounded review, ticket-property, and check-in proofs.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Attend an event (Priority: P1)

Two synthetic attendees view events, create tickets, post reviews, and check in once.
An organizer views the event and ticket results.

**Why this priority**: These flows establish the expected behavior for each security case.

**Independent Test**: Create a ticket, update an allowed attendee detail, post a plain
review, and complete a first check-in.

**Acceptance Scenarios**:

1. **Given** an attendee ticket, **When** an allowed detail is updated, **Then** it
changes without altering organizer-controlled properties.
2. **Given** an unused ticket, **When** it is checked in once, **Then** it records one
successful entry and later views show that state.

### User Story 2 - Assess three bounded conditions (Priority: P1)

The VAPT group checks whether a stored synthetic review produces a harmless local DOM
indicator for a second test account, whether an attendee can change an organizer-controlled
ticket property, and whether the same ticket can be checked in twice.

**Why this priority**: Content rendering, property authorization, and state transitions
are different from the other two targets.

**Independent Test**: Use only two approved browser accounts and one test ticket;
capture one indicator, one property change, and one duplicate check-in record.

**Acceptance Scenarios**:

1. **Given** a saved synthetic review, **When** the second test account opens it in
the vulnerable build, **Then** a local DOM indicator appears with no network exfiltration.
2. **Given** an attendee account, **When** the designated ticket update is submitted,
**Then** one organizer-controlled synthetic property changes in the vulnerable build.
3. **Given** a checked-in ticket, **When** its check-in is repeated, **Then** the
vulnerable build records a second successful entry.

### User Story 3 - Retest corrections (Priority: P2)

The group repeats the three actions on a patched copy of the fixture.

**Why this priority**: The fix must enforce rendering, field, and state rules without
breaking normal event usage.

**Independent Test**: The local indicator does not execute, forbidden ticket fields do
not change, and a second check-in is rejected while normal review/ticket/first entry work.

**Acceptance Scenarios**:

1. **Given** patched state, **When** the three proof actions repeat, **Then** all are
blocked or rendered harmless and normal attendee/organizer flows still succeed.

### Edge Cases

- A review is saved but never rendered in another account's browser.
- A forbidden field is echoed in a response but not persisted.
- Two check-in actions occur close together for the same ticket.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The app MUST provide event, ticket, review, and check-in flows for two
  attendees and one organizer using synthetic data.
- **FR-002**: The vulnerable build MUST permit one designated stored review to create
  a harmless local DOM indicator in the second test account's browser.
- **FR-003**: The vulnerable build MUST persist one designated organizer-controlled
  ticket property sent by an attendee and record a stable evidence ID.
- **FR-004**: The vulnerable build MUST accept a second check-in for the same ticket and
  record a stable evidence ID.
- **FR-005**: The patched build MUST render reviews safely, reject forbidden property
  updates, and enforce one-time check-in while normal journeys pass.
- **FR-006**: Reset MUST restore reviews, ticket properties, check-in state, and markers.
- **FR-007**: No proof may collect cookies, tokens, or transmit data outside the instance.

### Key Entities

- **Attendee/organizer**: synthetic identities with different privileges.
- **Event**: public description, capacity, and organizer relationship.
- **Ticket**: attendee, allowed/organizer-controlled properties, check-in state.
- **Review**: author, event, stored text, display state.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A blind reviewer completes normal event, ticket, review, and check-in flows.
- **SC-002**: Exactly three bounded proof categories are reproducible from clean state.
- **SC-003**: The patched build rejects all three proofs and preserves corresponding
  normal behavior.
- **SC-004**: The target meets the shared harness resource and isolation gates.

## Assumptions

- This target is assigned only to group 3; all accounts and events are synthetic.
- The DOM indicator is local and inert beyond visible proof; no secret access is needed.
