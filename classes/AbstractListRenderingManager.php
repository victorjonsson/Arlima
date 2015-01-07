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
     * @var bool|array
     */
    private $articles_to_render = false;

    /**
     * @var Arlima_WPFacade
     */
    protected $system;

    /**
     * Class constructor
     * @param Arlima_List|stdClass $list
     */
    function __construct($list)
    {
        $this->system = new Arlima_WPFacade();
        $this->list = $list;
    }

    /**
     * @return array
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
                    if( empty($art['options']['sectionDivider']) ) {
                        $articles[] = $art;
                        $this->getPostsFromArticle($post_ids, $art);
                    }
                }
                $this->articles_to_render = array_slice($articles, $this->getOffset());
            }
            $this->system->preLoadPosts($post_ids); // To lower the amount of db questions
        }
        return $this->articles_to_render;
    }

    private function getPostsFromArticle(&$post_ids, $art)
    {
        if( !empty($art['post']) && !$this->system->isPreloaded($art['post']) ) {
            $post_ids[] = $art['post'];
        }
        if( !empty($art['image']['attachment']) && !$this->system->isPreloaded($art['image']['attachment'])) {
            $post_ids[] = $art['image']['attachment'];
        }
        if( !empty($art['children']) ) {
            foreach($art['children'] as $child_article) {
                $this->getPostsFromArticle($post_ids, $child_article);
            }
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
     * @param bool $output[optional=true]
     * @return string
     */
    abstract protected function generateListHtml($output = true);

    /**
     * @param bool $output
     * @return string
     */
    function renderList($output = true)
    {
        $this->system->prepareForPostLoop($this->list);
        $content = $this->generateListHtml($output);
        $this->system->resetAfterPostLoop();
        return $content;
    }

    /**
     * @param array|stdClass $article_data
     * @param int $index
     * @return array (index, html_content)
     */
    protected function renderArticle($article_data, $index)
    {
        // File include
        if ( $this->isFileIncludeArticle($article_data) ) {
            // We're done, go on pls!
            return array($index + 1, $this->includeArticleFile($article_data));
        }

        // Scheduled article
        if ( !empty($article_data['options']['scheduled']) ) {
            if ( !$this->isInScheduledInterval($article_data['options']['scheduledInterval']) ) {
                return array($index, ''); // don't show this scheduled article right now
            }
        }

        // Setup
        list($post, $article_data, $is_empty) = $this->setup($article_data);

        // Future article
        if ( !empty($article_data['published']) && $article_data['published'] > Arlima_Utils::timeStamp() && apply_filters('arlima_omit_future_articles', true) ) {
            return array($index, $this->getFutureArticleContent($article_data, $index, $post));
        }

        return array($index+1, $this->generateArticleHtml($article_data, $index, $post, $is_empty));
    }

    /**
     * @param $article_data
     * @param $index
     * @param $post
     * @return mixed
     */
    protected function getFutureArticleContent($article_data, $index, $post)
    {
        $filtered = $this->system->applyFilters('arlima_future_post', array(
            'post' => $post,
            'article' => $article_data,
            'list' => $this->list,
            'count' => $index,
            'content' => ''
        ));

        if( empty($filtered['content']) && $filtered['content'] !== false) {
            $url = $article_data['post'] ? admin_url('post.php?action=edit&amp;post=' . $post->ID) : $article_data['url'];
            $filtered['content'] = '<div class="arlima future-post"><p>
                        Hey dude, <a href="' . $url . '" target="_blank">&quot;'.$article_data['title'].'&quot;</a> is
                        connected to a post that isn\'t published yet. The article will become public in '.
                human_time_diff(Arlima_Utils::timeStamp(), $article_data['published']).'.</p>
                    </div>';
        }

        return $filtered['content'];
    }

    /**
     * @param $article_data
     * @param $index
     * @param $post
     * @param $is_empty
     * @return mixed
     */
    abstract protected function generateArticleHtml($article_data, $index, $post, $is_empty);

    /**
     * Use the article object/array and set up the wordpress environment
     * as if we were in an ordinary wordpress loop, right after having called the_post();
     *
     * @param array|stdClass $article
     * @return array
     */
    protected function setup($article)
    {
        if ( !empty($article['post']) ) {
            $GLOBALS['post'] = $this->system->loadPost($article['post']);
           // setup_postdata($GLOBALS['post']);
        }
        else {
            $GLOBALS['post'] = false;
        }

        $is_empty = false;
        $has_image = !empty($article['image']) && (isset($article['image']['attachment']) || isset($article['image']['url']));

        if ( empty($article['content']) && empty($article['title']) && !$has_image ) {
            $is_empty = true;
        }

        $article['url'] = Arlima_Utils::resolveURL($article);

        return array($GLOBALS['post'], $article, $is_empty);
    }

    /**
     * Extract articles that's located in the section that's meant
     * to be rendered
     * @see AbstractListRenderingManager::setSection()
     * @param array $articles
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

            $is_section_divider = !empty($art['options']['sectionDivider']);

            if( !$begun && !$is_section_divider ) {
                // The list does not start with a section
                $section_index++;
                if( $wants_indexed_section && $section_index == $section ) {
                    $start_collecting_articles = true;
                    // Create a fake section divider
                    self::$current_section_divider = array();
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
                } elseif( !$wants_indexed_section && strcasecmp($section, $art['title']) == 0) {
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
            self::$current_section_divider = array();
            return array(array_slice($articles, $offset), $post_ids);
        } else {
            return array($extracted_articles, $post_ids);
        }
    }


    /**
     * Will try to parse a schedule-interval-formatted string and determine
     * if we're currently in this time interval
     * @example
     *  isInScheduledInterval('*:*');
     *  isInScheduledInterval('Mon,Tue,Fri:*');
     *  isInScheduledInterval('*:10-12');
     *  isInScheduledInterval('Thu:12,15,18');
     *
     * @param string $schedule_interval
     * @return bool
     */
    protected function isInScheduledInterval($schedule_interval)
    {
        $interval_part = explode(':', $schedule_interval);
        if ( count($interval_part) == 2 ) {

            // Check day
            if ( trim($interval_part[0]) != '*' ) {

                $current_day = strtolower(date('D', Arlima_Utils::timeStamp()));
                $days = array();
                foreach (explode(',', $interval_part[0]) as $day) {
                    $days[] = strtolower(substr(trim($day), 0, 3));
                }

                if ( !in_array($current_day, $days) ) {
                    return false; // don't show article today
                }

            }

            // Check hour
            if ( trim($interval_part[1]) != '*' ) {

                $current_hour = (int)date('H', Arlima_Utils::timeStamp());
                $from_to = explode('-', $interval_part[1]);
                if ( count($from_to) == 2 ) {
                    $from = (int)trim($from_to[0]);
                    $to = (int)trim($from_to[1]);
                    if ( $current_hour < $from || $current_hour > $to ) {
                        return false; // don't show article this hour
                    }
                } else {
                    $hours = array();
                    foreach (explode(',', $interval_part[1]) as $hour) {
                        $hours[] = (int)trim($hour);
                    }

                    if ( !in_array($current_hour, $hours) ) {
                        return false; // don't show article this hour
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param $article_data
     * @return string
     */
    protected function includeArticleFile($article_data)
    {
        $file_include = new Arlima_FileInclude();
        $args = array();
        if (!empty($article_data['options']['fileArgs'])) {
            parse_str($article_data['options']['fileArgs'], $args);
        }

        return $file_include->includeFile($article_data['options']['fileInclude'], $args, $this, $article_data);
    }

    /**
     * @param $article_data
     * @return bool
     */
    protected function isFileIncludeArticle($article_data)
    {
        return !empty($article_data['options']) && !empty($article_data['options']['fileInclude']);
    }

    /**
     * @var bool|array
     */
    static $current_section_divider = false;

    /**
     * @return bool|array
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
