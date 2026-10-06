# GoMove — sports facility rentals

GoMove is the working name for an independent web application where sports organizations can list facilities and rentable spaces, and residents can find available times and request bookings. Šlapanice is the first customer deployment, not the product owner or its geographic limit.

**Status: planning and review.** No application has been implemented. GoMove is the accepted working name and may be changed later. Accepted decisions are recorded in the decision document; remaining proposals await review. GoMove is independent, with Šlapanice as its first customer deployment.

## Proposed first release

- Czech-language, accessible website that works well on phones and computers, with multilingual support for adding languages later.
- Public facility listings with photos, addresses, sports, amenities, prices, and rental conditions.
- Accepted: administrator approval before first publication; organizations can publish later edits without further approval.
- Accepted: browsing without an account by default, with an administrator toggle to require sign-in for listings and availability.
- Availability for individual courts, fields, halls, or other rentable resources.
- Accepted: visitors see booked/unavailable times without requester identities or private booking details.
- Accepted: manual booking approval by default, with an organization toggle for automatic approval.
- Accepted: pending requests reserve the slot immediately and make it unavailable until declined or cancelled; any automatic expiry policy remains undecided.
- Accepted: exact start and end times, no grace periods or buffers, and back-to-back bookings allowed.
- Accepted: the first recorded reservation wins; later overlapping requests receive an error.
- Accepted: organization managers maintain availability, including training, closures, and bookings received outside GoMove.
- Accepted: a built-in GoMove calendar initially; external calendar imports and integrations may be added later.
- Platform administration for organization onboarding and access management.
- Accepted: registration and sign-in required to submit and manage booking requests.
- Accepted: payment arranged directly with the organization initially; payment facilitation may be added later.
- Proposed: email notifications; email verification policy remains open.

Expect to start with approximately 100 users and 5–10 sports organizations. The theoretical long-term scale is 10 million registered users and 1,000 registered sports facilities, without a hard organization limit. Start with infrastructure appropriate to the initial workload and grow based on measured demand. Peak traffic, budget, and performance validation remain open.

## Recommended technology

| Area | Proposal |
| --- | --- |
| Programming language | Accepted: TypeScript with strict checking |
| Application | Accepted: Next.js and React |
| Delivery | Accepted: responsive, accessible web application for phones, tablets, and computers |
| Customer deployment | Accepted: shared platform initially; separate municipality installations in the future |
| Database | Accepted: self-hosted PostgreSQL |
| Authentication and photo storage | Open: select separately |
| Website language | Accepted: Czech at launch; multilingual support for adding languages later |
| Code and technical documentation | Accepted: English |
| Prices and local time | Accepted: CZK, Czech date formatting, and Europe/Prague with daylight-saving handling |

Accepted choices are marked above; remaining choices await review. Nothing has been installed. PostgreSQL will be self-hosted; the hosting location, application hosting, operating costs, authentication, and photo storage remain undecided. See [Next.js TypeScript documentation](https://nextjs.org/docs/app/api-reference/config/typescript).

## Review documents

1. [Project description and context](docs/project-context.md)
2. [Decisions and open questions](docs/decisions.md)
3. [Things to do before development](docs/before-development.md)
4. [Next steps and proposed milestones](docs/next-steps.md)
5. [Project kickoff prompt](docs/kickoff-prompt.md)

Review the ownership, pilot participants, availability maintenance, booking rules, and budget first. These choices determine the implementation scope.

There are no installation or execution commands yet. Add them when implementation begins.
