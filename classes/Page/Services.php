<?php


class Arlima_Page_Services extends Arlima_AbstractAdminPage {

    function scripts()
    {
        return array(
            'arlima_js' => array('url'=>ARLIMA_PLUGIN_URL . 'js/page-service.js', 'deps' => array() )
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
        return __('Web Service', 'arlima');
    }

    function getMenuName()
    {
        return __('Web Service', 'arlima');
    }

    function slug()
    {
        return 'arlima-services';
    }

    public function parentSlug()
    {
        return 'arlima-main';
    }
}
