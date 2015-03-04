<?php



/**
 * Repository that is used to perform CRUD-operation on list objects
 *
 * @since 3.1
 * @package Arlima
 */
class Arlima_ListRepository extends Arlima_AbstractRepositoryDB {

    /**
     * Create a new list
     *
     * @param $title
     * @param $slug
     * @param array $options
     * @param int $max_length
     * @return Arlima_List
     */
    public function create($title, $slug, $options=array(), $max_length=50)
    {
        $list = new Arlima_List(true);
        $list->setCreated(Arlima_Utils::timeStamp());
        $list->setMaxlength($max_length);
        $list->setSlug($slug);
        $list->setTitle($title);
        $list->addOptions($options);

        $this->sanitizeList($list);

        // Insert list data in DB
        $sql = 'INSERT INTO ' . $this->dbTable() . '
                (al_created, al_title, al_slug, al_maxlength, al_options)
                VALUES (%d, %s, %s, %d, %s)';

        $sql = $this->cms->prepare($sql, array(
                    $list->getCreated(),
                    $title,
                    $slug,
                    $max_length,
                    serialize( $list->getOptions() )
                ));

        $id = $this->cms->runSQLQuery($sql);
        $list->setId($id);

        $this->cache->delete('arlima_list_slugs');

        return $list;
    }

    /**
     * Update a list in the database
     * @param Arlima_List $list
     */
    public function update($list)
    {
        $this->sanitizeList($list);

        $update_data = array(
            $list->getTitle(),
            $list->getSlug(),
            $list->getMaxlength(),
            serialize( $list->getOptions() ),
            (int)$list->getId()
        );

        $sql = 'UPDATE ' . $this->dbTable() . '
                    SET al_title = %s, al_slug = %s, al_maxlength=%d, al_options = %s
                    WHERE al_id = %d ';

        $this->cms->runSQLQuery($this->cms->prepare($sql, $update_data));

        // remove cache
        $this->cache->delete('arlima_list_'.$list->getId());
        $this->cache->delete('arlima_list_slugs');
    }

    /**
     * Remove a list from the database
     * @param Arlima_List $list
     */
    public function delete($list)
    {
        // Remove list properties
        $this->cms->runSQLQuery('DELETE FROM '.$this->dbTable().' WHERE al_id='.$list->getId());

        // remove cache
        $this->cache->delete('arlima_list_'.$list->getId());
        $this->cache->delete('arlima_list_slugs');
    }


    /**
     * @param int|string $id_or_slug
     * @return Arlima_List
     * @throws Exception
     */
    public function load($id_or_slug)
    {
        if( !$id_or_slug )
            throw new Exception('Invalid argument for list id/slug "'.$id_or_slug.'" ');

        $id = is_numeric($id_or_slug) ? $id_or_slug : $this->getListId($id_or_slug);

        $list = $this->cache->get('arlima_list_'.$id);

        if( !$list ) {
            $list_data = $this->cms->runSQLQuery('SELECT * FROM ' . $this->dbTable() . ' WHERE al_id = '.intval($id));
            $list_data = $this->removePrefix(current($list_data), 'al_');

            if ( empty($list_data) ) {
                $list = new Arlima_List(false);
            } else {
                $list = new Arlima_List(true, $id);
                $list->setCreated($list_data['created']);
                $list->setTitle($list_data['title']);
                $list->setSlug($list_data['slug']);
                $list->setMaxlength($list_data['maxlength']);

                // Need to sanitize options also when list is loaded due to make it backwards compat
                $list->setOptions( self::sanitizeListOptions(unserialize($list_data['options'])) );

                $this->cache->set('arlima_list_'.$list->getId(), $list);
            }
        }

        return $list;
    }

    /**
     * Will return an array with info (slug, id and title) about all the lists in the
     * database array( stdClass(id => ... title => ... slug => ...), ... )
     * @return array
     */
    public function loadListSlugs()
    {
        $data = $this->cache->get('arlima_list_slugs');
        if(!is_array($data)) {
            $sql = 'SELECT al_id, al_title, al_slug
                    FROM ' . $this->dbTable() . '
                    ORDER BY al_title ASC';

            $data = $this->cms->runSQLQuery($sql);
            $data = $this->removePrefix($data, 'al_', true);
            $this->cache->set('arlima_list_slugs', $data);
        }

        return $data;
    }

    /**
     * @param $slug
     * @return int|bool
     */
    public function getListId($slug)
    {
        foreach($this->loadListSlugs() as $data) {
            if($data->slug == $slug)
                return $data->id;
        }

        return false;
    }


    /* * * * * * * implementation of abstract functions * * * * * * */



    /**
     * @return void
     */
    function createDatabaseTables()
    {
        $sql = "CREATE TABLE " . $this->dbTable() . " (
            al_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            al_created bigint(11) DEFAULT '0' NOT NULL,
            al_title tinytext NOT NULL,
            al_slug varchar(50),
            al_options text,
            al_maxlength mediumint(9) DEFAULT '100' NOT NULL,
            UNIQUE KEY id (al_id),
            KEY created (al_created),
            KEY slug (al_slug)
        );";

        $this->cms->runSQLQuery($sql);
    }

    /**
     * @return array
     */
    function getDatabaseTables()
    {
        return array($this->dbTable());
    }

    /**
     * @param float $currently_installed_version
     */
    function updateDatabaseTables($currently_installed_version)
    {
        // No change in tbl structure needed since version 3.1
    }




    /* * * * * * * * * Helper functions * * * * * * * * * */



    /**
     * @param Arlima_List $list
     */
    private function sanitizeList( $list )
    {
        $list->setTitle( stripslashes($list->getTitle()) );
        $list->setSlug( sanitize_title(stripslashes($list->getSlug())) );
        $list->setOptions( array_map( 'stripslashes_deep', self::sanitizeListOptions( $list->getOptions() )) );

        if( !is_numeric($list->getMaxlength()) )
            $list->setMaxlength( 50 );
    }


    /**
     * @param array $options
     * @return array
     */
    private function sanitizeListOptions($options)
    {
        // Override default options
        foreach(Arlima_List::getDefaultListOptions() as $name => $val) {
            if( !isset($options[$name]) )
                $options[$name] = $val;
        }

        return $options;
    }
}