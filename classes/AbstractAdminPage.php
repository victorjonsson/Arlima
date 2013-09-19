<?php

/**
 * Base class extended by classes representing admin pages in wordpress.
 *
 * Using this class reduces the amount of code that needs to be written when
 * wanting to have several admin pages in a plugin. It also reduces code
 * duplication that appears when having several admin pages in one plugin.
 *
 * @since 2.7
 * @package Arlima
 */
abstract class Arlima_AbstractAdminPage {

    /**
     * @var Arlima_Plugin
     */
    protected $plugin;


    /**
     * @param Arlima_Plugin $arlima_plugin
     */
    final public function __construct($arlima_plugin)
    {
        $this->plugin = $arlima_plugin;
    }

    /**
     * @param \Arlima_Plugin $plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @return \Arlima_Plugin
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
            $page_ref = add_submenu_page(
                    $this->parentSlug(),
                    $this->getName(),
                    $this->getMenuName(),
                    $this->capability(),
                    $this->slug(),
                    array($this, 'loadPage')
                );
        } else {
            $page_ref = add_menu_page(
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
        foreach($this->styleSheets() as $handle => $data) {
            wp_enqueue_style($handle, $data['url'], $data['deps'], ARLIMA_FILE_VERSION, false);
        }
    }

    /**
     * Enqueue's the scripts returned by Arlima_AbstractAdminPage::scripts()
     */
    public function enqueueScripts()
    {
        wp_enqueue_script('jquery');
        foreach($this->scripts() as $handle => $data) {
            wp_enqueue_script($handle, $data['url'], $data['deps'], ARLIMA_FILE_VERSION, false);
        }

        wp_localize_script(
            'arlima-js',
            'ArlimaJS',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'arlimaNonce' => wp_create_nonce('arlima-nonce'),
                'imageurl' => ARLIMA_PLUGIN_URL . 'images/',
                'baseurl' => get_bloginfo('url'),
                'is_admin' => current_user_can('manage_options') ? 1 : 0,
                'preview_query_arg' => Arlima_List::QUERY_ARG_PREVIEW,
                'lang' => array( // todo: but these args in a separate .js.php file when this array gets to long
                    'unsaved' => __('You have one, or more, unsaved article lists', 'arlima'),
                    'laterVersion' => __('It exists an older version of this article list', 'arlima'),
                    'overWrite' => __('Do you still want to save this version of the list?', 'arlima'),
                    'severalExtras' => __('This article list has more than one extra-streamer', 'arlima'),
                    'changesBeforeRemove' => __('You have made changes to this list, do you want to remove it anyway?','arlima'),
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
                    'missingPreviewPage' => __('This list is not yet related to any page', 'arlima'),
                    'hasUnsavedChanges' => __('This list has unsaved changes', 'arlima'),
                    'dragAndDrop' => __('Drag images to this container', 'arlima'),
                    'sticky' => __('Sticky', 'arlima'),
                    'loggedOut' => __('Your login session seems to have expired, pls reload the page!', 'arlima'),
                    'notValidColor' => __('Not a valid color!', 'arlima'),
                    'invalidURL' => __('This URL seems to be invalid', 'arlima'),
                    'nothingFound' => __('Nothing found...', 'arlima')
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
        <div class="wrap">
            <div id="icon-plugins" class="icon32"></div>
            <h2><?php echo $this->getName() ?></h2>
            <?php require ARLIMA_PLUGIN_PATH . '/pages/' . str_replace('arlima-', '', $this->slug()) . '.php'; ?>
        </div>
        <?php
    }
}