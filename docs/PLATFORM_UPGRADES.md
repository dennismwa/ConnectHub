# ConnectHub existing-system upgrades

This package upgrades the existing ConnectHub application. It does not replace the PHP application, its route structure, authentication, user records, payments, existing matching system or existing advertisement manager.

## What changed

### Phase 1 — Escort operating mode and public copy

- The supplied database had `booking_enabled = 0` while `host_module_mode = booking`.
- Migration 0032 aligns the settings to contact mode when booking is disabled. The resulting configuration is `host_module_mode = contact` and `booking_enabled = 0`.
- The Admin > Hosts mode selector now updates both settings together. The generic Settings editor no longer exposes the legacy booking flag separately.
- Contact-mode copy now describes profile discovery and direct contact, not booking deposits. Booking-specific copy remains conditional and is used only when booking mode is enabled.
- The default internal `fun` experience key is retained for compatibility with existing member preferences and cookies, but its public label and default copy now say **Escorts**.
- The existing landing-page Escort showcase copy is updated by the migration without changing its enabled state or order.

### Phase 2 — Real image and video stories

- The existing showcase strip and full-screen story viewer are extended to show actual member-uploaded image and video stories.
- Approved Escort profiles can upload JPG, PNG, WebP, MP4 and WebM media through `/host/stories`.
- Image files are validated and re-encoded. Video uploads are MIME-checked, size-limited and stored with random names.
- New stories require Admin approval by default. Approval starts the public 24-hour expiry period. If approval is disabled, the 24-hour period starts when the story is published.
- Expired stories are excluded from public queries immediately and a cleanup job runs every five minutes to delete story records, view records and media files.
- Only daily aggregate counts (story count, image count, video count and views) remain for analytics. Individual story content, captions, media paths and viewer records are deleted. Aggregate daily statistics are retained for up to 400 days.
- Admin > Story moderation provides pending, live, rejected, hidden and all-story views, plus approve, reject, hide and delete actions.
- Admin > Platform management controls story availability, pages, approval, active-story limit and upload-size limits.

### Phase 3 — Discovery and Today's Picks

- Today's Picks is moved directly below the discovery filter controls, ahead of nudges and promotional content.
- It is displayed in both grid/list and swipe discovery layouts where enabled.
- Its heading and explanatory copy have been refined while retaining the existing pick-generation logic and settings.
- Admin can enable/disable picks, set the number shown and allow/disallow featured profiles in the selection.

### Phase 4 — Central Admin controls

- A new Admin > Platform management hub links to the existing Visitor choices, Navigation, Landing, Plans, Hosts, Story moderation, Launch access, Advertising, SEO and Analytics managers.
- The hub provides quick controls for experience selection, Today's Picks, story display and upload rules.
- It shows the existing subscription-plan feature matrix and links to each plan editor. It does not introduce a parallel entitlement system.
- Links are shown only when the administrator has permission to view the relevant module.

### Phase 5 — Launch access

- A configurable Launch Access campaign uses the existing plan entitlement lookup as an overlay. It does not alter paid subscriptions or create a payment mandate.
- Default campaign duration is 60 days and the campaign is disabled until an administrator enables it.
- Admin can set the campaign window, duration, eligible roles, feature overrides, whether existing accounts qualify and reminder timing.
- New eligible registrations receive a grant automatically. Existing accounts are processed in small batches if that option is enabled.
- Members see the offer on registration and pricing pages while the campaign is active.
- A reminder is sent before expiry. After expiry the grant stops applying and the member returns to their normal plan. There is no automatic charge.

### Phase 6 — Advertising and sponsored placement reporting

- The existing advertisement manager remains the source of truth.
- An Elite Connections placement is added alongside the existing Dating, Escort, landing, discovery, messages and global placements.
- Impression, click and sponsored-story-view events are recorded without storing IP addresses or raw session IDs. Unique reach is estimated using a hash of the signed-in account ID or browser session ID.
- Admin > Adverts now shows 30-day impressions, unique reach, tracked clicks, CTR and placement-level performance.
- Event analytics are retained for 400 days.

### Phase 7 — SEO by experience

- Dating discovery (`browse`), the Escort directory (`hosts`) and Elite Connections (`elite`) have separate default SEO titles and descriptions.
- Escort directory defaults reflect the current contact-only, non-sexual companionship policy and do not promise bookings or deposits.
- Existing age-gate and sitemap safeguards are preserved. Private account pages, messages, verification records and private media are not added to the public sitemap.
- Public Escort profile indexing remains subject to the existing age-gate and SEO index settings.

### Phase 8 — Analytics

- The existing registration, engagement, cohort and revenue reports are retained.
- Experience participation adds counts for Dating members, Escort-experience visitors, approved Escort profiles and Elite members, including new registrations in the selected period.
- Story reporting combines live stories with daily aggregate counts after expiry.
- Advertising reporting uses the new impression/click/reach event table.
- Launch-access grants are reported separately.

## New Admin locations

- `/admin/platform` — central platform controls and existing-module links.
- `/admin/stories` — member story moderation and performance.
- `/admin/launch-access` — launch campaign configuration.
- `/host/stories` — Escort profile owner's story upload and management page.

Existing pages remain in place:

- `/admin/choices`
- `/admin/nav`
- `/admin/landing`
- `/admin/hosts`
- `/admin/plans`
- `/admin/ads`
- `/admin/seo`
- `/admin/analytics`

## Database changes

Migration: `database/migrations/0032_platform_upgrades.php`

New tables:

- `agent_stories`
- `agent_story_views`
- `agent_story_daily_stats`
- `advertisement_events`
- `launch_access_campaigns`
- `launch_access_grants`

The schema version is now 32. The migration is safe to rerun and does not reset user, profile, message, payment or advertising data.

## Deployment notes

1. Back up the live database and the complete existing `uploads/` directory before deployment.
2. Upload the updated application files without deleting or replacing existing user uploads.
3. On an existing installation, let the application's migration runner apply versions 31 and 32. Do **not** import the full `connecthub_updated.sql` snapshot over a live database; that file is for a full database restore or a fresh environment.
4. If restoring from the updated full SQL snapshot, it already includes the new schema, settings, contact-mode alignment and migration version rows.
5. Ensure `uploads/stories/` is writable by PHP and that Apache `.htaccess` rules are active. The supplied `uploads/stories/.htaccess` disables directory listing, script execution and search indexing for story media.
6. Keep the existing cron schedule at every five minutes. The new cleanup and launch-access jobs use the existing cron runner.
7. Verify PHP upload limits (`upload_max_filesize` and `post_max_size`) are at least as high as the configured story limits.
8. Test uploads, moderation, expiry, deletion, registration grants, expiry reminders, ad impressions/clicks and the three experience SEO pages on staging before production release.

## Verification performed in this package

- PHP syntax lint passed for all PHP files in the project.
- JavaScript syntax check passed for the updated story viewer.
- A full live database/browser/payment test was not possible in the build environment because the supplied archive does not include production configuration or database credentials, and the local PHP CLI lacks the production-required database/image extensions. Complete staging end-to-end verification is still required before launch.

## Visual refinement pass

The phased upgrade screens now use a scoped visual layer at `assets/src/modules/platform_upgrade.css`, imported by `assets/src/app.css` and included in the production `assets/css/app.css` bundle. It reuses the existing Warm Dusk design tokens, typography, surface colours, buttons, admin shell, and card language rather than introducing a separate theme.

The refinement covers the Platform Management hub, Launch Access settings, Story Moderation, and the member Story upload/history screens. It improves page hierarchy, card treatment, form spacing, story preview proportions, mobile stacking, focus states, and reduced-motion behaviour. The member story upload screen also includes a local image/video preview before submission. No database schema changes are required for this visual pass.

The existing app stylesheet build command remains `npm run build:css`. If rebuilding CSS from source on deployment, run it from the project root after installing the package-lock dependencies. The delivered `assets/css/app.css` already contains the new scoped styles.
