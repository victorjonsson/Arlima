<?php


/**
 * Abstract class that can be extended by object repository classes
 *
 * @since 3.1
 * @package Arlima
 */
abstract class Arlima_AbstractRepositoryDB {

    /**
     * @var Arlima_CMSFacade
     */
    protected $cms;

    /**
     * @var string
     */
    protected $dbTablePrefix;

    /**
     * @var Arlima_CacheManager
     */
    protected $cache;


    /**
     * @param Arlima_CMSInterface $sys
     * @param Arlima_CacheInterface $cache
     */
    public function __construct($sys = null, $cache = null)
    {
        $this->cms = $sys === null ? Arlima_CMSFacade::load() : $sys;
        if( !($this->cms instanceof Arlima_CMSInterface) ) {
            var_dump($this->cms);
        }

        $this->cache = $cache === null ? Arlima_CacheManager::loadInstance() : $cache;
        $this->dbTablePrefix = $this->cms->getDBPrefix() .(defined('ARLIMA_DB_PREFIX') ? ARLIMA_DB_PREFIX:'arlima_');
    }

    /**
     * @param Arlima_CacheInterface $cache_instance
     */
    public function setCache( $cache_instance )
    {
        $this->cache = $cache_instance;
    }

    /**
     * @return Arlima_CMSInterface
     */
    public function getCMSFacade()
    {
        return $this->cms;
    }

    /**
     * Get name of database table (adds prefixes used as namespace for arlimas db tables)
     * @param string $type
     * @return string
     */
    protected function dbTable($type='')
    {
        return $this->dbTablePrefix.'articlelist'.$type;
    }

    /**
     * Remove prefix from array keys, will also turn stdClass objects to arrays unless
     * $preserve_std_objects is set to true
     *
     * @static
     * @param array $array
     * @param string $prefix
     * @param bool $preserve_std_objects[optional=false]
     * @return array
     */
    protected function removePrefix($array = array(), $prefix, $preserve_std_objects=false)
    {
        $convert_to_std = $preserve_std_objects && $array instanceof stdClass;
        $new_array = array();
        $prefix_len = strlen($prefix);
        if($array) {
            foreach ( $array as $key => $value ) {
                $newkey = $key;
                if(substr($key, 0, $prefix_len) == $prefix)
                    $newkey = substr($key, $prefix_len);
                if(is_array($value) || $value instanceof stdClass)
                    $value = self::removePrefix($value, $prefix, $preserve_std_objects);
                $new_array[$newkey] = $value;
            }
        }
        return $convert_to_std ? (object)$new_array:$new_array;
    }



    /* * * * * * * * Abstract functions * * * * * * * * * */



    /**
     * Create database tables needed for this repository
     * @return void
     */
    abstract function createDatabaseTables();

    /**
     * Get database tables used by this repository
     * @return array
     */
    abstract function getDatabaseTables();

    /**
     * @param float $currently_installed_version
     */
    abstract function updateDatabaseTables($currently_installed_version);

}