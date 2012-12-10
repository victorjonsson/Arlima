<?php

/**
 * Class with the know how about how lists gets related to wordpress posts/pages
 *
 * @package Arlima
 * @since 2.5
 */
class Arlima_ListConnector {

    const META_KEY_LIST = '_arlima_list';
    const META_KEY_ATTR = '_arlima_list_data';

    /**
     * @var Arlima_List|null
     */
    private $list;

    /**
     * @param Arlima_List $list
     */
    public function __construct($list=null)
    {
        $this->list = $list;
    }

    /**
     * @param Arlima_List $list
     */
    public function setList($list)
    {
        $this->list = $list;
    }

    /**
     * @return Arlima_List|null
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param int $post_id
     * @param array $attr
     */
    public function relate($post_id, $attr)
    {
        update_post_meta($post_id, self::META_KEY_LIST, $this->list->id());
        update_post_meta($post_id, self::META_KEY_ATTR, $attr);
    }

    /**
     * Remove all relations for given list
     */
    public function removeAllRelations()
    {
        foreach($this->loadRelatedPages() as $p) {
            $this->removeRelation($p->ID);
        }
    }

    /**
     * @param int $post_id
     */
    public function removeRelation($post_id)
    {
        delete_post_meta($post_id, self::META_KEY_LIST);
        delete_post_meta($post_id, self::META_KEY_ATTR);
    }

    /**
     * @return stdClass[]
     */
    public function loadRelatedPages()
    {
        if( $this->list->exists() ) {
            return get_pages(array(
                    'meta_key' => self::META_KEY_LIST,
                    'meta_value' => $this->list->id(),
                    'hierarchical' => 0
                ));
        }

        return array();
    }

    /**
     * Returns false if not relation is made
     * @param int $post_id
     * @return array|bool
     */
    public function getRelationData($post_id)
    {
        $list_id = get_post_meta($post_id, self::META_KEY_LIST, true);
        if ( $list_id ) {
            return array(
                'id' => $list_id,
                'attr' => get_post_meta($post_id, self::META_KEY_ATTR, true)
            );
        }

        return false;
    }
}