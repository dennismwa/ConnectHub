# ConnectHub v10 – Corrected location marquee and mode-specific mobile dock

This update corrects the two issues reported after v9. It is an incremental change to the existing project; it does not replace the application architecture or database.

## 1. Find people in your town – actual alternating mobile rows
- The number of rows is now based on available locations: 2 rows from 4 locations, and 3 rows from 7 locations. Smaller sets remain a single row rather than creating repetitive one-card loops.
- Locations are distributed round-robin so rows remain balanced.
- Each row keeps its own direction (`+1`, `-1`, `+1`) and moves slowly at a slightly different speed.
- The infinite-loop measurement now uses each card's rendered width rather than `offsetLeft`, which could be measured relative to an unrelated positioned ancestor on some layouts.
- Enough duplicate content is placed on either side to keep a continuous loop without blank gaps.
- Touch, wheel, keyboard and focus interaction remain usable; motion pauses for interaction and resumes afterwards. The pause control and reduced-motion preference remain supported.
- Desktop/tablet retain the static grid.

## 2. Mobile navigation – one contextual destination after Home
- Both the authenticated app shell and public-page member dock now use the same `choice_contextual_dock()` helper.
- The dock starts with Home, followed immediately by exactly one mode destination:
  - Dating → Dating (`/discover`, heart)
  - Friends → Friends (`/discover`, users; the Discover feed is rendered in the saved Friends experience)
  - Fun → Escorts (`/hosts`, concierge bell)
  - Elite → Elite (`/elite`, gem)
- Stale or duplicate Discover, Hosts/Escorts and Elite entries are removed from the rendered dock, regardless of their admin-configured position. Other links such as Likes, Messages, Matches and Profile are retained in their existing relative order.
- The dock remains limited to five items. Admin menu records are not deleted or changed; this is a mode-aware presentation layer.

## Validation
- PHP syntax checks passed for all project PHP files.
- `node --check` passed for `assets/js/landing.js`.
- ZIP archive integrity checked after packaging.
- No SQL migration is required.
- Browser/device testing against the live database was not available in this environment; please stage-test at 320, 360, 390 and 430px and switch through all four modes while signed in.
