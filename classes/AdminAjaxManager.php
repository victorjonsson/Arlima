<?php

/**
 * Class that has all wp ajax functions used by this plugin. Important that you don't use closures
 * or any other php features that isn't available in php 5.2 in this file
 *
 * @todo: Remove all muting of possibly incorrect ajax requests
 * @todo: Move HTML code to other files
 * @package Arlima
 * @since 2.0
 */
class Arlima_AdminAjaxManager
{

    /**
     * @var Arlima_Plugin
     */
    private $arlima_plugin;

    /**
     * @var bool
     */
    private $has_preamble_func;

    /**
     * @var bool
     */

    /**
     * @param Arlima_Plugin $arlima_plugin
     */
    public function __construct($arlima_plugin)
    {
        $this->arlima_plugin = $arlima_plugin;
        $this->has_preamble_func = function_exists('vk_get_preamble');
    }

    /**
     * Setup all ajax functions
     */
    public function initActions()
    {
        add_action('wp_ajax_arlima_query_posts', array($this, 'queryPosts'));
        add_action('wp_ajax_arlima_get_scissors', array($this, 'getScissors'));
        add_action('wp_ajax_arlima_get_attached_images', array($this, 'getAttachedImages'));
        add_action('wp_ajax_arlima_get_post', array($this, 'getPost'));
        add_action('wp_ajax_arlima_add_list_widget', array($this, 'loadListData'));
        add_action('wp_ajax_arlima_check_for_later_version', array($this, 'checkForLaterVersion'));
        add_action('wp_ajax_arlima_save_list', array($this, 'saveList'));
        add_action('wp_ajax_arlima_prepend_article', array($this, 'prependArticle'));
        add_action('wp_ajax_arlima_save_list_setup', array($this, 'saveListSetup'));
        add_action('wp_ajax_arlima_get_list_setup', array($this, 'getListSetup'));
        add_action('wp_ajax_arlima_save_image', array($this, 'saveImage'));
        add_action('wp_ajax_arlima_save_external_img', array($this, 'saveExternalImage'));
        add_action('wp_ajax_arlima_print_custom_templates', array($this, 'printCustomTemplates'));
        add_action('wp_ajax_arlima_duplicate_image', array($this, 'duplicateImage'));
        add_action('wp_ajax_arlima_import_arlima_list', array($this, 'importList'));
        add_action('wp_ajax_arlima_remove_image_versions', array($this, 'removeImageVersions'));
        add_action('wp_ajax_arlima_update_article', array($this, 'updateArticle'));
        add_action('wp_ajax_arlima_connect_attach_to_post', array($this, 'connectAttachmentToPost'));

        // The following action is not possible to hook into wtf???
        // add_action('wp_ajax_image-editor', array($this, 'removeImageVersions'));
        if ( $this->isSavingEditedImage() ) {
            add_action('init', array($this, 'removeImageVersions'));
        }
    }

    public function updateArticle()
    {
        $this->initAjaxRequest();
        if( empty($_POST['ala_id']) ) {
            die(json_encode(array('error'=>'No ala_id given')));
        }
        elseif( empty($_POST['update']) ) {
            die(json_encode(array('error'=>'Nothing to update')));
        }
        else {
            try {
                $factory = new Arlima_ListFactory();
                $factory->updateArticle($_POST['ala_id'], $_POST['update']);
                die(json_encode(array('success'=>1)));
            } catch(Exception $e) {
                die(json_encode(array('error'=>$e->getMessage())));
            }
        }
    }

    /**
     * Tells whether or not this request is made to save an edited image.
     * This is a workaround for add_action('wp_ajax_image-editor', ...)
     * @return bool
     */
    private function isSavingEditedImage()
    {
        return isset($_POST['action']) &&
                isset($_POST['postid']) &&
                isset($_POST['do']) &&
                isset($_POST['context']) &&
                $_POST['action'] == 'image-editor' &&
                $_POST['do'] == 'save' &&
                $_POST['context'] == 'edit-attachment' &&
                basename($_SERVER['PHP_SELF']) == 'admin-ajax.php';
    }

    /**
     * Removes all arlima image versions (nothing will happen if WP version < 3.5)
     */
    public function removeImageVersions()
    {
        // Arlima admin request
        if( !empty($_POST['attachment']) ) {
            $this->initAjaxRequest();
            Arlima_ImageVersionManager::removeVersions($_POST['attachment']);
            die( json_encode(array('success'=>true)));
        }

        // image editor in wp-admin
        elseif( !empty($_POST['postid']) && is_user_logged_in() ) {
            Arlima_ImageVersionManager::removeVersions($_POST['postid']);
        }
        else {
            die( json_encode(array('error'=>'No attachment given')) );
        }
    }

    /**
     * Import an external arlima list or RSS feed
     */
    function importList()
    {
        $this->initAjaxRequest(false);

        $import_manager = new Arlima_ImportManager($this->arlima_plugin);
        try {
            $list = $import_manager->importList($_POST['url']);
            Arlima_ImportManager::displayImportedList($_POST['url'], $list['title']);
        } catch (Exception $e) {
            echo '<div class="error">' . $e->getMessage() . '</div>';
        }
        die;
    }

    /**
     * Make copy of an wordpress attachment
     */
    function duplicateImage()
    {
        $this->initAjaxRequest();
        try {
            $attachment_id = intval($_POST['attachment']);
            if ( $attachment_id ) {
                $file = get_post_meta( $attachment_id, '_wp_attached_file', true );
                if( $file ) {
                    $tmp_file = get_temp_dir().'/'.uniqid();
                    copy(WP_CONTENT_DIR .'/uploads/'. $file, $tmp_file);
                    $new_attach_id = Arlima_Plugin::saveImageFileAsAttachment($tmp_file, basename($file), '');
                    list($attach_url) = wp_get_attachment_image_src($new_attach_id, 'default');

                    echo json_encode(
                        array(
                            'attach_id' => $new_attach_id,
                            'attach_url' => $attach_url,
                            'error' => false
                        )
                    );
                }
            } else {
                throw new Exception('File does not exist');
            }

        } catch(Exception $e) {
            echo json_encode(
                array(
                    'attach_id' => -1,
                    'error' => $e->getMessage()
                )
            );
        }

        die;
    }

    /**
     * Get arlima templates
     */
    function printCustomTemplates()
    {
        $templates = array(
            array(
                'name' => __('Example article', 'arlima'),
                'title' => 'Lorem te ipusm dolor sit amet',
                'text' => '<p><span class="teaser-entryword">Lorem te ipsum</span> dolor sit amet anno del torro.</p>',
                'url' => 'http://google.com/',
                'title_fontsize' => 20,
                'options' => array(
                    'streamerType' => 'text',
                    'streamerColor' => '3399ff', // Important, no #-sign
                    'streamerImage' => '',
                    'streamerContent' => 'Wild thing!',
                    'preTitle' => 'Demo:',
                    'overridingURL' => 'http://google.de/'
                )
            ),
            array(
                'name' => __('Empty article', 'arlima'),
                'title' => '',
                'text' => '',
                'url' => ''
            )
        );

        // Make it possible for theme to override templates
        $templates = apply_filters('arlima_teaser_templates', $templates);
        foreach($templates as $key => $data) {
            $templates[$key] = Arlima_ListFactory::createArticleDataArray($data);
        }

        echo json_encode($templates);
        die();
    }

    /**
     * Check logged in and correct nonce
     */
    private function initAjaxRequest($send_json=true)
    {
        if( $send_json ) {
            header('Content-Type: application/json');
        }

        if( !check_ajax_referer('arlima-nonce') ) {
            die(json_encode(array('error' => 'incorrect nonce')));
        } elseif( !is_user_logged_in() ) {
            die(json_encode(array('error' => 'not logged in')));
        }
    }

    function saveImage() {
        $this->initAjaxRequest();

        try {
            $id = Arlima_Plugin::saveImageAsAttachment(
                        $_POST['image'],
                        $_POST['name'],
                        empty($_POST['postid']) ? '':$_POST['postid']
                    );

            die(json_encode(array('attachment'=>$id, 'url'=>current(wp_get_attachment_image_src($id, 'full')))));

        } catch(Exception $e) {
            die(json_encode(array('error' =>$e->getMessage())));
        }
    }

    /**
     * Side load an external image and attach it to post
     */
    function saveExternalImage()
    {
        $this->initAjaxRequest();

        if ( !function_exists('wp_generate_attachment_metadata') ) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
        }

        $post_id = intval($_POST['postid']);

        if ( $post_id ) {

            media_sideload_image(urldecode($_POST['imgurl']), $post_id, '');

            $attachments = get_posts(
                array(
                    'post_type' => 'attachment',
                    'number_posts' => 1,
                    'post_status' => null,
                    'post_parent' => $post_id,
                    'orderby' => 'post_date',
                    'order' => 'DESC',
                )
            );

            $attach_id = $attachments[0]->ID;

        } else {
            $url = current(explode('?', $_POST[ 'imgurl' ]));
            $tmp = download_url( $url );
            $file_array = array(
                'name' => basename( $url ),
                'tmp_name' => $tmp
            );

            /* @var WP_Error|string $tmp */
            // Check for download errors
            if ( is_wp_error( $tmp ) ) {
                @unlink( $file_array[ 'tmp_name' ] );
                die( json_encode( array( 'error' => $tmp->get_error_messages() ) ) );
            }

            $attach_id = media_handle_sideload( $file_array, 0 );

            // Check for handle sideload errors.
            if ( is_wp_error( $attach_id ) ) {
                @unlink( $file_array['tmp_name'] );
                die( json_encode( array( 'error' => $attach_id->get_error_messages() ) ) );
            }

        }


        if ( empty($attach_id) ) {
            die(json_encode(array('error' => 'no attach_id')));
        }

        echo json_encode(
            array(
                'attachment' => $attach_id,
                'url' => current(wp_get_attachment_image_src($attach_id, 'default'))
            )
        );
        die();
    }

    /**
     * Get the list setup for currently logged in user
     */
    function getListSetup()
    {
        $this->initAjaxRequest();

        global $current_user;
        get_currentuserinfo();

        $setup = get_user_meta($current_user->ID, 'arlima-list-setup', true);
        if ( !$setup ) {
            $setup = array();
        }
        die(json_encode($setup));
    }

    /**
     * Saves the user setup (lists to load on startup and their position and size)
     */
    function saveListSetup()
    {
        $this->initAjaxRequest();

        global $current_user;
        get_currentuserinfo();

        $lists = isset($_POST['lists']) ? $_POST['lists'] : null;

        if ( $lists ) {
            update_user_meta($current_user->ID, 'arlima-list-setup', $lists);
        } else {
            delete_user_meta($current_user->ID, 'arlima-list-setup');
        }

        die(json_encode(array()));
    }

    /**
     * Prepend an article to the top of a list
     */
    function prependArticle()
    {
        $this->initAjaxRequest();

        global $current_user, $post;
        get_currentuserinfo();

        $list_id = isset($_POST['alid']) ? intval($_POST['alid']) : false;
        $post_id = isset($_POST['postid']) ? intval($_POST['postid']) : false;

        if ( $list_id && $post_id ) {

            $post = get_post($post_id);
            setup_postdata($post);
            $GLOBALS['post'] = $post; // Soemhting is removing post from global, even though we call setup_postdata

            $list = $this->loadListFactory()->loadList($list_id, false, true);
            $articles = $list->getArticles();
            array_unshift($articles, $this->postToArlimaArticle($post));

            $this->saveAndOutputList($list, $articles);
        }
        die(json_encode(array()));
    }

    /**
     * @return Arlima_ListFactory
     */
    private function loadListFactory() {
        return new Arlima_ListFactory();
    }

    /**
     * Save a new version of a list
     */
    function saveList()
    {
        $this->initAjaxRequest();

        $list_id = isset($_POST['alid']) ? intval($_POST['alid']) : false;

        if ( $list_id ) {
            $articles = !empty($_POST['articles']) ? $_POST['articles'] : array();
            $this->saveAndOutputList($list_id, $articles, isset($_POST['preview']));
        }

        die;
    }

    /**
     * @param Arlima_List|int $list_id
     * @param $articles
     * @param bool $preview
     */
    private function saveAndOutputList($list_id, $articles, $preview = false)
    {
        $list_factory = $this->loadListFactory();

        if ( $list_id instanceof Arlima_List ) {
            $list = $list_id;
        } else {
            $list = $list_factory->loadList($list_id);
        }

        $list_factory->saveNewListVersion($list, $articles, get_current_user_id(), $preview);

        // Reload list to get latest version
        $list = $list_factory->loadList($list_id, false, true);

        $this->outputListData($list);
    }

    /**
     * Checks if there is a later version of the list that's about to be saved
     */
    function checkForLaterVersion()
    {
        $this->initAjaxRequest();

        $list_id = isset($_POST['alid']) ? (int)$_POST['alid'] : false;
        $version = isset($_POST['version']) ? (int)$_POST['version'] : false;

        if ( $list_id && $version ) {
            $list = $this->loadListFactory()->loadList($list_id);
            if ( $list->getVersionAttribute('id') > $version ) {
                echo json_encode(
                    array(
                        'version' => $list->getVersion(),
                        'versioninfo' => $list->getVersionInfo()
                    )
                );
                die;
            }
        }

        echo json_encode(array('version' => false));
        die;
    }

    /**
     * Fetches an arlima list and outputs it in widget form
     */
    function loadListData()
    {

        $this->initAjaxRequest();

        $list_id = isset($_POST['alid']) ? trim($_POST['alid']) : null;
        $version = isset($_POST['version']) && is_numeric($_POST['version']) ? (int)$_POST['version'] : false;

        if ( is_numeric($list_id) ) {
            $list = $this->loadListFactory()->loadList($list_id, $version, true);
            $this->outputListData($list);
        } elseif ( $list_id ) {
            // Probably url referring to an imported list
            try {
                $import_manager = new Arlima_ImportManager($this->arlima_plugin);
                $list = $import_manager->loadList($list_id);
                echo $this->listToJSON($list, '', 0);
            } catch (Exception $e) {
                header('HTTP/1.1 500 Internal Server Error');
                echo json_encode(array('error' => $e->getMessage()));
            }
        }

        die();
    }

    /**
     * Outputs a list in json format
     * @param Arlima_List $list
     */
    private function outputListData($list)
    {
        $connector = new Arlima_ListConnector($list);
        $preview_page = current($connector->loadRelatedPages());
        $preview_url = '';
        $preview_width = '';

        // Get article width from a related page
        if( $preview_page ) {
            $preview_url = get_permalink($preview_page->ID);
            $relation = $connector->getRelationData($preview_page->ID);
            $preview_width = $relation['attr']['width'];
        }

        // Get article width form a widget where the list is used
        elseif( $widget = current($connector->loadRelatedWidgets()) ) {
            $preview_width = $widget['width'];
        }

        if( empty($preview_url) ) {
            $preview_url = apply_filters('arlima_preview_url', '', $list);
        }

        $preview_width = apply_filters('arlima_tmpl_width', $preview_width, $list);

        echo $this->listToJSON($list, $preview_url, $preview_width);
    }

    /**
     * @param WP_Post $post
     * @return bool|mixed|void
     */
    private function setupPostObject($post) {
        if( is_object($post) && ($post->post_status == 'future' || $post->post_status == 'publish' || $post->post_status == 'draft') ) {
            $post->url = get_permalink($post->ID);
            $post->published = Arlima_Utils::getPostTimeStamp($post);
            $post->display_date = $post->post_date;
            $post->display_author = get_the_author_meta('display_name', $post->post_author);
            $post->edit_url = get_edit_post_link($post->ID);
            return apply_filters('arlima_wp_post', $post);
        }
        return false;
    }

    /**
     * Get wordpress posts in json format
     */
    function getPost()
    {
        $this->initAjaxRequest();

        if( strpos($_POST['postid'], ',') !== false) {
            $posts = array();
            foreach(explode(',', $_POST['postid']) as $id) {
                if( $p = $this->setupPostObject(get_post($id)) ) {
                    $posts[$p->ID] = $p;
                }
            }
            die(json_encode(array('posts'=>$posts)));
        } else {
            $post_id = intval($_POST['postid']);
            if( $p = $this->setupPostObject(get_post($post_id)) ) {
                die(json_encode(array('posts' => array($p->ID => (array)$p))));
            }
        }

        die(json_encode(array()));
    }

    /**
     * Get post attachments
     */
    function getAttachedImages()
    {
        $this->initAjaxRequest();

        $post_id = intval($_POST['postid']);

        $args = array(
            'order' => 'DESC',
            'post_parent' => $post_id,
            'post_status' => null,
            'post_type' => 'attachment',
            'post_mime_type' => 'image'
        );

        $images = array();

        $attachments = get_children($args);

        if ( $attachments ) {
            foreach ($attachments as $attachment) {
                $images[] = array(
                    'attachment' => $attachment->ID,
                    'thumb' => wp_get_attachment_image($attachment->ID, 'thumbnail'),
                    'url' => wp_get_attachment_image_src($attachment->ID, 'large')
                );
            }
        }

        echo json_encode($images);
        die;
    }

    /**
     * Connect wordpress attachment to post
     */
    function connectAttachmentToPost()
    {
        $this->initAjaxRequest();

        if( empty($_POST['attachment']) ) {
            die(json_encode( array('error'=>'Argument attachment missing') ));
        }
        if( empty($_POST['post']) || !is_numeric($_POST['post']) || !$_POST['post'] ) {
            die(json_encode( array('error'=>'Argument post_id missing') ));
        }

        $attach = get_post($_POST['attachment']);
        if( !$attach || $attach->post_type != 'attachment' ) {
            die(json_encode( array('error'=>'Argument attachment is not referring to an attachment') ));
        }

        wp_update_post(array(
            'ID' => $attach->ID,
            'post_parent' => $_POST['post']
        ));

        echo json_encode(array('success'=>1));
        die;
    }

    function getScissors()
    {
        $this->initAjaxRequest();

        $attachment_id = $_POST['attachment'];

        if ( Arlima_Plugin::isScissorsInstalled() ) {

            $scissors_output = '';
            $thumb = get_post($attachment_id);
            $scissors_output = scissors_media_meta($scissors_output, $thumb);

            if ( !empty($scissors_output) ) {
                echo $scissors_output;
            }
        }
        die(json_encode(array()));
    }

    /**
     * Search for posts
     */
    function queryPosts()
    {
        $this->initAjaxRequest();

        $catid = !empty($_POST['catid']) ? $_POST['catid'] : false;
        $search = !empty($_POST['search']) ? $_POST['search'] : false;
        $author = !empty($_POST['author']) ? $_POST['author'] : false;
        $offset = !empty($_POST['offset']) && is_numeric($_POST['offset']) ? (int)$_POST['offset'] : 0;

        if ( $catid ) {
            $args['cat'] = $catid;
        }
        if ( $author ) {
            $args['author'] = $author;
        }

        $args['s'] = '';
        if ( $search ) {
            if ( is_numeric($search) ) {
                $args['p'] = $search;
            } else {
                $args['s'] = $search;
            }
        }

        $args['numberposts'] = 10;
        if ( $offset ) {
            $args['offset'] = $offset;
        }

        $args['post_status'] = array('publish', 'future');
        $args['post_type'] = apply_filters('arlima_search_post_types', array('post', 'page'));

        // Possibly modified by other plugins or the theme (take a look at readme.txt for more info)
        $args = Arlima_PostSearchModifier::filterWPQuery($args, $_POST);

        $this->iteratePosts(query_posts($args), $offset);

        die();
    }

    /**
     * @param array|WP_Post[] $posts
     * @param int $offset
     */
    private function iteratePosts($posts, $offset = 0)
    {
        $articles = array();
        foreach ($posts as $post) {

            setup_postdata($post);
            $GLOBALS['post'] = $post; // Soemhting is removing post from global, even though we call setup_postdata

            $articles[] = array(
                'data' => $this->postToArlimaArticle($post),
                'post' => $this->setupPostObject($post)
            );
        }

        echo json_encode(array(
            'articles' => $articles
        ));
    }

    /**
     * @param $post
     * @return array
     */
    private function postToArlimaArticle($post)
    {
        if ( $this->has_preamble_func ) {
            $text = vk_get_preamble();
        } else {
            // weird, "get_the_excerpt" should return the manual excerpt but does not seem to do this in the admin context
            $text = !empty($post->post_excerpt) ? $post->post_excerpt : get_the_excerpt();
        }
        if ( stristr($text, '<p>') === false ) {
            $text = '<p>' . $text . '</p>';
        }
        return Arlima_ListFactory::postToArlimaArticle($post, $text);
    }

    /**
     * @param Arlima_List $list
     * @param string $preview_url
     * @param int $preview_width
     * @return mixed|string|void
     */
    protected function listToJSON($list, $preview_url, $preview_width)
    {
        return json_encode(array(
            'articles' => $list->getArticles(),
            'version' => $list->getVersion(),
            'versionDisplayText' => $list->getVersionInfo(),
            'versions' => $list->getVersions(),
            'titleElement' => $list->getTitleElement(),
            'isImported' => $list->isImported(),
            'exists' => $list->exists(),
            'options' => $list->getOptions(),
            'title' => $list->getTitle(),
            'previewURL' => $preview_url,
            'previewWidth' => (int)$preview_width,
            'id' => $list->id()
        ));
    }
}