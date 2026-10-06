# Next steps and proposed milestones

## 1. Review the proposal

Review the README and decision table. Mark recommendations as accepted, changed, or deferred. Prioritize availability maintenance, booking rules, authentication, and budget. Real participant names are deferred to launch preparation and do not block development.

Deliverable: an agreed pilot scope and updated decision record.

## 2. Validate the workflow

Collect sample data from participants and sketch public browsing, resource details, booking requests, manager schedules, and request review screens. Walk through normal bookings, simultaneous attempts at the same slot, pending reservations, closures, and cancellations with a manager and a resident.

Deliverable: reviewed screen flows, sample content, and acceptance criteria.

## 3. Specify the system

Design organizations, memberships, facilities, resources, availability rules, exceptions, requests, and audit records. Define the permission matrix, booking state transitions, conflict handling, registered-account access and recovery, email verification policy, and notification retry behavior.

Deliverable: an implementation-ready specification and migration plan.

Use explicit municipality/customer scoping for the initial shared deployment. Keep configuration reusable and outline how customer records and photos can move to future separate municipality installations. Do not implement migration tooling in the first release.

Size the initial deployment for approximately 100 users and 5–10 sports organizations. Outline a growth path toward the theoretical maximum of 10 million registered users and 1,000 facilities. Define workload assumptions, growth triggers, and staged load tests. The theoretical maximum is not a launch provisioning or performance requirement.

## 4. Build one complete booking flow

Implement a resource listing, availability view, registration and sign-in, authenticated request submission, manager approval, and decision notification. Add transactional overlap protection and organization access controls from the beginning. Use fictional demo data locally.

Deliverable: a working end-to-end flow in a development environment.

## 5. Complete pilot management features

Add listing editing, photos, a built-in GoMove calendar with weekly availability, exceptions and manually entered external bookings, cancellations, organization onboarding, and audit history. External calendar imports and integrations remain deferred. Include useful loading, empty, error, and success states.

Deliverable: an application that pilot managers can operate independently.

## 6. Verify and prepare launch

Test permission isolation, concurrent approvals, calendar boundaries, daylight-saving transitions, email failures, and cancellation behavior. Review usability and accessibility. Complete the launch checklist, deploy to staging, and obtain participant acceptance before production rollout.

Deliverable: an accepted pilot with documented setup, support, backups, and recovery.

## 7. Run the pilot and decide what follows

Track successful requests, response times, schedule accuracy, notification failures, and support feedback. Expand only after managers can maintain reliable availability. Reconsider payments, recurring bookings, and integrations using evidence from the pilot.

No delivery dates are committed. Estimate milestones after participants and business rules are confirmed. The current task stops at documentation for review; implementation has not started.
