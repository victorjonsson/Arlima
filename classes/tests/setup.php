<?php
/**
 * Unit tests for arlima
 * -------------------------
 *
 * Before running the unit tests you will have to do the following:
 *
 * 1. Install the library using composer
 *      - Install composer (http://getcomposer.org/)
 *      - Navigate to the plugin directory of Arlima (../wp-content/plugins/arlima)
 *      - $ composer install --dev
 *
 * 2. Rename config-example.php located in this directory to config.php and
 * change the constants in that file so they
 *
 * @since 2.5
 * @package Arlima
 */


// Load error reporting
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// Setup server vars expected to exist by wordpress
$_SERVER['DOCUMENT_ROOT'] = getcwd();
$_SERVER['SERVER_PROTOCOL'] = '';
$_SERVER['HTTP_HOST'] = '';

// Load wp
require_once __DIR__.'/../../../../../wp-load.php';

// Load PHPUnit
require_once __DIR__.'/../../vendor/autoload.php';


// Setup arlima class loader if plugin not installed
if( !class_exists('Arlima_Plugin')) {
    require_once __DIR__.'/../../constants.php';
    require_once __DIR__.'/../Plugin.php';
    spl_autoload_register('Arlima_Plugin::classLoader');
}
