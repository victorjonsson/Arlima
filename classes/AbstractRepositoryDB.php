<?php


/**
 * @since 2.3
 */
abstract class Arlima_AbstractRepositoryDB {

    /**
     * @var Arlima_CMSFacade
     */
    protected $system;

    /**
     * @var string
     */
    protected $dbTablePrefix;

    /**
     * @var Arlima_CacheManager
     */
    protected $cache;


    public function __construct($sys = null, $cache = null)
    {
        $this->system = $sys === null ? Arlima_CMSFacade::load() : $sys;
        if( !($this->system instanceof Arlima_CMSInterface) ) {
            var_dump($this->system);
        }

        $this->cache = $cache === null ? Arlima_CacheManager::loadInstance() : $cache;
        $this->dbTablePrefix = $this->system->getDBPrefix() .(defined('ARLIMA_DB_PREFIX') ? ARLIMA_DB_PREFIX:'arlima_');
    }

    /**
     * @param Arlima_CacheManager $cache_instance
     */
    public function setCacheManager( $cache_instance )
    {
        $this->cache = $cache_instance;
    }

    /**
     * @return Arlima_CMSInterface
     */
    public function getCMSFacade()
    {
        return $this->system;
    }

    /**
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
     * @return void
     */
    abstract function createDatabaseTables();

    /**
     * @return array
     */
    abstract function getDatabaseTables();

    /**
     * @param float $currently_installed_version
     */
    abstract function updateDatabaseTables($currently_installed_version);

}