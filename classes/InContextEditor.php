<?php

/**
 * Class responsible of applying "in-context-editing" features to article lists
 *
 * @package Arlima
 * @since 3.0
 */
class Arlima_InContextEditor {

    /**
     * @var Arlima_Plugin
     */
    private $plugin;

    /**
     * @var bool
     */
    private static $footer_js_added = false;

    /**
     * @param Arlima_Plugin $plugin
     */
    function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param array $filter_suffixes
     */
    function apply($filter_suffixes = array('', 'widget'))
    {
        foreach ($filter_suffixes as $suffix) {
            $this->addActionsAndFilters($suffix);
        }
    }

    /**
     * @param $suffix
     */
    private function addActionsAndFilters($suffix)
    {
        $filter_name = 'arlima_template_object';
        $action_suffix = '';
        if ( !empty($suffix) ) {
            $filter_name .= '-' . $suffix;
            $action_suffix = '-' . $suffix;
        }

        add_filter('arlima_list_content' . $action_suffix, array($this, 'listContentContainer'), 10, 2);
        add_action('arlima_list_begin' . $action_suffix, array($this, 'listBegins'), 10, 2);
        add_action('arlima_list_end' . $action_suffix, array($this, 'listEnds'), 10, 2);

        add_filter($filter_name, array($this, 'filterTemplateObject'), 10, 3);
    }

    /**
     * @param string $content
     * @param Arlima_AbstractListRenderingManager $renderer
     * @return string
     */
    function listContentContainer($content, $renderer)
    {
        if ( !empty($content) ) {
            $content = $this->generateOpeningContainerDiv($renderer->getList()) . $content . '</div>';
        }

        return $content;
    }

    /**
     * @param Arlima_AbstractListRenderingManager $renderer
     * @param array $args
     */
    function listBegins($renderer, $args)
    {
        if ( !empty($args['echo']) ) {
            echo $this->generateOpeningContainerDiv($renderer->getList());
        }
    }

    /**
     * @param Arlima_AbstractListRenderingManager $renderer
     * @param array $args
     */
    function listEnds($renderer, $args)
    {
        if ( !empty($args['echo']) ) {
            echo '</div>';
        }
    }

    /**
     * @param Arlima_List $list
     * @return string
     */
    private function generateOpeningContainerDiv($list)
    {
        return '<div class="arlima-editor-container" data-list="' .
                    $list->id() . '" data-version="' . $list->getVersionAttribute('id') . '">';
    }

    /**
     * @param array $obj
     * @param array $article
     * @param Arlima_List $list
     * @return mixed
     */
    function filterTemplateObject($obj, $article, $list)
    {
        if ( !self::$footer_js_added ) {
            self::$footer_js_added = true;
            add_action('wp_footer', array($this, 'wpFooter'));
        }

        $obj['article']['html_title'] = Arlima_List::getTitleHtml($article, $list->getOptions(), array('arlima-ice-title'));
        $obj['article']['html_text'] = '<div class="arlima-ice-content">' . $obj['article']['html_text'] . '</div>';
        $obj['container']['class'] .= ' arlima-editable-article article-'.$article['id'];

        return $obj;
    }

    /**
     * Function outputting the necessary javascript that enables
     * the editing features in browser
     */
    function wpFooter()
    {
        wp_enqueue_script('jquery-editable', ARLIMA_PLUGIN_URL.'js/jquery/jquery.editable.min.js', array('jquery'), ARLIMA_FILE_VERSION, true);
        wp_enqueue_script('arlima-editor', ARLIMA_PLUGIN_URL.'js/in-context-editor.js', array('jquery-editable'), ARLIMA_FILE_VERSION, true);
        $this->plugin->addAdminJavascriptVars('arlima-editor');
    }
}