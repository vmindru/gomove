# Project description and context

## Current saved state — 2026-10-09

GoMove containers are stopped at the user’s request; the application, PostgreSQL data, and generated assets are preserved. The presentation MVP includes 15/30/60-minute field slots, multiple slot selection, and an optional manager-controlled whole-field reservation. See [saved state, remaining plan, and restart instructions](session-state.md) before resuming. Latest verification: 51 tests / 329 assertions, concurrent part/whole conflict checks, and desktop/mobile browser flows passed. The local `mvp-0.1` checkpoint predates the latest worktree changes.

## User-requested purpose

Create an independent web product for sports organizations to list sports facilities and fields available for rent. Members of the public should be able to choose a facility and request a booking. Šlapanice is the first customer deployment, not a permanent geographic restriction on GoMove.

The project description supplies the scope for the [step-by-step implementation plan](implementation-plan.md). A [local presentation MVP](mvp-demo.md) now implements the core catalogue and booking flow; the plan tracks remaining pilot work.

The project owner has selected company-associated operation. The specific company, ownership, and support responsibilities remain to be confirmed before launch; they do not block core product development.

## Problem to validate

The proposed service brings facility discovery and booking requests into one place. Interviews should establish how residents currently discover spaces and how each organization handles bookings. Fragmented listings, phone enquiries, or disconnected calendars are hypotheses until confirmed by participants.

The main operational dependency is accurate availability. Each organization's managers are responsible for maintaining its calendar, including training sessions, matches, closures, and bookings received by phone or email. The product must provide practical controls for this; an operational update cadence remains unspecified.

Calendar functionality will be built into GoMove for the first release and will be the availability source for booking decisions. Managers enter schedules and external bookings directly. External calendar imports and integrations are deferred to a later phase.

## Proposed users and responsibilities

| User | Responsibilities and access |
| --- | --- |
| Public visitor | Browse published listings and availability without signing in when public browsing is enabled; otherwise sign-in is required |
| Requester | Register and sign in to submit requests, view decisions, and cancel according to the applicable rules |
| Organization manager | Maintain the organization's listings and calendars; handle requests and external bookings |
| Platform administrator | Onboard organizations, manage memberships, review listings, and provide support |

Administrator access to personal information should be limited to agreed support needs and recorded where appropriate.

## Facility structure

An organization can operate several facilities. A facility can contain several independently rentable resources. For example, a sports complex could contain two tennis courts and one hall.

Shared capacity is required from the first release. The owner defines the number of bookable parts of a facility and gives each a unique name within that facility, such as "1/2 half 1 (A)" and "1/2 half 2 (B)", or named quarters. Model parts explicitly with stable identifiers. Independent parts can be booked simultaneously; pending and confirmed reservations block only the reserved part. Optional manager-enabled whole-field bookings now cover every individual part of a facility and require every part to be free. Arbitrary overlapping half/quarter configurations remain deferred.

## Proposed core workflow

1. A manager creates a facility and its rentable resources and submits the listing for administrator approval before first publication.

   After first approval, the organization can publish listing edits immediately without further approval.
2. The manager enters regular availability, exceptions, prices, and rental conditions.
3. A visitor chooses a resource or enabled whole field and one or more slots generated from its 15, 30, or 60-minute setting.
4. The requester registers and signs in before submitting the request; whether email verification is mandatory remains undecided. Submission atomically checks availability and reserves the slot as pending when manual approval applies.
5. With manual approval, the manager approves or declines the request. With automatic approval enabled, a valid request is confirmed during submission.
6. Both approval paths check availability and booking rules and record confirmed bookings atomically to prevent conflicts.
7. The requester receives the decision and any arrival or payment instructions.

Recommended booking statuses: pending, confirmed, declined, and cancelled. If email verification is required, keep it separate from booking status. Pending and confirmed bookings both block availability. Approval retains the reservation; decline or cancellation releases it. Automatic expiry of pending reservations remains undecided.

Booking boundaries are exact: users start at the booked start time and must stop at the booked end time. There is no buffer or grace period before or after. Back-to-back bookings are allowed; a booking ending at 11:00 does not conflict with one starting at 11:00.

Conflict handling is first recorded reservation wins: reject a later overlapping request with an error and refresh availability. Enforce this with a database constraint so simultaneous submissions cannot create overlapping pending or confirmed bookings. No waitlist or automatic reassignment is needed.

Visitors see pending and confirmed reservations only as booked/unavailable time ranges. Public calendar responses must exclude requester identities, contact details, and private notes.

## Information to collect

- Organization name, responsible manager, and contact information.
- Facility name, address, description, photos, and location.
- Resource type, supported sports, capacity, surface, indoor/outdoor status, and amenities.
- Accessibility information, rental conditions, and arrival instructions.
- Organization-selected hourly or per-slot CZK rates; custom models display “Cena dohodou” (price by agreement).
- Regular availability, scheduled closures, and booking rules.
- Requester account, name, email, requested time, and optional message; collect additional details only when needed. The email verification policy remains open.

## Proposed boundaries

The first release includes discovery, schedules, requests, configurable approval, notifications, and organization management. Payment is arranged directly with the organization initially; payment facilitation may be added later. Online payments, recurring public bookings, external calendar synchronization, reviews, and native mobile applications are deferred.

No official municipal affiliation, real facility details, or organization participation should be implied without confirmation. Demo data must be clearly fictional.

Keep customer identity and location configurable. Use one shared platform initially, with explicit municipality/customer scoping and isolation. Plan for separate installations per municipality in the future, including a path to migrating municipality-specific records and photos. Future cross-installation account handling remains undecided.

## Accepted localization

Display prices in CZK, format dates for Czech users, and use Europe/Prague for facility schedules, including daylight-saving changes. Additional interface languages do not change the facility timezone or currency automatically.

## Accepted scalability goal

Expect to start with approximately 100 users and 5–10 sports organizations. The theoretical long-term scale is 10 million registered users and 1,000 registered sports facilities, with no hard organization limit. Use a simple initial deployment and expand based on measured demand. The theoretical maximum is not a launch capacity requirement. Define peak traffic and staged performance criteria as the platform grows.

## Proposed success measures

Agree on targets with pilot participants before launch:

- Managers can publish a resource and maintain its schedule without developer assistance.
- Residents can complete a request from a phone and understand whether it is confirmed.
- Pending and confirmed bookings never overlap on the same exclusive resource.
- Booking decisions are communicated reliably.
- Managers keep calendars current and respond within an agreed period.
- Pilot feedback identifies whether the service reduces manual coordination.
