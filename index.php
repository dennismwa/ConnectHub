<?php
/** Front controller: every page request is routed through here. */
require __DIR__ . '/includes/bootstrap.php';
if (!defined('CH_INSTALLER') && is_file(config_file())) {
    dispatch();
}
