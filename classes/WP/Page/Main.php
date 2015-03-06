<?php


/**
 * @since 2.7
 * @package Arlima
 */
class Arlima_WP_Page_Main extends Arlima_WP_AbstractAdminPage {

    const PAGE_SLUG = 'arlima-main';

    function scripts()
    {
        // Add an almost astronomical amount of javascript
        $scripts = array(
            'qtip'              => array(ARLIMA_PLUGIN_URL . 'js/jquery/jquery.qtip.min.js', 'jquery'),
            'colourpicker'      => array(ARLIMA_PLUGIN_URL . 'js/jquery/colourpicker/jquery.colourpicker.js', 'jquery'),
            'fancybox'          => array(ARLIMA_PLUGIN_URL . 'js/jquery/fancybox/jquery.fancybox.js', 'jquery'),
            'pluploadfull'      => array(ARLIMA_PLUGIN_URL . 'js/misc/plupload.js', 'jquery'),
            'arlima-js'         => array(ARLIMA_PLUGIN_URL . 'js/arlima/arlima.js', 'mustache'),
            'arlima-main-js'    => array(ARLIMA_PLUGIN_URL . 'js/page-main.js', 'jquery'),
            'new-hotkeys'       => array(ARLIMA_PLUGIN_URL . 'js/jquery/jquery.hotkeys.js', 'jquery'),
            'mustache'          => array('//cdnjs.cloudflare.com/ajax/libs/mustache.js/0.7.2/mustache.min.js', 'jquery')
        );

        if( ARLIMA_DEV_MODE ) {
            $scripts = $this->addDevScripts($scripts);
        }

        if( Arlima_WP_Plugin::supportsImageEditor() ) {
            // these files could not be enqueue´d until wp version 3.5
            global $wp_version;
            if( (int)$wp_version < 4 ) {
                $wp_inc_url = includes_url() .'/js/jquery/ui/';
                $scripts['jquery-ui-effects'] = $wp_inc_url .'jquery.ui.effect.min.js';
                $scripts['jquery-ui-effects-shake'] = $wp_inc_url .'jquery.ui.effect-shake.min.js';
                $scripts['jquery-ui-effects-highlight'] = $wp_inc_url .'jquery.ui.effect-highlight.min.js';
            } else {
                $wp_inc_url = includes_url() .'js/jquery/ui/';
                $scripts['jquery-ui-effects'] = $wp_inc_url .'effect.min.js';
                $scripts['jquery-ui-effects-shake'] = $wp_inc_url .'effect-shake.min.js';
                $scripts['jquery-ui-effects-highlight'] = $wp_inc_url .'effect-highlight.min.js';
            }
        }

        $scripts_to_enqueue = array();
        foreach($scripts as $handle => $js) {
            if( is_array($js) )
                $scripts_to_enqueue[$handle] = array('url'=>$js[0], 'deps'=>array($js[1]));
            else
                $scripts_to_enqueue[$handle] = array('url'=>$js, 'deps'=>array('jquery'));
        }

        add_action('admin_head', array($this, 'echoFormatColorStyleTag'));

        return $scripts_to_enqueue;
    }

    private function addDevScripts($scripts)
    {
        // The order of these scripts must be the same in package.json
        $scripts['arlima-js'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaUtils.js';
        $scripts['arlima-js-backend'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaBackend.js';
        $scripts['arlima-js-settings-menu'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaArticleSettingsMenu.js';
        $scripts['arlima-js-form-builder'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaFormBuilder.js';
        $scripts['arlima-js-list'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaList.js';
        $scripts['arlima-js-article'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaArticle.js';
        $scripts['arlima-js-preview'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaArticlePreview.js';
        $scripts['arlima-js-form'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaArticleForm.js';
        $scripts['arlima-js-connection'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaArticleConnection.js';
        $scripts['arlima-js-search'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaPostSearch.js';
        $scripts['arlima-js-loader'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaListLoader.js';
        $scripts['arlima-js-blocker'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaFormBlocker.js';
        $scripts['arlima-js-template-loader'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaTemplateLoader.js';
        $scripts['arlima-js-version-manager'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaVersionManager.js';
        $scripts['arlima-js-container'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaListContainer.js';
        $scripts['arlima-js-short-cuts'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaKeyBoardShortCuts.js';
        $scripts['arlima-js-list-preview'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaListPreview.js';
        $scripts['arlima-js-nested-sortable'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaNestedSortable.js';
        $scripts['arlima-js-image'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaImageManager.js';
        $scripts['arlima-js-image-upload'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaImageUploader.js';
        $scripts['arlima-js-file-includes'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaFileIncludes.js';
        $scripts['arlima-js-scissors'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaScissors.js';
        $scripts['arlima-article-templates'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaArticlePreset.js';
        $scripts['arlima-scheduled-interval'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaScheduledIntervalPicker.js';
        $scripts['arlima-js-tinymce'] = ARLIMA_PLUGIN_URL . 'js/arlima/dev/ArlimaTinyMCE.js';
        return $scripts;
    }

    function styleSheets()
    {
        wp_register_style('jquery_ui_css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css');
        $styles = array(
            'font-awesome' => array('url' => '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css', 'deps'=>array()),
            'arlima_css' => array('url'=>ARLIMA_PLUGIN_URL . 'css/admin.css', 'deps'=>array()),
            'jquery_ui_css' => array('url'=>'//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css', 'deps'=>array()),
            'colourpicker_css' => array('url'=>ARLIMA_PLUGIN_URL . 'js/jquery/colourpicker/colourpicker.css', 'deps'=>array()),
            'fancy_css' => array('url'=>ARLIMA_PLUGIN_URL . 'js/jquery/fancybox/jquery.fancybox.css', 'deps'=>array()),
        );
        return $styles;
    }

    function enqueueStyles()
    {
        parent::enqueueStyles();
        $this->plugin->addTemplateCSS();
    }

    public function echoFormatColorStyleTag()
    {
        ?>
        <style>
            <?php foreach(Arlima_ArticleFormat::getAll() as $format) {
                    if( empty($format['ui_color']) )
                        continue;
                    ?>
                .article.<?php echo $format['class'] ?> {
                    border-left: <?php echo $format['ui_color']; ?> solid 4px;
                }
            <?php } ?>
        </style>
        <?php
    }

    function enqueueScripts()
    {
        // Enqueue scissors scripts if installed
        if ( Arlima_WP_Plugin::isScissorsInstalled() ) {

            $scissors_url = WP_PLUGIN_URL . '/scissors-continued';
            wp_enqueue_script('scissors_crop', $scissors_url . '/js/jquery.Jcrop.js', array('jquery'));
            wp_enqueue_script('scissors_js', $scissors_url . '/js/scissors.js');

            $scissors_js_obj = array('ajaxUrl' => admin_url('admin-ajax.php'));
            foreach (array('large', 'medium', 'thumbnail') as $size) {
                $width = intval(get_option("{$size}_size_w"));
                $height = intval(get_option("{$size}_size_h"));
                $ratio = max(1, $width) / max(1, $height);
                if ( !get_option("{$size}_crop") ) {
                    $ratio = 0;
                }

                $scissors_js_obj[$size . 'AspectRatio'] = $ratio;
            }

            echo '<script>var scissors = ' . json_encode($scissors_js_obj) . ';</script>';
        }

        // Add our template css to tinyMCE
        if ( !function_exists('tdav_css') ) {
            function tdav_css($wp)
            {
                $arlima_plugin = new Arlima_WP_Plugin();
                $styles = $arlima_plugin->getTemplateStylesheets();
                if( empty($styles) ) {
                    $wp .= ',' . Arlima_WP_Plugin::getTemplateCSS();
                } else {
                    $wp .= ','.$styles[0];
                }
                return $wp;
            }
        }
        add_filter('mce_css', 'tdav_css');

        // We use our own version of hotkeys
        wp_deregister_script('jquery-hotkeys');

        // Enqueue all that jquery magic
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-resizable');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-mouse');
        wp_enqueue_script('jquery-ui-widget');
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('jquery-ui-datepicker');

        wp_enqueue_script('media-upload');

        parent::enqueueScripts();

        // Add tinymce filters
        add_filter('mce_external_plugins', array($this, 'mcePlugin'));
        add_filter('mce_buttons', array($this, 'mceButtons1'), 20);
        add_filter('mce_buttons_2', array($this, 'mceButtons2'), 20);

        // template loadin js
        add_action('admin_footer', array($this, 'addTemplateLoadingJS'));
    }

    /**
     * Will output javascript that loads all jQuery templates from backend
     */
    function addTemplateLoadingJS()
    {
        $tmpl_resolver = new Arlima_TemplatePathResolver();
        $style_sheets = $this->plugin->getTemplateStylesheets();
        ?>
        <script>
            ArlimaArticle.defaultData = <?php echo json_encode(Arlima_ListVersionRepository::createArticle()) ?>;
            ArlimaUtils.serverTime = <?php echo Arlima_Utils::timeStamp() * 1000; ?>;
            (function(win) {
                var tmpls = [];
                <?php foreach ($tmpl_resolver->getTemplateFiles()as $tmpl): ?>
                tmpls.push('<?php echo $tmpl['url']; ?>?v=<?php echo ARLIMA_FILE_VERSION ?>');
                <?php endforeach; ?>
                win.ArlimaTemplateLoader.load(tmpls);
                <?php if ( !empty($_GET['open_list']) ): ?>
                win.loadArlimaListOnLoad = <?php echo intval($_GET['open_list']); ?>;
                <?php endif; ?>
                <?php if( is_array($style_sheets) && !empty($style_sheets) ): ?>
                win.arlimaTemplateStylesheets = [];
                <?php foreach($style_sheets as $style): ?>
                win.arlimaTemplateStylesheets.push('<?php echo $style ?>');
                <?php endforeach; ?>
                <?php endif; ?>
            })(window);
        </script>
    <?php
    }


    /**
     * @param $buttons
     * @return mixed
     */
    public function mceButtons1($buttons)
    {
        unset($buttons[array_search('wp_more', $buttons)]);
        unset($buttons[array_search('fullscreen', $buttons)]);
        unset($buttons[array_search('vkpreamble', $buttons)]);
        unset($buttons[array_search('vksubheading', $buttons)]);
        array_unshift($buttons, "vkentrywords");
        return $buttons;
    }

    /**
     * @param $buttons
     * @return mixed
     */
    public function mceButtons2($buttons)
    {
        unset($buttons[array_search('outdent', $buttons)]);
        unset($buttons[array_search('indent', $buttons)]);
        unset($buttons[array_search('wp_help', $buttons)]);
        return $buttons;
    }

    /**
     * @param $plugin_array
     * @return mixed
     */
    public function mcePlugin($plugin_array)
    {
        $plugin_array['vkentrywords'] = ARLIMA_PLUGIN_URL . 'js/tinymce/plugins/vkentrywords/editor_plugin.js';
        return $plugin_array;
    }

    function getName()
    {
        return __('Article List Manager', 'arlima');
    }

    function getMenuName()
    {
        return __('Manage lists', 'arlima');
    }

    function slug()
    {
        return self::PAGE_SLUG;
    }

    public function parentSlug()
    {
        return false;
    }

    function icon()
    {
        return ARLIMA_PLUGIN_URL . '/images/arlima-icon.png';
    }

}
