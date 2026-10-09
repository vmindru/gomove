# Presenting the GoMove MVP

Local presentation build: 2026-10-09. Open **http://localhost:8080** on the host laptop. Apache/PHP and PostgreSQL remain running under rootless Podman. All seeded organizations, facilities, addresses, and people are fictional and labelled as demo content.

## Demo access

Use these accounts on the normal login page:

| Role | E-mail | Destination |
| --- | --- | --- |
| Requester | visitor@demo.gomove.test | My bookings: `/dashboard` |
| Organization manager | manager@demo.gomove.test | Club management: `/manage` |
| Platform administrator | admin@demo.gomove.test | First-publication review: `/admin` |

The generated local password is in **[.demo-access.txt](../.demo-access.txt)**, an ignored file with mode 0600. It is also stored in ignored `.env.compose` as `GOMOVE_DEMO_PASSWORD`. No password is committed to the project. Seeding preserves existing demo accounts and content; changing the seed password does not reset existing account passwords.

Use separate browser profiles or a normal window plus an incognito window for requester and manager. Tabs in the same profile share a login session.

## Five-minute presentation

1. Open the homepage anonymously. Introduce the Czech facility catalogue, sport filters, fictional demo notice, and mobile layout.
2. Open **Tenisové kurty Na Výsluní**. Show two independently bookable courts, daily availability, hourly CZK pricing, and unavailable time ranges without requester details.
3. Sign in as requester. Select a future date, court, duration, and a free start time; submit a request. Explain that pending status reserves the slot immediately. Show it under **Moje rezervace**.
4. In a separate profile, sign in as manager. Open **Správa klubu**, approve the pending request, and refresh the requester page to show confirmation.
5. Open **Sportoviště a ceny** in the manager view. Expand a facility and show organization-controlled hourly, per-slot, and agreement pricing. Slot lengths use 15-minute increments; the football demo has a 90-minute slot. Existing requests keep their recorded price when rates change.
6. Open **Kalendář a blokace**. Add a training, closure, or external reservation. The public calendar shows the interval as unavailable. Overlapping writes are rejected; independent parts and adjacent bookings remain valid.
7. Optionally enable automatic approval for new requests, submit a fresh request, and show immediate confirmation. Return the toggle to manual for the next presentation. Existing pending requests are not converted automatically.
8. Optionally create a new facility as manager, then sign in as administrator to approve its first publication. Show the administrator's public-browsing toggle. Restore public browsing after demonstrating restricted access.

There are already two confirmed tennis requests on the day after the build date from the browser rehearsal. They demonstrate status history; choose another free time or date for new requests. Do not delete the database to reset a presentation. Existing bookings can be cancelled through authorized UI actions while they are in the future.

## Implemented

- Czech public catalogue and sport filtering, facility details, responsive desktop/mobile screens, and original vector sports illustrations.
- Named independent resources, organization-specific pricing, organization-defined slots in positive 15-minute increments, and daily opening hours.
- Session-based registration/login and Czech password recovery screens. Reset messages use the existing local log mailer.
- Authenticated requests, manual/automatic approval, requester history, manager decisions, cancellation of future reservations, and price snapshots.
- Immediate pending occupancy; transactional resource locks and a PostgreSQL exclusion constraint across pending, confirmed, and manager blocks.
- Manager facility creation, description/conditions/amenity editing, resource creation, rate/slot/opening-hour editing, and external calendar blocks.
- Administrator first-publication approval and browsing access switch; customer, organization, and requester checks on the server.
- Basic internal audit events for booking changes, resource settings, and publication. Account deletion is blocked for accounts with bookings, memberships, or audit history until a retention workflow is defined.

The PostgreSQL range constraint uses `[)` intervals, allowing back-to-back bookings; see [PostgreSQL range documentation](https://www.postgresql.org/docs/17/rangetypes.html).

## Explicit demo defaults and remaining work

This presentation build is a useful subset of the [implementation roadmap](implementation-plan.md), not completion of every phase.

- Daily opening hours apply uniformly, with configurable times between 06:00 and 23:00; weekly schedules, recurring training, and date-specific opening exceptions remain planned. One-off blocks work now. Overnight booking is not supported.
- Start times and durations use 15-minute steps. A slot must fit inside the daily opening window. A per-slot request books exactly one configured slot; hourly/agreement requests can choose a duration within the day.
- The demo uses a 90-day horizon, no additional notice requirement, no pending expiry, nonmandatory email verification, and cancellation of future pending/confirmed bookings. Manager cancellation reasons and final cancellation policies remain open.
- Hourly prices are prorated by duration and rounded upward to the nearest minor unit for demo totals. Final rounding/quote rules remain open. Agreement pricing never produces a numeric total.
- Booking decision email delivery, queue/outbox workers, photo uploads, expanded onboarding/access management, full multilingual catalogs, database row-level policies, and production operations remain planned. The authoritative result is the booking status in the account.
- Real content, operator/terms/privacy, hosting/HTTPS, backup recovery, and pilot acceptance remain launch work. Online payment remains outside the initial scope.

## Operations and verification

Run on the host:

```bash
cd /home/vmindru/proj/gomove
./scripts/compose.sh ps
./scripts/compose.sh logs --tail=50 web
./scripts/test.sh
./scripts/test-concurrency.sh
```

`test.sh` uses the dedicated PostgreSQL database `gomove_test_mvp`; a bootstrap guard rejects other database names before PHPUnit can refresh data. Run it before the concurrency rehearsal to create test schema. Neither command resets the development database.

To reseed missing demo content, with local demo settings configured:

```bash
./scripts/compose.sh exec --user www-data web php artisan db:seed --class=GoMoveDemoSeeder --force
```

To rebuild assets after source changes:

```bash
./scripts/compose.sh exec --user www-data web npm run build
```

Do not commit `.env.compose`, `.demo-access.txt`, generated assets, or database contents. See the [verification report](mvp-verification.md) for evidence and limits.
