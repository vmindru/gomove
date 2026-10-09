# Local MVP verification — 2026-10-09

Build under test: the local Laravel/React GoMove presentation MVP at http://localhost:8080. See [presentation guide](mvp-demo.md) for access and implemented scope.

| Check | Result |
| --- | --- |
| TypeScript | `npm run typecheck` passed |
| Changed frontend files | ESLint passed without errors or warnings |
| Production frontend assets | `npm run build` passed inside the web container |
| PHP formatting | Laravel Pint applied to changed backend/test files |
| Backend suite | `./scripts/test.sh`: 39 tests passed, 167 assertions |
| Isolated PostgreSQL race rehearsal | Two independent PHP processes attempted the same part/time: one reservation, one rejected conflict |
| Desktop browser | Chrome rendered catalogue, facility details, requester history, manager UI, and administrator page |
| Mobile browser | 390×844 viewport; catalogue and detail page had no horizontal overflow; screenshots visually inspected |
| Manual booking browser flow | Signed-in requester submitted pending request; manager approved; requester saw confirmed |
| Public privacy | Anonymous facility response had only resource ID and start/end for occupied intervals; no requester fields |
| Independent parts | Reserved court start was disabled; the same time on the other court remained enabled |
| Pricing configuration | Manager changed a resource to 350 CZK per 75-minute slot; booking form showed fixed 75-minute duration; original hourly settings restored |
| Automatic approval | Manager enabled automatic mode; a new browser request was immediately confirmed; manual mode restored |
| Cancellation | Requester cancelled the automatic request; status changed to cancelled and availability was released |
| Date navigation | Moving to another date cleared the previous time selection |
| Browser runtime errors | None during successful end-to-end rehearsals |

Backend coverage includes registration/recovery/settings, first-publication approval and immediate later edits, customer/requester/manager access, restricted browsing, pending conflict rejection, adjacent/independent bookings, per-slot and agreement pricing, hourly fractions, 15-minute validation, 300-minute organization-defined slots, manager blocks, protected opening-hour edits, submission retry deduplication, and direct PostgreSQL constraint rejection.

All automated database tests used `gomove_test_mvp`, with a bootstrap guard before database refresh. Browser rehearsals used clearly fictional presentation accounts in the local demo database and left two confirmed tennis requests plus one cancelled automatic request as demonstration history. Pricing and manual-approval defaults were restored after testing.

This evidence validates the local presentation flow. It does not establish production capacity, a complete accessibility audit, real email delivery, backup recovery, or completion of every roadmap phase. The [demo guide](mvp-demo.md) lists the remaining work and explicit prototype rules.
