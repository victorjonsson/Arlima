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

    function prepareForPostLoop($list);

    function flushCaches();

    function getPostInLoop();

    function isPreloaded($id);

    function getPostInGlobalScope();

    function getArlimaArticleImageFromPost($id);

    function getQueriedPageId();

    function scheduleEvent($schedule_time, $event, $args);

    function applyFilters();

    function havePostsInLoop();

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

    /**
     * @param $id
     * @return null|WP_Post
     */
    function loadPost($id);

    function currentVisitorCanEdit();

    /**
     * Function that should be called after messing with the internal
     * globals used by the system
     */
    function resetAfterPostLoop();

    function doAction();

    function prepare($sql, $params);

    function getDBPrefix();

    function setPostInGlobalScope($post);

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
}