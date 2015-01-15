<?php


/**
 * Class that can put together list objects following a set of instructions
 *
 * @see Arlima_List::builder()
 * @since 3.1.0
 * @package Arlima
 */
class Arlima_ListBuilder {

    /**
     * @var Arlima_ListRepository
     */
    private $list_repo;

    /**
     * @var Arlima_ListVersionRepository
     */
    private $version_repo;

    /**
     * @var bool
     */
    private $include_future_posts = false;

    /**
     * @var bool|int
     */
    private $load_preview = false;

    /**
     * @var bool|int
     */
    private $load_version = false;

    /**
     * @var bool|string
     */
    private $import = false;

    /**
     * @var bool
     */
    private $save_imported = false;

    /**
     * @var bool
     */
    private $id_or_slug = false;

    /**
     * @var bool
     */
    private $from_page = false;


    /**
     * @param null $list_repo
     * @param null $version_repo
     */
    public function __construct($list_repo=null, $version_repo=null)
    {
        $this->version_repo = $version_repo === null ? new Arlima_ListVersionRepository() : $version_repo;
        $this->list_repo = $list_repo === null ? new Arlima_ListRepository() : $list_repo;
    }

    /**
     * @param $in
     * @return Arlima_ListBuilder
     */
    public function id($in)
    {
        $this->id_or_slug = $in;
        return $this;
    }

    /**
     * @param $in
     * @return Arlima_ListBuilder
     */
    public function slug($in)
    {
        $this->id_or_slug = $in;
        return $this;
    }

    /**
     * @param $page_id
     * @return Arlima_ListBuilder
     */
    public function fromPage($page_id)
    {
        $this->from_page = $page_id;
        return $this;
    }

    /**
     * @return Arlima_ListBuilder
     */
    public function includeFuturePosts()
    {
        $this->include_future_posts = true;
        return $this;
    }

    /**
     * URL of external RSS-feed or Arlima list JSON
     * @param string $in
     * @return Arlima_ListBuilder
     */
    public function import($in)
    {
        $this->import = $in;
        return $this;
    }

    /**
     * @return Arlima_ListBuilder
     */
    public function saveImportedList()
    {
        $this->save_imported = true;
        return $this;
    }

    /**
     * @param $in
     * @return Arlima_ListBuilder
     */
    public function version($in)
    {
        $this->load_version = $in;
        return $this;
    }

    /**
     * @return Arlima_ListBuilder
     */
    public function loadPreview()
    {
        $this->load_preview = true;
        return $this;
    }

    /**
     * This function will always return a List object even if it might
     * not exists, thus you should call $list->exists() on returned list
     * to verify that the list actually do exist
     *
     * @return Arlima_List
     * @throws Exception
     */
    public function build()
    {
        if( $this->id_or_slug || $this->from_page ) {
            return $this->assembleList();
        } elseif( $this->import ) {
            return $this->assembleExternalList();
        } else {
            throw new Exception('Arlima_ListBuilder must be given an id or page_id or import URL');
        }
    }

    /**
     * @return Arlima_List
     */
    protected function assembleList()
    {
        if( $this->from_page ) {
            $list_relation = $this->list_repo->getCMSFacade()->getRelationData( $this->from_page );
            if( !$list_relation ) {
                $list = new Arlima_List();
            } else {
                $list = $this->list_repo->load($list_relation['id']);
            }
        } else {
            $list = $this->list_repo->load($this->id_or_slug);
        }

        if( $list->exists() ) {
            $this->version_repo->addVersionHistory($list);

            if ($this->load_preview) {
                $this->version_repo->addPreviewArticles($list);
            } else {
                $this->version_repo->addArticles($list, $this->load_version, $this->include_future_posts);
            }
        }

        return $list;
    }

    /**
     * @return Arlima_List
     */
    protected function assembleExternalList()
    {
        $importer = new Arlima_ImportManager();
        $list = $this->save_imported ? $importer->importList($this->import) : $importer->loadList($this->import);

        if( !$this->include_future_posts ) {
            $articles = array();
            $now = time();
            foreach ($list->getArticles() as $art) {
                if (empty($art['published']) || $art['published'] <= $now) {
                    $articles[] = $art;
                }
            }
            $list->setArticles($articles);
        }

        return $list;
    }

}
