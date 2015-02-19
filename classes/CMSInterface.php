<?php


/**
 * Facade in front of underlying system (WordPress)
 *
 * @todo Write function docs
 *
 * @package Arlima
 * @since 3.1
 */
interface Arlima_CMSInterface
{

    /*
     * Post iteration
     * ----------------------
     *
     * In wordpress a normal iteration over posts would look something like:
     * <code>
     * <?php
     *  while( have_posts() ) {
     *      the_post();
     *      ...
     *  }
     * </code>
     *
     * Arlima will try to imitate this behaviour when rendering lists. This will
     * look something like this (very simplified example)
     *
     * <code>
     *  <?php
     *      $cms->prepareForPostLoop($list)
     *      foreach($list->getArticles() as $article) {
     *          $cms->setPostInGlobalScope($article->getPostId());
     *          // render the article...
     *      }
     *      $cms->resetAfterPostLoop();
     * </code>
     *
     * Arlima may also want to use the entire iteration functionality from the CMS (wordpress)
     * <code>
     *  <?php
     *      $cms->prepareForPostLoop($list)
     *      while( $cms->havePostsInLoop() ) {
     *          $cms_post = $cms->getPostIDInLoop();
     *          $arlima_article $cms->postToArlimaArticle($cms_post);
     *          // render the article...
     *      }
     *      $cms->resetAfterPostLoop();
     * </code>
     */

    /**
     * Do the preparations necessary before iterating over a set of posts.
     * This function should be called when Arlima imitates an iteration over
     * posts that the underlying system normally does.
     *
     * @param Arlima_List $list
     * @return mixed
     */
    function prepareForPostLoop($list);

    /**
     * Function that should be called after messing with the internal
     * globals used by the system
     */
    function resetAfterPostLoop();

    /**
     * Get ID of the current post in
     * @return mixed
     */
    function getPostIDInLoop();

    /**
     * @return mixed
     */
    function havePostsInLoop();

    /**
     * @return mixed
     */
    function getPostInGlobalScope();

    /**
     * @param $id
     * @return mixed
     */
    function setPostInGlobalScope($id);

    /**
     * @param int|object $post
     * @param null|string $text
     * @return Arlima_Article
     */
    function postToArlimaArticle($post, $text=null);

    /**
     * @return mixed
     */
    function getContentOfPostInGlobalScope();

    /**
     * @param $input
     * @return string
     */
    function dbEscape($input);

    /**
     * Flush all caches affecting arlima
     * @return mixed
     */
    function flushCaches();

    function isPreloaded($id);

    function getArlimaArticleImageFromPost($id);

    function getQueriedPageId();

    function scheduleEvent($schedule_time, $event, $args);

    function applyFilters();

    function getPageEditURL($page_id);

    function humanTimeDiff($time);

    function getPageIdBySlug($slug);

    function getBaseURL();

    function getImportedLists();

    function loadExternalURL($url);

    function saveImportedLists($lists);

    function getRelationData($post_id);

    function loadRelatedWidgets($list);

    function loadRelatedPages($list);

    function removeRelation($post_id);

    function removeAllRelations($list);

    function relate($list, $post_id, $attr);

    function getDefaultListAttributes();

    function generateImageVersion($url, $dimension, $img_id);

    function getImageURL($img_id);

    function getImageData($img_id);

    function getFileURL($file);

    function getExcerpt($post_id, $excerpt_length = 35, $allowed_tags = '');

    function removeImportedList($url);

    /**
     * Sanitize text from CMS specific tags/code
     * @param string $txt
     * @param $allowed
     * @return string
     */
    function sanitizeText($txt, $allowed='');

    function currentVisitorCanEdit();

    function doAction();

    function prepare($sql, $params);

    function getDBPrefix();

    function preLoadPosts($post_ids);

    function getPostURL($post_id);

    function initLocalization();

    /**
     * Calls a method on DB and throws Exception if db error occurs
     * @param string $sql
     * @return mixed
     * @throws Exception
     */
    function runSQLQuery($sql);

    function dbDelta($sql);

    function getPostTimeStamp($p);

    /**
     * @param string $path
     * @param bool $relative
     * @return bool|string
     */
    function resolveFilePath($path, $relative = false);
}