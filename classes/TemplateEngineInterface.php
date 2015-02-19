<?php


/**
 * Interface for classes that can render Arlima_Article objects
 *
 * @package Arlima
 * @since 3.1
 */
interface Arlima_TemplateEngineInterface
{

    /**
     * @param string $template_name
     * @param int $article_counter
     * @param Arlima_Article $article
     * @param object|mixed $post
     * @param string $child_articles
     * @param bool $child_split_state
     * @return string
     */
    function renderArticle($template_name, $article_counter, $article, $post, $child_articles = '', $child_split_state = false);

    /**
     * Set which template that should be used as default. Will return false
     * if given template can't be found
     * @param string $tmpl_name
     * @return bool
     */
    function setDefaultTemplate($tmpl_name);
}