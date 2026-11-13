# ConnectHub Elite and Host Lifecycle Audit — v15

## Scope and evidence
This review uses the v14 source archive as its baseline. It inspects the Elite lifecycle and access/payment fulfillment in `includes/lib/elite.php` and `includes/plans.php`, host onboarding/verification and marketplace logic in `includes/lib/hosts.php` and related modules, booking payment callbacks, refunds, settlement, ledger and payout processing, plus the existing focused test scripts. This is a source-level review. No production database, gateway credentials, authenticated browser session, or live provider callbacks were available.

## Phase 1 — Elite onboarding and membership

### Source behavior confirmed
- Joining requires an active user account and can be configured to require account verification and an approved photo.
- Profile validation covers headline/about length, industry, seeking preferences, contact details and prohibited transactional wording.
- Approval can be manual or automatic according to `elite_approval`; rejected/withdrawn profiles can be resubmitted.
- Membership is considered paid when the configured fee is zero, the member is exempt by subscription tier, or `paid_until` is in the future.
- Join payments extend from the later of now or the existing paid-through date. A zero-day membership is represented as a far-future date.

### Requires staging verification
- Test each verification requirement toggle against existing and newly registered users.
- Verify a paid membership does not become discoverable until its profile is approved.
- Verify expiry and subscription exemption changes are reflected by cron/resync and do not leave stale dating visibility flags.
- Test payment cancellation, failed payment, duplicate gateway callback and manual admin completion.

## Phase 2 — Elite discovery, requests and paid access

### Source behavior confirmed
- Directory results are based on live Elite profiles and member criteria; request scoring can route requests to the filtered inbox.
- Requests have duplicate/pending/accepted/declined/withdrawn/expired handling and a configurable daily limit and expiry period.
- Accepting creates/reuses a conversation, grants accepted access and inserts the original introduction as the first message.
- Paid access supports single unlocks, credit packs and time passes; plan exemptions and global free/accept/pay modes are considered.
- Member photo privacy and admin overrides are enforced by `elite_can_view_full()` and photo-lock hooks in the v14 code.

### Requires staging verification
- Test all viewer/owner combinations: owner, staff, blocked member, accepted requester, unlocked viewer, pass holder, expired pass, credit holder and plan-exempt member.
- Confirm credits are consumed once under concurrent requests and that expired credits cannot be spent.
- Confirm pack purchase with an invalid, private or already-open target does not consume a credit for that target.
- Test request acceptance from two simultaneous sessions and verify only one acceptance/conversation/first message is produced.
- Test filters, empty results, pagination and hidden/suspended/inactive profiles.

## Phase 3 — Host onboarding, listing and verification

### Source behavior confirmed
- Host onboarding stores profile/application data separately from public listing approval.
- Verification supports configurable ID and photo checks, either/all rules, resubmission after rejection, and status-derived completeness/badges.
- Listing visibility depends on approval, listing/payment state and configured host availability.
- Service catalogue, profile editor, availability, settings, stories and host dashboard routes are present.

### Requires staging verification
- New application, incomplete save/resume, reject/resubmit, document replacement and verification toggle changes.
- Check that suspended, unapproved, expired-listing and incomplete hosts cannot appear in directory, direct profile URLs or booking APIs.
- Verify each service selection and rate/package is represented consistently on public profile and booking quote.

## Phase 4 — Host booking, communication, refunds and earnings

### Source behavior confirmed
- Booking creation checks host availability and computes price, deposit, commission and balance.
- Booking payment moves the booking to pending or confirmed for instant-booking hosts.
- A central transition function controls accept, decline, cancel, complete and no-show states with role checks and refund quotes.
- Refunds are recorded as amounts due for admin processing; they are not automatically sent back to the payment provider by this code path.
- Host earnings are calculated from settlement records; payout requests reserve funds by including requested payouts in the ledger balance calculation.

### Fix applied in v15
- Booking payment completion previously logged a deposit amount mismatch but continued to confirm/advance the booking. It now checks the actual paid amount against the expected deposit and checks the Host ID in payment metadata when present.
- On mismatch, it does not confirm the booking. It closes the booking, records the actual received amount as a full refund due, alerts the client and administrators, and logs the mismatch for reconciliation.

### Requires staging verification
- Run full payment lifecycle using gateway sandbox: success, failure, timeout, duplicate callback, delayed callback, wrong amount, wrong Host metadata and payment arriving after hold expiry.
- Confirm refund due records are actually reconciled by an administrator and the customer receives the correct refund reference.
- Test overlapping booking requests under concurrency and verify a slot cannot be double-booked.
- Test all cancellation tiers at exact boundaries, no-show, host decline, host timeout and admin override.
- Confirm completed/no-show settlement is idempotent and total earned is not incremented twice.
- Test payout requests from two concurrent sessions, payout rejection (funds become available again), payout paid (funds remain reserved), M-Pesa B2C result callback and reconciliation.
- Verify the booking balance due from the client is collected/handled according to the site's configured operating process; the inspected workflow records the deposit and host settlement, but does not establish an online collection flow for the remaining balance.

## Phase 5 — Account moderation and access controls

### Source behavior confirmed
- User and Host roles are checked in lifecycle functions, and booking transitions check whether the actor is the client, Host, administrator or system.
- Host approval status is checked before a Host can accept, complete or mark a booking no-show.
- Elite profile removal is separate from central account suspension/deletion.

### Requires staging verification
- Test account suspension/restoration while Elite requests, open conversations, bookings, listings, pending payments and payout requests exist.
- Confirm role-based admin permissions for every Elite/Host/payment action, not just page visibility.
- Confirm report review, evidence retention, user notification and appeal/support workflow.

## Verification performed
- PHP syntax check: 527 PHP files passed.
- JavaScript syntax check: 26 files passed `node --check`.
- The focused database-backed tests were not executed because no disposable database configuration was supplied. Do not run them against production; they mutate test records and settings.
- No real browser/device or payment-provider sandbox test was performed.

## Deployment notes
- No new database migration is required for the v15 booking payment mismatch guard.
- Back up files and database before deployment. Upload the full project, clear OPcache/CDN/browser caches, and complete the staging checklist before production sign-off.
- Refunds marked due require a real operational reconciliation and payment-provider refund process; a database status is not proof that money has been returned.
