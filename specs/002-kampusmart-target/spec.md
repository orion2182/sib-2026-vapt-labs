# Feature Specification: KampusMart VAPT Target

**Feature Branch**: `main` (planning baseline)  
**Created**: 2026-10-26  
**Status**: Ready for planning  
**Input**: Group 1 needs a retail lab with three bounded proofs and a patched retest.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Shop normally (Priority: P1)

Two synthetic customers browse products, create orders, view their own invoices, and use
coupons according to the displayed rules. A staff account handles a limited staff flow.

**Why this priority**: Normal behavior gives students a baseline for every comparison.

**Independent Test**: Complete one order and one valid coupon use for each test customer,
then confirm only the owner can reach the invoice in the patched build.

**Acceptance Scenarios**:

1. **Given** a customer account, **When** an order is placed, **Then** a matching order
and invoice appear in that customer's history.
2. **Given** an unused valid coupon, **When** it is applied once, **Then** the order
reflects the documented discount and the coupon becomes used.

### User Story 2 - Assess three bounded conditions (Priority: P1)

A VAPT group examines a dedicated web service, invoice ownership, and one-use coupon rule
using only approved synthetic markers and the assigned instance.

**Why this priority**: These are the three distinct proofs in the group's PTES card.

**Independent Test**: Reproduce each condition from a clean reset with one minimum proof,
then stop; no other files, accounts, or groups are accessed.

**Acceptance Scenarios**:

1. **Given** the lab's designated affected component/configuration, **When** the approved
bounded request is made, **Then** exactly the permitted synthetic file marker can be read.
2. **Given** customer A and B, **When** A requests B's designated invoice in the
vulnerable build, **Then** the synthetic invoice marker is exposed.
3. **Given** a used coupon, **When** the designated repeat action occurs, **Then** the
vulnerable build records one extra use against the stated one-use rule.

### User Story 3 - Retest corrections (Priority: P2)

The group repeats its three documented actions on an equivalent patched fixture.

**Why this priority**: Students must distinguish root-cause fixes from a changed marker.

**Independent Test**: The three proofs fail in the patched build while normal ordering,
own-invoice access, and first valid coupon use still pass.

**Acceptance Scenarios**:

1. **Given** patched state, **When** the three proof actions are repeated, **Then**
the marker file and foreign invoice remain unavailable, and repeat coupon use is rejected.

### Edge Cases

- A coupon is applied simultaneously or after an order status changes.
- An invoice ID exists but belongs to another account, or does not exist.
- The special web component cannot be safely isolated on a supported laptop.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The app MUST provide catalog, order, invoice, coupon, and limited staff flows.
- **FR-002**: Fixtures MUST contain at least two customers with distinguishable invoices.
- **FR-003**: The vulnerable build MUST expose only one approved synthetic file marker
  through the dedicated affected web component.
- **FR-004**: The vulnerable build MUST exhibit the designated invoice ownership failure
  and one coupon rule failure, each with stable evidence IDs.
- **FR-005**: The patched build MUST reject all three proofs while preserving normal flows.
- **FR-006**: Reset MUST restore both customers, the coupon state, and proof markers.
- **FR-007**: The special component MUST have no required host code execution path.

### Key Entities

- **Customer**: synthetic identity and role.
- **Product/order/invoice**: catalog item, purchase state, owner, and synthetic marker.
- **Coupon**: validity, allowed uses, recorded uses, and discount rule.
- **Service marker**: approved file and evidence identity in the isolated component.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A blind reviewer can complete each normal flow and three minimum proofs.
- **SC-002**: Each proof yields one stable synthetic evidence identifier after reset.
- **SC-003**: All three proofs fail on patched state and all three corresponding normal
  flows still pass.
- **SC-004**: The complete target meets the shared harness resource and isolation gates.

## Assumptions

- This is an instructor-controlled, isolated lab for group 1 only.
- The exact affected component build and configuration are selected after safety pilot.
- Only the designated synthetic file marker is within the approved proof scope.
