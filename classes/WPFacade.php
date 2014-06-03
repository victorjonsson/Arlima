<?php


/**
 * Facade in front of underlying system (WordPress)
 *
 * @package Arlima
 * @since 3.0
 */
class Arlima_WPFacade {

    private $p;

    function doAction()
    {
        return call_user_func_array('do_action', func_get_args());
    }

    function applyFilters()
    {
        return call_user_func_array('apply_filters', func_get_args());
    }

    function prepareForPostLoop($list)
    {
        $this->doAction('arlima_rendering_init', $list);
        $this->p = $this->getPostInGlobalScope();
    }

    /**
     * Function that should be called after messing with the internal
     * globals used by the system
     */
    function resetAfterPostLoop()
    {
        // unset global post data
        $this->setPostInGlobalScope($this->p);
        wp_reset_query();
    }

    function getPostInGlobalScope()
    {
        return isset($GLOBALS['post']) ? $GLOBALS['post']:false;
    }

    function setPostInGlobalScope($post)
    {
        $GLOBALS['post'] = $post;
    }

    function havePostsInLoop()
    {
        return have_posts();
    }

    function getPostInLoop()
    {
        the_post();
        return $GLOBALS['post'];
    }

    function getArlimaArticleImageFromPost($id)
    {
        if( $img = get_post_thumbnail_id($id) ) {
            return array(
                'attachment' => $img,
                'alignment' => '',
                'size' => 'full',
                'url' => wp_get_attachment_url($img)
            );
        }
        return array();
    }

    function preLoadPosts($post_ids)
    {
        $post_ids = array_unique($post_ids);
        if( empty($post_ids) )
            return;

        /** @var wpdb $wpdb */
        global $wpdb;
        foreach( $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'posts WHERE ID in ('.implode(',', $post_ids).')') as $post_data ) {
            $post_data = sanitize_post( $post_data, 'raw' );
            wp_cache_add( $post_data->ID, $post_data, 'posts' );
        }
        $this->updateMetaCache('post', $post_ids);
    }

    private function updateMetaCache($meta_type, $object_ids)
    {
        global $wpdb;

        $cache_key = $meta_type . '_meta';
        $ids = array();
        $cache = array();

        foreach ( $object_ids as $id ) {
            $cached_object = wp_cache_get( $id, $cache_key );
            if ( false === $cached_object )
                $ids[] = $id;
            else {
                $cache[$id] = $cached_object;
            }
        }

        if ( empty( $ids ) )
            return;

        $id_list = join( ',', $ids );
        $id_column = 'meta_id';
        $column = $meta_type . '_id';
        $table = _get_meta_table($meta_type);

        $meta_list = $wpdb->get_results( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list) ORDER BY $id_column ASC", ARRAY_A );

        if ( !empty($meta_list) ) {
            foreach ( $meta_list as $metarow) {
                $mpid = intval($metarow[$column]);
                $mkey = $metarow['meta_key'];
                $mval = $metarow['meta_value'];

                // Force subkeys to be array type:
                if ( !isset($cache[$mpid]) || !is_array($cache[$mpid]) )
                    $cache[$mpid] = array();
                if ( !isset($cache[$mpid][$mkey]) || !is_array($cache[$mpid][$mkey]) )
                    $cache[$mpid][$mkey] = array();

                // Add a value to the current pid/key:
                $cache[$mpid][$mkey][] = $mval;
            }
        }

        foreach ( $ids as $id ) {
            if ( ! isset($cache[$id]) )
                $cache[$id] = array();
            wp_cache_add( $id, $cache[$id], $cache_key );
        }
    }

    function isPreloaded($id)
    {
        return wp_cache_get($id, 'posts') ? true : false;
    }

    /**
     * @param $id
     * @return null|WP_Post
     */
    function loadPost($id)
    {
        return get_post($id);
    }
}