<?php
/**
 * Town cards on the landing page: each place can have its own picture, and the admin picks which places appear there.
 * Places that were already marked popular keep showing, so the page looks the same until the admin changes something.
 */
return function (): void {
    mig_add_column('locations', 'image_path', 'VARCHAR(255) NULL AFTER sort_order');
    mig_add_column('locations', 'show_landing', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER image_path');
    // Safe to run again after an interrupted attempt: the copy only happens while no place has been chosen yet.
    if ((int)val('SELECT COUNT(*) FROM locations WHERE show_landing = 1', [], 0) === 0) {
        q('UPDATE locations SET show_landing = is_popular');
    }
    mig_add_index('locations', 'idx_locations_landing', 'show_landing, is_active, sort_order');
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
