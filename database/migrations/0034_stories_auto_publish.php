<?php
/** Publish new stories immediately and release stories already waiting for approval. */
return function (): void {
    if (mig_has_table('agent_stories')) {
        q("UPDATE agent_stories
            SET status = 'active', published_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 1 DAY),
                moderation_note = NULL
            WHERE status = 'pending'");
    }
    if (function_exists('setting_set')) {
        setting_set('agent_story_require_approval', '0', 0);
    }
};
