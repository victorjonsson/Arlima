<?php
/*
 * Declare classes with old names as deprecated
 */


/**
 * @deprecated
 */
class ArlimaWPLoop extends Arlima_WPLoop
{
    /**
     * @deprecated
     * @param null $template_path
     * @param null|string $template
     */
    function __construct($template_path = null, $template = Arlima_TemplatePathResolver::DEFAULT_TMPL)
    {
        Arlima_Plugin::warnAboutUseOfDeprecatedFunction('ArlimaWPLoop::__construct', 2.0, 'Arlima_WPLoop::__construct');
        parent::__construct($template_path, $template);
    }
}

/**
 * @deprecated
 */
class ArlimaListSimpleRenderer extends Arlima_SimpleListRenderer
{

    /**
     * @deprecated
     * @param Arlima_List|null $list
     */
    function __construct($list)
    {
        Arlima_Plugin::warnAboutUseOfDeprecatedFunction(
            'ArlimaListSimpleRenderer::__construct',
            2.0,
            'Arlima_SimpleListRenderer::__construct'
        );
        parent::__construct($list);
    }
}

/**
 * @deprecated
 */
class ArlimaList extends Arlima_List
{

    /**
     * @deprecated
     * @param null $exists
     * @param string $version
     * @param bool $is_imported
     */
    public function __construct($exists = null, $version = '', $is_imported = true)
    {
        Arlima_Plugin::warnAboutUseOfDeprecatedFunction('ArlimaList::__construct', 2.0, 'Arlima_List::__construct');
        parent::__construct($exists, $version, $is_imported);
    }

}

/**
 * @deprecated
 */
class ArlimaListTemplateRenderer extends Arlima_ListTemplateRenderer
{

    /**
     * @deprecated
     * @param Arlima_List|stdClass $list
     * @param null $template_path
     */
    public function __construct($list, $template_path = null)
    {
        Arlima_Plugin::warnAboutUseOfDeprecatedFunction(
            'ArlimaListTemplateRenderer::__construct',
            2.0,
            'Arlima_ListTemplateRenderer::__construct'
        );
        parent::__construct($list, $template_path);
    }
}