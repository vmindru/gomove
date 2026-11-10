# GoMove implementation plan

Planning baseline: 2026-10-09. Implementation update: a presentation MVP subset is implemented (containers stopped at the user’s request; see [saved state and remaining work](session-state.md)); see [implemented behavior and remaining work](mvp-demo.md). Phase checkboxes remain open until all corresponding exit checks are met. This roadmap describes work to do; it does not mark application features as implemented or authorize production deployment. Follow the phases in order and record completed tasks and evidence in their detail files.

## Product and current baseline

GoMove is an independent sports facility rental service. Organizations publish facilities and independently rentable spaces; residents discover availability and submit booking requests. Managers maintain schedules and decide requests. Platform administrators onboard organizations, manage access, and approve the first publication of each facility. Šlapanice is the first customer, not the product owner or a fixed geographic limit.

The existing application is a Laravel 12 / React / Inertia starter with TypeScript, registration, login, account settings, and a placeholder dashboard. Apache/PHP 8.4 and PostgreSQL 17 run locally through Podman at http://localhost:8080. Frontend assets are built with Node.js 22. Since the planning baseline, booking models, public listings, and core management workflows have been implemented for the local presentation MVP. The rest of the paragraph records the original starting point. Reuse this application and its free UI components.

Sources: [project description](project-context.md), [accepted decisions](decisions.md), [architecture](server-architecture.md), and [local containers](local-containers.md). The current decision table and architecture supersede historical Next.js proposals. Proposed implementation choices below do not become accepted business decisions merely by appearing in this plan.

## First release boundaries

Deliver Czech, responsive, accessible browsing, resource availability, authenticated booking requests, manual and automatic approval, requester booking history, organization calendar management, initial listing review, and reliable decision notifications. Support named, independently bookable facility parts from the first release. Use CZK and Europe/Prague, translation catalogs, and configurable customer branding.

Keep these accepted rules throughout implementation:

- Public browsing is enabled by default; an administrator can require sign-in on listing and availability endpoints.
- First publication needs administrator approval; later organization edits need no further review.
- Pending and confirmed requests block their part immediately. First recorded reservation wins; later overlaps fail atomically. Adjacent intervals are allowed with no buffers.
- Public availability exposes time ranges only, without requester identities, contacts, or private notes.
- Manual approval is the organization default, with a toggle for automatic approval. Managers own calendar accuracy.
- Payment is arranged directly with the organization. The shared platform explicitly scopes data to customers and organizations.

Defer online payments, recurring public bookings, external calendar synchronization, reviews, native mobile apps, and municipality migration tooling. Multiple slot selection and optional manager-enabled whole-field reservations covering every part are now accepted and implemented. Arbitrary overlapping half/quarter combinations remain deferred; do not infer spatial containment from names.

## Ordered delivery phases

| Order | Phase | Visible result | Prerequisite |
| --- | --- | --- | --- |
| 1 | [Foundation, data, and access](implementation/01-foundation.md) | Czech GoMove shell, fictional data, customer and organization isolation | Current local containers |
| 2 | [Facilities and discovery](implementation/02-catalogue.md) | Managers prepare listings; administrator publishes; residents browse | Phase 1 |
| 3 | [Availability and reservation integrity](implementation/03-availability.md) | Schedules and named-part calendars with transactional conflict protection | Phases 1–2 |
| 4 | [Complete booking flow](implementation/04-booking-flow.md) | Requester books; manager approves; requester sees the result | Phases 1–3; booking policy proposals reviewed |
| 5 | [Management and notifications](implementation/05-management.md) | Organizations independently manage calendars, requests, photos, and notifications | Phase 4 |
| 6 | [Verification and pilot readiness](implementation/06-verification-launch.md) | Tested local MVP and documented staging/launch process | Phases 1–5 |

Finish each phase with its acceptance checks before moving on. Keep changes small: migrations and policies first, application services next, then controllers and screens. No delivery dates are committed; estimate after phase 1 and the unresolved rules are reviewed.

## Local MVP demonstration

The first complete demonstration arrives at the end of phase 4: a fictional manager prepares named parts and opening hours; an administrator approves the listing; a resident browses a part, signs in, requests a free interval, and sees pending status; another overlapping request fails; a manager approves; the resident sees confirmation. A second independent part remains available, and adjacent bookings succeed. Automatic approval also works without bypassing integrity checks. Show Czech labels and clearly mark fictional demo content.

Phase 5 completes the operational first release; phase 6 establishes readiness for a participant pilot. A phase 4 demo is not evidence that email delivery, backups, privacy processes, or production operations are ready.

## Decisions and dependencies

[Open decisions and proposed local defaults](implementation/00-open-decisions.md) separates choices needed for booking implementation from launch-only dependencies. Names of real organizations, the exact operator, production hosting, and migration tooling do not block the local core flow. Do not silently introduce an expiry duration, cancellation cutoff, pricing scheme, or capacity-combination rule.

## Working through the plan

Each detail file lists ordered tasks, likely code locations, and exit checks. Paths are proposed locations for future code, not assertions that those files exist. Keep [decisions](decisions.md) authoritative, record reviewed choices there, and update this roadmap if scope changes. Run database integrity checks against isolated PostgreSQL test data, never the running development booking database.
