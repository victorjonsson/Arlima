<?php


/**
 * Utility class for the Arlima plugin.
 *
 * @package Arlima
 * @since 3.1
 */
class Arlima_WP_Plugin
{
    /**
     * @deprecated
     * @see ARLIMA_PLUGIN_VERSION
     */
    const VERSION = ARLIMA_PLUGIN_VERSION;

    const EXPORT_FEED_NAME = 'arlima-export';
    const PLUGIN_SETTINGS_OPT = 'arlima_plugin_settings';

    private static $is_scissors_installed = null;

    /**
     * @var Arlima_CMSInterface
     */
    private $cms;

    /**
     */
    public function __construct($sys=null)
    {
        $this->cms = $sys ? $sys : Arlima_CMSFacade::load();
    }

    /**
     *
     */
    function init()
    {
        if( is_admin() ) {

            // Register install/uninstall procedures
            register_activation_hook('arlima/arlima.php', 'Arlima_WP_Plugin::install');
            register_deactivation_hook('arlima/arlima.php', 'Arlima_WP_Plugin::deactivate');
            register_uninstall_hook('arlima/arlima.php', 'Arlima_WP_Plugin::uninstall');

            // Add actions and filters used in wp-admin
            $this->initAdminActions();
        }
        else {

            // Add actions and filters used in the theme
            $this->initThemeActions();
        }
    }


    /**
     * Actions added in the theme
     */
    function initThemeActions()
    {
        // Register widget
        add_action('widgets_init', array($this, 'setupWidgets'));

        add_action('init', array($this, 'commonInitHook'));
        add_action('template_redirect', array($this, 'themeInitHook'));
        add_action('arlima_publish_scheduled_list', array($this, 'publishScheduledList'), 10 ,2);

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
            $ajax_manager = new Arlima_WP_Ajax($this);
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
            add_action('loop_start', 'Arlima_WP_Plugin::setupArlimaListRendering');
            add_action('loop_end', 'Arlima_WP_Plugin::tearDownArlimaListRendering');
            add_filter('body_class', 'Arlima_WP_Plugin::bodyClassFilter');
        }

        if( is_user_logged_in() && arlima_is_preview() ) {
            wp_enqueue_script('jquery'); // The list manager uses the jQuery object on this page
        }
    }

    /**
     * @param array $classes
     */
    static function bodyClassFilter($classes)
    {
        if( arlima_has_list() ) {
            $classes[] = 'has-arlima-list';
        }
        return $classes;
    }

    /**
     */
    static function setupArlimaListRendering()
    {
        add_filter('the_content', 'Arlima_WP_Plugin::displayArlimaList');
    }

    /**
     */
    static function tearDownArlimaListRendering()
    {
        remove_filter('the_content', 'Arlima_WP_Plugin::displayArlimaList');
    }

    /**
     */
    static function displayArlimaList($content)
    {
        if( arlima_has_list() ) {

            global $post;
            $relation = Arlima_CMSFacade::load()->getRelationData($post->ID);
            if( !isset($relation['attr']) )
                $relation['attr'] = array();

            $relation['attr']['echo'] = false;

            if( isset($relation['attr']['position']) && $relation['attr']['position'] == 'after') {
                $content .= arlima_render_list(arlima_get_list(), $relation['attr']);
            } else {
                $content = arlima_render_list(arlima_get_list(), $relation['attr']) . $content;
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

        $list = Arlima_List::builder()->id($attr['list'])->build();

        if ( !$list->exists() ) {
            return sprintf(
                $error_html,
                'Short code [arlima] is referring to a list that does not exist (' . $attr['list'] . ')'
            );
        }

        return arlima_render_list($list, $attr);
    }

    /**
     */
    public function adminBar()
    {
        /* @var WP_Admin_Bar $wp_admin_bar */
        global $wp_admin_bar;
        if ( is_admin_bar_showing() ) {
            Arlima_WP_Facade::initLocalization();
            $admin_url = admin_url('admin.php?page=arlima-main');
            $wp_admin_bar->add_menu(
                array(
                    'id' => 'arlima',
                    'parent' => '',
                    'title' => __('Article lists', 'arlima'),
                    'href' => $admin_url
                )
            );
            $list_repo = new Arlima_ListRepository();
            $lists = $list_repo->loadListSlugs();

            if( $this->getSetting('limit_access_to_lists') ) {
                $allowed_lists = get_user_meta( get_current_user_id(), 'arlima_allowed_lists', true);
                if( $allowed_lists == -1 ) $lists = array();
                if( is_array( $allowed_lists) ) {
                    $lists = array_filter( $lists, function($list) use ($allowed_lists) {
                        return in_array($list->id, $allowed_lists);
                    });
                }
            }

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

        // Check if scissors is installed.
        self::$is_scissors_installed = function_exists('scissors_create_image');

        // Add some formats
        arlima_register_format('format-inverted', $this->cms->translate('Inverted'), array('giant'));
        arlima_register_format('format-serif', 'Serif');

        // Invoke an action meant for the theme or other plugins to hook into
        // when wanting to register article formats
        do_action('arlima_register_formats', false);

        // Image version filters
        Arlima_WP_ImageVersionManager::registerFilters();
    }

    /**
     * Register our widgets and widget filters
     */
    public function setupWidgets()
    {
        register_widget('Arlima_WP_Widget'); // Class will be autoloaded
    }

    /**
     * Function called on init in wp-admin
     */
    function adminInitHook()
    {
        self::update();
        Arlima_WP_Facade::initLocalization();
        add_action('save_post', array($this, 'savePageMetaBox'));
        add_action('add_meta_boxes', array($this, 'addAttachmentMetaBox'));
        add_filter('plugin_action_links_arlima-dev/arlima.php', array($this, 'settingsLinkOnPluginPage'));

        if( current_user_can('manage_options') && $this->getSetting('limit_access_to_lists' ) ) {
            add_action('show_user_profile', array( $this, 'printUserAllowedLists' ) );
            add_action('edit_user_profile', array( $this, 'printUserAllowedLists' ) );
            add_action('personal_options_update', array( $this, 'saveUserAllowedLists' ) );
            add_action('edit_user_profile_update', array( $this, 'saveUserAllowedLists' ) );
        }
    }

    /**
     * Prints the html for editing allowed lists for a user
     */
    function printUserAllowedLists() 
    {
        global $user_id;
        $allowed_lists = get_user_meta( $user_id, 'arlima_allowed_lists', true );
        if( !$allowed_lists ) $allowed_lists = 1;
        wp_nonce_field(__FILE__, 'arlima_nonce');
        ?>
        <div id="arlima-allowed-lists">
            <h3>Tillgängliga Arlimalistor</h3>
            <p>Här kan du ställa in vilka arlima-listor som ska vara tillgängliga för användaren</p>
            <label><input type="radio" name="arlima_allowed_lists" value="1" <?php if($allowed_lists == 1 ) echo 'checked="checked"'; ?> /> Alla</label><br />
            <label><input type="radio" name="arlima_allowed_lists" value="-1" <?php if($allowed_lists == -1 ) echo 'checked="checked"'; ?> /> Inga</label><br />
            <label><input type="radio" name="arlima_allowed_lists" value="selection" <?php if( is_array( $allowed_lists ) ) echo 'checked="checked"'; ?> /> Urval: </label><br />
            <div id="arlima-allowed-lists-selection" class="scroll-window" style="padding-left: 10px;max-height: 200px;margin-top:10px; border:1px solid #e2e2e2; overflow-y: auto; display:<?php echo is_array( $allowed_lists ) ? 'block' : 'none'; ?>">
                <?php
                $list_repository = new Arlima_ListRepository();
                $lists = $list_repository->loadListSlugs();

                foreach( $lists as $list ) { 
                    $checked = '';
                    if( is_array( $allowed_lists ) )
                        $checked = in_array( $list->id, $allowed_lists ) ? 'checked="checked"' : '';
                    ?>
                    <p><label><input type="checkbox" name="arlima_allowed_lists_selection[]" value="<?php echo $list->id ?>" <?php echo $checked; ?> /> <?php echo $list->slug ?></label></p>
                <?php }  ?>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#arlima-allowed-lists input[type="radio"]').on('click', function(e){
                    if($(this).val() == 'selection') {
                        $('#arlima-allowed-lists-selection').slideDown('fast');
                    }else{
                        $('#arlima-allowed-lists-selection').slideUp('fast');
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Saves the allowed lists settings
     */
    function saveUserAllowedLists( $user_id ) 
    {
        if (!empty($_POST) && wp_verify_nonce(__FILE__, 'arlima_nonce')) {
            return false;
        }

        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        $value = $_POST['arlima_allowed_lists'];
        if( $value == 'selection' ) {
            $value = (array)$_POST['arlima_allowed_lists_selection'];
            if( sizeof( $value ) == 0 ) $value = -1;
        }
        update_user_meta( $user_id, 'arlima_allowed_lists', $value );

    }

    /**
     * Add a settings link to given links
     * @param array $links
     * @return array
     */
    function settingsLinkOnPluginPage($links)
    {
        $settings_link = '<a href="admin.php?page='.Arlima_WP_Page_Settings::PAGE_SLUG.'">'.__('Settings', 'arlima').'</a>';
        array_unshift($links, $settings_link);
        return $links;
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
        $version_manager = new Arlima_WP_ImageVersionManager($post->ID, $this);
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
        return current( array_slice( explode(Arlima_WP_ImageVersionManager::VERSION_PREFIX,  $parts['filename']), 1, 1));
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
     * @param Arlima_AbstractRepositoryDB[] $repos
     * @return bool
     */
    static function hasCreatedDBTables($repos)
    {
        $has_tables = false;
        foreach($repos[0]->getDatabaseTables() as $table) {
            $has_tables = Arlima_CMSFacade::load()->dbTableExists($table);
            break;
        }
        return $has_tables;
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
        if( is_network_admin() ) {
            throw new Exception('You can not install Arlima for an entire multi-site network.');
        }

        $repos = self::loadRepos();

        if( self::hasCreatedDBTables($repos) ) {
            self::update();
        }
        else {

            // Add db tables
            foreach($repos as $repo) {
                $repo->createDatabaseTables();
            }

            // Add feed
            global $wp_rewrite;
            $plugin = new self();
            $plugin->addExportFeeds();
            $wp_rewrite->flush_rules();

            // Add settings
            $plugin = new self();
            $settings = $plugin->loadSettings();
            $settings['install_version'] = ARLIMA_PLUGIN_VERSION;
            $settings['image_quality'] = 100;
            $plugin->saveSettings($settings);
        }
    }

    /**
     * Uninstall procedure for this plugin
     *  - Removes plugin settings
     *  - Removes database tables
     * @static
     */
    public static function uninstall()
    {
        global $wpdb;
        foreach(self::loadRepos() as $repo) {
            foreach($repo->getDatabaseTables() as $tbl) {
                $wpdb->query('DROP TABLE IF EXISTS '.$tbl);
            }
        }
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
        $current_version = $plugin->getSetting('install_version', 0);

        if( is_multisite() && !self::hasCreatedDBTables(self::loadRepos()) ) {
            self::install();
            return;
        }

        // Time for an update
        if ( $current_version !== ARLIMA_PLUGIN_VERSION ) {

            $settings = $plugin->loadSettings();
            $current_version_float = Arlima_Utils::versionNumberToFloat($current_version);

            if( $current_version_float < 3.01 ) {
                // Add some missing settings
                $settings['newsbill_tag'] = '';
                $settings['streamer_pre'] = '';
                $settings['editor_sections'] = '';
            }

            if( $current_version < 3.1 ) {
                delete_option('arlima_db_version');
            }

            // Let the repos do their thang
            foreach(self::loadRepos() as $repo) {
                $repo->updateDatabaseTables($current_version_float);
            }

            $settings['install_version'] = ARLIMA_PLUGIN_VERSION;
            $plugin->saveSettings($settings);
        }
    }

    /**
     * @return Arlima_AbstractRepositoryDB[]
     */
    public static function loadRepos()
    {
        return array(new Arlima_ListVersionRepository(), new Arlima_ListRepository());
    }

    /**
     * Will try to export arlima list from currently visited page
     */
    function loadExportFeed()
    {
        if( is_404() )
            return;

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
        $export_manager = new Arlima_ExportManager($this->getSetting('available_export'));
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
            $repo = new Arlima_ListRepository();
            ?>
            <input type="hidden" name="arlima-postid" id="arlima-postid" value="<?php echo $post->ID; ?>"/>
            <select name="arlima-listid" id="arlima-listid">
                <option value=""><?php _e('Choose article list', 'arlima') ?></option>
                <?php foreach ($repo->loadListSlugs() as $arlima_list): ?>
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
        $repo = new Arlima_ListRepository();
        $lists = $repo->loadListSlugs();

        $import_manager = new Arlima_ImportManager( $this );
        $imported = $import_manager->getImportedLists();

        $relation_data = false;
        if ( $post ) {
            $relation_data = $this->cms->getRelationData($post->ID);
        }

        if( !$relation_data )
            $relation_data = array('id' => '', 'attr'=>$this->cms->getDefaultListAttributes());

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

                if ( empty($_POST['arlima_list']) ) {
                    $this->cms->removeRelation($post_id);
                } else {

                    $this->cms->relate($_POST['arlima_list'], $post_id, array(
                        'width' => (int)$_POST['arlima_width'],
                        'offset' => (int)$_POST['arlima_offset'],
                        'limit' => (int)$_POST['arlima_limit'],
                        'position' => $_POST['arlima_position']
                    ));
                }

                do_action('arlima_meta_box_save', $post_id);

            } else {
                $ver_repo = new Arlima_ListVersionRepository();
                $ver_repo->updateArticlePublishDate($this->cms->getPostTimeStamp($post_id), $post_id);
            }
        }
    }

    /**
     * Creates the menu in wp-admin for this plugin
     */
    function adminMenu()
    {
        $pages_classes = array(
            'Arlima_WP_Page_Main',
            'Arlima_WP_Page_Edit',
            'Arlima_WP_Page_Settings'
        );

        /* @var Arlima_WP_AbstractAdminPage $page */
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
        // Default crop templates
        $crop_templates = array(
            'Widescreen' => array(16,9),
            '19:9' => array(19,9),
            'Cinema' => array(21,9),
            'Square' => array(666,666)
        );

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
                ),
                'scissorsCropTemplates' => apply_filters('arlima_scissors_crop_templates', $crop_templates)
            )
        );
    }

    /**
     * Will enqueue the css for the presentation of articles in an arlima list.
     */
    function addTemplateCSS()
    {
        if( !is_admin() ) {
            $style_sheets = $this->getTemplateStylesheets();
            if( !empty($style_sheets) ) {
                $tmpl_typo_css = basename(ARLIMA_PLUGIN_PATH) .'/css/template-typo.css'; // File only used in preview
                foreach($style_sheets as $i => $css) {
                    if( strpos($css, $tmpl_typo_css) === false ) {
                        wp_register_style('arlima_template_css_'.$i, $css, array(), ARLIMA_FILE_VERSION);
                        wp_enqueue_style('arlima_template_css_'.$i);
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    function getTemplateStylesheets()
    {
        $styles = apply_filters('arlima_template_stylesheets', array());
        if( empty($styles) && $styles !== false ) { // false meaning that we should not append any styles at all
            $styles = array(ARLIMA_PLUGIN_URL . 'css/template-typo.css', ARLIMA_PLUGIN_URL . 'css/template.css');
        }
        return $styles;
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
     * Class loader that either tries to load the class from arlima class
     * directory or template engine directory
     * @static
     * @param string $class
     */
    public static function classLoader($class)
    {
        if ( strpos($class, 'Arlima_') === 0 ) {

            require_once ARLIMA_CLASS_PATH . '/' . str_replace('_', '/', substr($class, 7)) . '.php';
        }
        elseif (strpos($class, 'Mustache') === 0) {
            require_once ARLIMA_CLASS_PATH . '/mustache/src/' . str_replace('_', '/', $class) . '.php';
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
        $plugin = new Arlima_WP_Plugin();
        $colors = apply_filters('arlima_streamer_colors', array());
        if ( empty($colors) ) {
            // http://clrs.cc/
            $colors = array(
                '001F3F',
                '0074D9',
                '7FDBFF',
                '39CCCC',
                '3D9970',
                '2ECC40',
                '01FF70',
                'FFDC00',
                'FF851B',
                'FF4136 ',
                '85144B',
                'F012BE',
                'B10DC9',
                'F9F9F9',
                'DDDDDD',
                'AAAAAA',
                '111111'
            );
        }

        $colors = array_merge($colors, $plugin->getSetting('streamer_colors', array()));

        foreach ($colors as $hex) {
            echo '<option value="' . $hex . '">#' . $hex . '</option>';
        }

    }

    /**
     * @param $img_file
     * @param $file_name
     * @param $connected_post
     * @return int
     * @throws Exception
     */
    public static function saveImageFileAsAttachment($img_file, $file_name, $connected_post)
    {
        if ( !function_exists('wp_generate_attachment_metadata') ) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }

        // Set variables for storage
        // fix file filename for query strings
        preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file_name, $matches );
        $file_array['name'] = basename($matches[0]);
        $file_array['tmp_name'] = $img_file;

        $time = current_time( 'mysql' );
        $file = wp_handle_sideload( $file_array, array('test_form'=>false), $time );

        if ( isset($file['error']) )
            throw new Exception( 'Could not save image due to '.$file['error'] );

        $local_url = $file['url'];
        $type = $file['type'];
        $file = $file['file'];

        if( empty($title) )
            $title = pathinfo($file, PATHINFO_FILENAME);

        // Don't know why this happens....?
        if( strpos($local_url, 'http:///') === 0 ) {
            $local_url = dirname(dirname(get_stylesheet_directory_uri())) .'/uploads/'. substr($local_url,8);
        }

        // Construct the attachment array
        $attachment = array(
            'post_mime_type' => $type,
            'guid' => $local_url,
            'post_parent' => $connected_post,
            'post_title' => $title,
            'post_content' => '',
        );

        // Save the attachment metadata
        $id = wp_insert_attachment($attachment, $file);

        if( !is_wp_error($id) ) {
            wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
            return $id;
        } else {
            /* @var WP_Error $id */
            throw new Exception($id->get_error_message());
        }
    }

    /**
     * Create a wordpress attachment out of a string with base64 encoded image binary
     * @param string $base64_img
     * @param string $file_name
     * @param string $connected_post
     * @return int The attachment ID
     * @throws Exception
     */
    public static function saveImageAsAttachment($base64_img, $file_name, $connected_post='')
    {
        $img_file = tempnam(get_temp_dir(), $file_name);
        file_put_contents($img_file, base64_decode($base64_img));
        return self::saveImageFileAsAttachment($img_file, $file_name, $connected_post);
    }

    /**
     * Publishes a scheduled arlima list
     * @param int $list_id
     * @param int $version_id
     */
    public static function publishScheduledList($list_id, $version_id)
    {
        $ver_repo = new Arlima_ListVersionRepository();
        $list = Arlima_List::builder()
                    ->id($list_id)
                    ->version($version_id)
                    ->includeFutureArticles()
                    ->build();

        if( $list->numArticles() > 0 ) {
            // No articles would mean that the version did not exist (todo: how should one detect that a requested version does not exist)
            $ver_repo->delete($version_id);
            $ver_repo->create($list, $list->getArticles(), $list->getVersionAttribute('user_id')); // Publish the list as a new version
        }
    }

}