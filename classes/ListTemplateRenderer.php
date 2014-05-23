<?php

/**
 * Class that can render an Arlima article list using jQueryTmpl. The class
 * uses templates available in the path given on construct, if template not
 * found it falls back on templates available in this plugin directory (arlima/templates)
 *
 * @package Arlima
 * @since 2.0
 */
class Arlima_ListTemplateRenderer extends Arlima_AbstractListRenderingManager
{
    /**
     * Current unix time
     * @var int
     */
    private $now;

    /**
     * @var Arlima_TemplateObjectCreator
     */
    private $template_obj_creator;

    /**
     * @var string
     */
    protected $default_template_name = null;

    /**
     * Class constructor
     * @param Arlima_List|stdClass $list
     * @param string $template_path - Optional path to directory where templates should exists
     */
    function __construct($list, $template_path = null)
    {
        $this->now = time();
        $this->template_engine = new Arlima_TemplateEngine($template_path);
        $this->default_template_name = $list->getOption('template');
        parent::__construct($list);
    }

    /**
     * Prepares the template object creator. All callbacks must be added to this class
     * before running this function. A callback added after this function is called
     * will not be triggered
     */
    protected function setupTemplateObjectCreator()
    {
        $this->template_obj_creator = new Arlima_TemplateObjectCreator();
        $this->template_obj_creator->setList($this->getList());
        if ( $this->list->hasOption('before_title') ) {
            $this->template_obj_creator->setBeforeTitleHtml($this->list->getOption('before_title'));
            $this->template_obj_creator->setAfterTitleHtml($this->list->getOption('after_title'));
        }

        $this->template_obj_creator->setImgSize($this->img_size_name);
        $this->template_obj_creator->setArticleBeginCallback($this->article_begin_callback);
        $this->template_obj_creator->doAddTitleFontSize($this->list->getOption('ignore_fontsize') ? false : true);
        $this->template_obj_creator->setArticleEndCallback($this->article_end_callback);
        $this->template_obj_creator->setImageCallback($this->image_callback);
        $this->template_obj_creator->setRelatedCallback($this->related_posts_callback);
        $this->template_obj_creator->setContentCallback($this->content_callback);
    }

    /**
     * Will render all articles in the arlima list using jQuery templates. The template to be
     * used is an option in the article list object (Arlima_List). If no template exists in declared
     * template paths we will fall back on default templates (plugins/arlima/template/[name].tmpl)
     *
     * @param bool $output[optional=true]
     * @return string
     */
    function renderList($output = true)
    {
        $count = 0;
        $list_content = '';

        if( !$this->template_engine->setDefaultTemplate($this->default_template_name) ) {
            $message = 'You are using a default template for the list "'.$this->list->getTitle().'" that could not be found';
            if( $output ) {
                echo $message;
            } else {
                return $message;
            }
        }

        // Setup template object creator
        $this->setupTemplateObjectCreator();
        $articles = $this->getArticlesToRender();

        do_action('arlima_rendering_init');

        foreach ($articles as $article_data) {
            list($count, $content) = $this->outputArticle($article_data, $count);

            if ( $output ) {
                echo $content;
            } else {
                $list_content .= $content;
            }

            if ( $count == $this->getLimit() ) {
                break;
            }
        }

        // unset global post data
        $GLOBALS['post'] = null;
        wp_reset_query();

        return $list_content;
    }

    /**
     * @param array|stdClass $article_data
     * @param int $article_counter
     * @return array
     */
    protected function outputArticle($article_data, $article_counter)
    {
        // File include
        if ( $this->isFileIncludeArticle($article_data) ) {
            // We're done, go on pls!
            return array($article_counter + 1, $this->includeArticleFile($article_data));
        }

        // Scheduled article
        if ( !empty($article_data['options']['scheduled']) ) {
            if ( !$this->isInScheduledInterval($article_data['options']['scheduledInterval']) ) {
                return array($article_counter, ''); // don't show this scheduled article right now
            }
        }

        // Setup
        list($post, $article, $is_post, $is_empty) = $this->setup($article_data);

        // Future article
        if ( !empty($article_data['published']) && $article_data['published'] > $this->now ) {
            return array(
                    $article_counter,
                    call_user_func(
                        $this->future_post_callback,
                        $post,
                        $article,
                        $this->list,
                        $article_counter
                    )
                );
        }

        list($art_template, $template_obj) = $this->setupTemplateData($article_counter, $article, $is_empty, $post);

        // Add class that makes it possible to target the first article in the list
        if( $article_counter == 0 ) {
            $template_obj['class'] .= ' first-in-list';
        }
    
        $has_child_articles = !empty($article['children']) && is_array($article['children']);

        // load sub articles if there's any
        if ( $has_child_articles ) {
            $template_obj['child_articles'] = $this->renderChildArticles($article['children']);
        }

        // output the article
        if( $is_empty && !$has_child_articles ) {
            $content = ''; // empty article, don't render!
        } else {
            $content = $this->template_engine->render($template_obj, $art_template);
        }

        return array($article_counter + 1, $content);
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
    private function isInScheduledInterval($schedule_interval)
    {
        $interval_part = explode(':', $schedule_interval);
        if ( count($interval_part) == 2 ) {

            // Check day
            if ( trim($interval_part[0]) != '*' ) {

                $current_day = strtolower(date('D', $this->now + (get_option('gmt_offset') * 3600)));
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

                $current_hour = (int)date('H', $this->now + (get_option('gmt_offset') * 3600));
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
     * @param array $articles
     * @return string
     */
    private function renderChildArticles(array $articles)
    {
        $child_articles = '';
        $count = 0;
        $has_open_child_wrapper = false;
        $num_children = count($articles);
        $has_even_children = $num_children % 2 === 0;
        $is_child_split = $num_children > 1;
        $image_size = !$is_child_split ? $this->img_size_name_sub_article_full : $this->img_size_name_sub_article;

        // if ARLIMA_GROUP_CHILD_ARTICLES is false will the variable $has_open_child_wrapper always be false
        // and then no grouping will be applied

        // Configure object creator for child articles
        $this->template_obj_creator->setImgSize($image_size);
        $this->template_obj_creator->setIsChild(true);

        foreach ($articles as $article_data) {

            $this->template_obj_creator->setIsChildSplit(false);
            $first_or_last_class = '';

            if(
                ARLIMA_GROUP_CHILD_ARTICLES && (
                    ($num_children == 4 && ($count == 1 || $count == 2)) ||
                    ($num_children == 6 && ($count != 0 && $count != 3)) ||
                    ($num_children > 1 && $num_children != 4 && $num_children != 6 && ($count != 0 || $has_even_children) )
                )
            ) {
                $this->template_obj_creator->setIsChildSplit( true );
                $first_or_last_class = (($count==1 && $num_children > 2) || ($count==0 && $num_children==2) || $count==3 || ($count==4 && $num_children ==6)? ' first':' last');
                if( $first_or_last_class == ' first' ) {
                    $child_articles .= '<div class="arlima child-wrapper">';
                    $has_open_child_wrapper = true;
                }
            }

            // File include
            if( $this->isFileIncludeArticle($article_data) ) {
                $count++;
                $child_articles .= '<div class="arlima-file-include teaser '.$first_or_last_class.
                                    ( $this->template_obj_creator->getIsChildSplit() ? ' teaser-split':'').
                                    '">'.$this->includeArticleFile($article_data).'</div>';
                continue;
            }

            list($post, $article, $is_post, $is_empty) = $this->setup($article_data);

            if ( is_object($post) && $post->post_status == 'future' ) {
                if( ARLIMA_GROUP_CHILD_ARTICLES && $has_open_child_wrapper  && $first_or_last_class == ' last' ) {
                    $child_articles .= '</div>';
                    $has_open_child_wrapper = false;
                }
                continue;
            }

            list($template_name, $template_obj) = $this->setupTemplateData(-1, $article, $is_empty, $post);
            if( $first_or_last_class ) {
                $template_obj['class'] .= $first_or_last_class;
            }
            $child_articles .= $this->template_engine->render($template_obj, $template_name);

            $count++;
            if( $has_open_child_wrapper && $first_or_last_class == ' last') {
                $child_articles .= '</div>';
                $has_open_child_wrapper = false;
            }
        }

        if( $has_open_child_wrapper )
            $child_articles .= '</div>';

        // Reset configuration for child articles
        $this->template_obj_creator->setIsChild(false);
        $this->template_obj_creator->setIsChildSplit(false);
        $this->template_obj_creator->setImgSize($this->img_size_name);

        return $child_articles;
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
     * Returns the name of the template being used for this article and
     * the constructed template object used in the template
     * @param $article_counter
     * @param $article
     * @param $is_empty
     * @param $post
     * @return array
     */
    protected function setupTemplateData($article_counter, $article, $is_empty, $post)
    {
        $art_template = empty($article['options']['template']) ? $this->default_template_name : $article['options']['template'];
        $template_data = $this->template_obj_creator->create(
            $article,
            $is_empty,
            $post,
            $article_counter,
            false, // deprecated
            $art_template
        );
        return array($art_template, $template_data);
    }
}