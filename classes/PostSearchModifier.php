<?php


/**
 * Class working as a container from where you can get or set
 * rules that modifies a search form using WP_Query
 *
 * @package Arlima
 * @since 2.2
 */
class Arlima_PostSearchModifier
{

    const TYPE_EXCLUDE = 'exclude';

    /**
     * @var bool
     */
    private static $search_action_has_fired = false;

    /**
     * @var array
     */
    private static $callbacks = array('form' => array(), 'query' => array());

    /**
     * @static
     */
    public static function modifySearch($form_callback, $query_callback)
    {
        self::$callbacks['form'][] = $form_callback;
        self::$callbacks['query'][] = $query_callback;
    }

    /**
     * Fires off wordpress action that makes it possible
     * for plugins or theme to modify the post search
     */
    private static function runAction()
    {
        if ( !self::$search_action_has_fired ) {
            do_action('arlima_post_search'); // Let theme or plugins modify this search form
            self::$search_action_has_fired = true;
        }
    }

    /**
     * @static
     * @return array
     */
    public static function invokeFormCallbacks()
    {
        self::runAction();
        foreach (self::$callbacks['form'] as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * @param $args
     * @param $post_data
     * @return mixed
     */
    public static function filterWPQuery($args, $post_data)
    {
        self::runAction();
        foreach (self::$callbacks['query'] as $filter) {
            $args = $filter($args, $post_data);
        }

        return $args;
    }
}