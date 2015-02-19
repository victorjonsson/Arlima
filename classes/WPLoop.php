<?php

/**
 * @deprecated
 * @see Arlima_CMSLoop
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_WPLoop extends Arlima_CMSLoop {

    public function __construct($in) {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_CMSLoop::__construct');
        parent::__construct($in);
    }
}
