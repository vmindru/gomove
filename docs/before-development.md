# Things to do before development

Use this checklist during project review. Unchecked items are outstanding; they do not all need to block a local prototype. Ownership, pilot scope, and booking semantics should be resolved before implementing the production booking model. Launch requirements must be complete before public release.

## Discovery and ownership

- [ ] Identify the company-associated operator, ownership, and responsibilities before launch; GoMove is the accepted working name. Core development can proceed before these operational details are finalized.
- [ ] Confirm Šlapanice customer-specific branding permissions and deployment arrangements; GoMove is an independent product.
- [ ] Assign responsibility for initial listing reviews; approval is required only before first publication, and later organization edits do not need reapproval.
- [ ] Before launch, identify participating organizations and managers. Leave the list empty during development; approximately 100 users and 5–10 organizations are sizing estimates, not prerequisites.
- [ ] Interview managers about current schedules, requests, payments, cancellations, and support needs.
- [ ] Interview prospective public users about finding and renting facilities.
- [ ] Before onboarding, choose actual pilot resources. Develop with fictional examples; define supported resource behavior independently of participant names.

## Booking and availability rules

- [ ] Define the manager workflow for calendar updates and external bookings; organization managers are the accepted calendar owners.
- [ ] Specify the built-in calendar screens and editing controls; external imports and integrations are outside the first release.
- [ ] Define handling of existing pending requests when approval mode changes and whether per-resource overrides are needed; manual approval is the default with an organization toggle for automatic approval.
- [ ] Define whether pending reservations expire automatically, the duration if applicable, and reminders; pending requests reserve time immediately.
- [ ] Define minimum/maximum duration and allowed start-time increments.
- [ ] Define advance notice and future booking horizon; bookings have exact boundaries with no turnaround buffers or grace periods.
- [ ] Define closures, recurring training blocks, and date-specific exceptions.
- [ ] Define request response expectations and any expiry behavior.
- [ ] Define requester and manager cancellation rules.
- [ ] Define hourly rounding, quote validity, and any essential rate variations. Accepted: organizations define slot durations in 15-minute increments. Accepted: organizations choose hourly or per-slot CZK rates, with “price by agreement” for custom models.
- [ ] Decide whether individuals, groups, and organizations use the same request flow.
- [ ] Choose the authentication implementation, account recovery and revocation rules, and email verification policy; registration is required for booking requests.

## Content and prototype

- [ ] Gather organization and facility names, addresses, contacts, and descriptions.
- [ ] Obtain permission to use photographs and logos.
- [ ] Collect resource details, amenities, accessibility information, and rental conditions.
- [ ] Gather opening hours, blocked times, prices, and arrival instructions.
- [ ] Review Czech terminology and copy with participants.
- [ ] Define multilingual content handling and Czech fallback for organization-provided descriptions.
- [ ] Walk through a prototype: publish a court, block training, request a slot, approve it, and cancel it.
- [ ] Test the prototype on a phone and with keyboard navigation.
- [ ] Agree on measurable pilot acceptance criteria.

## Technical preparation

- [ ] Approve the stack and estimate recurring service costs.
- [ ] Define initial peak traffic, booking volume, and performance criteria for approximately 100 users and 5–10 organizations; outline growth triggers toward the theoretical maximum of 10 million registered users and 1,000 facilities.
- [ ] Choose development, staging, and production environments.
- [ ] Decide who owns service accounts, domain, and billing.
- [ ] Choose a hosting region and email delivery provider.
- [ ] Choose the self-hosted PostgreSQL deployment location and maintenance owner.
- [ ] Select authentication and photo storage solutions separately.
- [ ] Approve the organization/resource/booking data model and permission matrix.
- [ ] Include municipality/customer scoping and isolation for the shared platform, with a data-separation path for future municipality installations.
- [ ] Define transactional overlap prevention and schedule-change behavior for existing bookings.
- [ ] Plan database migrations, secret management, and deployment.

## Before public launch

- [ ] Have the operator review privacy information and booking terms.
- [ ] Define necessary personal data, permitted access, retention, and deletion processes.
- [ ] Establish support contacts and responsibility for unanswered requests.
- [ ] Configure the domain and authenticated email sender.
- [ ] Set up error monitoring and notification-failure handling.
- [ ] Configure backups and verify a recovery procedure.
- [ ] Verify organization isolation, simultaneous approvals, cancellations, and daylight-saving cases.
- [ ] Obtain participating organizations' acceptance of their listings and workflow.

These are planning items, not a statement that legal or operational compliance has already been established.
