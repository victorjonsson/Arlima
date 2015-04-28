<?php
/*
Plugin Name: Arlima (article list manager)
Plugin URI: https://github.com/victorjonsson/Arlima
Description: Manage the order of posts on your front page, or any page you want. This is a plugin suitable for online newspapers that's in need of a fully customizable front page.
Author: VK (<a href="http://twitter.com/chredd">@chredd</a>, <a href="http://twitter.com/znoid">@znoid</a>, <a href="http://twitter.com/victor_jonsson">@victor_jonsson</a>, <a href="http://twitter.com/lefalque">@lefalque</a>)
Version: 3.1.beta.58
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Load arlima constants
require_once dirname(__FILE__).'/constants.php';

// Load arlima plugin class
require_once ARLIMA_CLASS_PATH.'/WP/Plugin.php';

// Register class loader for this plugin
spl_autoload_register('Arlima_WP_Plugin::classLoader');

// Instance plugin
$arlima_plugin = new Arlima_WP_Plugin();
$arlima_plugin->init();


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
 * Wrapper function for Arlima_PostSearchModifier::modifySearch. May be used by the theme to modify the post
 * search in the article editor
 * @param Closure $form_callback
 * @param Closure $query_callback
 */
function arlima_modify_post_search($form_callback, $query_callback) {
    Arlima_PostSearchModifier::modifySearch($form_callback, $query_callback);
}

/**
 * Tells whether or not current request is requesting an arlima preview
 * @return bool
 */
function arlima_is_preview() {
    static $is_arlima_preview = null;
    if( $is_arlima_preview === null ) {
        $is_arlima_preview =  isset( $_GET[Arlima_List::QUERY_ARG_PREVIEW] ) &&
                                Arlima_CMSFacade::load()->currentVisitorCanEdit();
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
 *  arlima_register_format('my-funny-format', 'Funny looking article', array(), 'pink');
 *
 * @param string $format_class - The class that will be added to the article container
 * @param string $label - The name of this format, displayed in wp-admin
 * @param array $templates[optional=array()] - This argument tells arlima that this format
 * should only be available for certain templates. The array should contain only the names of
 * the templates where the format should be available, without path and extension. Omit this
 * argument if you want your format to be available on all templates
 * @param $ui_color String with hex-color. Used as border color on articles having this format in the list manager
 */
function arlima_register_format($format_class, $label, $templates=array(), $ui_color='') {
    Arlima_ArticleFormat::add($format_class, $label, $templates, $ui_color);
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

    $cms = Arlima_CMSFacade::load();

    if( $cms->currentVisitorCanEdit() ) {
        if( !$message ) {
            $message = $cms->translate('Edit article list').' &quot;'.$list->getTitle().'&quot;';
        }
        ?>
        <div class="arlima-edit-list admin-tool">
            <a href="<?php echo $cms->getListEditURL($list->getId()) ?>" target="_arlima">
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

        $current_arlima_list = array('list'=>new Arlima_List(), 'post'=>false);
        $page_id = Arlima_CMSFacade::load()->getQueriedPageId();

        if( $page_id ) {

            try {
                $builder = Arlima_List::builder()->fromPage($page_id);
                if( isset($_GET[Arlima_List::QUERY_ARG_PREVIEW]) )
                    $builder->loadPreview();

                $list = $builder->build();

            } catch (Exception $e) {
                if( $e->getCode() != Arlima_List::ERROR_PREVIEW_VERSION_NOT_FOUND ) {
                    throw $e;
                }

                $list = Arlima_List::builder()->fromPage($page_id)->build();
            }


            if( $list->exists() ) {
                $current_arlima_list = array('list'=>$list, 'post'=>$page_id);
            }
        }

    }

    return $list_only ? $current_arlima_list['list'] : $current_arlima_list;
}

/**
 * Load an arlima list
 * @param int|string $id_or_slug Either list id, list slug or URL to external list or RSS-feed
 * @param mixed $version Omit this argument, or set it to false, if you want to load the latest published version of the list. This argument won't have any effect if you're loading an external list/feed
 * @param bool $include_future_articles Whether or not the list should include future posts. This argument won't have any effect if you're loading an external list/feed
 * @return Arlima_List
 */
function arlima_load_list($id_or_slug, $version=false, $include_future_articles=false) {

    $builder = Arlima_List::builder();
    $builder->includeFutureArticles($include_future_articles);

    if( filter_var($id_or_slug, FILTER_VALIDATE_URL) !== false ) {
        $builder->import($id_or_slug);
    }
    else {

        $builder->id($id_or_slug);

        if( $version === 'preview' )
            $builder->loadPreview();
        elseif( $version )
            $builder->version($version);
    }

    return $builder->build();
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
                'check_if_exists' => true,
                'no_list_message' => true // True meaning the the message will be displayed
            ), $args);

    $builder = Arlima_List::builder();
    $cms = Arlima_CMSFacade::load();

    if( is_numeric($list) || is_string($list) ) {
        $renderer = new Arlima_ListTemplateRenderer( $builder->id($list)->build() );
    } elseif( $list instanceof Arlima_AbstractListRenderingManager ) {
        $renderer = $list;
    } else {
        $renderer = new Arlima_ListTemplateRenderer($list);
    }

    if( $args['check_if_exists'] && !$renderer->getList()->exists() ) {
        if( $args['no_list_message'] ) {
            $msg = '<p>'.$cms->translate('This list does not exist').'</p>';
            if( $args['echo'] )
                echo $msg;
            else
                return $msg;
        }
    } else {

        $renderer->setOffset( $args['offset'] );
        $renderer->setLimit( $args['limit'] );
        $renderer->setSection( $args['section'] );


        if( $renderer->havePosts() ) {

            $action_suffix = '';
            if( !empty($args['filter_suffix']) ) {
                Arlima_TemplateObjectCreator::setFilterSuffix($args['filter_suffix']);
                $action_suffix = '-'.$args['filter_suffix'];
            }

            $cms->doAction('arlima_list_begin'.$action_suffix, $renderer, $args);
            Arlima_TemplateObjectCreator::setArticleWidth($args['width']);

            $content = $renderer->renderList($args['echo']);

            Arlima_TemplateObjectCreator::setFilterSuffix('');

            $cms->doAction('arlima_list_end'.$action_suffix, $renderer, $args);

            if( $args['echo'] ) {
                return true;
            } else {
                return $cms->applyFilters('arlima_list_content', $content, $renderer);
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
        $file_args = Arlima_FileInclude::currentFileArgs();
        $new_args = array();
        foreach($default as $name => $arg) {
            // Back compat, they should all be numeric
            if( is_numeric($name) ) {
                // This is the new way
                $new_args[$arg['property']] = !isset($file_args[$arg['property']]) ? $arg['value'] : $file_args[$arg['property']];
            }
            elseif( empty($file_args[$name]) ) {
                $new_args[$name] = is_array($arg) ? $arg['value'] : $arg;
            } else {
                $new_args[$name] = is_array($file_args[$name]) ? $file_args[$name]['value'] : $file_args[$name];
            }
        }
        return $new_args;
    }
}

/**
 * @param string $file
 * @param array $args
 * @param Arlima_AbstractListRenderingManager $renderer
 * @param Arlima_Article $article
 * @return string
 */
function arlima_include_file($file, $args = array(), $renderer = null, $article = null) {
    $file_include = new Arlima_FileInclude();
    return $file_include->includeFile($file, $args, $renderer, $article);
}