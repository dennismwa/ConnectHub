<?php
/** Photo limits: a site-wide ceiling, a daily upload cap, and an optional limit set for one member by an admin. */
return function (): void {
    mig_add_column('user_profiles', 'photo_limit_override', 'TINYINT UNSIGNED NULL');
    mig_setting_default('photo_cap_all', '30');
    mig_setting_default('photo_uploads_per_day', '0');
};
