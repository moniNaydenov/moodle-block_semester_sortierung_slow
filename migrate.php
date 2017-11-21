<?php

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login();

if (!is_siteadmin()) {
    redirect(new moodle_url('/my'), get_string('adminonly', 'badges'));
}



$res = block_semsort_migrate_user_preferences();
var_dump($res);