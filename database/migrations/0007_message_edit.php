<?php
/** Messages: remember when a member edited their own message, so the chat can show "edited". */
return function (): void {
    mig_add_column('messages', 'edited_at', 'DATETIME NULL AFTER is_deleted');
};
