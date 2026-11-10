# Phase 3 — Availability and reservation integrity

Prerequisite: [catalogue](02-catalogue.md). Review the relevant [open rules](00-open-decisions.md). Next: [booking flow](04-booking-flow.md).

## Ordered tasks

1. [ ] Define one availability service used by calendar reads, date/time catalogue filters, booking submission, manager blocks, and approval. It combines weekly opening intervals, effective dates, date-specific exceptions, active occupancy, and reviewed booking-policy values.
2. [ ] Implement PostgreSQL migrations for availability and common calendar occupancy. Add start-before-end checks and a database overlap constraint for active occupancy of the same resource. A proposed mechanism is a GiST exclusion constraint over resource identity and a `tstzrange` with `[)` boundaries, with `btree_gist` support for identity equality. Validate extension availability and migration-role permissions in the actual database before adopting it.
3. [ ] Store booking instants in UTC; interpret weekly schedules and date exceptions in Europe/Prague. Define overnight schedule representation explicitly, for example split intervals across dates. Reject nonexistent local times and make ambiguous offsets explicit according to the reviewed policy. Format Czech dates without changing timezone when UI language changes.
4. [ ] Implement resource-scoped transactional mutations. Lock the resource row before checking opening rules and writing occupancy; use the same lock for booking submission, approval, blocks, and schedule changes. Use a consistent lock order for bulk schedule operations. The overlap constraint remains the final protection against conflicting active reservations.
5. [ ] Add manager weekly-opening and date-exception forms plus one-off training, closure, and external-booking blocks. External bookings must use the same occupancy protection as online reservations. Keep public external-booking details anonymous.
6. [ ] Reject schedule changes that conflict with active reservations under the proposed reviewed rule; show the manager affected records. Do not silently cancel or move bookings. A block cannot win against an already recorded overlapping reservation.
7. [ ] Build a public named-resource calendar with an accessible list alternative, selected date and multiple slot intervals, open/unavailable ranges, and refresh behavior. Generate complete intervals from opening time using the resource’s 15, 30, or 60-minute setting. Bound date-range queries and response size. Return time ranges and availability only; pending versus confirmed private status need not be disclosed.
8. [ ] Add PostgreSQL integration and concurrency tests before connecting the booking UI. Cover direct database conflicts as well as service-level validation. Avoid relying on SQLite or a single transaction to simulate simultaneous clients.

## Reservation invariants

A booking uses one stable named Resource and one occupancy interval. A multi-slot submission groups adjacent selections into one booking and separated selections into distinct bookings; the entire submission commits atomically. Pending and confirmed states retain active occupancy; decline/cancellation releases it in the same transaction. Approval changes the existing reservation instead of creating a second occupancy. Independent parts permit simultaneous use. Names never imply spatial overlap or shared capacity. The explicit manager-enabled whole-field option covers all individual resources in its facility and checks every part’s opening hours. Store one occupancy row per covered resource with PostgreSQL overlap exclusion. Whole-field and partial bookings must share a facility lock before acquiring resource locks; status transitions update covered occupancy atomically.

For a resource booked 10:00–11:00, 11:00–12:00 succeeds and 10:30–11:30 fails. Unavailable periods include closures, training, and external reservations through the same model. First recorded wins means first successful database reservation, not first browser click.

Constraint errors should become a Czech availability-conflict response with refreshed availability. Opening-hour checks and authorization errors remain distinct. Do not return raw SQL or private details.

## Exit checks

- Concurrent whole-field and individual-part requests for an overlapping interval produce one success and one conflict.
- Concurrent requests for one interval produce exactly one reservation; requests for two independent parts can both succeed.
- Back-to-back reservations succeed, zero/negative durations fail, and pending/confirmed intervals both block.
- Booking-versus-block and booking-versus-schedule-change races cannot leave an invalid reservation.
- Date exceptions and overnight schedules behave as specified; spring/fall daylight-saving cases preserve valid instants.
- Public calendar HTML/JSON contains no identities, emails, notes, external customer names, or sensitive audit metadata.
- Manager availability controls cannot affect another organization's resources.
