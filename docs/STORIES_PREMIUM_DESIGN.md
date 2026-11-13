# ConnectHub Stories — Premium Design Update

This update applies the supplied three-screen ConnectHub Stories reference to the existing PHP implementation. It extends the current story strip, full-screen viewer, member story manager and admin moderation page. It does not replace the story backend or add decorative controls that have no working action behind them.

## Visual direction

- Deep plum story surfaces (`#160d19` / `#100a13`) with vivid rose accents (`#df286e`).
- Circular profile story avatars with clear unread/seen states and paid-tier distinctions.
- Full-screen media viewer with progress segments, creator identity, verification indicator, caption, profile/contact actions and safe-area spacing.
- Original media displayed with `object-fit: contain`; a blurred, dimmed version of the same media fills unused background space. This avoids stretching or cropping the original in the viewer.
- Responsive member story manager with My Stories, Archived and Insights tabs, a large upload drop area, portrait media cards, status labels and view counts.
- Responsive admin moderation cards and status filters.

## Existing functionality retained

- Existing story upload and moderation endpoints.
- Existing 24-hour expiry and cron cleanup.
- Existing view tracking.
- Existing original-file preservation and thumbnail generation.
- Existing ownership checks, CSRF protection and moderation permissions.

## Notes

The Insights tab currently reports totals based on the most recent 30 story records returned by the existing owner query. It is a useful compact overview, not a lifetime analytics report. The viewer uses only actions supported by the existing story data and routes; likes, comments and shares have not been represented as fake controls.

## Verification

- PHP syntax checked across the project.
- `stories.js` and `story_upload.js` passed Node syntax checks.
- Story CSS and bundled CSS brace counts are balanced.
- Browser/device visual QA and database-backed end-to-end testing still need to be performed on staging.
