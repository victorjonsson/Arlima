<?php


/**
 * @since 2.7
 * @package Arlima
 */
class Arlima_Page_Settings extends Arlima_AbstractAdminPage {

    function scripts()
    {
        return array(
            'arlima-js' => array('url' =>ARLIMA_PLUGIN_URL . 'js/page-settings.js', 'deps'=>array('jquery'))
        );
    }

    function styleSheets()
    {
        return array(
            'arlima_css' => array('url'=>ARLIMA_PLUGIN_URL . 'css/admin.css', 'deps'=>array()),
        );
    }

    function getName()
    {
        return __('Settings', 'arlima');
    }

    function getMenuName()
    {
        return __('Settings', 'arlima');
    }

    function slug()
    {
        return 'arlima-settings';
    }

    public function parentSlug()
    {
        return 'arlima-main';
    }
}
