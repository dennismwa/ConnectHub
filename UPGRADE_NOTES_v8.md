# ConnectHub v8 – Escort Bio, Ratings and Fun Landing Sections

This update builds on the existing ConnectHub project and preserves its PHP/PDO architecture, current routes, user records, subscriptions, and review history.

## Changes

- Escort card bios now use the concise format `Meet Grace, a 33 year old female from Ruiru, Nairobi.` The word `companion` is not appended. Age and gender are shown only when the existing member privacy/profile data makes them available. Town and county are used for location, with Kenya as a fallback.
- Public/member-facing rating displays are disabled across the public, app, and authentication layouts. Host profile rating/review sections and client rating forms are disabled. New rating submissions are rejected while `public_ratings_enabled` is `0`.
- Historical review and rating records are retained in the database for admin records; they are not deleted.
- Fun landing page now has separate VIP Escorts, Prime Escorts, Featured Escorts near you, and Recently Joined Escorts showcases.
- Showcase sections use multi-row responsive grids (4 columns desktop, 3 tablet, 2 mobile) and larger result counts (12/12/12/16).
- Fun landing page deduplicates profile IDs across the four sections in priority order: VIP → Prime → Featured → Recently Joined.
- Recently Joined is sorted by account creation time, while only approved and active escort profiles qualify.

## How escorts qualify

- **VIP:** set the VIP flag on the escort's admin profile, or the escort must have an active subscription with tier 2 (VIP).
- **Prime:** an active subscription with tier 1 (the current Premium subscription tier) qualifies for the Prime showcase. The card badge/section is labelled Prime; the underlying subscription tier and billing plan names are not renamed.
- **Featured:** the existing admin Featured flag or an active paid featured placement qualifies.
- **Recently Joined:** approved and active escort profiles not already displayed in VIP, Prime, or Featured are listed here.

If a tier has no qualifying escorts, its section remains empty/hidden rather than filling it with unqualified profiles. Admins can assign VIP/Featured status or activate the appropriate plan to populate those sections.

## Database upgrade

The new migration is `database/migrations/0033_fun_escort_sections_and_public_ratings.php`. The application applies it automatically on the first request after the files are uploaded. `CH_SCHEMA_VERSION` is now 33. No manual SQL import is required.

## Verification

- PHP syntax validation: all 525 PHP files passed.
- ZIP integrity validation: performed after packaging.
- Full database-backed integration tests and browser/mobile visual tests were not run in this environment because the CLI PHP runtime lacks `mbstring` and no live database/browser session was configured. Test on staging before deploying to production.
