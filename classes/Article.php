<?php


/**
 * Object representing an (read-only) Arlima article
 */
class Arlima_Article implements ArrayAccess {

    /**
     * @var array
     */
    private $data = array();

    /**
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $opt
     * @return string
     */
    function opt($opt)
    {
        return isset($this->data['options'][$opt]) ? $this->data['options'][$opt]:null;
    }

    /**
     * @return bool|string
     */
    public function url()
    {
        if( $overriding = $this->opt('overridingURL') ) {
            return $overriding;
        } elseif( $this->hasPost() ) {
            return get_permalink($this->data['post']);
        }
        return '';
    }

    /**
     * @return bool
     */
    function hasPost()
    {
        return !empty($this->data['post']);
    }

    /**
     * @return bool
     */
    function isPublished()
    {
        return $this->data['published'] >= time();
    }

    /**
     * @return bool
     */
    function isScheduled()
    {
        return $this->opt('scheduled') ? true:false;
    }

    /**
     * @return bool
     */
    function isScheduledForLater()
    {
        return $this->isScheduled() && !$this->isInScheduledInterval($this->opt('scheduledInterval'));
    }


    /* * * * * ArrayAccess Impl * * * * */



    public function offsetSet($offset, $value)
    {
        throw new Exception('Modifying the Arlima article object is not allowed');
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        if( $offset == 'url' ) {
            return $this->url();
        }
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }



    /* * * * Setter/getters * * * */


    public function __get($key) {
        return $this->offsetGet($key);
    }

    public function __set($key, $val) {
        return $this->offsetGet($key, $val);
    }

}