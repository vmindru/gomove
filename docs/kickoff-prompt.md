# Project kickoff prompt

Use this prompt after reviewing the planning documents. It requests specification work first; it does not authorize public deployment or paid service provisioning.

```text
Start the planning phase for GoMove, an independent sports facility rental web
product. Šlapanice, Czech Republic, is the first customer deployment.

Read the workspace instructions, README.md, and docs/ before making changes.
Inspect existing files without overwriting unrelated work. Treat recommendations
as proposals unless docs/decisions.md records their acceptance.

Purpose:
Sports organizations publish facilities and rentable resources. Residents browse
availability and request bookings. Each organization manages its own listings,
schedules, and requests.

Accepted deployment approach: one shared platform initially, with explicit
municipality/customer scoping and isolation. Plan a path to separate installations
per municipality later. Keep configuration reusable and data separable; defer
migration tooling and decisions about cross-installation accounts.

First deliverable:
Produce a concise product specification, data model, permission matrix, booking
state transitions, screen flows, and phased implementation plan. Identify missing
business decisions and label assumptions. Reuse the existing planning documents
and update them consistently. Do not implement the application, provision paid
services, or publish it during this planning stage.

Proposed stack:
- TypeScript with strict checking; Next.js and React.
- Accepted database decision: self-hosted PostgreSQL. Authentication and photo
  storage need separate decisions; do not assume Supabase services.
- Database migrations and documented local development setup.
- Accessible, responsive UI.
- Czech user-facing text, English code and technical documentation.
- Accepted localization: CZK prices, Czech date formatting, and Europe/Prague
  timezone with explicit daylight-saving handling.
- Accepted language decision: Czech at launch with multilingual support from
  the start. Use translation keys and locale catalogs so new languages can be
  added later; define Czech fallback for untranslated organization content.

Users:
- Public visitors and registered booking requesters.
- Organization managers with access only to their own organization.
- Platform administrators with explicitly defined support permissions.

Data:
Organizations, memberships, facilities, individually rentable resources,
availability rules, date-specific exceptions, booking requests, confirmed
bookings, and audit records. Photos and organization contact details belong
to listings. Accepted: visitors see booked/unavailable times only, for both pending
and confirmed reservations. Exclude requester identities, contacts, and private
notes from public calendar responses on the server.

Public experience:
Allow browsing without an account by default, with a platform administrator
toggle to require sign-in for listings and availability. Enforce this setting
on the server and account for cached responses. Filter by sport, resource type, date, and time;
view photos, prices, amenities, conditions, and availability. Require registration
and sign-in to request a specific time, view private request details, and cancel
through the requester account. Account-free request links are not the selected
access model. Define account recovery and the email verification policy.

Manager experience:
Accepted: build calendar functionality inside GoMove as the initial availability
source. Defer external calendar imports and integrations until a later phase.
Accepted: organization managers are responsible for their availability calendars,
including training, closures, and bookings received outside GoMove.
Maintain listings, regular schedules and exceptions; block training, matches,
and maintenance; record external bookings; approve, decline, or cancel requests;
toggle organization approval mode between manual (default) and automatic;
receive notifications.

Administrator experience:
Onboard organizations, manage memberships, review listings, and support users
with an audit trail and agreed access boundaries.
Require administrator approval before a facility listing is first published.
Unapproved listings must not be publicly discoverable or accept public bookings.
After first approval, organization managers publish edits without reapproval.
Continue enforcing organization permissions and recording important changes.

Proposed pilot booking rules:
- Accepted: manual approval by default, with an organization toggle for automatic
  approval. Both paths enforce availability and booking rules atomically.
- Accepted: payment arranged directly with the organization initially; payment
  facilitation may be added later. Do not implement checkout in the first release.
- Accepted: pending requests reserve time immediately and make the slot unavailable.
  Decline or cancellation releases the reservation; automatic expiry is undecided.
- If email verification is required, keep it separate from booking status.
- Submission and approval enforce availability atomically. Prevent overlapping
  pending or confirmed bookings at the database level, including concurrent
  submissions and approvals. Approval retains the existing reservation.
- Accepted: first recorded reservation wins. Return a clear availability error
  for a later overlapping request and refresh the schedule. Keep this simple
  using a database constraint; do not add waitlists or automatic reassignment.
- Accepted: exact start/end times with no buffers or grace periods. Allow a booking
  to start exactly when another ends; use start-inclusive/end-exclusive intervals.
- Duration, increments, notice periods, booking horizon, cancellations,
  and request expiry must follow reviewed business decisions.
- Define how schedule changes affect existing confirmed bookings.
- Shared or divisible spaces need an explicit conflict model before support.

Quality:
Enforce authorization on the server and in database policies. Validate inputs,
protect forms against abuse, keep secrets out of source control, and use clearly
fictional Czech demo data. Plan meaningful tests for organization isolation,
concurrent approvals, availability exceptions, timezones, and cancellations.
Account for failed notifications and document deployment, backup, and recovery.

Scope:
Expect approximately 100 users and 5–10 sports organizations initially. Use a
simple deployment sized for that workload, with a growth path toward a theoretical
maximum of 10 million registered users and 1,000 registered sports facilities.
These are not hard limits. Do not provision for the theoretical maximum at launch.
Define traffic assumptions, growth triggers, and staged performance validation.
Defer online payments, recurring
public bookings, calendar synchronization, reviews, and native mobile applications.

Initial participant names are intentionally left empty and do not block
development. Use fictional organizations and resources until onboarding.

Design:
A welcoming local service with readable schedules, straightforward navigation,
and configurable customer identity and location for future deployments,
and accessible mobile forms. Do not imply municipal endorsement or confirmed
organization participation without evidence.
```
