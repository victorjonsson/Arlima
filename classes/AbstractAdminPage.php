<?php

/**
 * @since 1.0
 * @package Aurora
 */
abstract class Arlima_AbstractAdminPage {

    /**
     * @var Arlima_Plugin
     */
    protected $plugin;


    /**
     * @param Arlima_Plugin $arlima_plugin
     */
    function __construct($arlima_plugin)
    {
        $this->plugin = $arlima_plugin;
    }

    function styleSheets()
    {
        return array();
    }

    function scripts()
    {
        return array();
    }

    abstract function getName();

    abstract function getMenuName();

    abstract function slug();

    abstract function parentSlug();

    function capability()
    {
        return 'edit_posts';
    }

    /**
     * Only used when !$this->isSubPage()
     * @return string
     */
    public function icon() {
        return '';
    }

    final public function registerPage() {
        $page_ref = false;
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
            add_action('admin_print_scripts-' . $page_ref, array($this, 'enqueueScripts'));
            add_action('admin_print_styles-' . $page_ref, array($this, 'enqueueStyles'));
        }
    }

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

    public function enqueueStyles()
    {
        foreach($this->styleSheets() as $handle => $data) {
            wp_enqueue_style($handle, $data['url'], $data['deps'], ARLIMA_FILE_VERSION, false);
        }
    }

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
                    'missingPreviewPage' => __('This list is not yet related to any page', 'arlima'),
                    'hasUnsavedChanges' => __('This list has unsaved changes', 'arlima'),
                    'dragAndDrop' => __('Drag images to this container', 'arlima'),
                    'sticky' => __('Sticky', 'arlima'),
                    'loggedOut' => __('Your login session seems to have expired, pls reload the page!', 'arlima')
                )
            )
        );
    }

    function loadPage() {
        ?>
        <div class="wrap">
            <div id="icon-plugins" class="icon32"></div>
            <h2><?php echo $this->getName() ?></h2>
            <?php require ARLIMA_PLUGIN_PATH . '/pages/' . str_replace('arlima-', '', $this->slug()) . '.php'; ?>
        </div>
        <?php
    }
}