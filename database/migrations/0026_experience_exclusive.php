<?php
/**
 * "Show only what they chose": a member who picked Dating sees only the people side, Fun only the Hosts, Elite only
 * its own members, on the home page and on Discover. Also the number of people the full-page Fun and Elite grids hold.
 * Switch the first one off under Admin > Visitor choices to go back to every block for everyone.
 * Nothing is removed or rewritten. Safe to run twice: each setting is only added when it is missing.
 */
return function (): void {
    mig_setting_default('choice_exclusive', '1');
    mig_setting_default('discover_focus_count', '24');
};
