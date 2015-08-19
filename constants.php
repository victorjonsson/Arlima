<?php

// Paths
define('ARLIMA_PLUGIN_PATH', dirname(__FILE__));
define('ARLIMA_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('ARLIMA_CLASS_PATH')
    or define('ARLIMA_CLASS_PATH', ARLIMA_PLUGIN_PATH.'/classes');


// dev-mode will load the uncompressed js files and compile less file in the browser
defined('ARLIMA_DEV_MODE')
    or define('ARLIMA_DEV_MODE', false);

// Make it possible to use pre-compiled less even though in dev-mode
defined('ARLIMA_COMPILE_LESS_IN_BROWSER')
    or define('ARLIMA_COMPILE_LESS_IN_BROWSER', ARLIMA_DEV_MODE);

// Plugin version (only edit this via grunt!)
define('ARLIMA_PLUGIN_VERSION', '3.1.beta.64');
define('ARLIMA_FILE_VERSION', ARLIMA_PLUGIN_VERSION .(ARLIMA_DEV_MODE ? '__'.time():''));

// Which type of tag to use for images in Arlima RSS feeds
defined('ARLIMA_RSS_IMG_TAG')
    or define('ARLIMA_RSS_IMG_TAG', 'enclosure');

// Whether or not you should be able to set templates on section dividers
defined('ARLIMA_SUPPORT_SECTION_DIV_TEMPLATES')
    or define('ARLIMA_SUPPORT_SECTION_DIV_TEMPLATES', false);

// Whether or not the list manager in wp-admin should send js errors to the
// server log via ajax
defined('ARLIMA_SEND_JS_ERROR_TO_LOG')
    or define('ARLIMA_SEND_JS_ERROR_TO_LOG', false);

// This is the time limit in seconds between automatic reloading of the lists in the list manager
defined('ARLIMA_LIST_RELOAD_TIME')
    or define('ARLIMA_LIST_RELOAD_TIME', 180); // Seconds

// We have battled the problems with timestamps many times before. Use this
// variable to adjust the unix timestamp
defined('ARLIMA_TIME_ADJUST')
    or define('ARLIMA_TIME_ADJUST', 0); // -3600 to put timestamp back one hour

// The publish date of articles connected to wordpress posts sometimes
// gets totally screwed up. Arlima converts the publish date of posts to unix timestamps
// but which property to use out of 'post_date' and 'post_date_gmt' seems to differ on
// different installations. For what reason is still unknown. Any how, if the publish date
// of your arlima articles gets incorrect you can try to change the value of this
// contant to 'post_date_gmt'
define('ARLIMA_POST_DATE_PROP', 'post_date');

// The facade class in front of underlying system
define('ARLIMA_CMS_FACADE', 'Arlima_WP_Facade');
