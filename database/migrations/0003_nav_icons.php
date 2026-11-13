<?php
/** The Matches menu entry gets its own icon (two hearts) instead of the generic sparkles. */
return function (): void {
    q("UPDATE nav_items SET icon = 'matches' WHERE url = '/matches' AND icon IN ('sparkles', 'sparkle', '')");
    if (function_exists('cache_clear')) {
        cache_clear();
    }
};
