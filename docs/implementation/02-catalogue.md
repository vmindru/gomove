# Phase 2 — Facilities and discovery

Prerequisite: [foundation](01-foundation.md). Next: [availability](03-availability.md).

## Ordered tasks

1. [ ] Add manager facility/resource forms with names, address, sports, descriptions, amenities, accessibility, conditions, arrival/payment instructions, and organization-defined CZK rental rates. Support organization-selected hourly, per-slot, or custom/agreement models. Display “Cena dohodou” for custom arrangements. Organizations define slot lengths in 15-minute increments. Review final rounding rules; the local demo prorates hourly rates and rounds upward to the nearest minor unit. Store money precisely; do not introduce payment checkout.
2. [ ] Let managers create any required number of independent named resources. Validate unique names within each facility. Preserve stable IDs when names change; prevent destructive removal of parts referenced by reservations.
3. [ ] Implement draft → submitted → first-approved publication. Administrator rejection returns a reason for revision. Proposed publication model: store first approval separately from published/unpublished state, so previously approved listings can be edited and republished without reapproval. Record review and publication events.
4. [ ] Build the Czech public directory and facility/resource detail pages. Replace the starter homepage with discovery. Include resource types, sports, location, simple price display, conditions, and clearly fictional demo markers. Use placeholders until phase 5 uploads exist.
5. [ ] Add customer-scoped, paginated filtering by sport and resource type. Add date/time availability filtering after phase 3 provides the shared availability service; do not fake availability in the catalogue.
6. [ ] Implement the administrator's platform-wide public-browsing switch. Apply it to HTML and JSON endpoints, resource details, and later availability endpoints. Invalidate relevant caches when access or publication changes; authenticated browsing does not grant draft access.
7. [ ] Add loading, empty, validation, permission-denied, and unpublished states. Use accessible labels, meaningful headings, keyboard navigation, and responsive cards/detail screens.

## Screen flow

Manager dashboard → facility editor → named-resource editor → submit for review. Administrator review queue → review details → approve or return with a reason. Resident homepage → filtered directory → facility details → selected named resource → availability (phase 3) → booking (phase 4).

Proposed route groups: public `/facilities`, authenticated `/manage`, administrator `/admin`. Customer context must be explicit and consistently applied; do not expose every customer's data simply because the platform shares one database.

## Likely code locations

Facility/resource controllers and form requests, publication service, policies, public response serializers, `resources/js/pages/facilities`, `resources/js/pages/manage`, `resources/js/pages/admin`, and feature tests. Extend existing UI components rather than selecting a paid template.

## Exit checks

- Draft/unapproved facilities are absent from every public listing, direct resource URL, availability endpoint, and booking path.
- A manager can publish later edits to a first-approved listing without administrator review.
- Duplicate resource names within a facility fail; the same name in another facility is allowed.
- Visitors browse approved content by default; disabling browsing requires authentication everywhere and invalidates stale access caches.
- Managers can choose hourly or per-slot CZK rates, or custom pricing displayed as “Cena dohodou”. The pricing basis is clear, and custom pricing does not produce a numeric total. Demo content implies neither real participation nor municipal endorsement.
