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
     * @var Arlima_TemplateEngineInterface
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
     * @param bool $echo_output[optional=true]
     * @return string
     */
    protected function generateListHtml($echo_output = true)
    {
        $count = 0;
        $list_content = '';

        // Set default template
        try {
            $this->template_engine->setDefaultTemplate($this->default_template_name);
        } catch(Exception $e) {
            $message = 'You are using a default template for the list "'.$this->list->getTitle().'" that could not be found';
            if( $echo_output ) {
                echo $message;
            } else {
                return $message;
            }
        }

        foreach ($this->getArticlesToRender() as $article) {

            list($count, $content) = $this->renderArticle($article, $count);

            if ( $echo_output ) {
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
     * @param Arlima_Article $article
     * @param int $index
     * @param mixed $post
     * @return array
     */
    protected function generateArticleHtml($article, $index, $post)
    {
        $child_article_html = '';

        if( $article->hasChildren() ) {
            $child_article_html = $this->renderChildArticles($article->getChildArticles());
        }

        $template_name = $this->getTemplateToUse($article);
        return $this->template_engine->renderArticle($template_name, $index, $article, $post, $child_article_html);
    }

    /**
     * @param Arlima_Article $article
     * @param int $index
     * @return string
     */
    protected function includeArticleFile($article, $index)
    {
        $data = $article->toArray();
        $data['content'] = parent::includeArticleFile($article, $index);
        $article = new Arlima_Article($data);
        return $this->template_engine->renderArticle('file-include', $index, $article, false, '');
    }

    /**
     * @param Arlima_Article[] $articles
     * @return string
     */
    private function renderChildArticles($articles)
    {
        $child_articles = '';
        $count = 0;
        $split_state = null;
        $has_open_child_wrapper = false;

        foreach ($articles as $i => $art) {

            $first_or_last_class = '';
            $is_floating = (bool)$art->opt('floating');

            if ($is_floating) {

                if (!$split_state || ($split_state && $art->opt('inlineWithChild') === false)) {

                    $following_count = 0;
                    for ($j = $i + 1; $j < count($articles); $j++) {
                        if ( $articles[$j]->opt('floating') && $articles[$j]->opt('inlineWithChild') !== false ) {
                            $following_count++;
                        } else {
                            break;
                        }
                    }
                    $split_state = array(
                            'index' => 0,
                            'count' => $following_count + 1
                         );

                } else {
                    $split_state['index'] += 1;
                }

                if ($split_state['count'] == 1) { // single floating. reset status!
                    $is_floating = false;
                }
                elseif ( $split_state['index'] == 0 ) {
                    $first_or_last_class = ' first';
                    $child_articles .= '<div class="arlima child-wrapper child-wrapper-'.$split_state['count'].'">';
                    $has_open_child_wrapper = true;
                }
                elseif ( $split_state['index'] == $split_state['count'] - 1 ) {
                    $first_or_last_class = ' last';
                }

            } else {
                $split_state = null;
            }

            $post = $this->setup($art);

            if ( !$art->isPublished() ) {
                $child_articles .= $this->getFutureArticleContent($art, $i, $post);
            }
            elseif( $art->isFileInclude() ) {
                $child_articles .= '<div class="arlima-file-include teaser '.$first_or_last_class.
                    ( $is_floating ? ' teaser-split':'').
                    '">'.$this->includeArticleFile($art, -1).'</div>';
            }
            else {
                $template_name = $this->getTemplateToUse($art);
                $child_articles .= $this->template_engine->renderArticle($template_name, -1, $art, $post, '', $split_state);
            }

            $count++;

            if( $has_open_child_wrapper && $first_or_last_class == ' last') {
                $child_articles .= '</div>';
                $has_open_child_wrapper = false;
            }
        }

        if( $has_open_child_wrapper ) {
            $child_articles .= '</div>';
        }

        return $child_articles;
    }

    /**
     * @param Arlima_Article $article
     * @return null|string
     */
    protected function getTemplateToUse($article)
    {
        return $article->opt('template', $this->default_template_name);
    }
}
