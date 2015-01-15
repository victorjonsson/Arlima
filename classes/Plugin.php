<?php


/**
 * Utility class for the Arlima plugin.
 *
 * @deprecated
 * @see Arlima_WP_Plugin
 *
 * @package Arlima
 * @since 1.0
 */
class Arlima_Plugin extends Arlima_WP_Plugin {

    public function __construct()
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_WP_Plugin::__construct');
        parent::__construct();
    }

}