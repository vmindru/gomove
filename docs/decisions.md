# Decisions and open questions

The sports rental purpose, Šlapanice as the first customer deployment, and documentation-first stage have been requested by the user. Accepted choices are recorded below; remaining proposals await review.

## Proposed product and technical decisions

| Topic | Recommended starting point | Status |
| --- | --- | --- |
| Product name | GoMove as a working name; may be renamed later | Accepted 2026-10-05 |
| Public language | Czech at launch; multilingual support from the start for adding languages later | Accepted 2026-10-05 |
| Technical language | TypeScript; English code and technical documentation | Accepted 2026-10-05 |
| Framework | Next.js with React | Accepted 2026-10-05 |
| Database | Self-hosted PostgreSQL | Accepted 2026-10-05 |
| Authentication and photo storage | Select separately for the self-hosted setup | Open |
| Delivery | Responsive, accessible web application for phones, tablets, and computers | Accepted 2026-10-05 |
| Customer deployment | One shared platform initially; separate installations per municipality in the future | Accepted 2026-10-05 |
| Scale and rollout | Start with approximately 100 users and 5–10 sports organizations; theoretical long-term scale of 10 million registered users and 1,000 facilities | Accepted 2026-10-05; traffic assumptions open |
| Browsing | Public without an account by default; platform administrator can toggle sign-in requirements for listings and availability | Accepted 2026-10-05 |
| Requester access | Registration and sign-in required to submit and manage booking requests | Accepted 2026-10-05; email verification policy open |
| Booking approval | Manual by default; organization managers can toggle automatic approval | Accepted 2026-10-05 |
| Pending requests | Reserve the slot immediately while awaiting approval; unavailable for overlapping requests | Accepted 2026-10-05; expiry policy open |
| Booking conflict protection | First recorded reservation wins; reject later overlapping requests with an error | Accepted 2026-10-05 |
| Booking boundaries | Exact start and end times; no grace period or buffer before or after; back-to-back bookings allowed | Accepted 2026-10-05 |
| Payments | Direct payment to the organization initially; payment facilitation may be added later | Accepted 2026-10-05 |
| Listing publication | Administrator approval before first publication; organizations publish later edits without further approval | Accepted 2026-10-05 |
| Public calendars | Show booked/unavailable slots without requester identity, contact details, or private notes | Accepted 2026-10-05 |
| Localization | CZK prices, Czech date formatting, and Europe/Prague time with daylight-saving handling | Accepted 2026-10-05 |

## Decisions needed before implementation

| Question | Why it matters |
| --- | --- |
| Which company will operate the platform, and who owns it? | Company-associated operation is selected; exact entity, ownership, and responsibilities remain to be confirmed before launch |
| What customer-specific branding and deployment arrangements does Šlapanice need? | GoMove is independent; Šlapanice is the first customer deployment, not the product owner |
| Which organizations and resources join the pilot? | Leave the list empty for now; confirm for onboarding before launch, not as a development prerequisite |
| Who maintains availability, and how often? | Accepted: organization managers maintain training, closures, and external bookings; update cadence remains unspecified |
| Calendar implementation | Accepted: build calendar management in GoMove initially; external imports and integrations deferred |
| Are resources exclusively rentable, shared, or divisible? | Determines capacity and conflict rules |
| What are duration, start-time increments, advance notice, and booking horizon rules? | Determines valid slots; zero buffers and exact start/end boundaries are already accepted |
| How long may requests remain unanswered? | Determines notifications, escalation, and any expiry behavior |
| What cancellation and manager-cancellation rules apply? | Determines status transitions and messages |
| What pricing variations are essential? | Determines whether simple rates suffice |
| What requester information is required? | Determines forms, access, and retention |
| How are accounts recovered or revoked, and is email verification required before requesting a booking? | Determines account security and recovery flows |
| What budget and hosting constraints apply? | Determines managed services and deployment options |
| Who handles backups, incidents, and ongoing maintenance? | Determines operational readiness |
| What authentication and photo storage solutions should be used? | Supabase is no longer the proposed backend; these services need separate choices |
| What peak concurrency, request rates, and booking volume should be supported? | The 10 million user target means registered users; traffic assumptions determine capacity planning and load-test targets |

## Deferred scope

- Online payments, deposits, and refunds through the platform.
- Recurring public bookings and subscriptions.
- External calendar imports, integrations, and synchronization.
- Public reviews and ratings.
- Native mobile applications.
- Complex shared-resource and whole-facility booking combinations unless required by the pilot.

## Decision record

For each accepted or changed decision, record: date, decision, owner, rationale, and implementation consequences.

### 2026-10-05 — Built-in calendar first

- Decision owner: project owner (user).
- Decision: Create calendar functionality in GoMove for the first release. Add external calendar imports and integrations later.
- Rationale: The project owner accepted manual management within GoMove initially.
- Implementation consequences: Managers create and maintain schedules, blocked periods, and external bookings directly in GoMove. GoMove's calendar is the availability source for booking decisions. Do not implement external imports, synchronization, or integration dependencies in the first release; future providers and conflict-resolution rules remain undecided.

### 2026-10-05 — Organization-managed availability

- Decision owner: project owner (user).
- Decision: Each organization's managers maintain its availability calendar, including training sessions, closures, and bookings received outside GoMove.
- Rationale: The project owner accepted organization responsibility for calendar maintenance.
- Implementation consequences: Provide manager controls for regular schedules, blocked periods, closures, and external bookings, restricted to their organization's resources. Changes affect availability and respect existing reservations. No external calendar integration is required initially. The operational update cadence has not been specified.

### 2026-10-05 — Initial participant list deferred

- Decision owner: project owner (user).
- Decision: Leave the initial organization and resource list empty. Identifying participants is not required for development.
- Rationale: The project owner considers participant names irrelevant to product development.
- Implementation consequences: Use clearly fictional organizations and resources for development and verification. Collect real participants and content during launch preparation. The estimate of 5–10 initial organizations remains a sizing assumption, not a named roster or development dependency.

### 2026-10-05 — Shared platform first; municipality installations later

- Decision owner: project owner (user).
- Decision: Use one shared platform initially. Plan for separate installations for each municipality in the future.
- Rationale: The project owner selected a shared starting deployment and future municipality-specific installations.
- Implementation consequences: Associate organizations and resources with an explicit municipality/customer scope and enforce isolation. Keep deployment configuration reusable and customer identity configurable. Plan a path to exporting and migrating a municipality's records and photos into a separate installation. Do not build migration tooling or provision separate installations in the first release.
- Open questions for future separation: Migration timing, cross-municipality account handling, and any shared identity services. These do not block initial implementation.

### 2026-10-05 — Independent product; Šlapanice first customer

- Decision owner: project owner (user).
- Decision: GoMove is an independent product. Šlapanice is the customer where it will be installed first.
- Rationale: The project owner clarified the relationship between the product and its initial location.
- Implementation consequences: Do not hard-code Šlapanice into product logic or branding. Keep customer name, location, and relevant deployment settings configurable for future customers. The subsequent deployment decision selects a shared platform initially and separate municipality installations later. Municipal endorsement and customer-specific branding permissions must not be inferred.

### 2026-10-05 — Company-associated operation

- Decision owner: project owner (user).
- Decision: The platform will be associated with a company. The specific company, legal ownership, and operational responsibilities have not been identified.
- Rationale: The project owner selected company-associated operation.
- Implementation consequences: Keep operator identity, support contact, and notification sender configurable. Assign production service accounts and billing to the agreed operator before launch. This does not block development of the core booking workflow.

### 2026-10-05 — Localization

- Decision owner: project owner (user).
- Decision: Use CZK prices, Czech date formatting, and Europe/Prague time, including daylight-saving changes.
- Rationale: The project owner accepted all proposed localization settings.
- Implementation consequences: Use locale-aware price and date formatting. Interpret facility schedules in Europe/Prague and store booking instants consistently. Handle ambiguous or nonexistent local times at daylight-saving transitions explicitly and verify those cases. Adding languages later must not silently change facility timezone or currency.

### 2026-10-05 — Public calendar privacy

- Decision owner: project owner (user).
- Decision: Visitors may see that a court or other resource is booked, but must not see who requested the booking or any private booking details.
- Rationale: The project owner confirmed availability-only visibility for visitors.
- Implementation consequences: Public calendar responses contain time boundaries and availability only. Both pending and confirmed reservations appear as booked/unavailable without requester identity, contact details, or notes. Enforce this in server responses rather than hiding fields in the interface. Private details remain subject to requester ownership and organization permissions.

### 2026-10-05 — Approval before first listing publication

- Decision owner: project owner (user).
- Decision: Require platform administrator approval before a facility listing is first published.
- Rationale: The project owner selected approval before initial publication.
- Implementation consequences: Managers can prepare and submit listings for review. Unapproved listings are not visible to public visitors or ordinary registered users and cannot accept public booking requests. Administrators approve first publication. Once approved, organization managers can publish edits immediately without further administrator approval. Continue enforcing organization permissions and recording important listing changes.
- Follow-up decision: The project owner confirmed that organizations update approved listings on their own; no further approvals are needed.

### 2026-10-05 — Direct payments initially

- Decision owner: project owner (user).
- Decision: Users arrange payment directly with the sports organization in the first release. Payment facilitation can be added later.
- Rationale: Start with direct payments and leave platform payment services for a future phase.
- Implementation consequences: Display organization-provided payment instructions where appropriate. No checkout, payment processing, or automated payment confirmation in the initial scope. Keep booking status separate from any future payment status. A future provider, fees, settlement, refunds, and payment workflow remain undecided.

### 2026-10-05 — First recorded reservation wins

- Decision owner: project owner (user).
- Decision: The first successfully recorded reservation wins. A later request overlapping a pending or confirmed reservation on the same resource receives an error. Keep the workflow simple.
- Rationale: The project owner selected straightforward conflict rejection without competing requests or additional coordination workflows.
- Implementation consequences: Use a PostgreSQL overlap constraint and an atomic write to enforce the rule, including simultaneous submissions. Translate a conflict into a clear Czech message that the time is no longer available and refresh availability. Do not add a waitlist or automatic reassignment. Adjacent bookings remain valid under the accepted exact-boundary rule.

### 2026-10-05 — Exact booking boundaries without buffers

- Decision owner: project owner (user).
- Decision: Users start at the booked start time and must stop at the booked end time. No grace time or buffer applies before or after a booking.
- Rationale: The project owner specified end-to-end time slots with exact boundaries.
- Implementation consequences: Treat intervals as start-inclusive and end-exclusive for conflict checking. A booking ending at 11:00 permits another to start at 11:00. Actual overlapping pending or confirmed reservations still make the resource unavailable. The subsequent first-recorded-wins decision defines conflict handling.

### 2026-10-05 — Pending requests reserve time

- Decision owner: project owner (user).
- Decision: A submitted request reserves the slot as pending approval and makes it unavailable for other bookings.
- Rationale: The project owner requires pending requests to block availability.
- Implementation consequences: Atomically check availability and create the pending reservation. Treat pending and confirmed bookings as blocking states, including across manual and automatic approval paths. Approval retains the reservation; decline or cancellation releases it. Public calendars show the slot as unavailable without exposing requester details.
- Open question: Whether pending reservations expire automatically and, if so, after how long and with what reminders. Do not assume an expiry duration.

### 2026-10-05 — Configurable booking approval

- Decision owner: project owner (user).
- Decision: Default to manual booking approval and provide a toggle for automatic approval.
- Implementation interpretation: Organization managers control the setting for their organization. Per-resource overrides and handling of existing pending requests when toggling are not yet decided.
- Rationale: Support manual review by default while allowing organizations to offer immediate confirmation.
- Implementation consequences: Both approval modes must enforce availability and booking rules and prevent overlap with pending or confirmed bookings atomically. Show the applicable approval mode to requesters. Automatic approval confirms a valid request during submission; it does not bypass authentication or conflict checks.

### 2026-10-05 — Registration required for booking requests

- Decision owner: project owner (user).
- Decision: Require registration and sign-in to submit and manage booking requests.
- Rationale: The project owner selected registered accounts instead of account-free access through secure request links.
- Implementation consequences: Associate requests with requester accounts and enforce ownership when viewing or cancelling them. Include registration, sign-in, and account recovery flows. Public browsing remains independently configurable. Authentication implementation and the email verification policy remain open.

### 2026-10-05 — Configurable public browsing

- Decision owner: project owner (user).
- Decision: Allow visitors to browse facilities and availability without an account by default, and make this behavior toggleable.
- Implementation interpretation: A platform-wide administrator setting switches between public browsing and sign-in-required browsing. Per-organization controls have not been requested.
- Rationale: The project owner accepted public browsing and requested configurability.
- Implementation consequences: Enforce the setting on the server for listing pages and availability endpoints, not just in navigation. Ensure cached public responses do not bypass a switch to restricted access. Booking authentication remains a separate decision.

### 2026-10-05 — Working name

- Decision owner: project owner (user).
- Decision: Keep GoMove as the working name. It may be changed at any time later.
- Rationale: Proceed with the current name without treating it as permanent branding.
- Implementation consequences: Keep the product name centrally configurable when implementation begins so a later rename is straightforward.

### 2026-10-05 — Website language and multilingual support

- Decision owner: project owner (user).
- Decision: Launch in Czech and implement multilingual support so new languages can be added later. No additional launch language has been selected.
- Rationale: Serve local users first while supporting future language expansion.
- Implementation consequences: Use translation keys and locale catalogs for user-facing text, locale-aware formatting, and a documented process for adding languages. Plan how organization-provided content is handled across languages, with Czech fallback for missing translations.

### 2026-10-05 — Programming and technical documentation language

- Decision owner: project owner (user).
- Decision: Use TypeScript, with English for code and technical documentation.
- Rationale: The project owner accepted the recommended technical language choice.
- Implementation consequences: Configure strict TypeScript checking and use English identifiers, comments, and technical documentation. User-facing text follows the separate Czech and multilingual-support decision.

### 2026-10-05 — Application framework

- Decision owner: project owner (user).
- Decision: Use Next.js with React for the public website and management screens.
- Rationale: The project owner accepted the recommended framework.
- Implementation consequences: Build the public and authenticated management experiences in one Next.js application using TypeScript.

### 2026-10-05 — Self-hosted database

- Decision owner: project owner (user).
- Decision: Use self-hosted PostgreSQL instead of PostgreSQL through Supabase.
- Rationale: The project owner selected self-hosting.
- Implementation consequences: Manage PostgreSQL deployment, access, migrations, backups, recovery, and updates. Select authentication and photo storage separately. The hosting location and hosting approach for the application are not yet decided.

### 2026-10-05 — Delivery format

- Decision owner: project owner (user).
- Decision: Build a responsive, accessible web application for phones, tablets, and computers.
- Rationale: The project owner accepted the recommended delivery format.
- Implementation consequences: Support different screen sizes, keyboard navigation, labeled forms, and readable schedules. Include mobile usability and accessibility checks in pilot review.

### 2026-10-05 — Scalability instead of a fixed pilot size

- Decision owner: project owner (user).
- Decision: Expect an initial rollout of approximately 100 users and 5–10 sports organizations. These are planning estimates, not hard limits. The theoretical long-term scale is 10 million registered users and 1,000 registered sports facilities. The project owner explicitly clarified that the user target means registered users.
- Rationale: The platform should scale beyond its initial local use.
- Implementation consequences: Start with a simple deployment sized for the initial workload. Keep organization isolation, resource-level booking integrity, indexed and paginated queries, and a path to adding application instances, caching, background processing, and storage capacity as demand grows. Grow self-hosted PostgreSQL capacity based on measured workloads. The theoretical maximum is an architectural growth goal, not an initial provisioning requirement or a launch performance guarantee.
- Open sizing assumptions: Peak concurrency, request rates, booking volume, data growth, availability objectives, and infrastructure budget. Target-scale performance has not been demonstrated.
