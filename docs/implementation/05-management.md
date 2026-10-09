# Phase 5 — Management and notifications

Prerequisite: [complete booking flow](04-booking-flow.md). Next: [verification and pilot readiness](06-verification-launch.md).

## Ordered tasks

1. [ ] Complete the administrator onboarding UI for customers, organizations, manager memberships, and first-publication review. Validate ownership relationships and audit grants/revocations. Keep private requester support access limited to the reviewed policy.
2. [ ] Complete manager calendar editing: weekly schedules, date exceptions, training, closures, and external bookings. Add a usable daily/weekly view, pending age, affected-booking feedback, and explicit cancellation controls. Recurring manager availability/training is allowed; recurring public booking is deferred. Materialize recurring training blocks over the booking horizon before permitting submissions, or enforce equivalent transactional protection; do not let gaps between expansion jobs become bookable.
3. [ ] Add photo upload/reordering/removal using the reviewed storage choice. For the proposed local disk approach, persist files across container recreation and include them in backups. Validate type, size, and ownership; generate storage keys, safe image derivatives, and Czech alt text. Plan orphan cleanup without deleting referenced files. Test that PHP/uploaded executable content is never served as executable code.
4. [ ] Add transactional notification outbox/delivery records for submission, approval, decline, and cancellation. Persist the event with the booking change, then send after commit; delivery failure must not roll back a valid booking or release occupancy.
5. [ ] Add a database-backed queue worker to local Compose with the same application image, scoped environment, and required storage. Bypass web initialization for the worker, wait for migrations/readiness, and run the worker as the application user. Add scheduler execution only for implemented scheduled jobs. The current web/database setup alone does not process queued notifications.
6. [ ] Build Czech requester/manager email templates and local log-mail demonstrations; configure a real sender only when chosen. Avoid private booking content in public URLs or logs. Links must enforce authentication/authorization on the destination.
7. [ ] Implement bounded retry/backoff, failure visibility, and authorized manual retry. Use unique event/recipient/channel keys to suppress application duplicates. Account for the crash window after provider acceptance: use provider idempotency where available and describe the remaining delivery limitation; do not promise exactly-once email.
8. [ ] Finish manager overview and requester status screens with empty/loading/error states and audit history scoped to the actor's permissions. Display organization payment instructions without introducing payment statuses or processing.
9. [ ] Write manager guidance for updating calendars and resolving pending requests; document admin onboarding and notification recovery. Add a repeatable development demonstration and keep demo accounts/data out of production seed paths.

## Exit checks

- A fictional manager can publish later edits, maintain calendars, handle requests, and add photos without developer assistance.
- Worker processing and retries survive restarts; failed email leaves the booking intact and visible in the account.
- Revoking membership removes manager access; onboarding cannot associate a facility with an unauthorized customer.
- Photos remain available after container recreation; unsafe uploads fail and another organization's assets cannot be changed.
- Manager closures/external reservations continue using phase 3 integrity checks, including concurrent requests.
- Local documentation clearly distinguishes logged mail from actual delivered mail.
