# Open decisions and proposed local defaults

Return to the [roadmap](../implementation-plan.md). Accepted product rules remain in [decisions](../decisions.md). Every default below is a proposal for review, not an accepted requirement. Build unaffected foundation and catalogue work while these choices are reviewed.

## Resolve before the affected booking feature

| Choice | Proposed local MVP approach | Needed by |
| --- | --- | --- |
| Authentication and recovery | Reuse Laravel session authentication and existing password reset; reject new bookings by revoked accounts and preserve booking history | Phase 1 account design; phase 4 submissions |
| Email verification | Keep current nonmandatory verification for local demonstrations; decide whether to require it before real requests | Phase 4 authorization; pilot onboarding |
| Duration, increments, notice, horizon | Accepted: organizations define slots in 15-minute increments. The local MVP also aligns starts to 15 minutes and permits durations within daily opening hours, with zero additional notice and a 90-day horizon. Final notice/horizon rules remain open | Phase 3 slot generation and phase 4 validation |
| Pending expiry | Do not add automatic expiry until a duration and notification policy are agreed; expose pending age to managers | Phase 4 states; phase 5 operations |
| Approval-mode changes | Apply the setting to new submissions only; keep existing pending requests pending | Phase 4 transitions |
| Cancellation | Propose requester cancellation of own pending/confirmed future bookings; manager cancellation requires a reason; agree cutoffs and past-booking behavior | Phase 4 cancel action |
| Schedule changes affecting reservations | Reject conflicting closures or opening-hour reductions and list affected bookings; require explicit decisions before cancelling anything | Phase 3 schedule mutations |
| Pricing details | Accepted: organizations choose hourly or per-slot CZK rates, with “price by agreement” for custom models. Accepted: organizations define slot durations in 15-minute increments. Decide hourly rounding, variations, and quote validity before calculating totals | Phase 2 fields; phase 4 price snapshot |
| Requester details | Propose account name, email, time range, and optional message; no additional required personal fields | Phase 4 request form |
| Group requests | Use the same requester account flow unless participants require a separate workflow | Phase 4 form |
| Photos | Use Laravel local storage with a persistent host directory for the local MVP; production storage remains open | Phase 5 uploads |
| Administrator private-data access | Organization onboarding and publication review do not imply access to requester notes; agree explicit support permissions and audit requirements | Phase 1 permission matrix |

Single-part bookings are the implementation boundary. Do not infer containment from names such as Half A or Quarter A. Combination behavior needs a separate reviewed model before implementation.

For daylight-saving inputs, propose rejecting nonexistent local times and requiring explicit selection of an offset when a local time is ambiguous. Review Czech error wording and interaction before phase 4. Store valid booking instants consistently in UTC.

## Resolve before public pilot

Identify the operator, support responsibilities, participating organizations, approved content and branding, email provider/sender, domain/HTTPS, photo storage, retention/deletion process, backups/recovery owner, and incident handling. Agree calendar-update and request-response expectations with managers. Complete the existing [launch checklist](../before-development.md).

Agree measurable traffic and performance expectations for approximately 100 users and 5–10 organizations. Proposed local load-test starting point: 20 simultaneous browsers and 5 simultaneous booking writes, with p95 listing/availability responses below 1 second on the selected pilot host. These are review proposals, not a claim of validated capacity. The theoretical 10 million registered users does not determine initial provisioning.

## Decision workflow

1. Record the chosen rule, owner, rationale, and date in the decision record.
2. Update affected phase tasks and acceptance examples.
3. Implement policy values explicitly; do not bury business defaults in frontend code.
4. Demonstrate both valid and rejected cases before marking the affected task complete.
