# Phase 1 — Foundation, data, and access

Prerequisite: the [existing local containers](../local-containers.md). Next: [catalogue](02-catalogue.md).

## Ordered tasks

1. [ ] Confirm the Laravel starter baseline: registration, login/logout, password reset, settings, frontend build, migrations, and /up. Preserve existing environment files and database data. Add an isolated PostgreSQL test database; the current SQLite test configuration cannot verify PostgreSQL overlap constraints.
2. [ ] Replace starter navigation and branding with a GoMove shell. Remove Laravel deployment promotion from the public page. Introduce Czech translation catalogs shared with React and translated Laravel validation/errors. Keep product/customer names and operator contact configurable. Reuse free existing components and strict TypeScript.
3. [ ] Write migrations and model relationships for customers, organizations, memberships, facilities, and resources. Add booking/calendar tables in phase 3 and notification tables in phase 5. Define identifiers, foreign keys, tenant relationships, and indexes before controllers.
4. [ ] Add scoped queries, route binding, Laravel policies, and form-request authorization. Never trust a client-supplied organization/customer ID. Derive the tenant from an authorized route context; verify relationships on every read and write.
5. [ ] Implement platform-administrator assignment through a controlled setup command, not public registration. Implement manager membership checks; a user may hold multiple explicit memberships. Keep requester ownership distinct from manager permissions. Reject revoked memberships immediately.
6. [ ] Seed two clearly fictional customers and organizations, two managers, an administrator, and requester accounts through an explicit development-only seeder. Seed named parts and schedules as later migrations land. Document how to create demo accounts without publishing passwords in source or production.
7. [ ] Add structured audit records for publication, membership, schedule, and booking changes as those actions become available. Record actor, scope, entity, action, timestamp, and minimal change metadata; avoid copying private notes or credentials into logs.

## Proposed data model

| Entity | Essential fields and relationships |
| --- | --- |
| Customer | Name, slug, configurable branding; customer scope for organizations |
| Organization | Customer, name, public contact, payment/arrival instructions, approval mode (manual default) |
| Membership | User, organization, active/revoked status; unique user/organization membership |
| Facility | Organization, name, address, description, accessibility, amenities, publication state, first approval timestamp |
| Resource | Facility, unique name within facility, sport/type, surface, capacity description, pricing model (hourly/per-slot/agreement), CZK rate when numeric, booking policy, active flag |
| Availability rule | Resource, weekday, local start/end, effective date range; Europe/Prague interpretation |
| Calendar occupancy | Resource, UTC start/end, active/released state, kind (booking, closure, training, external booking); the common conflict boundary |
| Booking | Occupancy, requester, status, approval-mode snapshot, message, price/terms snapshot, decision metadata |
| Audit event | Actor, customer/organization scope, action, target, timestamp, minimal metadata |
| Photo | Facility, storage key, alt text, order, uploader; no arbitrary client-controlled file paths |
| Notification delivery | Booking event, recipient, channel, unique delivery key, attempt/failure metadata |

Treat a named part as a Resource, whether it is a whole independent court or one independent half. Do not introduce separate “part” and “resource” booking identities. Use integer minor units for monetary amounts. Prefer deactivation over deleting resources or memberships referenced by historical bookings.

Enforce consistent parentage with foreign keys and, where scope IDs are duplicated, composite tenant-aware constraints. PostgreSQL row-level policies are planned as defense in depth for sensitive tenant tables: define application-role restrictions and transaction-local tenant context, test with the actual non-superuser role, and avoid pooled context leakage. A migration owner/superuser test does not demonstrate isolation. Keep Laravel authorization mandatory even with database policies.

## Permission matrix

| Action | Visitor | Requester | Organization manager | Platform administrator |
| --- | --- | --- | --- | --- |
| Read approved listings/availability | If browsing enabled | Yes | Same public rules; own drafts allowed | Review access |
| Create/view/cancel private request | No | Own requests; cancellation policy applies | Own organization; agreed manager actions | Explicit support scope only; policy unresolved |
| Edit listing/schedule, decide requests | No | No | Own organization only | No implicit manager bypass |
| First-publication approval | No | No | Submit for review | Yes |
| Organization/membership onboarding | No | No | No implicit access-management permission | Yes |
| Change platform browsing setting | No | No | No | Yes |

One account can act as requester and manager, but each action uses its relevant policy. Public routes must enforce current browsing settings and publication state server-side. Do not serialize full Eloquent user/booking objects into public Inertia props.

## Likely code locations

`database/migrations`, `database/seeders`, `app/Models`, `app/Policies`, `app/Http/Middleware`, `app/Http/Requests`, `resources/js/layouts`, `resources/js/types`, translation catalogs, and `routes/web.php`. Prefer services for booking mutations once phase 3 starts.

## Exit checks

- A fresh local setup migrates and loads fictional data without dropping existing databases.
- Czech navigation works on phone and desktop; registration cannot grant manager/admin rights.
- Cross-organization/customer reads and writes fail, including guessed IDs and relationship reassignment; revoked membership loses access.
- Requester private details remain private even when browsing is sign-in-only.
- TypeScript/build checks pass; authorization tests run against a separate PostgreSQL database where relevant.
