# ConnectHub

A social discovery and companionship platform for Kenya: Explorer and Host accounts, location-based discovery, messaging, premium plans, boosts, gifts, adverts, M-Pesa payments, and a full admin panel. Plain PHP 8.1+ and MySQL/MariaDB. No framework, no Composer, no build step on the server.

## Quick start

1. Upload this folder to your host (the site root is the folder that contains `index.php`).
2. Create an empty MySQL/MariaDB database and user.
3. Either
   - open `https://your-domain/install/` and follow the installer (recommended), or
   - import the separate `connecthub.sql` file (delivered next to this zip, not inside it) into the empty database, copy `config/config.sample.php` to `config/config.php`, fill in the database details and a long random `app_key`.
4. Add the cron job from `docs/DEPLOY.md` (section 4).
5. Work through the first-hour checklist in `docs/DEPLOY.md` (section 5).

Full guide: **`docs/DEPLOY.md`**.

## Signing in after a SQL import

The separate `connecthub.sql` file contains one administrator so you can sign in without the installer:

| | |
|---|---|
| Address | `/login` |
| Email | `admin@example.com` |
| Temporary password | see the delivery note (it is also the only value you need to change first) |

You are forced to choose a new password at the first sign-in. Then change the email in Admin > Account.
If you use the browser installer instead, it creates your own admin account and none of this applies.

## Before you go live

- Settings > Payments: keep M-Pesa on **Sandbox** while testing, switch to **Production** only with live Daraja credentials.
- Settings > Email: set an SMTP account, otherwise no verification or reset emails are sent.
- Admin > Pages: the legal pages are starter text. Have them reviewed by a lawyer.
- Set `'debug' => false` in `config/config.php`.
- Delete the `install/` folder.

## Rebuilding the stylesheet (optional)

`assets/css/app.css` is already compiled. To change the design source (`assets/src/`, `tailwind.config.js`):

```
npm install
npm run build:css
```
