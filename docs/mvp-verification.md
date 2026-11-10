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
| Initial pricing rehearsal | Tested an older 75-minute configuration; a restoration error was corrected in the follow-up below |
| Automatic approval | Manager enabled automatic mode; a new browser request was immediately confirmed; manual mode restored |
| Cancellation | Requester cancelled the automatic request; status changed to cancelled and availability was released |
| Date navigation | Moving to another date cleared the previous time selection |
| Browser runtime errors | None during successful end-to-end rehearsals |

Backend coverage includes registration/recovery/settings, first-publication approval and immediate later edits, customer/requester/manager access, restricted browsing, pending conflict rejection, adjacent/independent bookings, per-slot and agreement pricing, hourly fractions, 15-minute validation, requests spanning several configured slots, manager blocks, protected opening-hour edits, submission retry deduplication, and direct PostgreSQL constraint rejection.

All automated database tests used `gomove_test_mvp`, with a bootstrap guard before database refresh. Browser rehearsals used clearly fictional presentation accounts in the local demo database and left two confirmed tennis requests plus one cancelled automatic request as demonstration history. Pricing and manual-approval defaults were restored after testing.

This evidence validates the local presentation flow. It does not establish production capacity, a complete accessibility audit, real email delivery, backup recovery, or completion of every roadmap phase. The [demo guide](mvp-demo.md) lists the remaining work and explicit prototype rules.

## Field slot and multiple-selection follow-up after mvp-0.1

The initial browser rehearsal used a positional selector and left Kurt 1 on its test rate. Its original 280 CZK/hour and 60-minute field slot were restored. Resource queries now order by stable ID, with regression coverage.

The final field configuration accepts only 15, 30, or 60 minutes. The calendar renders complete intervals of that length, with multiple selection, adjacent grouping, separated intervals, and matching totals. A submission saves every selected interval or rejects the entire selection if any interval conflicts. Retry deduplication and stale-setting checks are covered.

Checks: 45 backend tests / 225 assertions passed against isolated PostgreSQL; the concurrent booking rehearsal again produced exactly one success and one conflict. TypeScript, frontend lint, production asset build, and PHP formatting passed. Desktop Chrome verified all three saved field configurations: 56 fifteen-minute, 28 thirty-minute, and 14 sixty-minute intervals for the 08:00–22:00 tennis field. Multiple selection, deselection, and duration summaries passed. The 390×844 mobile layout had no horizontal overflow; no browser runtime errors occurred. Kurt 1 was restored to 60 minutes. A signed-in visitor submitted two adjacent one-hour slots as one 14:00–16:00 request at 560 CZK; both calendar intervals became unavailable. Cancellation released the intervals and preserved the rehearsal history.

The local `mvp-0.1` tag still points to commit `f4a4f95`, before this follow-up. Runtime configuration and the generated demo password are outside the source checkpoint.

## Optional whole-field booking follow-up

Managers now have a per-facility **Povolit rezervaci celého hřiště** toggle. It is disabled by default. Enabling creates a separately configurable whole-field resource with agreement pricing until the manager defines a rate. Calendar availability requires every individual part to be free and open. Whole bookings and manager blocks occupy every part through database-enforced occupancy rows. Turning off the option hides it from visitors while existing reservations retain occupancy.

The final backend suite passed **51 tests / 329 assertions** against isolated PostgreSQL, including a repeat database refresh. It covers opt-in and manager/customer scoping, partial-versus-whole conflicts in both directions, confirmation/cancellation/decline, independent whole-field prices, atomic multi-slot submission and retry handling, every part’s hours, protected opening-hour edits and part addition, manager blocks, and direct database overlap rejection. Separate concurrent processes tested both same-part and part-versus-whole races; each produced one reservation and one rejected conflict.

Chrome verified manager enable/disable, absence of the option on tennis, a half-field booking disabling the matching whole-field intervals, a whole-field booking disabling both halves, preserved occupancy after disabling, and cancellation releasing availability. The 390×844 viewport had no horizontal overflow and no browser runtime errors. Football at `/facilities/2` was left enabled for presentation; rehearsal bookings were cancelled. TypeScript, changed-file ESLint, PHP formatting, migration, production frontend build, and the `/up` health check passed.
