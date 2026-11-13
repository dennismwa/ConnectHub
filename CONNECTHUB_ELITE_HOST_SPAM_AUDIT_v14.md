# ConnectHub Elite, Host and Messaging Audit — v14

## Scope and method
Reviewed the v13 PHP source, routes, Elite profile lifecycle, Elite administration, photo access gates, host/escort route and page structure, messaging API and message-send pipeline. Applied source-level fixes and ran PHP/JavaScript syntax checks. This is a source-code audit, not a live authenticated browser or production-database test. Database-backed integration tests were not run because the bundled test harness requires an explicitly configured disposable database.

## Phase 1 — Elite administration and privacy
### Implemented
- Added migration `0035_elite_controls_spam_guard.php`.
- Added a global Elite setting, `elite_photo_privacy_enabled` (defaults to enabled), to turn the member-controlled “photos only for people I accept” feature on or off.
- Added per-member admin override `admin_photo_privacy`: `-1` follows the member choice, `0` disables the privacy gate for that member, `1` enforces it. Admin controls appear on the Elite member cards.
- The effective privacy rule is now used by profile unlocking, profile access, access purchase checks, photo-lock checks and synchronization with ordinary dating visibility.
- Admin member cards show when privacy is member-controlled or admin-overridden and retain links to the full user administration record.

### Existing controls confirmed in source
- Elite profile approval, rejection/request changes, suspension/restoration, verification badge, featured status, removal, search and status filters.
- Elite request review/removal, access/pass/credit administration, SEO/page appearance and membership/access pricing setup.

### Remaining review points
- Existing admin role permissions still gate Elite view/edit actions; test each custom admin role on the live installation.
- The existing `remove` action removes the Elite profile, not the underlying user account. Full account suspension/ban/deletion remains in the central user administration screen by design.
- Confirm photo-privacy override behavior with actual photo assets and a normal member, accepted requester, unlocked viewer and staff account after migration.

## Phase 2 — Elite member journey: source review
Reviewed join/edit, approval state, membership payment, directory/profile access, paid unlock/pass/pack, request send/accept/decline, saved profiles and photo privacy paths.

Items to test in a staging browser with real payment sandbox credentials:
1. New member blocked/unblocked by verified-photo requirements.
2. Pending, approved, rejected, suspended and withdrawn states, including re-submission.
3. Paid membership expiry and plan exemptions.
4. Free, paid and accept-only profile viewing modes.
5. Single unlock, pack credit and time-pass expiry/refund edge cases.
6. Request filter, duplicate request, daily limit and expiration behavior.
7. Profile photo privacy across Elite, ordinary Dating/Discover and accepted requests.
8. Admin override on/off/reset and global feature disable.

## Phase 3 — Host/escort journey: source review
Reviewed host route registration, host onboarding and verification modules, services, listing/discovery, booking, reviews, payouts, reports and admin host management source files. Existing focused tests are present under `tools/tests/`.

Staging acceptance checklist:
1. Host application and required fields; incomplete application recovery.
2. Verification upload, review, reject/resubmit and verified badge.
3. Public listing visibility and service catalogue selection.
4. Availability/calendar and booking creation, cancellation and status transitions.
5. Booking contact/access restrictions and user/host notifications.
6. Reviews, host replies and abuse reports.
7. Earnings ledger, payout request, admin approve/reject and reconciliation.
8. Host suspension and whether all public listings/bookings are handled safely.

No live host account was available in this source-only session, so these are explicit validation tasks rather than claims of successful end-to-end execution.

## Phase 4 — Direct-message spam protection
- Added a duplicate-message check: the same normalized message (12+ characters) is blocked after two recent identical sends by the same account within ten minutes.
- Added a burst guard at 18 messages per minute per sender.
- Abuse attempts are recorded in `message_abuse_events` with reason, account, conversation and optional content hash; message body is not copied into the abuse log.
- The normal route-level and per-account rate limits remain active.
- The filter is deliberately conservative: it does not automatically ban an account based on a single flag. Admin review/automated escalation should be added only after observing real false-positive rates.

**Terminology:** this protection covers in-app direct messages, not SMTP/email campaign sending. Newsletter/campaign abuse has a separate workflow and should not be conflated with member-to-member messaging.

## Phase 5 — Verification
- PHP syntax: all PHP files in the project passed `php -l` after changes.
- JavaScript syntax: existing Elite, host and messaging scripts checked where present; no JavaScript behavior was modified in this phase.
- Database migrations: migration file added but not executed against production.
- Existing integration tests: not executed because the harness refused to run without `CH_CONFIG` pointing to a throw-away database (or explicit `--yes`). No production database was used.
- Browser/device testing: not performed. Test the listed journeys in staging before deploying to production.

## Deployment
1. Back up files and database.
2. Upload the complete v14 project.
3. Run database migration `0035_elite_controls_spam_guard.php` through the application's migration runner.
4. Clear application, PHP OPcache, CDN and browser caches.
5. Test the acceptance checklist before enabling the new global privacy control for all users.
