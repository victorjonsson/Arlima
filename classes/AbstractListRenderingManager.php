<?php

/**
 * Abstract class extended by classes responsible of rendering an Arlima article list
 *
 * @package Arlima
 * @since 2.0
 */
abstract class Arlima_AbstractListRenderingManager
{
    /**
     * @var Arlima_List
     */
    protected $list = null;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $limit = -1;

    /**
     * @var bool|int|string
     */
    private $section = false;

    /**
     * @var Arlima_Article[]
     */
    private $articles_to_render = false;

    /**
     * @var Arlima_CMSInterface
     */
    protected $cms;

    /**
     * Class constructor
     * @param Arlima_List $list
     */
    function __construct($list)
    {
        $this->cms = Arlima_CMSFacade::load();
        $this->list = $list;
    }

    /**
     * @return Arlima_Article[]
     */
    public function getArticlesToRender()
    {
        if( $this->articles_to_render === false ) {
            $post_ids = array();
            if( $this->section !== false ) {
                list($this->articles_to_render, $post_ids) = $this->extractSectionArticles($this->list->getArticles(), $this->section);
            } else {
                $articles = array();
                foreach($this->list->getArticles() as $art) {
                    if( is_array($art) ) {
                        $art = new Arlima_Article($art);
                        $e = new Exception('');
                        error_log('Arlima article now array...'.PHP_EOL.$e->getTraceAsString());
                        // @todo: Remove this before stable release
                    }
                    if( !$art->isSectionDivider() ) {
                        $articles[] = $art;
                        $this->getPostsFromArticle($post_ids, $art);
                    }
                }
                $this->articles_to_render = array_slice($articles, $this->getOffset());
            }
            $this->cms->preLoadPosts($post_ids); // To lower the amount of db questions
        }
        return $this->articles_to_render;
    }

    /**
     * @param $post_ids
     * @param Arlima_Article $art
     */
    private function getPostsFromArticle(&$post_ids, $art)
    {
        $post_id = $art->getPostId();
        $attach_id = $art->getImageId();

        if( $post_id && !$this->cms->isPreloaded($post_id) ) {
            $post_ids[] = $post_id;
        }
        if( $attach_id && !$this->cms->isPreloaded($attach_id)) {
            $post_ids[] = $attach_id;
        }
        foreach($art->getChildArticles() as $child_article) {
            $this->getPostsFromArticle($post_ids, $child_article);
        }
    }

    /**
     * Do we have a list? Does the list have articles?
     * @return bool
     */
    function havePosts()
    {
        $this->getArticlesToRender(); // collect articles
        return !empty($this->articles_to_render);
    }

    /**
     * Render the list of articles
     * @abstract
     * @param bool $echo_output[optional=true]
     * @return string
     */
    abstract protected function generateListHtml($echo_output = true);

    /**
     * @param bool $output
     * @return string
     */
    function renderList($output = true)
    {
        $this->cms->prepareForPostLoop($this->list);
        $content = $this->generateListHtml($output);
        $this->cms->resetAfterPostLoop();
        return $content;
    }

    /**
     * @param Arlima_Article $article
     * @param int $index
     * @return array (index, html_content)
     */
    protected function renderArticle($article, $index)
    {
        // File include
        if ( $article->opt('fileInclude') ) {
            // We're done, go on pls!
            return array($index + 1, $this->includeArticleFile($article, $index));
        }

        if ( !$article->canBeRendered() ) {

            if( !$article->isPublished() ) {
                return array($index, $this->getFutureArticleContent($article, $index, $this->setup($article)));
            } else {
                return array($index, ''); // don't show this scheduled article right now
            }
        }

        return array($index+1, $this->generateArticleHtml($article, $index, $this->setup($article)));
    }

    /**
     * @param Arlima_Article $article
     * @param $index
     * @param $post
     * @return mixed
     */
    protected function getFutureArticleContent($article, $index, $post)
    {
        $filtered = $this->cms->applyFilters('arlima_future_post', array(
            'post' => $post,
            'article' => $article,
            'list' => $this->list,
            'count' => $index,
            'content' => ''
        ));

        if( empty($filtered['content']) && $filtered['content'] !== false) {
            $url = $article['post'] ? $this->cms->getPageEditURL($post) : $article->getURL();
            $filtered['content'] = '<div class="arlima future-post"><p>
                        Hey dude, <a href="' . $url . '" target="_blank">&quot;'.$article->getTitle().'&quot;</a> is
                        connected to a post that isn\'t published yet. The article will become public in '.
                        $this->cms->humanTimeDiff($article->getPublishTime()).'.</p>
                    </div>';
        }

        return $filtered['content'];
    }

    /**
     * @param Arlima_Article $article
     * @param int $index
     * @param object $post
     * @return mixed
     */
    abstract protected function generateArticleHtml($article, $index, $post);

    /**
     * @param Arlima_Article $article
     * @return mixed
     */
    protected function setup($article)
    {
        if ( $post_id = $article->getPostId() ) {
            $this->cms->setPostInGlobalScope($post_id);
        }
        else {
            $this->cms->setPostInGlobalScope(false);
        }
        return $this->cms->getPostInGlobalScope();
    }

    /**
     * Extract articles that's located in the section that's meant
     * to be rendered (by calling setSection)
     *
     * @see AbstractListRenderingManager::setSection()
     * @param Arlima_Article[] $articles
     * @param string|int $section
     * @return array
     */
    protected function extractSectionArticles($articles, $section)
    {
        $post_ids = array();
        self::$current_section_divider = false;
        $extract_all = false;
        if( substr($section, 0, 2) == '>=' ) {
            $section = substr($section, 2);
            $extract_all = true;
        }
        $offset = $this->getOffset();
        $wants_indexed_section = is_numeric($section);
        $start_collecting_articles = false;
        $section_index = -1;
        $extracted_articles = array();
        $begun = false;


        foreach($articles as $art) {

            $is_section_divider = $art->isSectionDivider();

            if( !$begun && !$is_section_divider ) {
                // The list does not start with a section
                $section_index++;
                if( $wants_indexed_section && $section_index == $section ) {
                    $start_collecting_articles = true;
                    // Create a fake section divider
                    self::$current_section_divider = new Arlima_Article(array());
                }
            }

            if( $start_collecting_articles ) {
                if( $is_section_divider ) {
                    if( !$extract_all ) {
                        // next section begins and we're not intending to collect all articles == we're done!
                        break;
                    }
                } else {
                    if( $offset > 1 ) {
                        $offset--;
                    } else {
                        $extracted_articles[] = $art;
                        $this->getPostsFromArticle($post_ids, $art);
                    }
                }
            }

            elseif( $is_section_divider ) {
                $section_index++;
                if( $wants_indexed_section && $section_index == $section ) {
                    $start_collecting_articles = true;
                } elseif( !$wants_indexed_section && strcasecmp($section, $art->getTitle()) == 0) {
                    $start_collecting_articles = true;
                }

                if( $start_collecting_articles ) {
                    self::$current_section_divider = $art;
                }
            }

            $begun = true;
        }

        if( $section_index == -1 && $section == 0 ) {
            // No sections yet exists in this list. Create "empty" section divider
            // and slice from the beginning of the article array
            self::$current_section_divider = new Arlima_Article(array());
            return array(array_slice($articles, $offset), $post_ids);
        } else {
            return array($extracted_articles, $post_ids);
        }
    }

    /**
     * @param Arlima_Article $article
     * @param int $index
     * @return string
     */
    protected function includeArticleFile($article, $index)
    {
        $file_include = new Arlima_FileInclude();
        $args = array();
        if ( $query_str = $article->opt('fileArgs') ) {
            parse_str($query_str, $args);
        }

        return $file_include->includeFile($article->opt('fileInclude'), $args, $this, $article);
    }

    /**
     * @var bool|array
     */
    static $current_section_divider = false;

    /**
     * @return bool|Arlima_Article
     */
    public static function getCurrentSectionDivider()
    {
        return self::$current_section_divider;
    }

    /**
     * - Set to false if you want to render entire list (default)
     * - Set to a string if you want to render the section with given name
     * - Set to a number if you want to render the section at given index
     * - Set to eg. >=2 if you want to render all articles, starting from the second section
     * @param int|bool|string $section
     */
    function setSection($section)
    {
        $this->articles_to_render = false; // recollect articles
        $this->section = $section;
    }

    /**
     * @return bool|int|string
     */
    function getSection()
    {
        return $this->section;
    }

    /**
     * @param \Arlima_List $list
     */
    public function setList($list)
    {
        $this->list = $list;
    }

    /**
     * @return Arlima_List
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Set to -1 to not limit the number of articles that will be rendered
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = (int)$limit;
        if ( !$this->limit ) {
            $this->limit = -1;
        }
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->articles_to_render = false; // recollect articles
        $this->offset = (int)$offset;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }
}
