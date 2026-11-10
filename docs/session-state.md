# GoMove saved implementation state

Saved 2026-10-09 at the user's request to stop containers and preserve context. Read this file first when resuming, then [project context](project-context.md), [decisions](decisions.md), and the [implementation roadmap](implementation-plan.md).

## Runtime and source checkpoint

- Project: `/home/vmindru/proj/gomove`. Personal environment; never use Popron credentials or other agents' state.
- Application: Laravel 12, React/Inertia, TypeScript; Apache/PHP 8.4 and PostgreSQL 17 via rootless Podman on the host.
- GoMove containers `gomove_web_1` and `gomove_database_1` are stopped. Containers and the PostgreSQL named volume are retained; no database reset or volume deletion was performed. Other projects' containers were left running.
- URL after restart: `http://localhost:8080`; health endpoint `/up`.
- Local version checkpoint: annotated `mvp-0.1` at commit `f4a4f95a3db8e354e88a2caad4b7115de811f703`. Subsequent slot-selection and whole-field changes are saved in the worktree, after that checkpoint; no new version/tag or push was requested. Do not move the existing tag.
- Latest production frontend assets were built and both slot migrations were applied to the development database before shutdown.
- Credentials remain in ignored `.env.compose` and `.demo-access.txt`. Do not copy passwords or secrets into documentation or commits. Demo accounts: `admin@demo.gomove.test`, `manager@demo.gomove.test`, `visitor@demo.gomove.test`.

## Implemented and verified

- Czech responsive catalogue, facility detail, private requester booking history, manager controls, and administrator publication/public-browsing controls.
- Registration/login/recovery scaffolding; authentication required to request reservations. Customer, organization, manager, and requester scoping.
- First-publication approval; immediate manager edits after publication. Fictional demo organizations and facilities.
- Manual approval by default, optional organization automatic approval. Pending/confirmed reservations and manager blocks occupy time; decline/cancellation release it. No online payment.
- Each field/resource has a configurable slot length of **15, 30, or 60 minutes**. The calendar renders complete intervals from opening time using that setting. Multiple selections are supported; adjacent slots merge into a reservation and separated groups become separate reservations in one atomic submission.
- Organization-defined hourly or per-slot CZK pricing; agreement pricing displays `Cena dohodou`. Price snapshots are preserved. Retry deduplication, stale configuration checks, and atomic conflict rejection are implemented.
- Optional **Celé hřiště**: facility managers enable/disable it under **Sportoviště a ceny → Povolit rezervaci celého hřiště**. Default is off. When enabled, managers configure its own price and slot length; initial pricing is by agreement, initial slot 15 minutes.
- Whole-field slots require every individual part in the facility to be open and free. A whole booking/block occupies every part; a part booking/block prevents overlapping whole reservations. Disabling whole-field booking preserves existing occupancy. Adding parts is rejected while active future whole-field bookings exist.
- PostgreSQL exclusion constraints and booking-to-part occupancy synchronization enforce conflicts even for direct booking database writes. Booking operations lock the facility before its resource.
- Daily daytime opening hours and one-off training/closure/external blocks; stable resource ordering by ID.

## Presentation data and verification

- Tennis `/facilities/1`: whole-field option off; Kurt 1 restored to 280 CZK/hour and 60-minute slots.
- Football `/facilities/2`: **Celé hřiště enabled** for presentation; individual halves use 30-minute slots at 150 CZK. Whole-field price remains by agreement and slot length 15 minutes, configurable by the manager.
- Whole-field browser rehearsal bookings were cancelled; their history remains. Prior fictional demo booking history remains intact.
- Final suite: **51 tests passed / 329 assertions**, using guarded isolated PostgreSQL database `gomove_test_mvp`.
- Separate-process concurrency checks: same part versus same part, and part versus whole field; exactly one success and one conflict in each case.
- TypeScript, changed-file ESLint, PHP formatting, migrations, production build, desktop/mobile browser flows, and health endpoint passed.
- Browser checks covered all slot lengths, multi-slot price/submission, manager whole-field enable/disable, partial-versus-whole unavailability, cancellation release, preserved occupancy after disabling, and 390×844 layout without overflow/runtime errors.
- See [verification evidence](mvp-verification.md). Temporary browser tools lived under `/tmp/gomove-browser-tools`; they are not a committed regression harness. The dedicated rehearsal Chrome instance was stopped.

## Remaining implementation plan

The presentation MVP is implemented; full roadmap phase acceptance is still open. Next work, in practical order:

1. **Availability and manager workflow:** weekly schedules, date-specific exceptions, recurring training, clearer pending age, cancellation reasons, and affected-booking details when schedule edits conflict. Define overnight/DST interaction before implementing overnight opening.
2. **Administration and content:** organization onboarding, manager invitation/revocation/access management, richer facility details/accessibility, photo upload validation and persistent storage. Replace fictional content only with approved real data.
3. **Notifications:** durable booking event/outbox, supervised queue worker, Czech email templates, bounded retries, failure/retry visibility; select a sender/provider. Account status remains authoritative until delivery exists.
4. **Product completion:** multilingual catalogs and configurable customer branding; robust empty/loading/error states and accessible keyboard/calendar alternatives. Consider a committed browser regression harness for the verified booking/manager flows.
5. **Integrity and pilot checks:** additional booking-versus-schedule/block and approve/cancel races, date-exception/DST coverage, broader tenant isolation and account-deletion/retention checks, accessibility review and measured load tests. Evaluate database row-level policies as planned.
6. **Launch operations:** operator/support responsibilities, terms/privacy and retention, domain/HTTPS, deployment/rollback runbook, supervised workers, monitoring, PostgreSQL/photo backup and isolated restore rehearsal, staging/pilot participant acceptance. No production deployment is authorized by this handoff.

Decisions still needed: final booking horizon/notice, pending expiry, mandatory email verification, cancellation cutoffs/past-booking behavior, final rounding/quote rules, administrator support access, production storage/hosting/email, and operator/privacy/retention policy. Current demo defaults: 90-day horizon, no extra notice, no automatic expiry, future requester cancellation, nonmandatory email verification, hourly totals rounded upward to minor units, daily opening between 06:00 and 23:00.

Deferred: online payments, arbitrary overlapping half/quarter combinations, recurring public bookings, external calendar synchronization, reviews, native mobile apps, and municipality migration tooling. The implemented whole-field model covers all individual parts of one facility; it does not infer containment from part names.

## Resume commands

Run on the host (SSH from the personal agent as `vmindru@localhost` when Podman is unavailable inside the agent container):

```bash
cd /home/vmindru/proj/gomove
./scripts/compose.sh start
./scripts/compose.sh ps
curl --fail http://localhost:8080/up
```

For new backend/schema changes, run migrations explicitly. For changed frontend source, rebuild assets. For verification:

```bash
./scripts/compose.sh exec -T --user www-data web php artisan migrate --force
./scripts/compose.sh exec -T --user www-data web npm run build
./scripts/test.sh
./scripts/test-concurrency.sh
```

Run the test suite before the concurrency script to initialize its isolated database. Never run database refresh/reset against the development database. `start` resumes retained containers; use `up -d --build` only when recreation/build is needed. Do not use `down -v` to resume or stop.
