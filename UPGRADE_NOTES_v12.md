# ConnectHub v12 – Stories and mobile navigation

## Changes
- Real photo/video stories now publish immediately after successful upload. The pre-publication approval setting is forced off in application code and in the platform settings save handler.
- Added migration `0034_stories_auto_publish.php`. It releases stories that were already pending when the migration runs and starts their 24-hour display window at rollout.
- Existing host-profile approval, upload validation, file size/type checks, caption validation, active-story limits and moderation/removal tools remain in place.
- Rebuilt the signed-in member mobile dock from the visitor's explicit experience choice rather than the admin default.
- No explicit choice: Home, Escorts, Dating, Elite, Friends.
- Dating/Friends/Fun/Elite choice: Home first, selected experience second, followed by useful member links. Pricing/Premium is excluded from the mobile bottom bar.
- All experiences: Home, Escorts, Dating, Elite, Friends, Join.
- Added `?experience=` support so tapping an experience in the All dock persists the selected mode for the account/device.
- Corrected the Home icon to the existing `home` SVG sprite symbol; the previous `house` symbol was not present in the project's icon sprite.
- Added compact six-item mobile dock typography for narrow screens.

## Verification
- PHP lint run across the complete project.
- Navigation helper exercised for no-choice, Dating, Friends, Fun, Elite and All modes; resulting menu contents/order checked.
- Full ZIP integrity check performed after packaging.
- Live browser/device testing and production database migration execution have not been performed in this environment.
