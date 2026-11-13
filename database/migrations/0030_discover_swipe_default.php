<?php
/**
 * Discover opens on the swipe cards for everyone. A view a member picked before this update (grid or list) is kept in
 * their saved preferences but is no longer applied automatically: from now on a view only sticks once the member
 * chooses it again (one tap on Swipe, Grid or List, or in Settings > Discovery). The column records when they chose.
 *
 * Nothing saved is changed or removed. Safe to run twice.
 */
return function (): void {
    mig_add_column('discovery_preferences', 'view_set_at', 'DATETIME NULL DEFAULT NULL AFTER preferred_view');
};
