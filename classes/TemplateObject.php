<?php

/**
 * Simple object that won't trigger "undefined index" notice when requesting
 * a property that does not exist
 *
 * @since 2.6.X
 * @package Arlima
 */
class Arlima_TemplateObject extends stdClass {

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : false;
    }

    /**
     * @param array $data
     * @return Arlima_TemplateObject
     */
    public static function create( $data )
    {
        $obj = new Arlima_TemplateObject();
        foreach($data as $key => $val) {
            if( is_array($val) || $val instanceof stdClass ) {
                $obj->$key = self::create($val);
            }
            else {
                $obj->$key = $val;
            }
        }

        return $obj;
    }
}