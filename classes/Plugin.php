<?php


/**
 * Utility class for the Arlima plugin.
 * @package Arlima
 * @since 1.0
 */
class Arlima_Plugin
{
    const VERSION = 2.8;
    const EXPORT_FEED_NAME = 'arlima-export';
    const PLUGIN_SETTINGS_OPT = 'arlima_plugin_settings';

    private static $is_scissors_installed = null;
    private static $is_wp_related_post_installed = null;
    private static $has_loaded_textdomain = false;

    /**
     * Actions added in the theme
     */
    function initThemeActions()
    {
        // Register widget
        add_action('widgets_init', array($this, 'setupWidgets'));

        add_action('init', array($this, 'commonInitHook'));
        add_action('template_redirect', array($this, 'themeInitHook'));
    }

    /**
     * Actions added in wp-admin
     */
    function initAdminActions()
    {
        // Register widget
        add_action('widgets_init', array($this, 'setupWidgets'));

        add_action('init', array($this, 'commonInitHook'));
        add_action('init', array($this, 'adminInitHook'));
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('add_meta_boxes', array($this, 'addMetaBox'));

        // Ajax actions
        if( defined('DOING_AJAX') && DOING_AJAX ) {
            $ajax_manager = new Arlima_AdminAjaxManager($this);
            $ajax_manager->initActions();
        }
    }

    /**
     * function called on init in the theme
     */
    function themeInitHook()
    {
        add_action('wp_print_styles', array($this, 'addTemplateCSS'));
        add_shortcode('arlima', array($this, 'arlimaListShortCode'));

        if ( is_page() ) {
            add_filter('the_content', array($this, 'displayArlimaList'));
        }

        // Add filters that makes content editable in context
        if( is_user_logged_in() ) {
            if( arlima_is_preview() ) {
                wp_enqueue_script('jquery'); // The list manager uses the jQuery object on this page
            } elseif( $this->getSetting('in_context_editing') ) {
                $editor = new Arlima_InContextEditor($this);
                $editor->apply();
            }
        }
    }

    /**
     */
    function displayArlimaList($content)
    {
        if( arlima_has_list() ) {
            global $post;
            $connector = new Arlima_ListConnector();
            $relation = $connector->getRelationData($post->ID);
            if( isset($relation['attr']['position']) && $relation['attr']['position'] == 'after') {
                $relation['attr']['echo'] = false;
                $content .= arlima_render_list(arlima_get_list(), $relation['attr']);
            } else {
                arlima_render_list(arlima_get_list(), isset($relation['attr']) ?  $relation['attr'] : array());
            }
        }

        return $content;
    }

    /**
     * Short code for arlima
     * @param array $attr
     * @return string
     */
    public function arlimaListShortCode($attr)
    {
        $factory = new Arlima_ListFactory();

        $attr = shortcode_atts(
            array(
                'offset' => 0,
                'limit' => 0,
                'width' => 560,
                'list' => null,
                'filter_suffix' => ''
            ),
            $attr
        );

        $attr['echo'] = false;

        $error_html = '<div><p style="background:red; color:white; font-weight: bold">%s</p></div>';

        if ( empty($attr['list']) ) {
            return sprintf($error_html, 'Short code [arlima] is missing argument &quot;list&quot;');
        }

        $list = $factory->loadList($attr['list']);

        if ( !$list->exists() ) {
            return sprintf(
                $error_html,
                'Short code [arlima] is referring to a list that does not exist (' . $attr['list'] . ')'
            );
        }

        return arlima_render_list($list, $attr);
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
            $admin_url = admin_url('admin.php?page=arlima-main');
            $wp_admin_bar->add_menu(
                array(
                    'id' => 'arlima',
                    'parent' => '',
                    'title' => __('Article lists', 'arlima'),
                    'href' => $admin_url
                )
            );
            $factory = new Arlima_ListFactory();
            $lists = $factory->loadListSlugs();

            // Put current list first in navigation
            if( arlima_has_list() ) {
                $current_list_slug = arlima_get_list()->getSlug();
                foreach($lists as $key => $list_data) {
                    if( $list_data->slug == $current_list_slug ) {
                        unset($lists[$key]);
                        array_unshift($lists, $list_data);
                    }
                }
            }

            foreach ($lists as $list_data) {
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
        // Add links to admin bar
        add_action('wp_before_admin_bar_render', array($this, 'adminBar'));

        // Add export feeds
        $this->addExportFeeds();

        // Check if some other plugins might be installed
        self::$is_scissors_installed = function_exists('scissors_create_image');
        self::$is_wp_related_post_installed = function_exists('MRP_get_related_posts');

        // Add some formats
        arlima_register_format('format-inverted', 'Inverted', array('giant'));
        arlima_register_format('format-serif', 'Serif');

        // Invoke an action meant for the theme or other plugins to hook into
        // when wanting to register article formats
        do_action('arlima_register_formats', false);

        // Image version filters
        Arlima_ImageVersionManager::registerFilters();
    }

    /**
     * Register our widgets and widget filters
     */
    public function setupWidgets()
    {
        register_widget('Arlima_Widget'); // Class will be autoloaded
    }

    /**
     * Function called on init in wp-admin
     */
    function adminInitHook()
    {
        self::update();
        self::loadTextDomain();
        add_action('save_post', array($this, 'savePageMetaBox'));
        add_action('add_meta_boxes', array($this, 'addAttachmentMetaBox'));
    }

    /**
     * Adds attachment meta box
     */
    function addAttachmentMetaBox()
    {
        if( $this->doAddAttachmentMetaBox() ) {

            add_meta_box(
                'arlima-attachment-media',
                __('Arlima image versions', 'arlima'),
                array($this, 'attachmentMetaBox'),
                'attachment'
            );
        }
    }

    /**
     * Outputs HTML content of arlima versions meta box
     */
    function attachmentMetaBox()
    {
        global $post;
        $version_manager = new Arlima_ImageVersionManager($post->ID, $this);
        $versions = $version_manager->getVersions(null, true);
        $no_version_style = count($versions) > 0 ? ' style="display:none"':'';
        $versions_style = $no_version_style == '' ? ' style="display:none"':'';
        ?>
        <p id="arlima-no-versions-info"<?php echo $no_version_style ?>>
            <?php _e('This image has no generated versions', 'arlima') ?>
        </p>
        <p id="arlima-versions"<?php echo $versions_style ?>>
            <strong><?php _e('Generated versions', 'arlima') ?>:</strong>
            <?php foreach($versions as $version): ?>
                <a href="<?php echo $version ?>" target="_blank">
                    [<?php echo $this->getVersionDisplayName($version) ?>]
                </a>
            <?php endforeach; ?>
        </p>
        <?php if($no_version_style != ''): ?>
            <p>
                <input type="button" data-post-id="<?php echo $post->ID ?>" id="delete-arlima-versions" class="button" value="<?php _e('Delete versions', 'arlima') ?>" />
            </p>
        <?php endif;
    }

    /**
     * @param $file
     * @return string
     */
    private function getVersionDisplayName($file)
    {
        $parts = pathinfo($file);
        return current( array_slice( explode(Arlima_ImageVersionManager::VERSION_PREFIX,  $parts['filename']), 1, 1));
    }

    /**
     * @return bool
     */
    private function doAddAttachmentMetaBox()
    {
        global $post;
        $img_content_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        return self::supportsImageEditor() &&
                is_object($post) &&
                in_array(strtolower($post->post_mime_type), $img_content_types);
    }

    /**
     * @var null|bool
     */
    private static $is_wp_support_img_editor = null;

    /**
     * @return bool
     */
    public static function supportsImageEditor()
    {
        if( self::$is_wp_support_img_editor === null ) {
            global $wp_version;
            self::$is_wp_support_img_editor = version_compare( $wp_version, '3.5', '>=' );
        }
        return self::$is_wp_support_img_editor;
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
     *  - Adds initial settings
     * @static
     */
    public static function install()
    {
        // Add db tables
        $factory = new Arlima_ListFactory();
        $factory->install();

        // Add feed
        global $wp_rewrite;
        $plugin = new self();
        $plugin->addExportFeeds();
        $wp_rewrite->flush_rules();

        // Add settings
        $plugin = new self();
        $settings = $plugin->loadSettings();
        $settings['install_version'] = self::VERSION;
        $settings['in_context_editing'] = true;
        $settings['image_quality'] = 100;
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
        delete_option(self::PLUGIN_SETTINGS_OPT);
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

            // Update to version 2.6
            if( $current_version < 2.6 ) {
                Arlima_ListFactory::databaseUpdates($current_version);

                $pages = get_pages(
                    array(
                        'meta_key' => '_wp_page_template',
                        'meta_value' => 'page-arlima.php',
                        'hierarchical' => 0
                    )
                );

                // Include arlima template to get width
                $list_width = false;
                $page_template = get_stylesheet_directory() . '/page-arlima.php';
                if ( file_exists($page_template) ) {
                    $page_content = explode('TMPL_ARTICLE_WIDTH', file_get_contents($page_template));
                    if( count($page_content) > 1 ) {
                        $definition = explode(';', $page_content[1]);
                        $list_width = (int)preg_replace('([^0-9]*)', '', current($definition));
                    }
                }

                if( !$list_width )
                    $list_width = 468;

                $connector = new Arlima_ListConnector();
                $factory = new Arlima_ListFactory();
                $list_attr = array(
                    'width' => $list_width,
                    'offset' => 0,
                    'limit' => 0
                );

                foreach ($pages as $page) {
                    $arlima_slug = get_post_meta($page->ID, 'arlima', true);
                    if ( $arlima_slug ) {
                        $list = $factory->loadList($arlima_slug);
                        if( $list->exists() ) {
                            $connector->setList($list);
                            $connector->relate($page->ID, $list_attr);
                        }
                    }
                }
            }

            // Update to 2.7
            if ( $current_version < self::VERSION ) {
                $settings['in_context_editing'] = true;
                $settings['image_quality'] = 100;
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
        // Make sure URL always ends with slash
        $path = explode('?', $_SERVER['REQUEST_URI']);
        if( substr($path[0], -1) != '/' ) {
            $new_url = $path[0] .'/';
            if( isset($path[1]) )
                $new_url .= '?'.$path[1];
            header('Location: '.$new_url);
            die;
        }

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
        return get_option(self::PLUGIN_SETTINGS_OPT, array());
    }

    /**
     * @param array $setting
     */
    function saveSettings($setting)
    {
        update_option(self::PLUGIN_SETTINGS_OPT, $setting);
    }

    /**
     * @param string $name
     * @param bool $default
     * @return mixed
     */
    function getSetting($name, $default=false)
    {
        $settings = $this->loadSettings();
        return isset($settings[$name]) ? $settings[$name] : $default;
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
                   value="<?php _e('Send', 'arlima') ?>" />
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
        $factory = new Arlima_ListFactory();
        $connector = new Arlima_ListConnector();
        $lists = $factory->loadListSlugs();

        $import_manager = new Arlima_ImportManager( $this );
        $imported = $import_manager->getImportedLists();
       
        $relation_data = false;
        if ( $post ) {
            $relation_data = $connector->getRelationData($post->ID);
        }

        if( !$relation_data )
            $relation_data = array('id' => '', 'attr'=>$connector->getDefaultListAttributes());

        ?>
        <div id="arlima-list-settings">
            <?php if( empty($lists) ): ?>
                <p>
                    <a href="admin.php?page=arlima-edit" target="_blank">
                        <?php _e('Create your first article list', 'arlima') ?>
                    </a>
                </p>
            <?php else: ?>
                <table cellpadding="5">
                    <tr>
                        <td><strong><?php _e('List', 'arlima') ?>:</strong></td>
                        <td>
                            <select name="arlima_list" id="arlima-lists">
                                <option value="">- - <?php _e('No list', 'arlima') ?> - -</option>
                                <?php foreach ($lists as $arlima_list): ?>
                                    <option value="<?php echo $arlima_list->id; ?>"<?php
                                    // may be either slug or id
                                    if ( in_array($relation_data['id'], (array)$arlima_list) ){
                                        echo ' selected="selected"';
                                    }
                                    ?>><?php echo $arlima_list->title; ?></option>
                                <?php endforeach; ?>
                                <?php if(!empty($imported)): ?>
                                    <optgroup label="<?php _e('Imported lists', 'arlima') ?>">
                                        <?php foreach($imported as $list_data): ?>
                                            <option value="<?php echo $list_data['url'] ?>"<?php
                                                // may be either slug or id
                                                if ( $relation_data['id'] == $list_data['url']  ){
                                                    echo ' selected="selected"';
                                                }
                                                ?>><?php echo $list_data['title'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif ?>
                            </select>
                            <a href="" style="display: none" target="_blank" id="arlima-edit">[<?php _e('edit', 'arlima') ?>]</a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Position') ?></strong>:</td>
                        <td>
                            <select name="arlima_position">
                                <option value="before"<?php if($relation_data['attr']['position'] == 'before') echo ' selected="selected"' ?>>
                                    <?php _e('Before content', 'arlima') ?>
                                </option>
                                <option value="after"<?php if($relation_data['attr']['position'] == 'after') echo ' selected="selected"' ?>>
                                    <?php _e('After content', 'arlima') ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr class="arlima-opt">
                        <td><strong><?php _e('Width', 'arlima') ?>:</strong></td>
                        <td><input type="number" name="arlima_width" value="<?php echo $relation_data['attr']['width'] ?>" style="width:50px"/> px
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Offset', 'arlima') ?>:</strong></td>
                        <td><input type="number" name="arlima_offset" value="<?php echo $relation_data['attr']['offset'] ?>" style="width:50px"/></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Limit', 'arlima') ?>:</strong></td>
                        <td><input type="number" name="arlima_limit" value="<?php echo $relation_data['attr']['limit'] ?>" style="width:50px"/></td>
                    </tr>
                    <?php do_action('arlima_meta_box_setup') ?>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     */
    public function savePageMetaBox($post_id)
    {
        if ( !wp_is_post_autosave($post_id) && !wp_is_post_revision($post_id) ) {

            if ( isset($_POST['arlima_nonce']) && wp_verify_nonce($_POST['arlima_nonce'], __FILE__) ) {

                $connector = new Arlima_ListConnector();

                if ( empty($_POST['arlima_list']) ) {
                    $connector->removeRelation($post_id);
                } else {

                    $list_factory = new Arlima_ListFactory();
                    $connector->setList($list_factory->loadList($_POST['arlima_list']));
                    $connector->relate($post_id, array(
                            'width' => (int)$_POST['arlima_width'],
                            'offset' => (int)$_POST['arlima_offset'],
                            'limit' => (int)$_POST['arlima_limit'],
                            'position' => $_POST['arlima_position']
                        ));
                }

                do_action('arlima_meta_box_save', $post_id);

            } else {
                Arlima_ListFactory::updateArticlePublishDate(get_post($post_id));
            }
        }
    }

    /**
     * Creates the menu in wp-admin for this plugin
     */
    function adminMenu()
    {
        $pages_classes = array(
            'Arlima_Page_Main',
            'Arlima_Page_Edit',
            'Arlima_Page_Settings'
        );

        /* @var Arlima_AbstractAdminPage $page */
        foreach($pages_classes as $page_class) {
            $page = new $page_class($this);
            $page->registerPage();
        }

        $php_file = basename($_SERVER['PHP_SELF']);
        if ( $php_file == 'post-new.php' || $php_file = 'post.php' ) {
            wp_enqueue_script(
                'arlima_js_admin',
                ARLIMA_PLUGIN_URL . '/js/admin-post.js',
                array('jquery'),
                ARLIMA_FILE_VERSION
            );
            $this->addAdminJavascriptVars('arlima_js_admin');
        }

        if ( function_exists('poll_footer_admin') ) {
            add_action('admin_footer', 'poll_footer_admin');
        }
    }

    /**
     *
     */
    public function addAdminJavascriptVars($handle)
    {
        wp_localize_script(
            $handle,
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

    /**
     * Will enqueue the css for the presentation of articles in an arlima list.
     * There is two ways to add the template CSS.
     *  Old way:    return URL to css file in filter arlima_template_css. The css
     *              file will be added to the header in arlima admin
     *  New way:    return array with URL´s to stylesheets that will be added to
     *              iframe in arlima admin
     */
    function addTemplateCSS()
    {
        $style_sheets = $this->getTemplateStylesheets();
        if( empty($style_sheets) ) {
            // Old way
            $css = self::getTemplateCSS();
            if( !empty($css) ) {
                wp_enqueue_style('arlima_template_css', $css, array(), null);
            }
            else {
                // theme has not applied any css, neither the old nor the new way
                // then let the plugin add the css
                if( is_admin() ) {
                    add_filter('arlima_template_stylesheets', array($this, 'templateCSSFilter'));
                } else {
                    wp_enqueue_style('arlima_template_css', ARLIMA_PLUGIN_URL . 'css/template.css', array(), ARLIMA_FILE_VERSION);
                }
            }
        }
    }

    /**
     * @param $files
     * @return array
     */
    public function templateCSSFilter($files)
    {
        $files[] = ARLIMA_PLUGIN_URL . 'css/template-typo.css';
        $files[] = ARLIMA_PLUGIN_URL . 'css/template.css';
        return $files;
    }

    /**
     * @return array
     */
    function getTemplateStylesheets()
    {
        $style_sheets = apply_filters('arlima_template_stylesheets', array());
        return is_array($style_sheets) ? $style_sheets : array();
    }

    /**
     * Get the path to the CSS file that controls the presentation of
     * articles in an arlima list
     * @static
     * @return string
     */
    public static function getTemplateCSS()
    {
        return apply_filters('arlima_template_css', '');
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
        if ( strpos($class, 'Arlima_') === 0 ) {
            require_once ARLIMA_PLUGIN_PATH . '/classes/' . str_replace('_', '/', substr($class, 7)) . '.php';
        } elseif ( strpos($class, 'jQueryTmpl') === 0 ) {
            $jquery_tmpl_class = ARLIMA_PLUGIN_PATH . '/classes/jquery-tmpl-php/' .
                                    str_replace('_', '/', $class) . '.php';
            require_once $jquery_tmpl_class;

        } // Deprecated classes
        elseif ( strpos($class, 'Arlima') === 0 ) {
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
     * Will output a set of option elements containing streamer background colors.
     * @static
     */
    public static function loadStreamerColors()
    {
        // Make it possible for theme or other plugins to
        // define their own streamer colors
        $plugin = new Arlima_Plugin();
        $colors = $plugin->getSetting('streamer_colors', array());
        $predefined_colors = apply_filters('arlima_streamer_colors', $colors);
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