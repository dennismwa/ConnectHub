# ConnectHub v9 – Mobile cards, experience navigation and story rings

This is an incremental update on top of the v8 project. Existing routes, menu configuration, profile data, story data and landing-page sections are preserved.

## Changes

1. **Agent/escort contact actions on small screens**
   - Tightened spacing and reduced the Call/WhatsApp icon controls on narrow phones.
   - Allowed the View profile action to shrink safely and prevented the action row from forcing the final button outside the card.
   - Kept the existing tel: and WhatsApp destinations and click layers intact.

2. **Experience-aware mobile navigation**
   - The existing Discover slot in the member bottom dock now reflects the signed-in member's selected experience.
   - Fun displays Escorts with the concierge-bell icon and links to `/hosts`.
   - Elite displays Elite with the gem icon and links to `/elite`.
   - Dating displays Dating with the heart icon and links to `/discover`.
   - Friends displays Friends with the users icon and links to `/discover`.
   - The same contextual link is applied to the public layout's signed-in member dock. Host and guest navigation remain unchanged.
   - This reuses the existing menu slot, preserving admin ordering and the five-item dock layout; no SQL migration is required.

3. **Story status rings**
   - Replaced the dark plum outer ring/keyline with the site's primary colour on Hosts and Discover story strips.
   - The ring follows the configured theme primary colour variables.

4. **Find people in your town**
   - Preserved the existing mobile multi-row slow drift implementation: alternating directions, pause control, touch/keyboard interaction hold, infinite-loop clones, and reduced-motion preference support.
   - Desktop/tablet remain a static grid. This was already implemented in the v8 base and was not unnecessarily rewritten.

## Validation

- PHP syntax validation: all 525 PHP files passed `php -l`.
- The ZIP archive was checked with `unzip -t`.
- No database migration is required.
- Live browser testing, actual-device tap testing, and authenticated database-mode testing were not available here. Please test the ZIP on staging at 320px, 360px, 390px, and 430px widths before production deployment.
