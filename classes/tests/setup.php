<?php
/**
 * PHPUnit setup
 *
 * @since 2.5
 * @package Arlima
 */


// Noisy error reporting
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// Setup server vars expected to exist by wordpress
$_SERVER['DOCUMENT_ROOT'] = getcwd();
$_SERVER['SERVER_PROTOCOL'] = '';
$_SERVER['HTTP_HOST'] = '';
$_SERVER['REQUEST_METHOD'] = 'GET';
define('ARLIMA_UNIT_TEST', true);

// Load wp
require_once __DIR__ . '/../../../../../wp-load.php';

// Load PHPUnit
require_once __DIR__.'/../../vendor/autoload.php';

// Setup arlima class loader if plugin not installed
if( !class_exists('Arlima_Plugin') ) {
    require_once __DIR__ . '/../../constants.php';
    require_once __DIR__ . '/../Plugin.php';
    require_once __DIR__ . '/../../arlima.php';
}
