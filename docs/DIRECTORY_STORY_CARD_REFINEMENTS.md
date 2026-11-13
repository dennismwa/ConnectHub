# Directory and story-strip refinements

## Changes
- Added separate solid background colour controls for the story strip on the Escort directory and Dating discovery pages under **Admin → Platform management → Story strip appearance**.
- Story strip rows now remain constrained to their rounded container. The circular items stay in one horizontal, touch-scrollable row; the final items no longer paint outside the panel.
- Escort directory cards no longer render service chips or the profile's free-form tagline. Services remain on the full profile page, where the complete service catalogue can be viewed.
- Directory cards use a compact generated line based on the profile's display name, visible age, gender and town/county. Hidden age remains hidden; missing location falls back to Kenya.
- Added an admin toggle to show or hide ratings on Escort directory cards. Existing reviews and ratings are not changed.
- Replaced the large text-style Get in touch treatment with a compact pill CTA.

## Admin controls
Open **Admin → Platform management** and use **Story strip appearance** to select the two background colours and toggle directory ratings. Save platform controls to apply the settings.

## Database
No schema migration is required. The options use the existing settings store and are written when the Platform management form is saved.

## Files changed
- `app/admin/platform.php`
- `templates/partials/story_strip.php`
- `templates/partials/host_card.php`
- `assets/src/modules/stories.css`
- `assets/src/modules/hosts.css`
- `assets/css/app.css` (served compiled stylesheet override)
