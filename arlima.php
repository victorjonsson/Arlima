<?php
/*
Plugin Name: Arlima (article list manager)
Plugin URI: http://www.vk.se/dev
Description: Manage the order of posts on your front page, or any page you want. This is a plugin suitable for online newspapers that's in need of a fully customizable front page. (Notice! this plugins requires PHP version >= 5.3)
Author: VK (<a href="http://twitter.com/chredd">@chredd</a>, <a href="http://twitter.com/znoid">@znoid</a>, <a href="http://twitter.com/victor_jonsson">@victor_jonsson</a>, <a href="http://twitter.com/lefalque">@lefalque</a>)
Version: 2.4.25
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
    $ajax_manager = new Arlima_AdminAjaxManager($arlima_plugin);
    $ajax_manager->initActions();

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
 * Replaces the entry-word span (tinymce plugin) with a link
 * @param string $content
 * @param string $url
 * @return string
 */
function arlima_link_entrywords( $content, $url ) {
    $pattern = '/(<span)(.*class=\".*teaser-entryword.*\")>(.*)(<\/span>)/isxmU';
    return preg_replace($pattern, '<a href="'.$url.'" class="teaser-entryword">$3</a>' , $content);
}

/**
 * Function that displays a link to wp-admin where
 * given arlima list can be edited
 * @param Arlima_List $list
 * @param string $message
 * @return void
 */
function arlima_edit_link($list, $message=null) {
    if( !$list->isPreview() && is_user_logged_in() && current_user_can('edit_posts') ) {
        if($message === null) {
            Arlima_Plugin::loadTextDomain();
            $message = __('Edit article list', 'arlima').' &quot;'.$list->getTitle().'&quot;';
        }
        ?>
        <div class="arlima-edit-list admin-tool">
            <a href="<?php echo admin_url('admin.php?page=arlima&open_list='.$list->id()) ?>" target="_arlima">
                <?php echo $message ?>
            </a>
        </div>
        <?php
    }
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
                $related->html_comment_stats = get_comment_count($post_id);
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
function arlima_is_requesting_preview() {
    return isset( $_GET[Arlima_List::QUERY_ARG_PREVIEW] ) && !is_admin() && is_user_logged_in();
}

/**
 * Tells whether or not a preview of the list with given slug is requested
 * @param $list_id
 * @return bool
 */
function arlima_requesting_preview($list_id) {
    return arlima_is_requesting_preview() && $_GET[Arlima_List::QUERY_ARG_PREVIEW] == $list_id;
}

/**
 * @deprecated
 * @return string
 */
function arlima_preview_url() {
    Arlima_Plugin::warnAboutUseOfDeprecatedFunction('arlima_preview_url', 2.4, 'Function is removed');
}

/**
 * @param $list_id
 * @param $url
 * @param bool $entity_encode
 * @return string
 */
function arlima_get_preview_url($list_id, $url, $entity_encode = false) {
    $new_url = $url . (strpos($url, '?') === false ? '?':'&') . Arlima_List::QUERY_ARG_PREVIEW . '='. $list_id;
    return $entity_encode ? htmlentities($new_url) : $new_url;
}

/**
 * This function makes it possible to add formats (class names) that will be possible
 * to put on arlima articles in. The format class will be added to the div containing the
 * article using the template variable ${container.class}
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
 * @param Arlima_List|Arlima_AbstractListRenderingManager|int|string $list
 * @param array $args
 * @return string|void
 */
function arlima_render_list($list, $args=array()) {

    $args = array_merge(array(
                'width' => 530,
                'offset' => 0,
                'limit' => 0,
                'echo' => true,
                'filter_suffix' => ''
            ), $args);

    $factory = new Arlima_ListFactory();

    if( is_numeric($list) ) {
        $renderer = new Arlima_ListTemplateRenderer( $factory->loadList($list) );
    }
    elseif( is_string($list) ) {
        $renderer = new Arlima_ListTemplateRenderer( $factory->loadListBySlug($list) );
    }
    elseif( $list instanceof Arlima_AbstractListRenderingManager ) {
        $renderer = $list;
    }
    else {
        $renderer = new Arlima_ListTemplateRenderer($list);
    }

    if( $renderer->getList()->exists() ) {
        $msg = '<p>'.__('This list does not exist', 'arlima').'</p>';
        if( $args['output'] )
            echo $msg;
        else
            return $msg;
    }
    else {

        $renderer->setOffset( $args['offset'] );
        $renderer->setLimit( $args['limit'] );

        if( $renderer->havePosts() ) {

            // Add wordpress filters
            if( !empty($args['filter_suffix']) )
                Arlima_FilterApplier::setFilterSuffix($args['filter_suffix']);

            Arlima_FilterApplier::setArticleWidth($args['width']);
            Arlima_FilterApplier::applyFilters($renderer);

            $content = $renderer->renderList($args['output']);
            Arlima_FilterApplier::setFilterSuffix('');

            return $content;
        }
    }

    return '';
}