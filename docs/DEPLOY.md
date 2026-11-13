# ConnectHub: install and launch guide

ConnectHub is plain PHP 8.1+ and MySQL/MariaDB. No framework, no Composer, no build step on the server. It runs on ordinary shared hosting (cPanel), a VPS, or a local machine.

## 1. What you need

- PHP 8.1 or newer with these extensions: `pdo_mysql`, `mbstring`, `gd` (with JPEG and WebP), `fileinfo`, `dom`, `openssl`, `curl`. `exif` is recommended (photo rotation).
- MySQL 5.7+ or MariaDB 10.4+ (utf8mb4).
- A domain with HTTPS. Browsers will not install the app to the home screen or allow location sharing without it.
- Apache with `mod_rewrite` (the `.htaccess` files are included) or Nginx (see `docs/nginx.conf.sample`).

## 2. Install (about five minutes)

> Already running a ConnectHub site? Do not install again. Follow **section 11, Upgrading a live site**: it keeps your members, settings and uploaded photos.

**Option A: the browser installer (recommended)**

1. Upload the whole folder to your host. The site root is the folder that contains `index.php`.
2. Create an empty database and a database user in your hosting panel.
3. Open `https://your-domain/install/` and fill in the database details, your site name, and your admin email and password.
4. The installer creates all tables, loads the starter content (47 counties and towns, interests, plans, pages, FAQs), creates your admin account and writes `config/config.php`.
5. Delete the `install/` folder when it finishes.

**Option B: import the SQL yourself**

1. Create an empty database and import the separate `connecthub.sql` file (it is delivered next to the zip, and is deliberately not inside it so it can never be downloaded from your site) with phpMyAdmin or `mysql < connecthub.sql`.
2. Copy `config/config.sample.php` to `config/config.php` and fill in the database details. Replace `app_key` with a long random string.
3. Sign in at `/login` with the admin account created by the SQL file (`admin@example.com`; the temporary password is in the delivery note). You are made to choose a new password at the first sign-in. Then change the email in Admin > Account.

If the site lives in a sub-folder (for example `https://example.com/connect`), the installer detects it. You can also set `base_path` in `config/config.php`.

## 3. Folder permissions

These folders must be writable by the web server (775, or 755 with the right owner): `storage/` (and its sub-folders), `uploads/`, `config/` during installation.
`config/`, `storage/`, `includes/`, `app/`, `templates/`, `database/` and `docs/` are blocked from the web by `.htaccess`. Chat photos in `uploads/messages/` are blocked too and are only served to the two people in that conversation. Sharp member photos (`uploads/photos/*_l.jpg`, `_m.jpg`, `_t.jpg`) cannot be downloaded directly either: the app serves them through `/media/photo` after checking who is looking (the blur-for-visitors switch, Premium-only photos, the owner and staff). Only the blurred `_b.jpg` copies are public. Apache reads the rule from `uploads/.htaccess`; on Nginx it is in the sample file. On Nginx, use the rules in `docs/nginx.conf.sample` (they include the same blocks).

## 4. Scheduled jobs (important)

Add one cron entry that runs every 5 minutes. It expires subscriptions and launch-access grants, closes unpaid bookings where booking mode is enabled, refreshes match scores, sends reminders and the weekly summary email, deletes expired story media, and cleans old data.

```
*/5 * * * * /usr/bin/php /home/youruser/public_html/cron/run.php >/dev/null 2>&1
```

The Admin dashboard shows "Cron job: OK" once it has run. Admin, Tools, "Cron job commands" shows these same commands with your real server paths and a Copy button for each.
Hosts that only allow URL cron jobs (Apache/cPanel): put a long random value as `'cron_key' => '...'` in `config/config.php`, then call `https://your-domain/cron/run.php?key=THAT_VALUE` every 5 minutes. Nginx users should use the command-line form.

## 5. First hour checklist (Admin > Settings)

1. **Email**: set the mail driver. Use SMTP (host, port, user, password) for reliable delivery. Send yourself a test from Settings > Email. With the driver set to "Log only", nothing is sent and members will not receive verification or reset emails.
2. **Site basics**: name, tagline, support email, phone, currency (default KES, KSh).
3. **Logo, colours and fonts**: Admin > Settings > Branding (a logo file and a size for each place it appears: the header over the hero photo, the header after scrolling, the mobile header in light mode, dark mode and over the hero photo, sign-in, side panel, footer in light and dark mode, admin) and Settings > Appearance (colours, fonts, corners, dark mode). The site opens in the light theme by default; change that under Appearance > Theme.
4. **Hero and pictures**: Admin > Landing page (slides, and every landing section: each picture can be replaced, removed, uploaded or chosen from the media library) and Settings > Hero & header (hero layout, text, buttons, the county/town finder, the hero picture collage). Until you add your own, the site shows the built-in illustrations. Admin > Locations controls the "Find people in your town" cards: which towns show, whether towns without members show, member counts, and a picture for each town.
5. **Privacy toggles**: Settings > Privacy (blur photos for visitors, which contact details each kind of member can see) and Settings > Messaging (who can message whom: anyone signed in, Premium only, or matched members only).
6. **Payments**: Admin > Payment methods (section 13 below). Switch on only the methods you have finished setting up: M-Pesa, cards, Paystack, PayPal, Paybill/till, bank transfer. Keep M-Pesa on **Sandbox** while testing and switch to **Production** only when you go live. Every address you give a provider is shown on that method's setup page with a Copy button, and must be reachable over HTTPS from the internet.
7. **Legal pages**: Admin > Pages. The Terms, Privacy, Cookie, Community and Host guidelines pages are starter text only. Have a lawyer review them against the Kenya Data Protection Act and local law before launch.
8. **Plans and prices**: Admin > Plans. Each plan can be sold weekly, monthly and yearly; set a period's price to 0 to stop selling it. Defaults: Premium KSh 200 a month, VIP KSh 500 a month. When a member switches to a different plan, the value of the days left on the old plan is turned into extra days on the new one, and the payment page says so before they pay. Boost credits in a plan are per month, so a week earns a quarter of them and a year twelve times as many.
9. **Password rules**: Settings > Sign in & register > Rules. Members may use a simple password by default; you can set a minimum length, a strength rule, a common-password block and a strength bar. Administrators always need at least 8 characters and a mixed password.
10. **Turn off debug**: `'debug' => false` in `config/config.php` (the installer sets this).

## 6. Hosts (companionship) feature

The Hosts directory is for approved, ID-verified companions offering social company: dinners, events, city tours and travel. Sexual services are prohibited in the terms and the Host guidelines, and the site offers no way to advertise them. You are responsible for enforcing this: review every Host application (Admin > Hosts), check ID documents, publish or decline client reviews (Admin > Host reviews, while Settings > Hosts has review moderation on manual), act on reports, and seek legal advice on how the feature should be operated in your jurisdiction. The feature can be switched off completely in Settings > Hosts.

## 7. Security notes

- The first admin password you choose is stored hashed. Use a long, unique password and turn on two-factor sign-in (Admin > Account).
- Uploaded files go to `uploads/`. PHP execution is disabled there. Private files (ID checks, verification selfies) live in `storage/private/` and are only served through a permission check.
- Keep `config/config.php` out of version control and backups that others can read. Back up the database and `uploads/` regularly (Admin > Tools > Backup downloads a database export).
- If an admin loses their 2FA device, another admin with full access to Admins and roles can open that person under Admin > Users and choose More actions > Turn off two-step sign-in. If the only admin is locked out, run `UPDATE users SET totp_enabled = 0, totp_secret = NULL WHERE email = 'you@example.com';` in the database.
- Admin > Tools has a database backup download, a scan for unused or missing uploaded files, maintenance mode (visitors see a notice, admins keep working) and demo data tools.
- Review Admin > Logs for failed sign-ins and admin actions.

## 8. Updating the look without touching code

All colours, fonts, radii and logo sizes are settings. The compiled stylesheet is already in `assets/css/app.css`. If you change the Tailwind source (`assets/src/*.css`, `tailwind.config.js`), rebuild with Node 18+:

```
npm install
npx tailwindcss -c tailwind.config.js -i assets/src/app.css -o assets/css/app.css --minify
```

## 9. What is intentionally not included

Social login (Google/Facebook), web push notifications, in-app coins, voice and video calls, and automated nude-image detection are not included. They need third-party accounts and keys. The code is split into small modules under `includes/lib/` and `app/routes/`, so they can be added later.

## 10. Troubleshooting

| Symptom | Fix |
|---|---|
| White page or "Something went wrong" | Look at `storage/logs/error.log` and `storage/logs/php.log`. Set `'debug' => true` temporarily on a private copy. |
| Photos fail to upload | Raise `upload_max_filesize` and `post_max_size` in PHP (Admin > Tools shows the current limits). Check GD has JPEG and WebP. |
| Emails do not arrive | Use SMTP, check SPF/DKIM for your domain, read `storage/mail/` if the driver is "Log only". |
| M-Pesa prompt arrives but the plan is not activated | The callback URL must be public HTTPS. Check Admin > Payments for the payment's status and `storage/logs/mpesa.log`. |
| Admin dashboard says cron never ran | Check the cron path and PHP binary path (`which php`), and that `storage/` is writable. |
| Browser says "refused to connect" for your domain on some pages | The page is being opened over an address that does not answer: usually https:// on a domain without an SSL certificate (port 443 closed), a www/non-www name that has no DNS record, or an IPv6 (AAAA) record that points nowhere. Install SSL for the exact host name, add DNS for both names, remove stray AAAA records, and leave Settings > General > Connection & security > "Send visitors to the https:// version" and HSTS off until https works. The site itself builds relative links, so it follows whichever address the visitor used. Also open the site in a normal browser tab: preview frames and embedded viewers that block frames show the same message. |
| 404 on every page except the home page | `mod_rewrite` or `AllowOverride All` is off (Apache), or the Nginx `try_files` rule is missing. |

## 11. Upgrading a live site (keeps uploads, members and settings)

The zip is built to be copied over a running site. It contains **no** `config/config.php`, **no** photos or other uploads, **no** private ID documents and **no** logs or cache, so nothing of yours can be overwritten by it.

**Never import `connecthub.sql` into your live database.** It is for brand-new sites only: it drops and recreates every table, which would delete all members, messages and payments. An upgrade needs no SQL file at all: the database updates itself the first time the site loads the new code. If your host will not let the site change its own database, run **`connecthub-upgrade.sql`** instead (phpMyAdmin > your database > SQL tab, or Import). It only adds what is new, never deletes or overwrites anything, and is safe to run twice. Take a backup first.

What to keep, always:

| Keep this | Why |
| --- | --- |
| `config/config.php` | Your database login, app key and cron key. |
| `uploads/` | Member photos, host photos, logos, hero and blog pictures, the media library. |
| `storage/private/` | Host ID documents and selfies (not web-readable). |

Steps:

1. **Back up first.** In Admin > Tools, download the database backup. Then, in your hosting file manager, make a copy or zip of `uploads/` and `storage/private/`. Five minutes now saves a bad day later.
2. **Switch maintenance mode on** (Admin > Settings > General), so nobody uses the site halfway through.
3. **Upload `connecthub.zip` into your site folder** (the one that contains `index.php`) and extract it **over** the existing files, choosing "overwrite". Extracting a zip only adds and replaces the files inside it. It does not delete the `uploads/` photos or your `config/config.php`, because they are not in the zip. Do not delete the old folder first, and do not "replace the folder" in a file manager that empties it before copying.
   - Over FTP: upload the contents of the `connecthub/` folder from the zip into the site folder, choosing "overwrite if different".
4. **Delete the `install/` folder** that the zip put back. The installer already refuses to run on an installed site, but it should not be there.
5. **Clear caches.** Delete the files inside `storage/cache/` (not the folder). If your host uses PHP OPcache, restart PHP or wait a few minutes.
6. **Open the site once** (any page). The database upgrade runs automatically and takes a second. Then open Admin > Dashboard to check everything is normal.
7. **Switch maintenance mode off.**
8. **Refresh the installed app** for people who added it to their phone: Admin > Mobile app (PWA) > Refresh apps.

After the upgrade:

- Your settings are kept. New options start with their default value; look through Admin > Settings > Hosts and Mobile app (PWA) once.
- **Visitors can now see agents in full by default.** Right after this upgrade, any visitor (not signed in) can open the Hosts pages, see photos without blur, the services, the rates and the contact details (call and WhatsApp), after the 18+ screen. This is on purpose, so the site can launch with agents showing everywhere. It only applies to settings you never saved: if you had already chosen something under Settings > Hosts, your choice is kept. To tighten it later use Admin > Agent showcase > Visibility (blur for visitors, who sees contact details, who sees rates). The Host listing fee stays as you had it.
- If you switch the listing fee on, approved Hosts are hidden until they pay. Use Admin > Hosts > "Give all Hosts listing days" first.

Moving to a **new server** instead: copy the old site's `uploads/` and `storage/private/` folders into the new site, export the old database (Admin > Tools > backup, or phpMyAdmin), import that export into the new empty database (not `connecthub.sql`), copy `config/config.php` and update the database details inside it.

## 12. Money and growth features (where to manage each one)

All of these are in the Admin sidebar. Every price, limit and switch below can be changed without touching code.

| Feature | Where | What you control |
| --- | --- | --- |
| Gifts, wallet and payouts | Admin > Wallet and gifts | Members earn gifts into a wallet (My profile > Wallet) and ask for a payout to an M-Pesa number they choose. You send the money by hand, then enter the M-Pesa code and mark it paid. You can also check each gift, and choose whether gifts need your check before they are credited. |
| Email alerts and providers | Admin > Settings > Email | Pick the server's own mail, SMTP, or an API provider. Set the address that receives alerts (new gift, payout request, verification, International listing). Use "Send test" to confirm. |
| Boost | Admin > Boosts | Boost packs (length and price), featured packs, the rules, and what is running now. |
| Photo limits | Admin > Photo limits | Photos per plan (Free, Premium, VIP), the overall ceiling, uploads per day, photos needed at sign-up, and a personal limit for one member (on the member's page). |
| Paid direct messages | Admin > Paid messages | Credit packs and prices, messages per credit, new chats per day, welcome credits, whether Hosts can be messaged this way, and giving credits to a member. Plans can include credits. |
| International connections | Admin > International | Free or paid listing, free or paid access, who pays (see below), approval, verified-only, listings, who has access, and the contact types members can choose. |
| Verification methods | Admin > Verification methods | Switch the selfie method on or off, add "copy this pose" methods with a reference photo, choose "members pick" or "random", and set what verification asks for. The review screen shows the pose, the photo sent and the member's profile photos together. |
| Landing page sections | Admin > Landing page | Show or hide any section (for example Packages), reorder them, and edit each one. Settings > Hero & header controls the hero, including a compact mobile hero and what the button says for signed-in members. |
| Editing and deleting messages | Settings > Messaging | Members can edit or delete their own sent messages. The edit option only appears while messages are stored in the database. |

### International connections: the recommended setup

Press "Use the recommended setup" on Admin > International > Who pays. It sets:

- **Listing is free**, so there are enough people to find. Members who want to be found create a short international profile (country, languages, what they are looking for, and the type of phone number they use: WhatsApp, mobile or Telegram).
- **Access is paid** for people who want to browse and contact. This is the group that gets value, so this is who you charge. Premium members get access free (change this if you prefer).
- **Listings need your approval and a verified profile**, which keeps scammers out. Listings are never shown without a photo check.
- **Phone numbers stay hidden** until both people have written in the chat, so a number cannot be harvested from a profile.

You can switch every one of these independently: free for all, paid listing, paid access, or both.

### Things to know before you go live

- Mail: the test server could not send real email, so send yourself a test from Settings > Email on your host before relying on alerts.
- If you import `connecthub.sql` into a new empty database, the one administrator is `admin@example.com` with a temporary password (given in the delivery note). You must choose a new password at first sign-in. If you use the web installer instead, you choose the administrator details yourself.
- Verification reference photos are stored in `uploads/verification-poses/`; members' own verification photos stay private in `storage/private/`.
- Upgrading an older live site: follow section 11. The database updates itself through migration 0032. Recent migrations add wallet, boosts, paid messages, International, landing order, verification methods, search engine settings, the article library, Host verification options, payment methods, Connection profiles, the agent showcase, story bubbles, visitor choices, Elite, real media stories, launch access and advertising analytics. Never import a full SQL snapshot into a live database; use the migration runner.

## 13. Taking payments (Admin > Payment methods)

One page lists every way to pay with its own logo, a switch, a Set up button and up/down arrows that decide the order members see at checkout. A method cannot be switched on until its keys are complete. Members always pay in your site currency; PayPal converts at the rate you set.

**Scheduled job.** The same cron entry from section 4 also re-checks online payments that never came back, sends automatic-renewal reminders and charges, and sends small M-Pesa payouts. Without cron none of those happen.

### M-Pesa (Admin > Payment methods > M-Pesa, three tabs)

1. **Prompts on the phone (STK push).** Enter the Daraja consumer key and secret, short code, passkey and environment. Use **Test connection**, then **Send a KSh 1 test**. Safaricom refuses callback addresses containing the word "mpesa", so ConnectHub gives it a neutral address (`/api/pay/stk/...`); it is filled in for you.
2. **Paybill or till (C2B).** Customers pay to your Paybill or till and type their order reference as the account number; ConnectHub finds the order and turns it on by itself. Click **Register addresses with Safaricom** (on a live short code you can register only once, so check the details first). Turn on "Check the account number before accepting" only if Safaricom has also switched validation on for your short code. In the sandbox, **Simulate a customer payment** tests the whole path. Payments with a wrong account number, too little money, or paid twice are kept in the list for you to match by hand.
3. **Payouts to phones (B2C).** Needs an approved B2C product on your short code. Enter the initiator name and password and paste the Safaricom certificate (the sandbox and live ones differ), or paste a ready-made security credential. Then Payouts and Wallet pages get a **Send via M-Pesa** button. A request that gets no answer is never sent twice: it shows "no answer, check the portal" until you release it. "Send small payouts automatically" pays requests up to the limit you choose within a few minutes; larger ones wait for you.

### Cards, Paystack and PayPal

Each opens a secure page run by the provider, so card numbers never touch your server.

- **Cards**: choose Stripe or Paystack as the card processor. For Stripe add the secret key, then in the Stripe dashboard add a webhook to the address shown (events `checkout.session.completed` and `checkout.session.async_payment_succeeded`) and paste its signing secret.
- **Paystack**: add the secret key and put the webhook address shown into Paystack Settings > API Keys & Webhooks. Members can pay by card, M-Pesa or bank on its page.
- **PayPal**: add the client ID and secret, choose Sandbox or Live, set the currency PayPal charges and your exchange rate, and add a webhook for `PAYMENT.CAPTURE.COMPLETED` to the address shown; paste its webhook ID.

A payment is confirmed twice, once when the member returns and once by the provider's signed webhook, and only if the amount, currency and reference match. A late success on an order that was cancelled is still honoured once.

### Automatic renewal

Turn it on under Payment methods > Automatic renewal. Members then see a "Renew automatically" tick at checkout and controls on Billing. A saved card is charged by Stripe or Paystack the day before the plan ends; an M-Pesa member gets a prompt to approve; everyone else gets a reminder with a pay link. Failed charges are retried after the number of days you choose, the member is told each time, and renewal switches itself off after the last try. Card details are kept by the provider; only a token and the last four digits are stored.

### Testing without real money

Use the M-Pesa and PayPal sandboxes and Stripe/Paystack test keys. The "Test payment" method (off by default) completes orders without paying so you can try the whole flow. Switch it off before launch.

## 14. Search engines and the blog

- **Admin > SEO** holds the search title template, default description, social image, organisation details, verification codes for Google, Bing and others, the sitemap options, IndexNow, redirects and a log of pages visitors could not find. Every page has its own title, description and social card, and public pages carry structured data. Private areas (messages, settings, admin) are kept out of search.
- After launch: add the site to Google Search Console and Bing Webmaster Tools, paste the verification codes into Admin > SEO, and submit `https://your-domain/sitemap.xml`.
- **Blog**: 31 long guides ship ready to publish, in six categories. Edit or add articles under Admin > Blog. Each has a search title, description, focus keyword, FAQ (which becomes FAQ rich results), social image and optional canonical or noindex. Articles you write or edit are never overwritten by upgrades.
- Turn off "Ask search engines not to index the site" (Settings > General) when you go live. System health on the dashboard warns while it is still on.

## 15. Other things worth knowing

- **Download my data**: members can download a copy of their data from Settings > Account (asked for with their password). Admins can export one member from the member's page.
- **System health** (Dashboard and Tools) now also checks payments (methods switched on without keys, missing webhooks, online payments stuck for over a day) and whether search engines are allowed in.

## 16. Logos, landing page, Hosts and International (latest changes)

- **Logos for each place** (Admin > Settings > Branding > "Signed-in app and menus"): the top bar when a member is signed in, the side panel for members, a separate side panel logo for Hosts, and the phone menu on the landing page. Each has a light and a dark file and falls back to the compact logo when left empty. Sizes sit next to them.
- **Landing page "Places" section** (Admin > Landing page > Places): choose *Cards with photos*, *Buttons with a count* or *Buttons in one swipeable row*. "Show member counters" turns the numbers on or off. The *Articles* section can be a swipe slider on phones ("Slide on phones").
- **Hosts without bookings** (Admin > Settings > Hosts > "How the Host module works"): turn off "Clients can book Hosts on the site" to run Hosts as a directory only. Booking, availability, earnings and payout screens and settings are hidden or switched off, Hosts are not asked for M-Pesa details, and Hosts with bookings still in progress can finish them. Turn it back on any time.
- **Contact details** (same screen, "Contact details"): choose who sees a Host's phone, WhatsApp, email and Instagram (after a booking, signed-in members, Premium members, everyone, by your rules, or nobody), which channels exist, buttons or a list, and a short note. Hosts add their details under My profile > Contact details and can switch sharing off.
- **International** (Admin > International) now has four tabs: *Overview* (what needs you, money, countries, setup checklist), *Listings*, *Setup* (page text, who pays with only the relevant prices shown, safety rules) and *Access*. Members see a three-step path (profile, go live, meet people), country quick-filters, sorting, grid or list, and a clear unlock panel. The profile form has a live preview and checklist.
- **Admin dashboard**: hero with member search and live chips, 7, 30 or 90 day range, featured KPIs, revenue by type, member locations, plan mix, site modes and an extended work queue (wallet withdrawals and International listings added).
- No database structure changed in this round. If you already run the previous release, the code update is enough. The upgrade SQL is the same as before and is safe to run again.

## 17. New faces grid, places design, banners, payouts and member editing (latest changes)

- **New faces near you** (Admin > Landing page > Page sections > New faces > Edit): the landing section is now a grid, not a slider. Set how many members to show (2 to 24), 3, 4 or 5 columns, and which groups to offer as tabs: *Newest*, *VIP and featured* (VIP plan, active boost, or featured by you from a member's page), *Suggested* (verified or well-filled and recently active) and *Regular*. Rename each tab and choose which opens first. A group with nobody in it is hidden, and one group shows no tabs.
- **Design of the "Find people in your town" cards** (Admin > Locations, top card, or Landing page > Page sections > Browse by place > Edit): *Cards with photos* (default), *Small cards* (no photo, no counters, a letter badge, county or town label), *Buttons with a count* and *One swipeable row*. "Show member counters" applies to the picture cards and the buttons.
- **Dashboard banners** (Admin > Settings > Logo & branding > "Dashboard banner pictures"): upload the picture behind the banner on the Host dashboard, the Host earnings and payouts pages, the member wallet and the admin dashboard. Leave a slot empty to use the built-in picture, pick how strong the colour over the picture is, or switch pictures off for plain colour banners.
- **Host dashboard** has the new banner with the numbers that matter, a row of shortcuts (profile, photos, availability, packages, bookings, earnings, payouts, reviews, messages, settings) and the existing queues below. Earnings and payouts stay open to every approved Host, also when bookings are switched off.
- **Payouts** (Hosts at *Payouts*, members at *Wallet > Get paid*): the request form is always visible. When something blocks it (no M-Pesa number, below the minimum, a request already waiting, payouts paused) the form is greyed out and says exactly what to do. Quick amounts (smallest, half, all) fill the field. The server still checks every amount.
- **Host profile, availability**: the calendar opens from an *Availability* button as a dialog (a bottom sheet on phones) instead of sitting open on the page.
- **Logo for signed-in members on large screens**: members viewing pages such as another member's profile see the *side panel logo*; on pages open to everyone (articles, public pages) they see a new *Top bar logo for signed-in members* (Settings > Logo & branding > "Public pages when signed in"), with a dark mode version and a height setting.
- **Admin > Members > a member**: *Edit profile* changes the name, date of birth, details, interests, location, contact details, sign-in email and phone. Changes are written to the activity log; a changed email must be verified again. The *More actions* menu is now a bottom sheet on phones. The same menu on Host and report pages follows.
- **Member photos** (Admin > Members > a member > Photos): add several photos for a member at once (approved straight away), make one the main photo, move photos earlier or later, rotate left or right, make a photo VIP only, approve (or approve all waiting), reject with a reason, or delete. On the Members list, the "..." menu on each row opens the member, the profile editor, the photo tools or the public profile. The editor has a photo strip and a section menu at the top.
- **Concierges band on the landing page** (Admin > Landing page > Page sections > Concierges > Edit): the picture now sits on a styled glass card with a soft glow. Upload a PNG with no background and it is shown whole, standing on the card, instead of being cropped. *Picture style* is Automatic (detects see-through PNG or WebP files), *Picture with no background* or *Photo (fills the card)*.
- **International band on the landing page** (Admin > Landing page > Page sections > International > Edit): redesigned with a full-width *Background picture* (change, remove, or pick from the media library; a built-in picture is used until you do), a dark wash that keeps the text readable, and a glass card listing where members are, by country. With no members yet it shows three short reasons to join.
- **Hosts page, top section** (Admin > Settings > Hosts, "Directory banner picture", or wherever that setting sits in your admin): the picture is now the background of the whole top section, with the title, trust points and search on top of it. Removing the picture returns the plain light design.
- No database structure changed. If you run the previous release, replace the code. The upgrade SQL is the same as before and is safe to run again.

## 18. Admin: missing data, hidden sections and the Database check (latest changes)

- **Problems are no longer hidden.** When a number or list on an admin page could not be loaded, a yellow box at the top of the page now says so and shows the reason. Before, the section just showed zero or stayed empty. The same reasons are written to Tools > Server logs.
- **Database check** (Admin > Tools > "Database check", also a line in System health on the dashboard): compares your database with what this version needs and lists missing upgrade steps, tables and columns. *Repair now* (needs full access to Tools) applies the missing steps and adds what is missing. It never deletes anything. Use it after replacing the code on a site that was running an older release, especially if parts of the admin look empty.
- **Limited roles are explained.** If you are signed in with a role such as Moderator, Support agent, Finance or Content editor, the dashboard says so: other menus and sections are hidden on purpose. A Super admin changes this under Admins and roles.
- Fixed: the Verification methods page was blocked by the browser's security policy (an inline script without its nonce), and a warning on the Places admin page.
- No database structure changed (the repair tool only adds what a newer release already expects). The upgrade SQL is unchanged.

## 19. Admin pages were being hidden by browser ad-blockers (latest changes)

- **Problem seen:** on almost every admin page only the KPI cards showed; the welcome banner, the launch checklist and all the lower sections (charts, work queue, system health, newest members, latest payments) were missing. This happened on sites viewed with Chrome plus uBlock Origin, Brave, Edge with built-in tracking protection, AdBlock Plus or any browser using the EasyList filters.
- **Why:** the admin's CSS classes used the prefix `ad-` (such as `ad-hero`, `ad-callout`, `ad-grid`, `ad-stack`, `ad-head`, `ad-card`, `ad-section`, `ad-tag`, `ad-link`). EasyList, which almost every ad-blocker uses, hides elements named like this because advertisers use the same names. Nothing was wrong on the server — the browser was removing those sections before you ever saw them.
- **Fix:** every admin CSS class that started with `ad-` was renamed to `xp-` (short for "admin panel"). This covers the dashboard, Settings, Tools, Members, Hosts, Payments, Analytics, Landing editor, Content, Media library and every admin partial. Advertisers' own ads (`.xad-*` and the `.ad-card` inside them) still use the ad-labelled classes, as they should.
- **What to do:** replace the code, reload the admin, and the hidden sections come back. Your browser's ad-blocker never has to be touched. No database change.

## 20. Agent showcase, stories, visitor tab bar and launch checklist (latest changes)

Everything below is in the Admin sidebar and needs no code. The words "agent" and "Host" mean the same thing; you can rename them under Settings > Host wording.

**Admin > Agent showcase** (one page, five tabs)

- **Landing page sections.** The landing page shows blocks of agents (for example "Top Hosts near you" and "New Hosts"). For each block choose how many agents show, which kinds qualify (VIP, featured, premium members, paid listing, boosted), whether other agents fill the block when there are too few paid ones, the order (rotate, newest, best rated, most recently active, nearest), the category, how many cards sit in a row on a computer, a tablet and a phone, the layout (grid, or a swipe rail on phones), the card style, and what each card shows (services, rating, price, tier badge, call and WhatsApp buttons, bio). Add as many blocks as you want, reorder, hide or delete them under Admin > Landing page too.
- **Order of importance.** An agent you pin comes first, then VIP, featured, premium, paid listing and boosted agents. When a block has no paid agents it simply shows the others, so it is never empty.
- **VIP and pin.** On any agent's page in Admin > Hosts use More > "Mark as VIP agent" or "Pin to the top of showcases" (pin 1 comes before pin 2). More > "Edit services" lets you change which services an agent offers; agents choose their own under My Host profile > Services.
- **Visibility.** Choose whether visitors see photos blurred, services, rates and contact details, and who may see contact details (everyone, signed-in members, premium only). New installs and upgrades start with everything visible to visitors; change it here whenever you like.
- **Default bio.** Agents who have not written a bio get "Meet {name} from {location}. ..." with the name, town and their own short text. Edit the template under Settings > Hosts > Agent showcase.

**Story bubbles** (Admin > Agent showcase > Stories). The round, Instagram-style strip at the top of the landing page, the Hosts page and (optionally) the member Discover page. Tap a bubble to open a full-screen story with the agent's picture, bio, services and Call, WhatsApp and View profile buttons.

- Choose where the strip shows, how many agents, which kinds of agents (VIP and featured first), whether other agents fill it up, the first "Become an agent" bubble, seconds per story and the slow automatic scroll. Switch the whole strip off with one tick.
- **Sponsored stories.** In Admin > Adverts add an advert with the format "Story" (picture, headline, button link, dates and limits). It appears as a bubble after the paid agents, marked Sponsored, and its views and clicks are counted. Adverts must be switched on under Settings > Adverts.
- **Promotion line.** Each agent can add one short line (up to 140 characters) under My Host profile; it becomes the headline of their story. Admins can remove an offensive line from the agent's page (More > "Clear promotion line"). Switch the feature off under Settings > Hosts > Agent showcase.

**Visitor tab bar.** On phones and tablets, public pages (home, Hosts, Browse, Pricing, Help, Blog...) now have the same floating bottom bar as the member app. Edit its items under Admin > Navigation > Visitor tab bar (five fit well) or switch it off under Settings > Landing page > Header. Signed-in members and Hosts see their own tab bar on public pages.

**Page headers.** The Browse, Help, Contact, Pricing and Blog pages use a rounded app-style header on phones, with pill filters and a grouped question list. Colours follow your brand colours.

**Launch checklist.** The Admin dashboard shows a short checklist until the basics are in place (logo, contact email, real email sending, a way to pay, landing slides, approved agents, agents visible to visitors).

**Before you launch, in this order:** change the default admin password, send a test email from Settings > Email, switch on one payment method and send a test payment, approve at least four agents, open the home page on a phone and check the bubbles and the Hosts blocks, then check Admin > Agent showcase > Visibility is how you want it.

## 21. Visitor choices, Elite, dating sign-up and the M-Pesa checkout (latest changes)

**Visitor choices** (Admin > Visitor choices). A first-time visitor on the home page is asked "What brings you here?" with three cards: Dating, Fun and Elite. A Friends card is there too, switched off. On a phone it is a bottom sheet they can swipe down, on a computer a centred panel.
- Their choice is saved on their device in a cookie for a year. Once they sign in it is also saved on their account, so a new phone remembers it. The IP address is deliberately not used: on mobile data many strangers share one address.
- What a choice changes: the home page heading, the line under it, the main button, and which landing blocks come first or are hidden. You set all of this per card. A card can also open a page instead (for example Fun can go straight to /hosts).
- A small "Dating · Fun · Elite · All" switch at the top of the home page lets people change their mind. Search engines never see the picker. "Preview the picker" on the admin page opens it for you.

**Dating needs an account** (Settings > Privacy & visibility > Dating members, on by default). Visitors see blurred previews on Browse, member profiles and the home page member blocks, with a sign-up prompt. Hosts and Elite follow their own settings.

**Elite** (Admin > Elite). A private circle for established, successful people and the members who want to meet them. You can rename it under Setup (for example Luxe or Prestige).
- *Joining:* members fill in an Elite profile at /elite/me: headline, about, what they do, a one-word lifestyle (never income), what they enjoy and what they are looking for. You approve each profile (strongly advised) and can give it a Verified Elite badge or feature it. Joining can be free, a one-time price, or a price per period, and free for Premium or VIP plans.
- *Who can reach them:* each Elite member sets criteria (gender, age, where people live, verified badge, photo), each as off, nice to have or must have. Every request gets a match score. With the filter on, requests that miss a must-have or score below their minimum go to a Filtered folder with no notification, and the sender is not told.
- *Privacy:* Elite members can stay out of ordinary dating (Discover, Browse, search), show photos only to people they accept, and share their phone number with people they accept.
- *Seeing a full profile:* choose one of three modes. *Pay:* one profile, a pack of N profiles used within X days, or a time pass. *Free:* for signed-in members. *Accept:* only after the Elite member accepts. Before that, people see a first name, age, place, headline, a short excerpt and a blurred photo. Sharp photos are refused by the server until the profile is open, even with a direct link.
- *Contact:* always a request ("Introduce yourself"). Accepting opens a chat with the introduction as the first message.
- *Safety:* profile text and requests may not contain contact details or wording that turns dates into a transaction (allowance, pay per meet ...). Members can report a profile.
- Payments use the normal checkout. Admin > Elite > Access shows passes and credits and lets you give some.

**The manual M-Pesa checkout** (Admin > Payment methods > M-Pesa > Paybill or till). Fill in how members pay (Pay Bill, Buy Goods till, Send Money or Pochi la Biashara), the number, the name M-Pesa shows, and optionally a fixed account number. Left empty, each order's own reference is used.
- The checkout shows a Lipa na M-PESA card with the number in large digits, Copy buttons that confirm "Business number copied", and the menu steps.
- On Android phones an "Open the M-PESA menu" button dials *334#, the Safaricom M-PESA menu. iPhones cannot open USSD codes from a web page, so they see a short hint instead.
- Sites that only filled in the older free-text "Where to pay" keep working: the number is read from that text.


## 22. Elite: pages, address, search appearance and saved profiles (latest changes)

Everything below is on **Admin > Elite > Pages and look**. Nothing here needs setup: the defaults work, and an upgrade keeps what you already saved.

- **Page heading and introduction.** Press Edit on the Elite page or the access page. The heading is the big line at the top and also the default browser title. The introduction sits under it.
- **Search appearance (SEO).** In the same box, write the search title and description that Google shows, upload a share picture for WhatsApp, Facebook and X, or tick "Hide this page from search engines" (adds noindex and removes it from the sitemap). A live preview shows how it will look. Leave a field empty to use the heading or introduction. "Clear the search title, description and picture" puts everything back. The same pages also appear in Admin > SEO.
- **Web address.** The Elite page lives at `/elite` by default. Type another word (for example `circle` or `prestige`) and the menus, buttons, canonical links and sitemap all follow it. The old address sends visitors and Google to the new one with a permanent (301) redirect, so no link breaks. Words the site already uses (such as `admin`, `hosts` or `browse`) are refused with a message.
- **Colour and banner.** Five accent colours (gold, rose, emerald, sapphire, amethyst) change buttons, badges and selected options. A banner picture can sit softly behind the heading. The banner can also fan three blurred member cards and show member numbers; both appear only when there are enough live members, so the page never looks empty.
- **Help for visitors.** "How Elite works" shows four short steps worded for the way you let people open profiles. "Questions and answers" is a text box: write the question on one line, the answer under it, and leave a blank line between items (up to 12). The questions are also sent to Google, which can show them in search results.
- **Saved profiles.** Members can tap a heart on any card or profile and find them again under Saved. Switch this off in the same tab. An Elite member only sees how many people saved them, never who. Saves leave the list when a profile is suspended, removed or blocked.
- **For members:** search by first name, headline, town or county, sort by best match, newest or recently active, filter by verified or active this week, share a profile link, and on a phone a bar at the bottom of a profile offers "See options" or "Send a request". Elite members see views, saves and requests on their own page.

**Upgrading:** extract the zip over the site and open any page: the database adds one new table (`elite_saves`) by itself. If your host will not let the site change its database, run `connecthub-upgrade.sql` once instead. It never deletes or overwrites anything and is safe to run twice.

## 23. The contact module, Discover sections, the menu button and the passcode (latest changes)

### The name you give these accounts now reaches search results
**Admin > Hosts > Names and Discover.** Typing a new name (Concierge, Companion, anything) already changed every page, menu and e-mail. It now also changes the page titles, the descriptions Google shows, the share cards on WhatsApp and Facebook, and the data search engines read. Nothing to switch on.

### Two ways to run the module: Booking or Contact
**Admin > Hosts > Names and Discover > How people reach them.**

- **Booking** is the original way and stays the default: a client picks a date and time, pays a deposit here, and the agent confirms.
- **Contact** is the new way: nothing is booked and no money passes through the site. People see what an agent offers and reach them on **WhatsApp, by phone, by email, on Instagram or with a message here** — whichever that agent filled in under their contact details. Services are shown as small colour cards and are only *named*, unless the agent adds a price to one.

Switch between them whenever you like. **Nothing is deleted either way**: rates, packages, calendars and every past booking stay in the database, so going back to Booking restores all of it exactly as it was.

In Contact mode you can also set:
- whether agents may put a price on a service (they can leave every price empty),
- whether they may write a short line above their contact buttons ("WhatsApp is fastest"),
- the heading above those buttons,
- how the service cards look: soft colour, full colour or outlined.

**Service colours.** Each service in **Admin > Host services** can be given one of eight colours, or left on Automatic and the site picks a steady one for it.

### Discover
**Admin > Settings > Discovery and matching > Sections on Discover.**

- A **chip next to the Filters button** lets a member switch between Dating, Fun, Elite and anything else you left in the switch. One tap and the page comes back arranged for that choice.
- **The chosen experience decides the order.** Dating puts people first and the agent row at the bottom. Fun leads with the agent row. Elite leads with the Elite row. No choice, or the setting off, uses your own order.
- An **Elite row** can be shown on Discover, with its own count.
- How many each section shows is yours to set: daily picks up to 24, the agent row up to 40, the Elite row up to 24, and the people feed up to 60 at a time.

### What appears in the "What brings you here?" question
**Admin > Visitor choices.** Each card now has two switches of its own: **in the question visitors are asked**, and **in the switch and the menu**. So you can keep Elite out of the opening question and still leave it one tap away in the menu and on Discover. (The question needs at least two cards to open at all.)

> While you are there: saving this page used to fail silently on some setups. That is fixed.

### The round "Become an agent" button
**Admin > Agent showcase > Stories.** The same round button can now sit **in the menu** (the new default), as the **first bubble in the story strip** (where it used to be), in **both** places, or **nowhere**. In the menu it appears in the phone menu and in the account dropdown, and is hidden from people who are already agents.

### Landing page
The "Find people in your town" row on phones now drifts slowly and smoothly on its own, can be swiped, and has a button on each side. It stops as soon as anyone touches it, and stays still for anyone who asked their phone for less motion.

### Account screens
- The create-account panel has an **"I have an account"** button at the top, beside "It takes about two minutes".
- The **sign-in page logo** can be set on its own: **Admin > Settings > Branding > Signed-in app and menus**, "Sign-in and sign-up page logo" (and a dark-mode one).
- The **Details** block on a profile (work, education, height, build and the rest) has been redrawn as tidy tiles.

### Staying signed in
**Admin > Settings > Security.** "Stay signed in for (days)" now starts at 180, and the clock is pushed back whenever the member uses the site, so someone who comes back regularly is never asked to sign in again.

### Passcode
**Members set this themselves: Settings > Privacy > Passcode.** A 4-number passcode, entered on a round keypad, asked for when they open the app again and after a quiet spell. The ring turns green when the code is right. They can change it or remove it at any time; both need their account password, and their account password also opens the lock if they ever forget the passcode, so nobody can be locked out. Five wrong tries signs them out.

**Admin > Settings > Security** has the switch for the whole feature and the quiet time (0 means ask only when the app is opened fresh).

**Upgrading:** extract the zip over the site and open any page: the database adds the new columns and settings by itself. If your host will not let the site change its database, run `connecthub-upgrade.sql` once instead. It never deletes or overwrites anything and is safe to run twice.

## 24. Town rows, no prices on cards, "show only what they chose", adverts and search (latest changes)

This round answers a long list of requests. Everything below works after you extract the zip over the site and open any page (the database updates itself). If your host will not let the site change its database, run `connecthub-upgrade.sql` once instead: it holds everything from this round, never deletes or overwrites saved data, and is safe to run twice.

### Landing page
- **Find people in your town** on phones is now three rows. The rows drift in opposite directions, can be swiped sideways, and have a pause and play button. They stop for anyone who asked their phone to reduce motion, and as soon as a finger touches them.
- **Dating cards** (home page and Discover) show the person's name larger, with the age and the town beneath in a cleaner style.
- **Spaces between sections** (Featured, VIP, Most popular and the rest) are tighter and even.
- A **Promotions** block can sit on the home page (see Adverts below). It prints nothing while no advert is running, so there is never an empty gap.

### Agent (Host) cards show no prices in the contact way
When the module runs the **contact way** (Admin > Settings > Hosts), no list or card shows a price: not on the home page, Discover, the directory or the "similar" row. A price appears only on a profile, and only if that agent chose to add one. The two price sorts disappear with them. The booking way is unchanged.

### Show only what they chose
**Admin > Visitor choices > Show only what they chose** (on by default). Someone who picks **Dating** sees only the people side, someone who picks **Fun** only the agents, someone who picks **Elite** only the Elite circle: on the home page and on Discover. Switch it off to give everyone every block again.
- **Admin > Settings > Discovery > People shown when someone picked Fun or Elite** sets how many people those full-page grids hold (24 by default, up to 60). The saved counts for New faces and the other blocks are yours to raise under **Admin > Landing page > New faces** and **Admin > Settings > Discovery**.

### Adverts and the Advertise page
- A public **Advertise** page (footer link, **Company** column) lets a business choose a package, upload its picture, enter the link, and **pay at the end**. The package decides how long the advert runs.
- **Admin > Adverts** lists every advert with a clear life: *unpaid* (a draft), *pending* (paid, waiting for you), *active*, *paused*, *rejected*. When **Check each paid advert** is on, the days start counting the moment you approve it. When the time is over the advert stops showing by itself; nothing needs a scheduled job.
- **Admin > Adverts > Packages** sets name, short line, days, price, where it appears and which one is marked "popular". Three starter packages are added once. Rename, reprice or delete them freely: an upgrade never puts them back.
- **Admin > Settings > Advertising > Businesses buying adverts**: turn the page on or off, require approval, set how many adverts show together, and edit the rules shown to advertisers (one per line). The email you get when a paid advert waits is under **Admin > Settings > Email > Alerts to you**.
- Adverts are small cards with a sharp picture, never a big banner. **One or two adverts** get a roomier layout automatically; three or more become a tidy row. **With none running, the block is not drawn at all.**
- The class names, folders and links are deliberately neutral (`spot`, `/out/{id}`) so that browser ad-blockers do not hide them.
- The rules default to **no adult services, escorts or sexual content**.

### Search (SEO)
- **Admin > SEO** now has, for every page, **Keywords**, a **Heading** and a short **Introduction** next to the title and description, with a live Google-style preview. Page keywords are merged with the site-wide keywords.
- **Admin > Host search and SEO** (it carries whatever name you gave the Hosts module) is a panel just for that module: words added to every page, the title pattern for each profile (`{name}, {category} in {place}`), search text for each **category**, a **frequently asked questions** block that is also marked up for Google, structured data switches, a **checklist with a score**, and ready-made suggestions you can add with one tap.
- **"Free dating site"** is now in the default site description, tagline, keywords, the Browse page and the sign-up page. On an existing site these are rewritten **only if they still hold the exact words the site shipped with**. If you had already typed your own tagline, keywords or description, they are left alone: add "free dating site" yourself under **Admin > SEO** (or Settings > SEO and social).
- **Browse and each town page are now indexable** for visitors who are not signed in: they show a heading, text, frequently asked questions and structured data instead of a blank locked page.
- The **18+ confirmation page** in front of the agent directory can be indexed too (switch under Host search and SEO), so the directory can rank. When the 18+ gate is off, category and profile pages are listed in `sitemap.xml`; when it is on they are left out so Google is not sent to a page it cannot open.
- `robots.txt` and `sitemap.xml` are generated from these settings. Private areas (admin, account, messages, payments) stay out of both.

### Smaller fixes
- Admin settings pages that stopped loading after the first draft of the adverts settings are fixed, and the build now checks that every settings group belongs to a real tab.
- A setting is only marked **changed from default** when its value really differs. Rows written by an upgrade that hold the built-in value no longer show the blue dot or count in "Only changed from default".
- Dark mode: the selected category chip in the agent directory and the advert package labels now have readable text.
- Submitting the Advertise form too quickly now shows a message and keeps what was typed, instead of silently reloading.

**Upgrading:** extract the zip over the site and open any page. If you also want the new words in your own saved site text, see the note under Search above.

## 25. More service options and the new contact buttons

Extract the zip over the site and open any page (the database updates itself), or run `connecthub-update-0029.sql` once if you prefer. It adds four service options and changes nothing else. Safe to run twice.

### New service options (Hosts)
- **Dinner Dates** (Meals and conversation), **Travel Companion** (Travel and outdoors), **Massage** (a new **Wellness** group, described as a therapeutic massage) and **Meet at my place** (How we meet).
- "I travel to you" already exists as **Can travel to you**, so it was not added twice.
- They appear for every Host under **Services** and for you under **Admin > Host services**, where you can rename, hide, re-colour or reorder them like any other. An upgrade only adds names that are missing: anything you renamed, hid or deleted stays as you left it.
- The listing deliberately offers no explicit sexual services, and the Host guidelines and the 18+ gate are unchanged.

### Contact buttons on profiles
- A Host's profile now has one **big button** for the first way to reach them (WhatsApp when they added it, with the real WhatsApp logo in its own green) and **tiles** underneath for the others: Call, Instagram (with its logo and colours), Email and **Message on this site**. Each shows the number or handle, so people know what they are tapping.
- The same buttons are used on connection profiles and on the Elite "you are connected" panel. On a phone the bar that follows the page shows round Call and WhatsApp buttons next to Message.
- When only one way is available it becomes the big button; with none, nothing is drawn. Which ways show is still decided by **Admin > Settings > Hosts > Contact details**, and who may see them by the same visibility setting as before.
- Numbers typed the local way (0712345678) are now shown as 0712 345 678.
- When a Host takes no bookings, the profile shows this same box at the top instead of a separate WhatsApp button, so there is one clear place to get in touch.

### Smaller fixes
- A label on connection profiles ("Approved profile") now meets contrast rules in light and dark mode.

## 26. Swipe-first Discover, Dating as the default, and a new Discover design

Extract the zip over the site and open any page (the database updates itself), or run `connecthub-update-0030.sql` once if you prefer. It adds one empty column and changes nothing else. Safe to run twice.

### Swipe is the default
- **Discover opens on the swipe cards** (swipe right to like, left to pass, up for a Super Like) for every member. **Grid** and **List** are exactly as they were and are one tap away at the top of the page.
- A view only sticks once the member **chooses it themselves**. Views people picked before this update are kept in their saved preferences but are not applied automatically any more, so everyone starts on Swipe and picks their own again with one tap. Nothing is deleted.
- Saving other Discovery preferences in Settings no longer pins the current view as a "choice".
- **Admin > Settings > Discovery & matching > Layout > Default view** still lets you pick another default for everyone.

### Dating is what the home page shows until a visitor chooses
- A visitor who has **not** picked Dating, Fun or Elite now sees the **Dating** home page (its heading, button and blocks) and a Dating-first Discover. The "What brings you here?" question still opens as before, and what a visitor picks always wins. "Show me everything" still shows everything.
- **Admin > Visitor choices > Shown until a visitor chooses** sets this (Dating, Fun, Elite, Friends or Everything). Search engines are shown the same page as new visitors.

### The new Discover
- **Cards fit the screen.** The like, pass, Super Like, Undo and Boost buttons are always in view above the bottom bar on any phone, and the card is sized to the window on tablets and computers. (Before, the buttons could sit under the bottom bar.)
- **Details sheet.** Tap the middle of a card, the arrow button on it, or press **I** to slide up the full bio, what they are hoping to find, their interests (shared ones marked), job, town, distance and match score, with **Pass**, **Full profile** and **Like** at the bottom. **Esc** closes it.
- **More on each card:** a match-score ring, "Likes you", "Super liked you", "New here" and "Featured" tags, **Online now** or when they were last active, and their job.
- **Quick filters above the cards:** Online now, Verified, Active today, what they are looking for (Dating, A relationship, Friendship, Fun and casual, Networking, Not sure yet) and distance. One tap each; **All filters** opens the full panel.
- **Side panels on large screens:** filters on the left; on the right **Likes you** (faces for Premium, blurred for Free with a "See who" button), your newest **Matches**, who is **Online now**, **Today's picks** and your Boost. On phones and tablets the same panels sit below the cards.
- **Boost** is a button next to the card buttons (it lights up while a boost is live). A short **swipe hint** shows once for someone who has never swiped here. Keyboard: arrows, **Z** to undo, **I** for details.
- Two new switches in **Admin > Settings > Discovery & matching > Layout**: *Side panels on large screens* and *Quick filters above the cards* (both on).

### Checks
- New tests: `default_mode_test.php`, `discover_rail_test.php`. The home page and Discover pass the accessibility check in light and dark at phone and computer sizes, including with the details sheet open.

## 27. A plain agent profile by default, one clear switch, and a redesigned admin

Extract the zip over the site and open any page (the database updates itself), or run `connecthub-update-0031.sql` once if you prefer. To run everything from 0006 onwards in one go, run `connecthub-upgrade.sql`. Both are safe to run twice and delete nothing.

### The agent profile no longer looks like a service booking
- The **contact way is now the default**. A profile shows the agent's photos, what they offer (service cards), where they meet people, reviews, the rules and **Get in touch** (WhatsApp, Call, Instagram, Message). It has **no** price box, instant-booking badge, "Booking with ..." panel, services-and-prices table, availability, completed-bookings count or cancellation policy.
- **Where to change it:** **Admin > Hosts**, the top card **How people reach a Host**. Two tiles, *Contact directly* and *Booking and deposits*; one click switches and the page updates at once. **See a profile** opens a live example. The older place (**Names and Discover**) still works and shows the same setting.
- Switching deletes nothing. Rates, packages, calendars and past bookings stay on file for when you switch back.
- Sites that had **never saved** this setting move to the contact way when the database updates (migration 0031). A site where an admin had already chosen a way keeps that choice.

### Redesigned admin pages
- **Dashboard:** a new Overview with large tinted number tiles (revenue, members, activity, what needs attention) and a second row (matches, messages, premium, paying customers, hosts, host profiles or bookings, international). Rows always fill the width. New *Hosts at a glance* and *Elite at a glance* cards, and the site-mode list shows how Host profiles are shown.
- **Hosts:** six number tiles, status pills with counts, search, category, ID-check and sort filters, a **List** or **Cards** view, quick **Feature / Unfeature** that returns to the same filtered list, and **Export CSV** (follows the current filters). Phones get stacked rows.
- **Elite:** a gold-accent header with **Switch on / Switch off**, tabs for Overview, Members, Requests, Pages, Setup and Access, number tiles, a requests chart, member cards with photo, status and one-tap Approve, Verify, Feature, Suspend and Remove.
- A shared refresh (`assets/src/modules/admin_refresh.css`) gives every admin page softer cards, better spacing, calmer tables and buttons, in light and dark mode. Other admin pages pick up the new look automatically; their layouts are unchanged.

### Checks
- `host_modes_test.php` now also proves the default is the contact way; `hosts_test.php` sets the booking way for its booking life cycle and puts it back.
- The dashboard, Hosts (list and cards) and every Elite tab pass the accessibility check in light and dark mode. A browser test covers the switch, feature buttons, CSV export, the Elite switch and member actions.
