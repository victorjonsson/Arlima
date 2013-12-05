<?php


/**
 * @since 2.7
 * @package Arlima
 */
class Arlima_Page_Main extends Arlima_AbstractAdminPage {

    function scripts()
    {
        // Add an almost astronomical amount of javascript
        $scripts = array(
            'qtip'              => ARLIMA_PLUGIN_URL . 'js/jquery/jquery.qtip.min.js',
            'colourpicker'      => ARLIMA_PLUGIN_URL . 'js/jquery/colourpicker/jquery.colourpicker.js',
            'fancybox'          => ARLIMA_PLUGIN_URL . 'js/jquery/fancybox/jquery.fancybox.js',
            'ui-nestedsortable' => ARLIMA_PLUGIN_URL . 'js/jquery/jquery.ui.nestedSortable.js',
            'pluploadfull'      => ARLIMA_PLUGIN_URL . 'js/misc/plupload.full.js',
            'jquery-tmpl'       => ARLIMA_PLUGIN_URL . 'js/jquery/jquery.tmpl.min.js',
            'arlima-jquery'     => ARLIMA_PLUGIN_URL . 'js/arlima/arlima-jquery-plugins.js',
            'arlima-tmpl'       => ARLIMA_PLUGIN_URL . 'js/arlima/template-loader.js',
            'arlima-js'         => ARLIMA_PLUGIN_URL . 'js/arlima/arlima.js',
            'arlima-plupload'   => ARLIMA_PLUGIN_URL . 'js/arlima/plupload-init.js',
            'arlima-main-js'    => ARLIMA_PLUGIN_URL . 'js/page-main.js',
            'new-hotkeys'       => ARLIMA_PLUGIN_URL . 'js/jquery/jquery.hotkeys.js'
        );

        if( Arlima_Plugin::supportsImageEditor() ) {
            // these files could not be enqueue´d until wp version 3.5
            $wp_inc_url = includes_url() .'/js/jquery/ui/';
            $scripts['jquery-ui-effects'] = $wp_inc_url .'jquery.ui.effect.min.js';
            $scripts['jquery-ui-effects-shake'] = $wp_inc_url .'jquery.ui.effect-shake.min.js';
            $scripts['jquery-ui-effects-highlight'] = $wp_inc_url .'jquery.ui.effect-highlight.min.js';
        }

        $scripts_to_enqueue = array();
        foreach($scripts as $handle => $js) {
            $dependency = array('jquery');
            if( $handle == 'ui-nestedsortable' )
                $dependency = array('jquery-ui-sortable');

            $scripts_to_enqueue[$handle] = array('url'=>$js, 'deps'=>$dependency);
        }

        return $scripts_to_enqueue;
    }

    function styleSheets()
    {
        wp_register_style('jquery_ui_css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css');
        return array(
            'arlima_css' => array('url'=>ARLIMA_PLUGIN_URL . 'css/admin.css', 'deps'=>array()),
            'jquery_ui_css' => array('url'=>'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css', 'deps'=>array()),
            'colourpicker_css' => array('url'=>ARLIMA_PLUGIN_URL . 'js/jquery/colourpicker/colourpicker.css', 'deps'=>array()),
            'fancy_css' => array('url'=>ARLIMA_PLUGIN_URL . 'js/jquery/fancybox/jquery.fancybox.css', 'deps'=>array()),
        );
    }

    function enqueueStyles()
    {
        parent::enqueueStyles();
        $this->plugin->addTemplateCSS();
    }

    function enqueueScripts()
    {
        // Enqueue scissors scripts if installed
        if ( Arlima_Plugin::isScissorsInstalled() ) {

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
                $arlima_plugin = new Arlima_Plugin();
                $styles = $arlima_plugin->getTemplateStylesheets();
                if( empty($styles) ) {
                    $wp .= ',' . Arlima_Plugin::getTemplateCSS();
                } else {
                    $wp .= ','.$styles[0];
                }
                return $wp;
            }
        }
        add_filter('mce_css', 'tdav_css');

        // Deregister scripts we need to override
        wp_deregister_script('jquery-hotkeys');
        wp_deregister_script('jquery-ui-core');
        wp_deregister_script('jquery-ui-draggable');
        wp_deregister_script('jquery-ui-droppable');
        wp_deregister_script('jquery-ui-mouse');
        wp_deregister_script('jquery-ui-widget');
        wp_deregister_script('jquery-ui-sortable');
        
        wp_register_script('jquery-ui-core', ARLIMA_PLUGIN_URL . 'js/jquery/jquery.ui.core.min-1.92.js', 12, true);
        wp_register_script('jquery-ui-draggable', ARLIMA_PLUGIN_URL . 'js/jquery/jquery.ui.draggable.min-1.92.js', 12, true);
		wp_register_script('jquery-ui-droppable', ARLIMA_PLUGIN_URL . 'js/jquery/jquery.ui.droppable.min-1.92.js', 12, true);
        wp_register_script('jquery-ui-mouse', ARLIMA_PLUGIN_URL . 'js/jquery/jquery.ui.mouse.min-1.92.js', 12, true);        
        wp_register_script('jquery-ui-widget', ARLIMA_PLUGIN_URL . 'js/jquery/jquery.ui.widget.min-1.92.js', 12, true);

        // Replace jquery.ui.sortable with old version of the same function
        wp_register_script('jquery-ui-sortable', ARLIMA_PLUGIN_URL . 'js/jquery/jquery.ui.sortable-1.82.js', array('jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse'), 12, true);
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-slider');
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
            (function(win) {
                var tmpls = [];
                <?php foreach ($tmpl_resolver->getTemplateFiles()as $tmpl): ?>
                    tmpls.push('<?php echo $tmpl['url']; ?>?v=11');
                <?php endforeach; ?>
                win.ArlimaTemplateLoader.load(tmpls);
                <?php if ( !empty($_GET['open_list']) ): ?>
                    win.loadArlimListOnLoad = <?php echo intval($_GET['open_list']); ?>;
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
        return 'arlima-main';
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
