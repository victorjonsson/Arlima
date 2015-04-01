<?php

/**
 * Base class extended by classes representing admin pages in wordpress.
 *
 * Using this class reduces the amount of code that needs to be written when
 * wanting to have several admin pages in a plugin. It also reduces code
 * duplication that often appears when having several admin pages in one plugin.
 *
 * @since 2.7
 * @package Arlima
 */
abstract class Arlima_WP_AbstractAdminPage {

    /**
     * @var Arlima_WP_Plugin
     */
    protected $plugin;


    /**
     * @param Arlima_WP_Plugin $arlima_plugin
     */
    final public function __construct($arlima_plugin)
    {
        $this->plugin = $arlima_plugin;
    }

    /**
     * @param Arlima_WP_Plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return Arlima_WP_Plugin
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * @return array
     */
    function styleSheets()
    {
        return array();
    }

    /**
     * @return array
     */
    function scripts()
    {
        return array();
    }

    /**
     * Name of this page
     * @return mixed
     */
    abstract function getName();

    /**
     * Menu name of this plugin
     * @return mixed
     */
    abstract function getMenuName();

    /**
     * slug used for this admin page
     * @return string
     */
    abstract function slug();

    /**
     * Menu slug of parent page. Return empty string to set as parent page
     * @return string
     */
    abstract function parentSlug();

    /**
     * Only used when parentSlug() returns empty string
     * @return string
     */
    public function icon()
    {
        return '';
    }

    /**
     * User capability needed to visit this page
     * @return string
     */
    function capability()
    {
        return 'edit_posts';
    }

    /**
     * Registers the page and enqueue's the js and css in case
     * this page is being visited.
     */
    final public function registerPage()
    {
        if( $this->parentSlug() ) {
            add_submenu_page(
                $this->parentSlug(),
                $this->getName(),
                $this->getMenuName(),
                $this->capability(),
                $this->slug(),
                array($this, 'loadPage')
            );
        } else {
            add_menu_page(
                $this->getName(),
                $this->getMenuName(),
                $this->capability(),
                $this->slug(),
                array($this, 'loadPage'),
                $this->icon()
            );
        }

        // enqueue scripts/links
        if( $this->requestedAdminPage() === $this->slug() ) {
            add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
            add_action('admin_enqueue_scripts', array($this, 'enqueueStyles'));
        }
    }

    /**
     * @return string|bool
     */
    protected function requestedAdminPage()
    {
        if(
            basename($_SERVER['PHP_SELF']) == 'admin.php' &&
            isset($_GET['page']) &&
            strpos($_GET['page'], 'arlima-') === 0
        ) {
            return $_GET['page'];
        }
        return false;
    }

    /**
     * Enqueue's the stylesheets returned by Arlima_AbstractAdminPage::styleSheets()
     */
    public function enqueueStyles()
    {
        $styles = $this->styleSheets();
        if( ARLIMA_COMPILE_LESS_IN_BROWSER ) { // The second constant makes it possible to use the compiled css event though we're in dev-mode
            unset($styles['arlima_css']);
            add_action('admin_head', 'Arlima_WP_AbstractAdminPage::outputLessJS');
        }

        foreach($styles as $handle => $data) {
            wp_enqueue_style($handle, $data['url'], $data['deps'], ARLIMA_FILE_VERSION, false);
        }
    }

    public static function outputLessJS() {
        echo '<link rel="stylesheet/less" type="text/css" href="'.ARLIMA_PLUGIN_URL.'css/admin.less" />
        <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/1.6.0/less.min.js"></script>';
    }

    /**
     * Enqueue's the scripts returned by Arlima_AbstractAdminPage::scripts()
     */
    public function enqueueScripts()
    {
        wp_enqueue_script('jquery');
        $js_filter = 'arlima_admin_scripts-'.substr($this->slug(), 7); // remove arlima- prefix from slug
        $scripts = apply_filters($js_filter, $this->scripts());

        foreach($scripts as $handle => $data) {
            wp_enqueue_script($handle, $data['url'], $data['deps'], ARLIMA_FILE_VERSION, false);
        }

        wp_localize_script(
            'arlima-js',
            'ArlimaJS',
            array(
                'ajaxURL' => admin_url('admin-ajax.php'),
                'arlimaNonce' => wp_create_nonce('arlima-nonce'),
                'imageURL' => ARLIMA_PLUGIN_URL . 'images/',
                'baseURL' => get_bloginfo('url'),
                'pluginURL' => ARLIMA_PLUGIN_URL,
                'hasScissors' => Arlima_WP_Plugin::isScissorsInstalled(),
                'isAdmin' => current_user_can('manage_options'),
                'devMode' => ARLIMA_DEV_MODE,
                'allowEditorsCreateSections' => $this->plugin->getSetting('editor_sections', true) ? true:false,
                'limitAccessToLists' => $this->plugin->getSetting('limit_access_to_lists', true) ? true:false,
                'userAllowedLists' => get_user_meta( get_current_user_id(), 'arlima_allowed_lists', true),
                'sectionDivsSupportTemplate' => ARLIMA_SUPPORT_SECTION_DIV_TEMPLATES,
                'previewQueryArg' => Arlima_List::QUERY_ARG_PREVIEW,
                'sendJSErrorsToServerLog' => ARLIMA_SEND_JS_ERROR_TO_LOG,
                'scheduledListReloadTime' => ARLIMA_LIST_RELOAD_TIME,
                'lang' => array( // todo: but these args in a separate .js.php file when this array gets to long
                    'unsaved' => __('You have one, or more, unsaved article lists, do you wish to proceed?', 'arlima'),
                    'laterVersion' => __('It exists an older version of this article list', 'arlima'),
                    'overWrite' => __('Do you still want to save this version of the list?', 'arlima'),
                    'severalExtras' => __('This article list has more than one extra-streamer', 'arlima'),
                    'changesBeforeRemove' => __('You have made changes to this list, do you want to remove it anyway?','arlima'),
                    'wantToRemove' => __('Do you want to remove "', 'arlima'),
                    'fromList' => __('" from this article list?', 'arlima'),
                    'chooseImage' => __('Choose image', 'arlima'),
                    'adminLock' => __('This article is locked by admin', 'arlima'),
                    'adminOnly' => __('Only administrators can manage article locks', 'arlima'),
                    'noList' => __('No list is active!', 'arlima'),
                    'noImages' => __('This article has no related image', 'arlima'),
                    'noConnection' => __('this article is not connected to any post', 'arlima'),
                    'listRemoved' => __('This list have been removed!', 'arlima'),
                    'savePreview' => __('to save article list', 'arlima'),
                    'isSaved' => __('This list has no unsaved changes', 'arlima'),
                    'missingPreviewPage' => __('This list is not yet related to any page', 'arlima'),
                    'hasUnsavedChanges' => __('This list has unsaved changes', 'arlima'),
                    'dragAndDrop' => __('Drag images to this container', 'arlima'),
                    'scheduled' => __('Scheduled', 'arlima'),
                    'willReload' => __('Will reload in', 'arlima'),
                    'loggedOut' => __('Your login session seems to have expired, pls reload the page!', 'arlima'),
                    'notValidColor' => __('Not a valid color!', 'arlima'),
                    'invalidURL' => __('This URL seems to be invalid', 'arlima'),
                    'nothingFound' => __('Nothing found...', 'arlima'),
                    'reloadLists' => __('Reload all lists', 'arlima'),
                    'reload' => __('Reload list', 'arlima'),
                    'publish' => __('Publish list', 'arlima'),
                    'preview' => __('Preview list', 'arlima'),
                    'future' => __('Future post', 'arlima'),
                    'insertImage' => __('Add to article', 'arlima'),
                    'noPostsFound' => __('No posts found', 'arlima'),
                    'onlyImages' => __('You can only add images to articles', 'arlima'),
                    'unknown' => __('Unknown'),
                    'deleteVersion' => __('Delete this version', 'arlima'),
                    'confirmDeleteVersion' => __('Are you sure you want to delete this version?', 'arlima'),
                    'saveFutureVersion' => __('Save future version', 'arlima'),
                    'publishedVersions' => __('Published versions', 'arlima'),
                    'scheduledVersions' => __('Future versions', 'arlima'),
                    'toPublish' => __('To publish', 'arlima'),
                    'seconds' => __('seconds', 'arlima'),
                    'updatedBy' => __('Updated by', 'arlima'),
                    'yes' => __('Yes'),
                    'no' => __('No'),
                )
            )
        );
    }

    /**
     * Loads the view of this page
     */
    function loadPage()
    {
        ?>
        <div class="wrap arlima <?php echo $this->slug() ?>">
            <h2 class="arlima-page-title">
                <img src="<?php echo ARLIMA_PLUGIN_URL.'/images/logo.png' ?>" width="142" alt="Arlima" />
                <?php if($this->slug() != Arlima_WP_Page_Main::PAGE_SLUG): ?>
                    <span>| <?php echo $this->getMenuName() ?></span>
                <?php endif; ?>
            </h2>
            <?php require ARLIMA_PLUGIN_PATH . '/pages/' . str_replace('arlima-', '', $this->slug()) . '.php'; ?>
        </div>
        <?php
    }
}