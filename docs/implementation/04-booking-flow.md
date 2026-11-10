# Phase 4 — Complete booking flow

Prerequisite: [availability](03-availability.md), plus reviewed submission/cancellation rules. Next: [management and notifications](05-management.md).

## Ordered tasks

1. [ ] Build the request form from the selected resource/date and one or more slots. Show selected intervals, total duration, and price; merge adjacent intervals and submit separated groups atomically. Validate the submitted field duration against current configuration. Show approval mode, rental conditions, the organization-selected hourly/per-slot CZK rate or “Cena dohodou”, and direct-payment instructions. Do not calculate a numeric total for custom/agreement pricing. Require registration/sign-in and preserve the intended resource selection across authentication without trusting stale availability.
2. [ ] Implement an authorized submission service: resolve published scoped resource; validate reviewed rules; lock the resource; recheck availability; atomically write booking, active occupancy, and audit event. Set pending in manual mode and confirmed in automatic mode. Read the organization setting consistently in the transaction and store its value on the booking.
3. [ ] Add submission deduplication with a server-validated key scoped to requester and operation. Refreshes or double clicks should return the existing successful submission; a different payload must not reuse its key. Keep conflict rejection for genuinely competing requests.
4. [ ] Build requester history and private details, showing pending/confirmed/declined/cancelled in Czech. Only the account owner can access these screens. Preserve snapshots of price/terms at submission so later listing edits do not silently change the request.
5. [ ] Build manager request queue, private detail, approve, and decline controls for the manager's own organization. Lock/recheck state before decisions, retain active occupancy on approval, and release it atomically on decline. Approval revalidates applicable availability while excluding the booking's own occupancy.
6. [ ] Add requester and manager cancellation actions only once the policy is reviewed. Record actor and applicable reason, release occupancy atomically, and make repeated decisions/cancellations safe without duplicate audit or notification events.
7. [ ] Add the organization approval-mode toggle. Proposed behavior applies only to new requests and does not convert existing pending requests. Enforce membership authorization and audit setting changes.
8. [ ] Record durable booking events for notifications. Until phase 5 delivery exists, the account status page is authoritative and demo notifications can use the local log mailer. Do not claim email delivery when the configured mailer only logs messages.
9. [ ] Run the complete fictional demonstration from the roadmap and add feature tests for account ownership, stale availability, manual/automatic paths, and transitions.

## State transitions

| Current state | Action | Result | Occupancy |
| --- | --- | --- | --- |
| No booking | Valid manual-mode submission | Pending | Created active |
| No booking | Valid automatic-mode submission | Confirmed | Created active |
| Pending | Manager approve | Confirmed | Retained |
| Pending | Manager decline | Declined | Released |
| Pending/confirmed | Authorized cancellation under agreed rule | Cancelled | Released |
| Declined/cancelled | New desired booking | New validated request | No resurrection of old interval |

No automatic expiry state or duration is assumed. Email verification, if required, gates request submission separately and never becomes a booking status. Concurrent approve/cancel operations must serialize to one valid transition; return current state when an attempted transition is no longer valid.

## Likely code locations

`app/Services/Bookings`, booking policies/form requests/controllers, public availability serializers, `resources/js/pages/bookings`, manager booking pages, factories, and feature/PostgreSQL integration tests. Keep writes in one shared domain service, not separate controller-specific implementations.

## Exit checks

- One fictional resident can discover → sign in → request → see pending → receive manager approval → see confirmed.
- Automatic approval returns confirmed through the same integrity checks; a later conflicting request gets a useful error.
- Decline and permitted cancellation make the slot available again without deleting history.
- Requester A cannot read/cancel requester B's request; manager A cannot decide organization B's request.
- Approval retains the original interval; retries and conflicting decisions do not duplicate records.
- A mobile and keyboard user can complete the flow with clear validation and status feedback.
