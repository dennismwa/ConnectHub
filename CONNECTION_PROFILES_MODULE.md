# ConnectHub Connection Profiles — upgraded module

This is an optional Host feature for adult social connection profiles and **non-sexual** companionship activities. It is separate from the existing Host booking profile. It reuses ConnectHub's PHP routing, authentication, CSRF protection, database helpers, approved photo gallery, settings system, navigation and visual tokens.

## Routes

- `/host/connections` — private Host editor, status, description, city/neighbourhood, service abbreviations, contact preference, approved gallery selection and publish switch.
- `/connections` — responsive public directory with location and activity filters.
- `/connections/{id}` — approved public profile, selected photo gallery, service descriptions and permitted contact actions.
- `/admin/connection-profiles` — profile review queue, status filters, search, pagination, review notes, approval/rejection/suspension and overview counts.
- `/admin/connection-profiles?tab=services` — service abbreviation catalogue, add/edit, activation and display ordering. Renaming an abbreviation updates saved Host selections.
- `/admin/settings/hosts` — global feature switch, approval policy and direct-contact visibility settings.

## Upgrade phases completed

### Phase 1 — Core workflow and safety
- Kept the feature opt-in and globally switchable.
- Hosts can save drafts, submit for approval, switch a listing off and edit their own details only.
- Approved gallery photos are checked against the logged-in Host's own user ID and moderation state.
- Suspended listings cannot be re-enabled by the Host.
- Uses the site's Host text moderation rules to reject prohibited terms and external contact details in profile text.
- Public pages honour the site's configured 18+ confirmation requirement.

### Phase 2 — Host experience
- Reworked the private editor into responsive, clearly separated profile, location, service, contact, gallery and publishing sections.
- Added selected-state styling, a live description character count, listing status messaging and administrator review notes.
- Added phone input validation and contextual contact guidance.
- Preserved ConnectHub's existing typography, surfaces, borders and primary colour through the site's theme tokens; styles adapt to light and dark themes.

### Phase 3 — Public discovery
- Added a responsive directory, location/activity filters, approved status marker, empty state and safety guidance.
- Profile cards show only photos explicitly selected for the connection listing. They no longer silently fall back to a different Host photo.
- Added an individual profile page with responsive gallery, accessible image lightbox, service labels/descriptions and working phone, WhatsApp or Host-specific ConnectHub message links.
- Global direct-contact visibility settings take precedence over Host preferences.

### Phase 4 — Admin management
- Added module status and policy overview, counts by review status, search, status filters and pagination.
- Added approve, request-changes and suspend actions with an optional note shown in the Host dashboard.
- Added a full service catalogue editor with code, label, description, active state and display order.
- Renaming a service code preserves existing Host selections. Deactivate old services rather than deleting them.

### Phase 5 — QA and release
- PHP syntax checks passed for the changed route and module files.
- Verify the end-to-end checklist below on a staging copy before production deployment. Live database execution and browser testing cannot be confirmed from the ZIP alone.

## Installation / deployment

1. Back up the live ConnectHub files and database.
2. Merge the files in this archive into the existing `connecthub/` directory. Preserve `config/`, secrets, live uploads, and any environment-specific settings.
3. Confirm migration `database/migrations/0019_connection_profiles.php` has run through the site's normal migration runner. It creates the profile and service tables and default settings. It is a PHP migration, **not** a raw SQL file.
4. In Admin → Settings → Hosts → Connection profiles, enable the module. Keep approval required while testing.
5. Check Admin → Connection profiles and the Service catalogue.
6. Test the checklist below with a Host account and a separate visitor account before switching on the live feature.

## End-to-end test checklist

- [ ] With the global module switch off, Host editor and public routes are unavailable and saved data remains in the database.
- [ ] With the switch on, a Host can open the editor, save a draft, select active service codes and choose only their own approved photos.
- [ ] Empty title, city or services prevent a listing from being submitted for publication.
- [ ] Prohibited text and direct contact details in the description are rejected by the existing Host moderation helpers.
- [ ] With approval required, a new listing becomes pending and does not appear publicly until approved.
- [ ] Editing an approved listing returns it to review when approval is required.
- [ ] A suspended listing cannot be re-enabled by its owner.
- [ ] Admin can search/filter listings, add review notes, approve, request changes and suspend.
- [ ] Admin can add a service, edit its details, change display order, deactivate it and rename its code without losing existing Host selections.
- [ ] Directory location and activity filters work together; no-match results show a useful empty state.
- [ ] Only selected, approved photos are shown publicly; deselected photos never appear as a fallback.
- [ ] Phone, WhatsApp and Host-specific ConnectHub messaging actions work as expected.
- [ ] Direct contact visibility setting overrides a Host's chosen method.
- [ ] Age confirmation appears when required by Hosts settings and remembers the confirmation for the current session.
- [ ] Layout is checked at 360px mobile width, tablet width and desktop, in both light and dark themes.
- [ ] Existing Host booking, profile and photo management flows still work.

## Policy boundary

This module is designed for adult social connections and non-sexual companionship. Sexual services, solicitation and bypassing platform contact/moderation rules are not supported. Hosts should not publish a home address; city and neighbourhood are intended for discovery only.
