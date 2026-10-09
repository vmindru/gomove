# Phase 6 — Verification and pilot readiness

Prerequisite: [management](05-management.md). This phase delivers local verification and launch documentation. Staging/production publishing is a separate operator-approved action; paid services are not provisioned by this planning task.

## Ordered tasks

1. [ ] Run meaningful backend tests against isolated databases. Use PostgreSQL for migrations, overlap constraints, tenant policies, and concurrent transactions. Keep test connection settings separate from `.env.compose`; assert the test database identity before any refresh/reset command. Run existing authentication/settings tests with an explicitly isolated test connection.
2. [ ] Run TypeScript, frontend build, and relevant lint checks. Add browser tests for discovery → login → request → manager decision → requester status, alongside approval-mode and cancellation paths. Check asset loading and actual mobile/keyboard interaction, not only HTTP 200 responses.
3. [ ] Test tenant/organization/requester isolation through guessed IDs, changed parent IDs, JSON endpoints, images, audit screens, and notification links. Confirm that changing public browsing settings cannot leave accessible cached content and unapproved resources cannot be booked.
4. [ ] Test independent parts, back-to-back bookings, pending occupancy, simultaneous submissions, booking-versus-closure races, approve/cancel races, overnight schedules, date exceptions, and both daylight-saving transitions. Verify database invariants after every concurrency case.
5. [ ] Review Czech wording, translation fallback, date/CZK formatting, phone layouts, keyboard order, visible focus, labels, contrast, error summaries, and accessible calendar alternatives with pilot users.
6. [ ] Agree traffic assumptions and run bounded load tests on representative data. Record p95 latency, errors, database lock waits, query plans, and worker queue age. Index and paginate directory, availability, and request queries based on evidence. Growth triggers include sustained latency, exhausted connections, lock contention, queue lag, and storage pressure; do not provision for 10 million registered users at launch.
7. [ ] Write a deployment runbook covering environment/secrets, HTTPS/domain, migration sequence, asset build, persistent PostgreSQL/photos, worker supervision, health checks, monitoring, and rollback. Production uses debug disabled and deploy-time initialization rather than reinstalling dependencies on every start. No public deployment is performed merely to validate this plan.
8. [ ] Back up PostgreSQL and photos; restore into an isolated environment and verify listings, bookings, accounts, and files. Record recovery steps, responsible operator, and agreed recovery targets. Never “test restore” by overwriting the running pilot database.
9. [ ] Resolve launch-only items in [before-development](../before-development.md): operator/support, participant approval, permitted content, privacy/terms, retention/deletion, sender, backups, and incident responsibilities. Confirm account deletion does not cascade away active bookings or required operational history under the reviewed retention rules.
10. [ ] Prepare a staging acceptance script and obtain manager/requester feedback. When staging deployment is separately authorized, run the same integrity checks there. Record unresolved findings before production approval; plan post-pilot monitoring of request completion, response time, schedule accuracy, and notification failures.

## Local MVP acceptance script

1. Create a clearly fictional organization with two named independent parts and opening hours.
2. Submit a facility for first review; verify an anonymous visitor cannot see or book it yet.
3. Approve it; browse it on a phone and submit an authenticated manual-mode request.
4. Attempt an overlapping request from another session; verify it fails and no second active reservation exists.
5. Book the other part concurrently and an adjacent interval on the first part; both succeed.
6. Approve the pending request; check requester status and the anonymous unavailable interval, with no private information leaked.
7. Decline another request and cancel according to reviewed policy; verify occupancy is released.
8. Toggle automatic approval and submit a fresh request; verify immediate confirmation and the same conflict protection.
9. Record an external booking and closure; verify online requests cannot overlap them.
10. Disable public browsing, revoke a manager membership, and simulate a notification failure; verify access changes, durable status, and retry visibility.

## Exit evidence

Store test results, build/check output summaries, concurrency outcomes, accessibility findings, measured load assumptions/results, and restore evidence in a dated verification report. Mark each phase task complete only when its acceptance checks pass. Local MVP completion, staging acceptance, and production launch are separate milestones.
