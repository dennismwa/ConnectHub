# Connection Profiles Upgrade Notes

## Summary
The connection profile feature has been upgraded across the Host editor, public directory, individual public profile, admin review queue and service catalogue. Existing route conventions and the current ConnectHub design tokens are retained.

## Changed files
- `app/host/connections.php`
- `app/connection/directory.php`
- `app/connection/profile.php`
- `app/admin/connection_profiles.php`
- `app/routes/40_hosts.php`
- `CONNECTION_PROFILES_MODULE.md`

## Database
No additional schema change was introduced by this visual and workflow upgrade. The existing `0019_connection_profiles.php` migration remains responsible for the module tables and default settings. If that migration has already run, do not try to execute it as SQL.

## Key behaviour fixes
- Public directory no longer uses an arbitrary approved gallery photo when the Host has not selected one for this listing.
- Public profiles link to the relevant Host conversation rather than opening only the general inbox.
- Connection pages honour the configured Hosts age confirmation setting.
- Host descriptions use existing Host moderation checks for prohibited terms and off-platform contact details.
- A suspended listing cannot be re-enabled by its owner.
- Renaming a service code updates existing saved selections.
- Admin review includes searchable, filterable and paginated results.

## Validation
PHP lint was run on all changed PHP files. The archive has not been tested against a live database or deployed site. Complete the end-to-end checklist in `CONNECTION_PROFILES_MODULE.md` on staging before production use.
