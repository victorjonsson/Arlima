<?php
/*
Plugin Name: Arlima (article list manager)
Plugin URI: https://github.com/victorjonsson/Arlima
Description: Manage the order of posts on your front page, or any page you want. This is a plugin suitable for online newspapers that's in need of a fully customizable front page.
Author: VK (<a href="http://twitter.com/chredd">@chredd</a>, <a href="http://twitter.com/znoid">@znoid</a>, <a href="http://twitter.com/victor_jonsson">@victor_jonsson</a>, <a href="http://twitter.com/lefalque">@lefalque</a>)
Version: 2.8.6
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Load arlima constants
require_once 'constants.php';

// Load arlima plugin class
require_once 'classes/Plugin.php';

// Register class loader for this plugin
spl_autoload_register('Arlima_Plugin::classLoader');

// Instance plugin helper
$arlima_plugin = new Arlima_Plugin();


if( is_admin() ) {

    // Register install/uninstall procedures
    register_activation_hook(__FILE__, 'Arlima_Plugin::install');
    register_deactivation_hook(__FILE__, 'Arlima_Plugin::deactivate');
    register_uninstall_hook(__FILE__, 'Arlima_Plugin::uninstall');

    // Add actions and filters used in wp-admin
    $arlima_plugin->initAdminActions();
}
else {

    // Add actions and filters used in the theme
    $arlima_plugin->initThemeActions();
}



/* * * * * * * * * * * ARLIMA PUBLIC API * * * * * * * * * * * * * * * * *

  Plugin setup finished. The functions declared from here on is
  meant to be used in the theme when writing template files
  that uses Arlima.

*/



/**
 * Replaces the entry-word span (tinymce plugin) with a link
 * @param string $content
 * @param string $url
 * @param bool|string $target
 * @param string $extra_classes
 * @param int &$found Whether or not an entry word was linked in given $content
 * @return string
 */
function arlima_link_entrywords( $content, $url, $target=false, $extra_classes='', &$found=null) {
    $pattern = '/(<span)(.*class=\".*teaser-entryword.*\")>(.*)(<\/span>)/isxmU';
    return preg_replace(
                $pattern,
                '<a href="'.$url.'" '.($target ? ' target="'.$target.'"':'').'class="teaser-entryword'.$extra_classes.'">$3</a>',
                $content,
                -1,
                $found
            );
}


/**
 * @param string $type - Deprecated
 * @param bool|int $post_id - Optional
 * @return array|bool
 */
function arlima_related_posts( $type = 'deprecated', $post_id = false) {
    global $post;
    $data = false;
    if( Arlima_Plugin::isWPRelatedPostsInstalled() && ($post_id || is_object($post)) ) {
        if(!$post_id)
            $post_id = $post->ID;

        $related_posts = MRP_get_related_posts($post_id, true);
        if( !empty($related_posts) ) {
            $data = array('single' => count($related_posts) == 1, 'posts'=>array());
            foreach ( $related_posts as $related ) {
                $related->url = get_permalink ( $related->ID );
                $related->html_comment_stats = 0;
                $stats = wp_count_comments($post_id);
                if( is_object($stats) && property_exists($stats, 'approved') )
                    $related->html_comment_stats = $stats->approved;

                $data['posts'][] = $related;
            }
        }
    }

    return $data;
}

/**
 * Wrapper function for Arlima_PostSearchModifier::modifySearch. May be used by the theme to modify the post
 * search in the article editor
 * @param Closure $form_callback
 * @param Closure $query_callback
 * @param $deprecated
 */
function arlima_modify_post_search($form_callback, $query_callback, $deprecated=false) {
    Arlima_PostSearchModifier::modifySearch($form_callback, $query_callback);
    if( $deprecated !== false ) {
        Arlima_Plugin::warnAboutUseOfDeprecatedFunction('arlima_modify_post_search', 2.22, 'Function arguments have changed');
    }
}

/**
 * Tells whether or not current request is requesting an arlima preview
 * @return bool
 */
function arlima_is_preview() {
    static $is_arlima_preview = null;
    if( $is_arlima_preview === null ) {
        $is_arlima_preview =  isset( $_GET[Arlima_List::QUERY_ARG_PREVIEW] ) &&
                               // has_arlima_list() &&
                               // get_arlima_list()->id() == $_GET[Arlima_List::QUERY_ARG_PREVIEW] &&
                                is_user_logged_in();
    }
    return $is_arlima_preview;
}

/**
 * This function makes it possible to add formats (class names) that will be possible
 * to choose for your arlima articles. The format class will be added to the div
 * containing the article using the template variable ${container.class}
 *
 * @example
 *  arlima_register_format('my-custom-format', 'Cool looking article', array('giant', 'my-other-template'));
 *  arlima_register_format('my-boring-format', 'Serious looking article');
 *
 * @param string $format_class - The class that will be added to the article container
 * @param string $label - The name of this format, displayed in wp-admin
 * @param array $templates[optional=array()] - This argument tells arlima that this format
 * should only be available for certain templates. The array should contain only the names of
 * the templates where the format should be available, without path and extension. Omit this
 * argument if you want your format to be available on all templates
 */
function arlima_register_format($format_class, $label, $templates=array()) {
    Arlima_ArticleFormat::add($format_class, $label, $templates);
}

/**
 * Remove a registered format
 * @param string $format_class
 * @param array $templates
 */
function arlima_deregister_format($format_class, $templates=array()) {
    Arlima_ArticleFormat::remove($format_class, $templates);
}


/**
 * Function that displays a link to wp-admin where given arlima
 * list can be edited
 * @param Arlima_List|bool $list
 * @param string|bool $message
 * @return void
 */
function arlima_edit_link($list=false, $message=false) {
    if( !($list instanceof Arlima_List) ) {
        $list = arlima_get_list();
    }

    if( !$list ) {
        trigger_error('Trying to get edit link for list that does not exist', E_USER_WARNING);
        return;
    }

    if( is_user_logged_in() && current_user_can('edit_posts') ) {
        if( !$message ) {
            Arlima_Plugin::loadTextDomain();
            $message = __('Edit article list', 'arlima').' &quot;'.$list->getTitle().'&quot;';
        }
        ?>
        <div class="arlima-edit-list admin-tool">
            <a href="<?php echo admin_url('admin.php?page=arlima-main&open_list='.$list->id()) ?>" target="_arlima">
                <?php echo $message ?>
            </a>
        </div>
        <?php
    }
}

/**
 * Template function that tells whether or not we're currently on page
 * that has a related article list
 * @return bool
 */
function arlima_has_list() {
    global $post;
    $list_data = arlima_get_list(false);
    return $post && $list_data['post'] == $post->ID;
}

/**
 * Get arlima list of currently visited page
 * @param bool $list_only
 * @return Arlima_List|array|bool
 */
function arlima_get_list($list_only = true) {
    static $current_arlima_list = null;
    if( $current_arlima_list === null ) {
        $current_arlima_list = array('list'=>false, 'post'=>false);
        if( is_page() ) {
            global $wp_query;
            $connector = new Arlima_ListConnector();
            $relation = $connector->getRelationData($wp_query->post->ID);
            if( $relation !== false ) {
                $list_factory = new Arlima_ListFactory();
                $relation = $connector->getRelationData($wp_query->post->ID);
                $is_requesting_preview = arlima_is_preview() && $_GET[Arlima_List::QUERY_ARG_PREVIEW] == $relation['id'];
                $version = $is_requesting_preview ? 'preview' : '';
                $list = $list_factory->loadList($relation['id'], $version, $is_requesting_preview);
                if( $list->exists() ) {
                    $current_arlima_list = array('list'=>$list, 'post'=>$wp_query->post->ID);
                }
            }
        }
    }
    return $list_only ? $current_arlima_list['list'] : $current_arlima_list;
}

/**
 * Load an arlima list
 * @param int|string $id_or_slug Either list id, list slug or URL to external list or RSS-feed
 * @param mixed $version Omit this argument, or set it to false, if you want to load the latest published version of the list. This argument won't have any effect if you're loading an external list/feed
 * @param bool $include_future_post Whether or not the list should include future posts. This argument won't have any effect if you're loading an external list/feed
 * @return Arlima_List
 */
function arlima_load_list($id_or_slug, $version=false, $include_future_post=false) {
    $factory = new Arlima_ListFactory();
    return $factory->loadList($id_or_slug, $version, $include_future_post);
}

/**
 * Render an article list
 * @see https://github.com/victorjonsson/Arlima/wiki/Programmatically-insert-lists
 * @param Arlima_List|Arlima_AbstractListRenderingManager|int|string $list
 * @param array $args
 * @return string|bool
 */
function arlima_render_list($list, $args=array()) {

    $args = array_merge(array(
                'width' => 560,
                'offset' => 0,
                'limit' => 0,
                'echo' => true,
                'filter_suffix' => '',
                'section' => false,
                'no_list_message' => true // True meaning the the message will be displayed
            ), $args);

    $factory = new Arlima_ListFactory();

    if( is_numeric($list) || is_string($list) ) {
        $renderer = new Arlima_ListTemplateRenderer( $factory->loadList($list) );
    } elseif( $list instanceof Arlima_AbstractListRenderingManager ) {
        $renderer = $list;
    } else {
        $renderer = new Arlima_ListTemplateRenderer($list);
    }

    $list_exist = $renderer->getList()->exists();

    if( !$list_exist && $args['no_list_message'] ) {
        $msg = '<p>'.__('This list does not exist', 'arlima').'</p>';
        if( $args['echo'] )
            echo $msg;
        else
            return $msg;
    }
    elseif( $list_exist ) {

        $renderer->setOffset( $args['offset'] );
        $renderer->setLimit( $args['limit'] );
        $renderer->setSection( $args['section'] );

        if( $renderer->havePosts() ) {
            
            $action_suffix = '';
            if( !empty($args['filter_suffix']) ) {
                Arlima_FilterApplier::setFilterSuffix($args['filter_suffix']);
                $action_suffix = '-'.$args['filter_suffix'];
            }

            do_action('arlima_list_begin'.$action_suffix, $renderer, $args);


            Arlima_FilterApplier::setArticleWidth($args['width']);
            Arlima_FilterApplier::applyFilters($renderer);

            $content = $renderer->renderList($args['echo']);
            Arlima_FilterApplier::setFilterSuffix('');

            do_action('arlima_list_end'.$action_suffix, $renderer, $args);

            if( $args['echo'] ) {
                return true;
            } else {
                return apply_filters('arlima_list_content', $content, $renderer);
            }
        }
    }

    return false;
}

/**
 * @param array $default
 * @return array|bool
 */
function arlima_file_args($default) {
    if( Arlima_FileInclude::isCollectingArgs() ) {
        Arlima_FileInclude::setCollectedArgs($default);
        return false;
    } else {
        return array_merge($default, Arlima_FileInclude::currentFileArgs());
    }
}

/**
 * Include arlima file outside of arlima list.
 * @param  string $file
 * @param  array  $args
 * @return string
 */
function arlima_include_file($file, $args = array()) {
    $file_include = new Arlima_FileInclude();
    return $file_include->includeFile($file, $args);
}

/* * * * * * * * * * * * DEPRECATED FUNCTIONS * * * * * * * * * * * * * */


/**
 * @deprecated
 * @see arlima_has_list()
 * @return bool
 */
function has_arlima_list() {
    Arlima_Plugin::warnAboutUseOfDeprecatedFunction('has_arlima_list()', 2.8, 'arlima_has_list()');
    return arlima_has_list();
}

/**
 * @deprecated
 * @return bool
 */
function is_arlima_preview() {
    Arlima_Plugin::warnAboutUseOfDeprecatedFunction('is_arlima_preview()', 2.8, 'arlima_is_preview()');
    return arlima_is_preview();
}

/**
 * @deprecated
 * @see arlima_get_list()
 * @param bool $list_only
 */
function get_arlima_list($list_only = true) {
    Arlima_Plugin::warnAboutUseOfDeprecatedFunction('get_arlima_list()', 2.8, 'arlima_get_list()');
    return arlima_get_list($list_only);
}

/**
 * Returns a readable string of the version data
 * @deprecated
 * @see Arlima_List::getVersionInfo
 * @param array $version
 * @return string
 */
function arlima_get_version_info( $version ) {
    Arlima_Plugin::warnAboutUseOfDeprecatedFunction('arlima_get_version_info()', 2.0, 'Arlima_List::getVersionInfo()');
    if( isset($version[ 'id' ]))
        return $version[ 'id' ];
    else
        return '';
}

/**
 * Tells whether or not a preview of the list with given slug is requested
 * @deprecated
 * @param $list_id
 * @return bool
 */
function arlima_requesting_preview($list_id) {
    Arlima_Plugin::warnAboutUseOfDeprecatedFunction('arlima_requesting_preview()', 2.5, 'Function is removed');
    return arlima_is_preview() && $_GET[Arlima_List::QUERY_ARG_PREVIEW] == $list_id;
}

/**
 * @deprecated
 */
function arlima_is_requesting_preview() {
    return !empty($_GET[Arlima_List::QUERY_ARG_PREVIEW]) && is_user_logged_in();
}

/**
 * @deprecated
 * @return string
 */
function arlima_preview_url() {
    Arlima_Plugin::warnAboutUseOfDeprecatedFunction('arlima_preview_url()', 2.4, 'Function is removed');
}