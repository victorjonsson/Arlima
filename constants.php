<?php

define('ARLIMA_PLUGIN_PATH', dirname(__FILE__));
define('ARLIMA_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('ARLIMA_DEV_MODE') or define('ARLIMA_DEV_MODE', false);
define('ARLIMA_FILE_VERSION', '3.0.alpha.22' .(ARLIMA_DEV_MODE ? time():''));