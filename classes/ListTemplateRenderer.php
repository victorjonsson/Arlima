<?php

/**
 * Class that can render an Arlima article list using a template engine. The class
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
     * @var string
     */
    protected $default_template_name = null;

    /**
     * @var Arlima_TemplateEngine
     */
    protected $template_engine;


    /**
     * Class constructor
     * @param Arlima_List|stdClass $list
     * @param string $template_path - Optional path to directory where templates should exists
     */
    function __construct($list, $template_path = null)
    {
        $this->now = time();
        $this->template_engine = Arlima_TemplateEngine::create($list, $template_path);
        $this->default_template_name = $list->getOption('template');
        parent::__construct($list);
    }

    /**
     * Will render all articles in the arlima list using templates. The template to be
     * used is an option in the article list object (Arlima_List). If no template exists in declared
     * template paths we will fall back on default templates (plugins/arlima/template/[name].tmpl)
     *
     * @param bool $output[optional=true]
     * @return string
     */
    protected function generateListHtml($output = true)
    {
        $count = 0;
        $list_content = '';

        // Set default template
        try {
            $this->template_engine->setDefaultTemplate($this->default_template_name);
        } catch(Exception $e) {
            $message = 'You are using a default template for the list "'.$this->list->getTitle().'" that could not be found';
            if( $output ) {
                echo $message;
            } else {
                return $message;
            }
        }

        foreach ($this->getArticlesToRender() as $article_data) {

            list($count, $content) = $this->renderArticle($article_data, $count);

            if ( $output ) {
                echo $content;
            } else {
                $list_content .= $content;
            }

            if ( $count == $this->getLimit() ) {
                break;
            }
        }

        return $list_content;
    }

    /**
     * @param array $article_data
     * @param int $index
     * @param null|stdClass|WP_Post $post
     * @param bool $is_empty
     * @return array
     */
    protected function generateArticleHtml($article_data, $index, $post, $is_empty)
    {
        $child_article_html = '';
        $content = '';

        if( !empty($article_data['children']) ) {
            $child_article_html = $this->renderChildArticles($article_data['children']);
        }

        $template_name = $this->getTemplateToUse($article_data);
        return $this->template_engine->renderArticle($template_name, $index, $article_data, $is_empty, $post, $child_article_html);
    }

    /**
     * @param array $articles
     * @return string
     */
    private function renderChildArticles($articles)
    {
        $child_articles = '';
        $count = 0;
        $has_open_child_wrapper = false;
        $num_children = count($articles);
        $has_even_children = $num_children % 2 === 0;

        // if ARLIMA_GROUP_CHILD_ARTICLES is false will the variable $has_open_child_wrapper always be false
        // and then no grouping will be applied

        // Configure object creator for child articles

        foreach ($articles as $article_data) {

            $first_or_last_class = '';
            $is_child_split = false;

            if(
                ARLIMA_GROUP_CHILD_ARTICLES && (
                    ($num_children == 4 && ($count == 1 || $count == 2)) ||
                    ($num_children == 6 && ($count != 0 && $count != 3)) ||
                    ($num_children > 1 && $num_children != 4 && $num_children != 6 && ($count != 0 || $has_even_children) )
                )
            ) {
                $is_child_split = true;
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
                    ( $is_child_split ? ' teaser-split':'').
                    '">'.$this->includeArticleFile($article_data).'</div>';
                continue;
            }

            list($post, $article, $is_empty) = $this->setup($article_data);

            if ( !empty($article['published']) && $article['published'] > Arlima_Utils::timeStamp() ) {
                if( ARLIMA_GROUP_CHILD_ARTICLES && $has_open_child_wrapper  && $first_or_last_class == ' last' ) {
                    $child_articles .= '</div>';
                    $has_open_child_wrapper = false;
                }
                continue;
            }

            $template_name = $this->getTemplateToUse($article);

            $child_articles .= $this->template_engine->renderArticle($template_name, -1, $article, $is_empty, $post, '', $first_or_last_class, $is_child_split);

            $count++;
            if( $has_open_child_wrapper && $first_or_last_class == ' last') {
                $child_articles .= '</div>';
                $has_open_child_wrapper = false;
            }
        }

        if( $has_open_child_wrapper )
            $child_articles .= '</div>';

        return $child_articles;
    }

    /**
     * @param $article
     * @return null|string
     */
    protected function getTemplateToUse($article)
    {
        return empty($article['options']['template']) ? $this->default_template_name : $article['options']['template'];
    }
}