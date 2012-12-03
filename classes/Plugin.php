<?php


/**
 * Utility class for the Arlima plugin.
 * @package Arlima
 * @since 1.0
 */
class Arlima_Plugin
{
    const VERSION = 2.5;
    const EXPORT_FEED_NAME = 'arlima-export';
    const STATIC_VERSION = '15.312';

    private static $is_scissors_installed = null;
    private static $is_wp_related_post_installed = null;
    private static $has_loaded_textdomain = false;

    /**
     * Actions added in the theme
     */
    function initThemeActions()
    {
        add_action('init', array($this, 'commonInitHook'));
        add_action('template_redirect', array($this, 'themeInitHook'));
    }

    /**
     * Actions added in wp-admin
     */
    function initAdminActions()
    {
        add_action('init', array($this, 'commonInitHook'));
        add_action('init', array($this, 'adminInitHook'));
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('add_meta_boxes', array($this, 'addMetaBox'));
    }

    /**
     * function called on init in the theme
     */
    function themeInitHook()
    {
        $this->addTemplateCSS();
        add_shortcode('arlima', array($this, 'arlimaListShortCode'));
        if ( is_page() ) {
            add_action('the_content', array($this, 'displayArlimaList'));
        }
    }

    /**
     */
    function displayArlimaList()
    {
        global $post;
        $list_id = get_post_meta($post->ID, '_arlima_list', true);
        if ( $list_id ) {
            $version = arlima_requesting_preview($list_id) ? 'preview' : '';
            $factory = new Arlima_ListFactory();
            $list = $factory->loadList($list_id, $version);
            if ( $list->exists() ) {
                $list_data = get_post_meta($post->ID, '_arlima_list_data', true);
                arlima_render_list($list, $list_data['width'], $list_data['offset'], $list_data['limit']);
            }
        }
    }

    /**
     * Short code for arlima
     * @param array $atts
     * @return string
     */
    public function arlimaListShortCode($atts)
    {
        $factory = new Arlima_ListFactory();

        $atts = shortcode_atts(
            array(
                'offset' => 0,
                'limit' => 0,
                'width' => 560,
                'list' => null
            ),
            $atts
        );

        $error_html = '<div><p style="background:red; color:white; font-weight: bold">%s</p></div>';

        if ( empty($atts['list']) ) {
            return sprintf($error_html, 'Short code [arlima] is missing argument &quot;list&quot;');
        }

        if( is_numeric($atts['list']) )
            $list = $factory->loadList($atts['list']);
        else
            $list = $factory->loadListBySlug($atts['list']);


        if ( !$list->exists() ) {
            return sprintf(
                $error_html,
                'Short code [arlima] is referring to a list that does not exist (' . $atts['list'] . ')'
            );
        }

        return arlima_render_list($list, $atts['width'], $atts['offset'], $atts['limit'], $atts['limit'], false);
    }

    /**
     * If we get a lot of calls to this function we might as well always make a call
     * to load_plugin_textdomain on init, and not only in wp-admin
     *
     * @see Arlima_Plugin::loadTextDomain()
     * @static
     * @return bool
     */
    public static function loadTextDomain()
    {
        if ( !self::$has_loaded_textdomain ) {
            self::$has_loaded_textdomain = true;
            load_plugin_textdomain('arlima', false, 'arlima/lang/');
        }
    }

    /**
     */
    public function adminBar()
    {
        /* @var WP_Admin_Bar $wp_admin_bar */
        global $wp_admin_bar;
        if ( is_admin_bar_showing() ) {
            Arlima_Plugin::loadTextDomain();
            $admin_url = admin_url('admin.php?page=arlima');
            $wp_admin_bar->add_menu(
                array(
                    'id' => 'arlima',
                    'parent' => '',
                    'title' => __('Article lists', 'arlima'),
                    'href' => $admin_url
                )
            );
            $factory = new Arlima_ListFactory();
            foreach ($factory->loadListSlugs() as $list_data) {
                $wp_admin_bar->add_menu(
                    array(
                        'id' => 'arlima-' . $list_data->id,
                        'parent' => 'arlima',
                        'title' => $list_data->title,
                        'href' => $admin_url .'&amp;open_list='. $list_data->id
                    )
                );
            }
        }
    }

    /**
     * Init hook taking place in both wp-admin and theme
     */
    function commonInitHook()
    {
        add_action('wp_before_admin_bar_render', array($this, 'adminBar'));

        $this->addExportFeeds();

        self::$is_scissors_installed = function_exists('scissors_create_image');
        self::$is_wp_related_post_installed = function_exists('MRP_get_related_posts');

        // Add some formats
        arlima_register_format('format-inverted', 'Inverted', array('giant'));
        arlima_register_format('format-serif', 'Serif');
    }

    /**
     * Function called on init in wp-admin
     */
    function adminInitHook()
    {
        self::update();
        self::loadTextDomain();
        add_action('save_post', array($this, 'savePageMetaBox'));
    }

    /**
     * Adds arlima export feed to Wordpress
     */
    function addExportFeeds()
    {
        add_feed(self::EXPORT_FEED_NAME, array($this, 'loadExportFeed'));
    }

    /**
     * Install procedure for this plugin
     *  - Adds database tables
     *  - Adds version number in db
     *  - Adds arlima export feed and flushed wp_rewrite
     * @static
     */
    public static function install()
    {
        $factory = new Arlima_ListFactory();
        $factory->install();
        global $wp_rewrite;
        $plugin = new self();
        $plugin->addExportFeeds();
        $wp_rewrite->flush_rules();
        $plugin = new self();
        $settings = $plugin->loadSettings();
        $settings['install_version'] = self::VERSION;
        $plugin->saveSettings($settings);
    }

    /**
     * Uninstall procedure for this plugin
     *  - Removes plugin settings
     *  - Removes database tables
     * @static
     */
    public static function uninstall()
    {
        $factory = new Arlima_ListFactory();
        $factory->uninstall();
        delete_option('arlima_plugin_settings');
    }

    /**
     * Deactivation procedure for this plugin
     *  - Removes feed from wp_rewrite
     * @static
     */
    public static function deactivate()
    {
        global $wp_rewrite;
        $feed_index = array_search('arlima', $wp_rewrite->feeds);
        if ( $feed_index ) {
            array_splice($wp_rewrite->feeds, $feed_index, 1);
            $wp_rewrite->flush_rules();
        }
    }

    /**
     * Update procedure for this plugin. Since wordpress is lacking this feature we
     * should call this function on a regular basis.
     */
    public static function update()
    {
        $plugin = new self();
        $settings = $plugin->loadSettings();
        $current_version = isset($settings['install_version']) ? $settings['install_version'] : 0;

        // Time for an update
        if ( $current_version != self::VERSION ) {

            // Update to v 2.0
            if ( $current_version < 2 ) {
                global $wp_rewrite;
                $plugin->addExportFeeds();
                $wp_rewrite->flush_rules();
            }

            // Update to version 2.2
            if ( $current_version < 2.2 ) {
                Arlima_ListFactory::databaseUpdates($current_version);
            }

            // Update to version 2.4
            if ( $current_version < self::VERSION ) {

                Arlima_ListFactory::databaseUpdates($current_version);

                $pages = get_pages(
                    array(
                        'meta_key' => '_wp_page_template',
                        'meta_value' => 'page-arlima.php',
                        'hierarchical' => 0
                    )
                );

                // Include arlima template to get width
                $page_template = get_stylesheet_directory() . '/page-arlima.php';
                if ( file_exists($page_template) ) {
                    include_once $page_template;
                }

                foreach ($pages as $page) {
                    $arlima_slug = get_post_meta($page->ID, 'arlima', true);
                    if ( $arlima_slug ) {
                        update_post_meta($page->ID, '_arlima_list', $arlima_slug);
                        update_post_meta(
                            $page->ID,
                            '_arlima_list_data',
                            array(
                                'width' => defined('TMPL_ARTICLE_WIDTH') ? TMPL_ARTICLE_WIDTH : 468,
                                'offset' => 0,
                                'limit' => -1
                            )
                        );
                    }
                }
            }

            $settings['install_version'] = self::VERSION;
            $plugin->saveSettings($settings);
        }
    }

    /**
     * Will try to export arlima list from currently visited page
     */
    function loadExportFeed()
    {
        global $wp_query;
        $format = isset($_REQUEST['format']) ? $_REQUEST['format'] : Arlima_ExportManager::DEFAULT_FORMAT;
        $page_slug = !empty($wp_query->query_vars['pagename']) ? $wp_query->query_vars['pagename'] : '';
        $export_manager = new Arlima_ExportManager($this);
        $export_manager->export($page_slug, $format);
        die;
    }

    /**
     * Settings of any kind related to this plugin
     * @return array
     */
    function loadSettings()
    {
        return get_option('arlima_plugin_settings', array());
    }

    /**
     * @param array $setting
     */
    function saveSettings($setting)
    {
        update_option('arlima_plugin_settings', $setting);
    }

    /**
     * Adds meta box to post edit/create page
     */
    function addMetaBox()
    {
        add_meta_box(
            'arlima-meta-box',
            'Arlima',
            array($this, 'postMetaBox'),
            'post',
            'side',
            'low'
        );

        add_meta_box(
            'arlima-page-meta-box',
            'Arlima',
            array($this, 'pageMetaBox'),
            'page',
            'side',
            'low'
        );
    }

    /**
     * Content of meta box used to send a wordpress post immediately from post
     * edit page in wp-admin to an arlima list
     */
    function postMetaBox()
    {
        global $post;

        wp_nonce_field(plugin_basename(__FILE__), 'vkuc_nonce');

        if ( $post->post_status == 'publish' ) {
            $factory = new Arlima_ListFactory();
            ?>
            <input type="hidden" name="arlima-postid" id="arlima-postid" value="<?php echo $post->ID; ?>"/>
            <select name="arlima-listid" id="arlima-listid">
                <option value=""><?php _e('Choose article list', 'arlima') ?></option>
                <?php foreach ($factory->loadListSlugs() as $arlima_list): ?>
                    <option value="<?php echo $arlima_list->id; ?>">
                        <?php echo $arlima_list->title; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input id="arlima-send-to-list-btn"
                   class="button-secondary action"
                   type="button"
                   name="arlima-send-to-list-btn"
                   value="Skicka" />
            <img src="<?php echo   ARLIMA_PLUGIN_URL . '/images/ajax-loader-trans.gif'; ?>"
                class="ajax-loader"
                style="display:none;" />
            <?php
        } else {
            echo '<em>' . __('Post needs to be published', 'arlima') . '</em>';
        }
    }

    function pageMetaBox()
    {
        global $post;
        wp_nonce_field(__FILE__, 'arlima_nonce');
        $default_data = array('list' => '', 'width' => 560, 'offset' => 0, 'limit' => 0);
        $list_data = $default_data;
        if ( $post ) {
            $list_id = get_post_meta($post->ID, '_arlima_list', true);
            $list_data = get_post_meta($post->ID, '_arlima_list_data', true);
            if ( $list_id ) {
                $list_data = array_merge($default_data, $list_data, array('list' => $list_id));
            } else {
                $list_data = $default_data;
            }
        }
        $factory = new Arlima_ListFactory();

        ?>
        <div id="arlima-list-settings">
            <table cellpadding="5">
                <tr>
                    <td><strong>List:</strong></td>
                    <td>
                        <select name="arlima_list" id="arlima-lists">
                            <option value="">- - No list - -</option>
                            <?php foreach ($factory->loadListSlugs() as $arlima_list): ?>
                                <option value="<?php echo $arlima_list->id; ?>"<?php
                                // may be either slug or id
                                if ( in_array($list_data['list'], (array)$arlima_list) ){
                                    echo ' selected="selected"';
                                }
                                ?>><?php echo $arlima_list->title; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="" style="display: none" target="_blank" id="arlima-edit">[edit]</a>
                    </td>
                </tr>
                <tr class="arlima-opt">
                    <td><strong>Width:</strong></td>
                    <td><input type="number" name="arlima_width" value="<?php echo $list_data['width'] ?>" style="width:50px"/> px
                    </td>
                </tr>
                <tr>
                    <td><strong>Offset:</strong></td>
                    <td><input type="number" name="arlima_offset" value="<?php echo $list_data['offset'] ?>" style="width:50px"/></td>
                </tr>
                <tr>
                    <td><strong>Limit:</strong></td>
                    <td><input type="number" name="arlima_limit" value="<?php echo $list_data['limit'] ?>" style="width:50px"/></td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     */
    public function savePageMetaBox($post_id)
    {
        if ( !defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE ) {

            if ( isset($_POST['arlima_nonce']) && wp_verify_nonce($_POST['arlima_nonce'], __FILE__) ) {
                if ( empty($_POST['arlima_list']) ) {
                    delete_post_meta($post_id, '_arlima_list');
                    delete_post_meta($post_id, '_arlima_list_data');
                } else {
                    update_post_meta($post_id, '_arlima_list', $_POST['arlima_list']);
                    update_post_meta(
                        $post_id,
                        '_arlima_list_data',
                        array(
                            'width' => (int)$_POST['arlima_width'],
                            'offset' => (int)$_POST['arlima_offset'],
                            'limit' => (int)$_POST['arlima_limit']
                        )
                    );
                }
            } else {
                Arlima_ListFactory::updateArlimaArticleData(get_post($post_id));
            }
        }
    }

    /**
     * Creates the menu in wp-admin for this plugin and then call Arlima_Plugin::addAdminPageScriptsAndStylesheets
     * that will add all javascript and stylesheets needed by this plugin in wp-admin
     */
    function adminMenu()
    {
        $arlima_mainpage = add_menu_page(
            __('Article List Manager', 'arlima'),
            __('Article lists', 'arlima'),
            'edit_posts',
            'arlima',
            'Arlima_Plugin::loadAdminMainPage',
            ARLIMA_PLUGIN_URL . '/images/skull-icon.png'
        );
        add_submenu_page(
            'arlima',
            __('Article lists', 'arlima'),
            __('Manage lists', 'arlima'),
            'edit_posts',
            'arlima',
            'Arlima_Plugin::loadAdminMainPage'
        );
        $arlima_editpage = add_submenu_page(
            'arlima',
            __('Edit lists', 'arlima'),
            __('Edit lists', 'arlima'),
            'edit_posts',
            'arlima-editpage',
            'Arlima_Plugin::loadAdminEditPage'
        );
        $arlima_webservicepage = add_submenu_page(
            'arlima',
            __('Web Service', 'arlima'),
            __('Web Service', 'arlima'),
            'edit_posts',
            'arlima-webservicepage',
            'Arlima_Plugin::loadAdminWebServicePage'
        );

        $this->addAdminPageScriptsAndStylesheets($arlima_mainpage, $arlima_editpage, $arlima_webservicepage);

        if ( function_exists('poll_footer_admin') ) {
            add_action('admin_footer', 'poll_footer_admin');
        }
    }

    /**
     * @param string $main_page_slug
     * @param string $edit_page_slug
     * @param string $service_page_slug
     */
    function addAdminPageScriptsAndStylesheets($main_page_slug, $edit_page_slug, $service_page_slug)
    {
        add_action('admin_print_scripts-' . $main_page_slug, array($this, 'addAdminMainPageScripts'));
        add_action('admin_print_styles-' . $main_page_slug, array($this, 'addAdminStyleSheets'));
        add_action('admin_print_styles-' . $main_page_slug, array($this, 'addTemplateCSS'));
        add_action('admin_print_scripts-' . $main_page_slug, array($this, 'addTinyMCEFilters'));
        add_action('admin_footer-' . $main_page_slug, array($this, 'addTemplateLoadingJS'));

        add_action('admin_print_scripts-' . $edit_page_slug, array($this, 'addAdminEditPageScripts'));
        add_action('admin_print_styles-' . $edit_page_slug, array($this, 'addAdminStyleSheets'));

        add_action('admin_print_styles-' . $service_page_slug, array($this, 'addAdminStyleSheets'));
        add_action('admin_print_scripts-' . $service_page_slug, array($this, 'addAdminServicePageScripts'));

        // Javascript used in meta-box on wp-admin page where blog posts is created
        $php_file = basename($_SERVER['PHP_SELF']);
        if ( $php_file == 'post-new.php' || $php_file = 'post.php' ) {
            wp_enqueue_script(
                'arlima_js_admin',
                ARLIMA_PLUGIN_URL . '/js/admin-post.js',
                array('jquery'),
                self::STATIC_VERSION
            );
            wp_localize_script(
                'arlima_js_admin',
                'ArlimaJSAdmin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'arlimaNonce' => wp_create_nonce('arlima-nonce'),
                    'imageurl' => ARLIMA_PLUGIN_URL . '/images/',
                    'lang' => array(
                        'notice' => __(
                            'Are you sure you want to insert this post in the top of selected article list?',
                            'arlima'
                        ),
                        'wasSentTo' => __('The post is inserted in article list', 'arlima')
                    )
                )
            );
        }
    }

    /**
     * Add javascripts used on list edit page in wp-admin
     */
    function addAdminEditPageScripts()
    {
        wp_enqueue_script('arlima_js', ARLIMA_PLUGIN_URL . 'js/page-edit.js', array('jquery'), self::STATIC_VERSION);
        wp_enqueue_script(
            'arlima_js_jquery',
            ARLIMA_PLUGIN_URL . 'js/arlima/arlima-jquery-plugins.js',
            array('jquery'),
            self::STATIC_VERSION
        );
    }

    /**
     * Add javascripts used on list edit page in wp-admin
     */
    function addAdminServicePageScripts()
    {
        wp_enqueue_script('arlima_js', ARLIMA_PLUGIN_URL . 'js/page-service.js', array('jquery'), self::STATIC_VERSION);
    }

    /**
     * Adds all javascript files needed in the list editor in wp-admin
     */
    function addAdminMainPageScripts()
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
                $wp .= ',' . Arlima_Plugin::getTemplateCSS();
                return $wp;
            }
        }
        add_filter('mce_css', 'tdav_css');

        wp_enqueue_script('jquery');
        wp_deregister_script('jquery-hotkeys');
        wp_deregister_script('jquery-ui');

        // Add an almost astronomical amount of javascript
        $javascripts = array(
            'jquery-ui'         => 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.js',
            'media-upload'      => false,
            'thickbox'          => false,
            'qtip'              => ARLIMA_PLUGIN_URL . 'js/jquery/qtip/jquery.qtip.min.js',
            'colourpicker'      => ARLIMA_PLUGIN_URL . 'js/jquery/colourpicker/jquery.colourpicker.js',
            'fancybox'          => ARLIMA_PLUGIN_URL . 'js/jquery/fancybox/jquery.fancybox-1.3.4.pack.js',
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

        foreach($javascripts as $handle => $js) {
            if( $js !== false ) {
                wp_register_script($handle, $js, array('jquery'), self::STATIC_VERSION, false);
            }
            wp_enqueue_script($handle);
        }

        wp_localize_script(
            'arlima-js',
            'ArlimaJS',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'arlimaNonce' => wp_create_nonce('arlima-nonce'),
                'imageurl' => ARLIMA_PLUGIN_URL . '/images/',
                'baseurl' => get_bloginfo('url'),
                'is_admin' => current_user_can('manage_options') ? 1 : 0,
                'preview_query_arg' => Arlima_List::QUERY_ARG_PREVIEW,
                'lang' => array( // todo: but these args in a separate .js.php file when this array gets to long
                    'unsaved' => __('You have one, or more, unsaved article lists', 'arlima'),
                    'laterVersion' => __('It exists an older version of this article list', 'arlima'),
                    'overWrite' => __('Do you still want to save this version of the list?', 'arlima'),
                    'severalExtras' => __('This article list has more than one extra-streamer', 'arlima'),
                    'changesBeforeRemove' => __(
                        'You have made changes to this list, do you want to remove it anyway?',
                        'arlima'
                    ),
                    'wantToRemove' => __('Do you want to remove "', 'arlima'),
                    'fromList' => __('" from this article list?', 'arlima'),
                    'chooseImage' => __('Choose image', 'arlima'),
                    'admin_lock' => __('This article is locked by admin', 'arlima'),
                    'admin_only' => __('Only administrators can manage article locks', 'arlima'),
                    'noList' => __('No list is active!', 'arlima'),
                    'noImages' => __('This article has no related image', 'arlima'),
                    'noConnection' => __('this article is not connected to any post', 'arlima'),
                    'listRemoved' => __('This list have been removed!', 'arlima'),
                    'savePreview' => __('to save article list', 'arlima'),
                    'isSaved' => __('This list has no unsaved changes', 'arlima'),
                    'missingPreviewPage' => __('This list is missing a preview page', 'arlima'),
                    'hasUnsavedChanges' => __('This list has unsaved changes', 'arlima'),
                    'dragAndDrop' => __('Drag images to this container', 'arlima'),
                    'sticky' => __('Sticky', 'arlima'),
                    'loggedOut' => __('Your login session seems to have expired, pls reload the page!', 'arlima')
                )
            )
        );
    }

    /**
     * Will enqueue the css for the presentation of articles in an arlima list
     */
    function addTemplateCSS()
    {
        wp_enqueue_style('arlima_template_css', self::getTemplateCSS(), array(), null);
    }

    /**
     * Get the path to the CSS file that controls the presentation of
     * articles in an arlima list
     * @static
     * @return string
     */
    public static function getTemplateCSS()
    {
        return apply_filters('arlima_template_css', ARLIMA_PLUGIN_URL . 'css/template.css');
    }

    /**
     * Will output javascript that loads all jQuery templates from backend
     */
    function addTemplateLoadingJS()
    {
        $tmpl_resolver = new Arlima_TemplatePathResolver();
        ?>
        <script>
            var tmpls = [];
            <?php foreach ($tmpl_resolver->getTemplateFiles() as $tmpl): ?>
                tmpls.push('<?php echo $tmpl_resolver->fileToUrl($tmpl); ?>?v=5');
            <?php endforeach; ?>
            ArlimaTemplateLoader.load(tmpls);
            <?php if ( !empty($_GET['open_list']) ): ?>
                var loadArlimListOnLoad = <?php echo intval($_GET['open_list']); ?>;
            <?php endif; ?>
        </script>
        <?php
    }

    /**
     * Add all stylesheets needed by this plugin in wp-admin
     */
    function addAdminStyleSheets()
    {
        wp_enqueue_style('arlima_css', ARLIMA_PLUGIN_URL . 'css/admin.css', null, self::STATIC_VERSION);
        wp_enqueue_style('thickbox');
        wp_enqueue_style(
            'jquery_ui_css',
            'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css'
        );
        wp_enqueue_style('qtip_css', ARLIMA_PLUGIN_URL . 'js/jquery/qtip/jquery.qtip.css');
        wp_enqueue_style('colourpicker_css', ARLIMA_PLUGIN_URL . 'js/jquery/colourpicker/colourpicker.css');
        wp_enqueue_style('fancy_css', ARLIMA_PLUGIN_URL . 'js/jquery/fancybox/jquery.fancybox-1.3.4.css');
    }

    /**
     * Will hook into tinyMCE setup and modify the functionality of tinyMCE HTML editor
     * @TODO: Decide whether or not this should be moved from this plugin to somewhere else
     */
    function addTinyMCEFilters()
    {
        add_filter('mce_external_plugins', array($this, 'mcePlugin'));
        add_filter('mce_buttons', array($this, 'mceButtons1'), 20);
        add_filter('mce_buttons_2', array($this, 'mceButtons2'), 20);
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
     * Tells whether or not the plugin ScissorsContinued is installed
     * @static
     * @return bool
     */
    public static function isScissorsInstalled()
    {
        return self::$is_scissors_installed;
    }

    /**
     * Tells whether or not plugin WP Related Posts is installed
     * @static
     * @return bool|null
     */
    public static function isWPRelatedPostsInstalled()
    {
        return self::$is_wp_related_post_installed;
    }

    /**
     * Class loader that either tries to load the class from arlima class
     * directory or jQueryTmpl directory
     * @static
     * @param string $class
     */
    public static function classLoader($class)
    {
        // use substr instead of strpos or regexp, way faster in this case
        if ( substr($class, 0, 7) == 'Arlima_' ) {

            require_once ARLIMA_PLUGIN_PATH . '/classes/' . substr($class, 7) . '.php';

        } elseif ( substr($class, 0, 10) == 'jQueryTmpl' ) {
            $jquery_tmpl_class = ARLIMA_PLUGIN_PATH . '/classes/jquery-tmpl-php/' .
                                    str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
            require_once $jquery_tmpl_class;

        } // Deprecated classes
        elseif ( substr($class, 0, 6) == 'Arlima' ) {
            require_once ARLIMA_PLUGIN_PATH . '/classes/deprecated.php';
        }
    }

    /**
     * @static
     * @param string $func
     * @param float $since
     * @param string|bool $replacement - optional
     */
    public static function warnAboutUseOfDeprecatedFunction($func, $since, $replacement = false)
    {
        if ( WP_DEBUG ) {
            trigger_error(
                sprintf(
                    'Use of deprecated arlima function %s, deprecated since vesion %f %s',
                    $func,
                    (string)$since,
                    $replacement ? ' use ' . $replacement . ' instead' : ''
                ),
                E_USER_WARNING
            );
        }
    }

    /**
     * @static
     * @param string $page
     */
    protected static function loadAdminPage($page)
    {
        ?>
        <div class="wrap">
            <div id="icon-plugins" class="icon32"></div>
            <h2><?php _e('Article List Manager', 'arlima') ?></h2>
            <?php require ARLIMA_PLUGIN_PATH . '/pages/' . $page . '.php'; ?>
        </div>
        <?php
    }

    public static function loadAdminMainPage()
    {
        self::loadAdminPage('main');
    }

    public static function loadAdminEditPage()
    {
        self::loadAdminPage('edit');
    }

    public static function loadAdminWebServicePage()
    {
        self::loadAdminPage('service');
    }

    /**
     * Will output a set of option elements containing streamer background colors.
     * @static
     */
    public static function loadStreamerColos()
    {
        // Make it possible for theme or other plugins to
        // define their own streamer colors
        $predefined_colors = apply_filters('arlima_streamer_colors', array());
        if ( !empty($predefined_colors) ) {
            foreach ($predefined_colors as $hex) {
                echo '<option value="' . $hex . '">#' . $hex . '</option>';
            }
        } // default colors
        else {
            $cs = array('00', '33', '66', '99', 'CC', 'FF');
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 6; $j++) {
                    for ($k = 0; $k < 6; $k++) {
                        $c = $cs[$i] . $cs[$j] . $cs[$k];
                        echo '<option value="' . $c . '">#' . $c . '</option>\n';
                    }
                }
            }
        }
    }

}