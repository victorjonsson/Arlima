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
     * Returns an array with info about widgets that's related to the list
     * @return array
     */
    public function loadRelatedWidgets()
    {
        global $wp_registered_widgets;
        $related = array();
        $sidebars = wp_get_sidebars_widgets();

        if( is_array($sidebars) && is_array($wp_registered_widgets) ) {
            $list_id = $this->list->id();
            $prefix_len = strlen(Arlima_Widget::WIDGET_PREFIX);
            foreach($sidebars as $sidebar => $widgets) {
                $index = 0;
                foreach( $widgets as $widget_id ) {
                    $index++;
                    if( substr($widget_id, 0, $prefix_len) == Arlima_Widget::WIDGET_PREFIX && !empty($wp_registered_widgets[$widget_id])) {
                        $widget = $this->findWidgetObject($wp_registered_widgets[$widget_id]);
                        if( $widget_id !== null) {
                            $settings = current( array_slice($widget->get_settings(), -1) );
                            if( $settings['list'] ==  $list_id )
                                $related[] = array('sidebar' => $sidebar, 'index' => $index, 'width' => $settings['width']);
                        }
                    }
                }
            }
        }

        return $related;
    }

    /**
     * @param array $registered_data
     * @return null|WP_Widget
     */
    private function findWidgetObject($registered_data)
    {
        if( !empty($registered_data['callback']) && !empty( $registered_data['callback'][0] ) ) {
            return is_object($registered_data['callback'][0]) ? $registered_data['callback'][0] : null;
        }
        return null;
    }

    /**
     * Returns false if not relation is made
     * @param int $post_id
     * @return array|bool
     */
    public function getRelationData($post_id)
    {
        $data = false;
        $list_id = get_post_meta($post_id, self::META_KEY_LIST, true);

        if ( $list_id ) {
            $data = array(
                'id' => $list_id,
                'attr' => get_post_meta($post_id, self::META_KEY_ATTR, true)
            );

            if( !is_array($data['attr']) ) {
                $data['attr'] = $this->getDefaultListAttributes();
            } else {
                $data['attr'] = array_merge($this->getDefaultListAttributes(), $data['attr']);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getDefaultListAttributes()
    {
        return array(
            'width' => 560,
            'offset' => 0,
            'limit' => 0,
            'position' => 'before'
        );
    }
}