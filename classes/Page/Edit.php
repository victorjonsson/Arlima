<?php


/**
 * @since 2.7
 * @package Arlima
 */
class Arlima_Page_Edit extends Arlima_AbstractAdminPage {

    function scripts()
    {
        return array(
            'arlima-js' => array('url' =>ARLIMA_PLUGIN_URL . 'js/page-edit.js', 'deps'=>array('jquery')),
            'arlima_js_jquery' => array('url'=>ARLIMA_PLUGIN_URL . 'js/arlima/arlima-jquery-plugins.js', 'deps'=>array('jquery'))
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
        return __('Edit lists', 'arlima');
    }

    function getMenuName()
    {
        return __('Edit lists', 'arlima');
    }

    function slug()
    {
        return 'arlima-edit';
    }

    public function parentSlug()
    {
        return 'arlima-main';
    }

    public function capability() {
        return 'manage_options';
    }
}
