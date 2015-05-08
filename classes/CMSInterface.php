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
     * @return int
     */
    function getPostIDInLoop();

    /**
     * @return bool
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
     * @return string
     */
    function getContentOfPostInGlobalScope();

    /**
     * @param int|object $post
     * @param null|string $text
     * @return Arlima_Article
     */
    function postToArlimaArticle($post, $text=null);



    /* * * * * LIST AND PAGE RELATIONS * * * * */



    /**
     * Get information about possible relations between given
     * post/page and Arlima lists
     * @param int $post_id
     * @return array
     */
    function getRelationData($post_id);

    /**
     * Get all "widgets" that displays given Arlima list
     * @param Arlima_List $list
     * @return array
     */
    function loadRelatedWidgets($list);

    /**
     * Get an array with all pages that give list is related to
     * @param Arlima_List $list
     * @return array
     */
    function loadRelatedPages($list);

    /**
     * Remove possible relation this post/page might have with an Arlima list
     * @param $post_id
     * @return void
     */
    function removeRelation($post_id);

    /**
     * Remove relations made between pages and given list
     * @param Arlima_List $list
     * @return void
     */
    function removeAllRelations($list);

    /**
     * Relate an Arlima list with a post/page
     * @param Arlima_List $list
     * @param int $post_id
     * @param array $attr
     * @return void
     */
    function relate($list, $post_id, $attr);



    /* * * * * IMAGES * * * * */


    /**
     * Generate an image version of given file with given max width (resizing image).
     * Returns the $attach_url if not possible to create image version
     * @param string $file
     * @param string $attach_url
     * @param int $max_width
     * @param int $img_id
     * @return string
     */
    function generateImageVersion($file, $attach_url, $max_width, $img_id);

    /**
     * @param int $img_id
     * @return string
     */
    function getImageURL($img_id);

    /**
     * Get an array with info (height, width, file path) about image with given id
     * @param int $img_id
     * @return array
     */
    function getImageData($img_id);

    /**
     * Get an array with 'attachmend' being image id, 'alignment', 'sizename' and 'url' of the image
     * that is related to the post/page with given id. Returns false if no image exists
     * @param $id
     * @return array|bool
     */
    function getArlimaArticleImageFromPost($id);


    /* * * * LIST IMPORTS
        The CMS is used to store which external lists that should be available
        in the list manager
    * * * */


    /**
     * @param string $url
     * @return void
     */
    function removeImportedList($url);

    /**
     * An array with URL:s of external lists
     * @return array
     */
    function getImportedLists();

    /**
     * Save an array with URL:s of external lists that should be
     * available in the list manager
     * @param array $lists
     * @return mixed
     */
    function saveImportedLists($lists);



    /* * * * EVENTS  * * * */


    /**
     * Invoke a system event
     * @return void
     */
    function doAction();

    /**
     * Schedule an event to take place in the future
     * @param int $schedule_time
     * @param string $event
     * @param mixed $args
     * @return void
     */
    function scheduleEvent($schedule_time, $event, $args);

    /**
     * Filter data
     * @return mixed
     */
    function applyFilters();


    /* * * * DATABASE  * * * */


    /**
     * Make string safe for use in a database query
     * @param string $input
     * @return string
     */
    function dbEscape($input);

    /**
     * Prepare an SQL-statement.
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    function prepare($sql, $params);

    /**
     * Get the prefix used in database table names
     * @return string
     */
    function getDBPrefix();

    /**
     * Calls a method on DB and throws Exception if db error occurs
     * @param string $sql
     * @return mixed
     * @throws Exception
     */
    function runSQLQuery($sql);

    /**
     * @param string $tbl
     * @return bool
     */
    function dbTableExists($tbl);



    /* * * * POSTS / PAGES * * */


    /**
     * Preloads posts/pages with given ids. Use this function to lower the amount of
     * db queries sent when using any of the post-functions provided by this class
     * @param array $post_ids
     * @return mixed
     */
    function preLoadPosts($post_ids);

    /**
     * Tells whether or not a page/post with given id is preloaded
     * @param int $id
     * @return bool
     */
    function isPreloaded($id);

    /**
     * Get URL for post/page with given id
     * @param $post_id
     * @return string
     */
    function getPostURL($post_id);

    /**
     * Get publish time for the post/page with given id
     * @param int $post_id
     * @return int
     */
    function getPostTimeStamp($post_id);

    /**
     * Get the excerpt of a post/page
     * @param int $post_id
     * @param int $excerpt_length
     * @param string $allowed_tags
     * @return string
     */
    function getExcerpt($post_id, $excerpt_length = 35, $allowed_tags = '');

    /**
     * Get id the page/post that currently is being visited
     * @return int|bool
     */
    function getQueriedPageId();

    /**
     * Get URL of where post/page with given id can be edited by an
     * administrator
     * @param int $page_id
     * @return string
     */
    function getPageEditURL($page_id);

    /**
     * Get id of the page/post with given slug name
     * @param string $slug
     * @return int|bool
     */
    function getPageIdBySlug($slug);




    /* * * * STRINGS  * * * */



    /**
     * Sanitize text from CMS specific tags/code as well as ordinary html tags. Use
     * $allowed to tell which tags that should'nt become removed
     * @param string $txt
     * @param string $allowed
     * @return string
     */
    function sanitizeText($txt, $allowed='');


    /**
     * Translate current string
     * @param $str
     * @return string
     */
    function translate($str);



    /* * * * FILES * * * */



    /**
     * Returns the file path if it resides within the directory of the CMS.
     * @param string $path
     * @param bool $relative
     * @return bool|string
     */
    function resolveFilePath($path, $relative = false);

    /**
     * Get URL of a file that resides within the directory of the CMS
     * @param string $file
     * @return string
     */
    function getFileURL($file);



    /* * * * MISC * * * */



    /**
     * Tells whether or not current website visitor can edit pages/posts
     * @return bool
     */
    function currentVisitorCanEdit();

    /**
     * Load the contents of an external URL. This function returns an array
     * with  'headers', 'body', 'response', 'cookies', 'filename' if request was
     * successful, or throws an Exception if failed
     * @param string $url
     * @return array
     */
    function loadExternalURL($url);

    /**
     * Get base URL of the website that the CMS provides
     * @return string
     */
    function getBaseURL();

    /**
     * Flush all caches affecting arlima
     * @return void
     */
    function flushCaches();

    /**
     * Get a human readable string explaining how long ago given time is, or how much time
     * there's left until the time takes place
     * @param int $time
     * @return string
     */
    function humanTimeDiff($time);

    /**
     * Get URL of where arlima list with given id can be edited by an
     * administrator
     * @param int $id
     * @return string
     */
    function getListEditURL($id);

}