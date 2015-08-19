## Table of contents

- [Arlima_AbstractListRenderingManager (abstract)](#class-arlima_abstractlistrenderingmanager-abstract)
- [Arlima_AbstractRepositoryDB (abstract)](#class-arlima_abstractrepositorydb-abstract)
- [Arlima_Article](#class-arlima_article)
- [Arlima_ArticleFormat](#class-arlima_articleformat)
- [Arlima_CacheInterface (interface)](#interface-arlima_cacheinterface)
- [Arlima_CacheManager](#class-arlima_cachemanager)
- [Arlima_CMSFacade](#class-arlima_cmsfacade)
- [Arlima_CMSInterface (interface)](#interface-arlima_cmsinterface)
- [Arlima_CMSLoop](#class-arlima_cmsloop)
- [Arlima_ExportManager](#class-arlima_exportmanager)
- [Arlima_FailedListImportException](#class-arlima_failedlistimportexception)
- [Arlima_FileInclude](#class-arlima_fileinclude)
- [Arlima_ImportManager](#class-arlima_importmanager)
- [Arlima_List](#class-arlima_list)
- [Arlima_ListBuilder](#class-arlima_listbuilder)
- [Arlima_ListFactory](#class-arlima_listfactory)
- [Arlima_ListRepository](#class-arlima_listrepository)
- [Arlima_ListTemplateRenderer](#class-arlima_listtemplaterenderer)
- [Arlima_ListVersionRepository](#class-arlima_listversionrepository)
- [Arlima_Plugin](#class-arlima_plugin)
- [Arlima_PostSearchModifier](#class-arlima_postsearchmodifier)
- [Arlima_SimpleListRenderer](#class-arlima_simplelistrenderer)
- [Arlima_TemplateEngine](#class-arlima_templateengine)
- [Arlima_TemplateEngineInterface (interface)](#interface-arlima_templateengineinterface)
- [Arlima_TemplateObjectCreator](#class-arlima_templateobjectcreator)
- [Arlima_TemplatePathResolver](#class-arlima_templatepathresolver)
- [Arlima_Utils](#class-arlima_utils)
- [Arlima_WP_AbstractAdminPage (abstract)](#class-arlima_wp_abstractadminpage-abstract)
- [Arlima_WP_Ajax](#class-arlima_wp_ajax)
- [Arlima_WP_Cache](#class-arlima_wp_cache)
- [Arlima_WP_Facade](#class-arlima_wp_facade)
- [Arlima_WP_ImageVersionManager](#class-arlima_wp_imageversionmanager)
- [Arlima_WP_Page_Edit](#class-arlima_wp_page_edit)
- [Arlima_WP_Page_Main](#class-arlima_wp_page_main)
- [Arlima_WP_Page_Settings](#class-arlima_wp_page_settings)
- [Arlima_WP_Plugin](#class-arlima_wp_plugin)
- [Arlima_WP_Widget](#class-arlima_wp_widget)
- [Arlima_WPLoop](#class-arlima_wploop)

<hr /> 
### Class: Arlima_AbstractListRenderingManager (abstract)

> Abstract class extended by classes responsible of rendering an Arlima article list

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em><br /><em>Class constructor</em> |
| public | <strong>getArticlesToRender()</strong> : <em>[\Arlima_Article](#class-arlima_article)[]</em> |
| public static | <strong>getCurrentSectionDivider()</strong> : <em>bool/[\Arlima_Article](#class-arlima_article)</em> |
| public | <strong>getLimit()</strong> : <em>int</em> |
| public | <strong>getList()</strong> : <em>[\Arlima_List](#class-arlima_list)</em> |
| public | <strong>getOffset()</strong> : <em>int</em> |
| public | <strong>getSection()</strong> : <em>bool/int/string</em> |
| public | <strong>havePosts()</strong> : <em>bool</em><br /><em>Do we have a list? Does the list have articles?</em> |
| public | <strong>renderList(</strong><em>bool</em> <strong>$output=true</strong>)</strong> : <em>string</em> |
| public | <strong>setLimit(</strong><em>int</em> <strong>$limit</strong>)</strong> : <em>void</em><br /><em>Set to -1 to not limit the number of articles that will be rendered</em> |
| public | <strong>setList(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em> |
| public | <strong>setOffset(</strong><em>int</em> <strong>$offset</strong>)</strong> : <em>void</em> |
| public | <strong>setSection(</strong><em>int/bool/string</em> <strong>$section</strong>)</strong> : <em>void</em><br /><em>- Set to false if you want to render entire list (default) - Set to a string if you want to render the section with given name - Set to a number if you want to render the section at given index - Set to eg. >=2 if you want to render all articles, starting from the second section</em> |
| protected | <strong>extractSectionArticles(</strong><em>[\Arlima_Article](#class-arlima_article)[]</em> <strong>$articles</strong>, <em>string/int</em> <strong>$section</strong>)</strong> : <em>array</em><br /><em>Extract articles that's located in the section that's meant to be rendered (by calling setSection)</em> |
| protected | <strong>abstract generateArticleHtml(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>int</em> <strong>$index</strong>, <em>object</em> <strong>$post</strong>)</strong> : <em>mixed</em> |
| protected | <strong>abstract generateListHtml(</strong><em>bool</em> <strong>$echo_output=true</strong>)</strong> : <em>string</em><br /><em>Render the list of articles</em> |
| protected | <strong>getFutureArticleContent(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>mixed</em> <strong>$index</strong>, <em>mixed</em> <strong>$post</strong>)</strong> : <em>mixed</em> |
| protected | <strong>includeArticleFile(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>int</em> <strong>$index</strong>)</strong> : <em>string</em> |
| protected | <strong>renderArticle(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>int</em> <strong>$index</strong>)</strong> : <em>array (index, html_content)</em> |
| protected | <strong>setup(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>)</strong> : <em>mixed</em> |

<hr /> 
### Class: Arlima_AbstractRepositoryDB (abstract)

> Abstract class that can be extended by object repository classes

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Arlima_CMSInterface](#interface-arlima_cmsinterface)</em> <strong>$sys=null</strong>, <em>[\Arlima_CacheInterface](#interface-arlima_cacheinterface)</em> <strong>$cache=null</strong>)</strong> : <em>void</em> |
| public | <strong>abstract createDatabaseTables()</strong> : <em>void</em><br /><em>Create database tables needed for this repository</em> |
| public | <strong>getCMSFacade()</strong> : <em>[\Arlima_CMSInterface](#interface-arlima_cmsinterface)</em> |
| public | <strong>abstract getDatabaseTables()</strong> : <em>array</em><br /><em>Get database tables used by this repository</em> |
| public | <strong>setCache(</strong><em>[\Arlima_CacheInterface](#interface-arlima_cacheinterface)</em> <strong>$cache_instance</strong>)</strong> : <em>void</em> |
| public | <strong>abstract updateDatabaseTables(</strong><em>\float</em> <strong>$currently_installed_version</strong>)</strong> : <em>void</em> |
| protected | <strong>dbTable(</strong><em>string</em> <strong>$type=`''`</strong>)</strong> : <em>string</em><br /><em>Get name of database table (adds prefixes used as namespace for arlimas db tables)</em> |
| protected | <strong>removePrefix(</strong><em>array</em> <strong>$array=array()</strong>, <em>string</em> <strong>$prefix</strong>, <em>bool</em> <strong>$preserve_std_objects=false</strong>)</strong> : <em>array</em><br /><em>Remove prefix from array keys, will also turn stdClass objects to arrays unless $preserve_std_objects is set to true</em> |

<hr /> 
### Class: Arlima_Article

> Object representing an (read-only) Arlima article. This class implements ArrayAccess and Countable which makes it possible to treat the object as an ordinary array

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>array</em> <strong>$data=array()</strong>)</strong> : <em>void</em> |
| public | <strong>addChild(</strong><em>array</em> <strong>$article_data</strong>)</strong> : <em>void</em> |
| public | <strong>canBeRendered()</strong> : <em>bool</em><br /><em>Whether or not the article should be rendered. It should not be rendered in case the article is considered to be empty (see ->isEmpty()) or the article is scheduled to be displayed at another time or if it's not yet published</em> |
| public | <strong>getChildArticles()</strong> : <em>[\Arlima_Article](#class-arlima_article)[]</em> |
| public | <strong>getContent()</strong> : <em>string</em><br /><em>Get the body content of the article</em> |
| public | <strong>getCreationTime()</strong> : <em>int</em><br /><em>Get an unix timestamp (in seconds) of when the article was created</em> |
| public | <strong>getId()</strong> : <em>int</em> |
| public | <strong>getImageAlignment()</strong> : <em>string</em><br /><em>Get aligment of possibly connected image</em> |
| public | <strong>getImageFilePath()</strong> : <em>string</em><br /><em>Returns path to image file if known (Remember that the article object may refer to an article on a remote website)</em> |
| public | <strong>getImageId()</strong> : <em>int/string</em><br /><em>Get id of possibly connected image. Will return empty string if no images is attached to the article</em> |
| public | <strong>getImageSize()</strong> : <em>string</em><br /><em>Get size name of possibly connected image (full, half, third, quarter, fifth, sixth)</em> |
| public | <strong>getImageURL()</strong> : <em>string</em><br /><em>Get the URL of article image. Returns empty string if not image is connected to the article</em> |
| public | <strong>getParentIndex()</strong> : <em>int</em><br /><em>Will return -1 if the article does'nt have a parent article</em> |
| public | <strong>getPostId()</strong> : <em>int</em><br /><em>Get id of possibly connected post</em> |
| public | <strong>getPublishTime()</strong> : <em>int</em><br /><em>Get an unix timestamp (in seconds) of when the article is considered to be published</em> |
| public | <strong>getSize()</strong> : <em>int</em><br /><em>Get "size" of the article. This is normally used as font-size of the article title when the article gets rendered</em> |
| public | <strong>getTitle(</strong><em>string</em> <strong>$linebreak_replace=`' '`</strong>, <em>bool</em> <strong>$entity_encode=false</strong>, <em>bool</em> <strong>$link_wrap=false</strong>)</strong> : <em>string</em><br /><em>Get title of the article. Set parameter $linebreak_replace to a br-tag if you want to convert a double-underscore to a linebreak</em> |
| public | <strong>getURL()</strong> : <em>string</em><br /><em>Get the URL of the article which is either the URL of the post connected to the article or the value of the option "overridingURL". This function will return an empty string if the article does'nt have a URL</em> |
| public | <strong>hasChildren()</strong> : <em>bool</em><br /><em>Tells whether or not the article is a parent of other articles (see getChildArticles())</em> |
| public | <strong>hasImage()</strong> : <em>bool</em><br /><em>Tells if this article has an image</em> |
| public | <strong>hasPost()</strong> : <em>bool</em><br /><em>Whether or not this Arlima article is connected to a post</em> |
| public | <strong>hasStreamer()</strong> : <em>bool</em><br /><em>Whether or not this article has a streamer (streamers is made out of the options streamerType, streamerContent and streamerColor)</em> |
| public | <strong>isChild()</strong> : <em>bool</em><br /><em>Whether or not this article is a child of another article</em> |
| public | <strong>isEmpty()</strong> : <em>bool</em><br /><em>The article is considered "empty" if it's missing title, content, image and child articles</em> |
| public | <strong>isFileInclude()</strong> : <em>bool</em><br /><em>Tells whether or not the purpose of this article is to execute (include) a php-file on the local file system</em> |
| public | <strong>isPublished()</strong> : <em>bool</em><br /><em>Whether or not this article is published. If this function returns false it means that the article has aa publish date set to a future date.</em> |
| public | <strong>isScheduled()</strong> : <em>bool</em><br /><em>Whether or not this article is scheduled to only be displayed at certain hours of the day and certain days of the week. (Notice! This has nothing to do with whether or not this article belongs to a scheduled list version)</em> |
| public | <strong>isSectionDivider()</strong> : <em>bool</em><br /><em>Tells whether or not this articles only purpose is to point out where one section ends and another section begins</em> |
| public | <strong>opt(</strong><em>string</em> <strong>$opt</strong>, <em>bool/mixed</em> <strong>$default=false</strong>)</strong> : <em>string</em><br /><em>Get an article option, will return the value of $default if the option does'nt exist</em> |
| public | <strong>toArray()</strong> : <em>array</em><br /><em>Get all data representing the article as an array</em> |
| protected | <strong>isInScheduledInterval(</strong><em>string</em> <strong>$schedule_interval</strong>)</strong> : <em>bool</em><br /><em>Will try to parse a schedule-interval-formatted string and determine if we're currently in the time interval</em> |
###### Examples of Arlima_Article::isInScheduledInterval()
```
isInScheduledInterval('*:*'); // All days of the week and all hours of the day
 isInScheduledInterval('Mon,Tue,Fri:*'); // All hours of the day on monday, tuesday and friday
 isInScheduledInterval('*:10-12'); // The hours 10, 11 and 12 all days of the week
 isInScheduledInterval('Thu:12,15,18'); // Only on thursday and at the hours 12, 15 and 18
````

*This class implements \ArrayAccess, \Countable*

<hr /> 
### Class: Arlima_ArticleFormat

> Class that manages information about article formats

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>add(</strong><em>string</em> <strong>$class</strong>, <em>string</em> <strong>$label</strong>, <em>array</em> <strong>$templates=array()</strong>, <em>string</em> <strong>$ui_color=`''`</strong>)</strong> : <em>void</em><br /><em>Register a format name that should be accessible in the list manager when editing an article (see https://github.com/victorjonsson/Arlima/wiki/Article-formats)</em> |
| public static | <strong>getAll()</strong> : <em>array</em><br /><em>Get all formats registered up to this point</em> |
| public static | <strong>remove(</strong><em>string</em> <strong>$class</strong>, <em>array</em> <strong>$templates=array()</strong>)</strong> : <em>void</em><br /><em>Remove a registered format</em> |

<hr /> 
### Interface: Arlima_CacheInterface

> Interface for a class that can cache arbitrary data

| Visibility | Function |
|:-----------|:---------|
| public | <strong>abstract delete(</strong><em>string</em> <strong>$key</strong>)</strong> : <em>bool</em> |
| public | <strong>abstract get(</strong><em>string</em> <strong>$key</strong>)</strong> : <em>bool/mixed</em> |
| public | <strong>abstract set(</strong><em>mixed</em> <strong>$key</strong>, <em>mixed</em> <strong>$val</strong>, <em>int</em> <strong>$expires</strong>)</strong> : <em>void</em> |

<hr /> 
### Class: Arlima_CacheManager

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>loadInstance()</strong> : <em>[\Arlima_CacheInterface](#interface-arlima_cacheinterface)</em> |

<hr /> 
### Class: Arlima_CMSFacade

> Class used to load a singleton instance of the facade in front of underlying system

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>load(</strong><em>mixed</em> <strong>$in=null</strong>, <em>bool/string/bool</em> <strong>$class=false</strong>)</strong> : <em>[\Arlima_CMSInterface](#interface-arlima_cmsinterface)</em><br /><em>Load facade in front of underlying system</em> |

<hr /> 
### Interface: Arlima_CMSInterface

> Facade in front of underlying system (WordPress)

| Visibility | Function |
|:-----------|:---------|
| public | <strong>abstract applyFilters()</strong> : <em>mixed</em><br /><em>Filter data</em> |
| public | <strong>abstract currentVisitorCanEdit()</strong> : <em>bool</em><br /><em>Tells whether or not current website visitor can edit pages/posts</em> |
| public | <strong>abstract dbEscape(</strong><em>string</em> <strong>$input</strong>)</strong> : <em>string</em><br /><em>Make string safe for use in a database query</em> |
| public | <strong>abstract dbTableExists(</strong><em>string</em> <strong>$tbl</strong>)</strong> : <em>bool</em> |
| public | <strong>abstract doAction()</strong> : <em>void</em><br /><em>Invoke a system event</em> |
| public | <strong>abstract flushCaches()</strong> : <em>void</em><br /><em>Flush all caches affecting arlima</em> |
| public | <strong>abstract generateImageVersion(</strong><em>string</em> <strong>$file</strong>, <em>string</em> <strong>$attach_url</strong>, <em>int</em> <strong>$max_width</strong>, <em>int</em> <strong>$img_id</strong>)</strong> : <em>string</em><br /><em>Generate an image version of given file with given max width (resizing image). Returns the $attach_url if not possible to create image version</em> |
| public | <strong>abstract getArlimaArticleImageFromPost(</strong><em>mixed</em> <strong>$id</strong>)</strong> : <em>array/bool</em><br /><em>Get an array with 'attachmend' being image id, 'alignment', 'sizename' and 'url' of the image that is related to the post/page with given id. Returns false if no image exists</em> |
| public | <strong>abstract getBaseURL()</strong> : <em>string</em><br /><em>Get base URL of the website that the CMS provides</em> |
| public | <strong>abstract getContentOfPostInGlobalScope()</strong> : <em>string</em> |
| public | <strong>abstract getDBPrefix()</strong> : <em>string</em><br /><em>Get the prefix used in database table names</em> |
| public | <strong>abstract getExcerpt(</strong><em>int</em> <strong>$post_id</strong>, <em>mixed/int</em> <strong>$excerpt_length=35</strong>, <em>string</em> <strong>$allowed_tags=`''`</strong>)</strong> : <em>string</em><br /><em>Get the excerpt of a post/page</em> |
| public | <strong>abstract getFileURL(</strong><em>string</em> <strong>$file</strong>)</strong> : <em>string</em><br /><em>Get URL of a file that resides within the directory of the CMS</em> |
| public | <strong>abstract getImageData(</strong><em>int</em> <strong>$img_id</strong>)</strong> : <em>array</em><br /><em>Get an array with info (height, width, file path) about image with given id</em> |
| public | <strong>abstract getImageURL(</strong><em>int</em> <strong>$img_id</strong>)</strong> : <em>string</em> |
| public | <strong>abstract getImportedLists()</strong> : <em>array</em><br /><em>An array with URL:s of external lists</em> |
| public | <strong>abstract getListEditURL(</strong><em>int</em> <strong>$id</strong>)</strong> : <em>string</em><br /><em>Get URL of where arlima list with given id can be edited by an administrator</em> |
| public | <strong>abstract getPageEditURL(</strong><em>int</em> <strong>$page_id</strong>)</strong> : <em>string</em><br /><em>Get URL of where post/page with given id can be edited by an administrator</em> |
| public | <strong>abstract getPageIdBySlug(</strong><em>string</em> <strong>$slug</strong>)</strong> : <em>int/bool</em><br /><em>Get id of the page/post with given slug name</em> |
| public | <strong>abstract getPostIDInLoop()</strong> : <em>int</em><br /><em>Get ID of the current post in</em> |
| public | <strong>abstract getPostInGlobalScope()</strong> : <em>mixed</em> |
| public | <strong>abstract getPostTimeStamp(</strong><em>int</em> <strong>$post_id</strong>)</strong> : <em>int</em><br /><em>Get publish time for the post/page with given id</em> |
| public | <strong>abstract getPostURL(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>string</em><br /><em>Get URL for post/page with given id</em> |
| public | <strong>abstract getQueriedPageId()</strong> : <em>int/bool</em><br /><em>Get id the page/post that currently is being visited</em> |
| public | <strong>abstract getRelationData(</strong><em>int</em> <strong>$post_id</strong>)</strong> : <em>array</em><br /><em>Get information about possible relations between given post/page and Arlima lists</em> |
| public | <strong>abstract havePostsInLoop()</strong> : <em>bool</em> |
| public | <strong>abstract humanTimeDiff(</strong><em>int</em> <strong>$time</strong>)</strong> : <em>string</em><br /><em>Get a human readable string explaining how long ago given time is, or how much time there's left until the time takes place</em> |
| public | <strong>abstract isPreloaded(</strong><em>int</em> <strong>$id</strong>)</strong> : <em>bool</em><br /><em>Tells whether or not a page/post with given id is preloaded</em> |
| public | <strong>abstract loadExternalURL(</strong><em>string</em> <strong>$url</strong>)</strong> : <em>array</em><br /><em>Load the contents of an external URL. This function returns an array with  'headers', 'body', 'response', 'cookies', 'filename' if request was successful, or throws an Exception if failed</em> |
| public | <strong>abstract loadRelatedPages(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>array</em><br /><em>Get an array with all pages that give list is related to</em> |
| public | <strong>abstract loadRelatedWidgets(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>array</em><br /><em>Get all "widgets" that displays given Arlima list</em> |
| public | <strong>abstract postToArlimaArticle(</strong><em>int/object</em> <strong>$post</strong>, <em>mixed/null/string</em> <strong>$text=null</strong>)</strong> : <em>[\Arlima_Article](#class-arlima_article)</em> |
| public | <strong>abstract preLoadPosts(</strong><em>array</em> <strong>$post_ids</strong>)</strong> : <em>mixed</em><br /><em>Preloads posts/pages with given ids. Use this function to lower the amount of db queries sent when using any of the post-functions provided by this class</em> |
| public | <strong>abstract prepare(</strong><em>string</em> <strong>$sql</strong>, <em>array</em> <strong>$params</strong>)</strong> : <em>mixed</em><br /><em>Prepare an SQL-statement.</em> |
| public | <strong>abstract prepareForPostLoop(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>mixed</em><br /><em>Do the preparations necessary before iterating over a set of posts. This function should be called when Arlima imitates an iteration over posts that the underlying system normally does.</em> |
| public | <strong>abstract relate(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>int</em> <strong>$post_id</strong>, <em>array</em> <strong>$attr</strong>)</strong> : <em>void</em><br /><em>Relate an Arlima list with a post/page</em> |
| public | <strong>abstract removeAllRelations(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em><br /><em>Remove relations made between pages and given list</em> |
| public | <strong>abstract removeImportedList(</strong><em>string</em> <strong>$url</strong>)</strong> : <em>void</em> |
| public | <strong>abstract removeRelation(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>void</em><br /><em>Remove possible relation this post/page might have with an Arlima list</em> |
| public | <strong>abstract resetAfterPostLoop()</strong> : <em>void</em><br /><em>Function that should be called after messing with the internal globals used by the system</em> |
| public | <strong>abstract resolveFilePath(</strong><em>string</em> <strong>$path</strong>, <em>bool</em> <strong>$relative=false</strong>)</strong> : <em>bool/string</em><br /><em>Returns the file path if it resides within the directory of the CMS.</em> |
| public | <strong>abstract runSQLQuery(</strong><em>string</em> <strong>$sql</strong>)</strong> : <em>mixed</em><br /><em>Calls a method on DB and throws Exception if db error occurs</em> |
| public | <strong>abstract sanitizeText(</strong><em>string</em> <strong>$txt</strong>, <em>string</em> <strong>$allowed=`''`</strong>)</strong> : <em>string</em><br /><em>Sanitize text from CMS specific tags/code as well as ordinary html tags. Use $allowed to tell which tags that should'nt become removed</em> |
| public | <strong>abstract saveImportedLists(</strong><em>array</em> <strong>$lists</strong>)</strong> : <em>mixed</em><br /><em>Save an array with URL:s of external lists that should be available in the list manager</em> |
| public | <strong>abstract scheduleEvent(</strong><em>int</em> <strong>$schedule_time</strong>, <em>string</em> <strong>$event</strong>, <em>mixed</em> <strong>$args</strong>)</strong> : <em>void</em><br /><em>Schedule an event to take place in the future</em> |
| public | <strong>abstract setPostInGlobalScope(</strong><em>mixed</em> <strong>$id</strong>)</strong> : <em>mixed</em> |
| public | <strong>abstract translate(</strong><em>mixed</em> <strong>$str</strong>)</strong> : <em>string</em><br /><em>Translate current string</em> |

<hr /> 
### Class: Arlima_CMSLoop

> Class that makes it possible to use Arlima_ListTemplateRenderer on ordinary article list iterations done by the underlying CMS

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>mixed/string</em> <strong>$template_path=null</strong>)</strong> : <em>void</em> |
| public static | <strong>defaultHeaderCallback(</strong><em>int</em> <strong>$article_counter</strong>, <em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>\stdClass</em> <strong>$post_id</strong>, <em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>mixed</em> |
| public | <strong>generateListHtml(</strong><em>bool</em> <strong>$echo_output=true</strong>)</strong> : <em>string</em><br /><em>Set $echo_output to false to rendered list as a string</em> |
| public | <strong>getExcludePosts()</strong> : <em>array</em> |
| public | <strong>havePosts()</strong> : <em>bool</em> |
| public | <strong>setDefaultArticleProperties(</strong><em>mixed</em> <strong>$arr</strong>)</strong> : <em>void</em> |
| public | <strong>setExcludePosts(</strong><em>array</em> <strong>$exclude_posts</strong>)</strong> : <em>void</em> |
| public | <strong>setHeaderCallback(</strong><em>\Closure</em> <strong>$callback</strong>)</strong> : <em>void</em> |
| protected | <strong>createArticleFromPost(</strong><em>int</em> <strong>$post_id</strong>, <em>int</em> <strong>$article_counter</strong>)</strong> : <em>[\Arlima_Article](#class-arlima_article)</em> |

*This class extends [\Arlima_ListTemplateRenderer](#class-arlima_listtemplaterenderer)*

<hr /> 
### Class: Arlima_ExportManager

> Class that is responsible of exporting Arlima lists related to wordpress pages.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>array</em> <strong>$avail_for_export=array()</strong>)</strong> : <em>void</em> |
| public | <strong>convertList(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>string</em> <strong>$format</strong>)</strong> : <em>string</em> |
| public | <strong>export(</strong><em>string</em> <strong>$page_slug</strong>, <em>string</em> <strong>$format</strong>)</strong> : <em>void</em><br /><em>This function will output the list related to given page in a JSON or RSS format. It sends out appropriate response headers depending on the request being made.</em> |
| public | <strong>getListsAvailableForExport()</strong> : <em>array</em> |
| public | <strong>isAvailableForExport(</strong><em>[\Arlima_List](#class-arlima_list)/int</em> <strong>$list</strong>)</strong> : <em>bool</em> |

<hr /> 
### Class: Arlima_FailedListImportException

> Exception thrown when loading of an external list fails

| Visibility | Function |
|:-----------|:---------|
| public | <strong>getURL()</strong> : <em>string</em> |
| public | <strong>setURL(</strong><em>mixed</em> <strong>$url</strong>)</strong> : <em>void</em> |

*This class extends \Exception*

<hr /> 
### Class: Arlima_FileInclude

> Class that can include php files in article lists

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>currentFileArgs()</strong> : <em>array/bool</em> |
| public | <strong>getFileArgs(</strong><em>string</em> <strong>$file</strong>)</strong> : <em>array</em> |
| public | <strong>includeFile(</strong><em>string</em> <strong>$file</strong>, <em>array</em> <strong>$args</strong>, <em>[\Arlima_AbstractListRenderingManager](#class-arlima_abstractlistrenderingmanager-abstract)/null</em> <strong>$renderer=null</strong>, <em>[\Arlima_Article](#class-arlima_article)/null</em> <strong>$article=null</strong>)</strong> : <em>string</em> |
| public static | <strong>isCollectingArgs()</strong> : <em>bool</em> |
| public static | <strong>setCollectedArgs(</strong><em>array</em> <strong>$args</strong>)</strong> : <em>void</em> |

<hr /> 
### Class: Arlima_ImportManager

> Class that can import lists in JSON or RSS format from a remote server, and turn them into Arlima_List objects.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |
| public static | <strong>displayImportedList(</strong><em>string</em> <strong>$url</strong>, <em>string</em> <strong>$name</strong>)</strong> : <em>void</em><br /><em>Helper function that displays info and remove button for an imported list</em> |
| public | <strong>getImportedLists()</strong> : <em>array</em> |
| public | <strong>importList(</strong><em>string</em> <strong>$url</strong>, <em>bool</em> <strong>$refresh=true</strong>)</strong> : <em>array containing 'title' and 'url'</em><br /><em>This function is used to register an external list as an imported list, if you only want to fetch content from an external list use Arlima_ImportManager::loadList()</em> |
| public | <strong>loadList(</strong><em>mixed</em> <strong>$url</strong>)</strong> : <em>[\Arlima_List](#class-arlima_list)</em><br /><em>Load a Arlima list or RSS feed from a remote website and convert to Arlima list object</em> |
| public | <strong>serverResponseToArlimaList(</strong><em>mixed</em> <strong>$response</strong>, <em>string</em> <strong>$url</strong>)</strong> : <em>[\Arlima_List](#class-arlima_list)</em> |

<hr /> 
### Class: Arlima_List

> Object representing an article list.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>bool</em> <strong>$exists=false</strong>, <em>int</em> <strong>$id</strong>, <em>bool</em> <strong>$is_imported=false</strong>)</strong> : <em>void</em> |
| public | <strong>addOptions(</strong><em>array</em> <strong>$new_options</strong>)</strong> : <em>array</em><br /><em>Merge in new options</em> |
| public static | <strong>builder()</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em> |
| public | <strong>containsPost(</strong><em>int</em> <strong>$post_id</strong>)</strong> : <em>bool</em><br /><em>Tells whether or not this list contains one or more articles connected to the post with given id</em> |
| public | <strong>exists()</strong> : <em>bool</em><br /><em>Tells whether or not this arlima list exists in the database</em> |
| public | <strong>getArticle(</strong><em>mixed</em> <strong>$index</strong>)</strong> : <em>[\Arlima_Article](#class-arlima_article)</em><br /><em>Get article object at given index</em> |
| public | <strong>getArticles()</strong> : <em>[\Arlima_Article](#class-arlima_article)[]</em> |
| public | <strong>getContainingPosts()</strong> : <em>array</em><br /><em>Returns a list with id numbers of the posts that has a connection to one or more articles in this list</em> |
| public | <strong>getCreated()</strong> : <em>int</em> |
| public static | <strong>getDefaultListOptions()</strong> : <em>array</em> |
| public | <strong>getId()</strong> : <em>int</em> |
| public | <strong>getMaxlength()</strong> : <em>int</em> |
| public | <strong>getOption(</strong><em>string</em> <strong>$name</strong>, <em>mixed</em> <strong>$default=null</strong>)</strong> : <em>string/null</em><br /><em>Get a list option (also has an aliased function named opt())</em> |
| public | <strong>getOptions()</strong> : <em>array</em> |
| public | <strong>getPublishedVersions()</strong> : <em>array</em> |
| public | <strong>getScheduledVersions()</strong> : <em>array</em> |
| public | <strong>getSlug()</strong> : <em>string</em> |
| public | <strong>getStatus()</strong> : <em>int</em> |
| public | <strong>getTitle()</strong> : <em>string</em> |
| public | <strong>getTitleElement()</strong> : <em>string</em><br /><em>Will return the HTMl element used as header for articles in this list. If something other then a valid header element is used this function will return an empty string</em> |
| public | <strong>getVersion()</strong> : <em>array</em><br /><em>Returns information about this version of the list</em> |
| public | <strong>getVersionAttribute(</strong><em>string</em> <strong>$name</strong>)</strong> : <em>string</em> |
| public | <strong>getVersions()</strong> : <em>array</em><br /><em>A list with the latest created versions of this list</em> |
| public | <strong>hasOption(</strong><em>string</em> <strong>$name</strong>)</strong> : <em>bool</em> |
| public | <strong>id()</strong> : <em>int</em> |
| public | <strong>isAvailable(</strong><em>string</em> <strong>$template</strong>)</strong> : <em>bool</em><br /><em>Tells whether or not this list allows use of given template</em> |
| public | <strong>isImported()</strong> : <em>bool</em><br /><em>Tells whether or not this arlima list is loaded from a remote host</em> |
| public | <strong>isLatestPublishedVersion()</strong> : <em>bool</em> |
| public | <strong>isPreview()</strong> : <em>bool</em><br /><em>Tells whether or not the list contains a preview version</em> |
| public | <strong>isPublished()</strong> : <em>bool</em> |
| public | <strong>isScheduled()</strong> : <em>bool</em><br /><em>Tells whether or not the list contains a scheduled version</em> |
| public | <strong>isSupportingEditorTemplateSwitch()</strong> : <em>bool</em><br /><em>Whether or not editors is allowed to switch template on specific articles in the list</em> |
| public | <strong>isSupportingSections()</strong> : <em>bool</em><br /><em>Whether or not admins can create "sections" in the list</em> |
| public | <strong>lastModified()</strong> : <em>int</em><br /><em>Get the modification date (timestamp) when this version of the list was created</em> |
| public | <strong>numArticles()</strong> : <em>int</em> |
| public | <strong>opt(</strong><em>mixed</em> <strong>$name</strong>, <em>mixed</em> <strong>$default=null</strong>)</strong> : <em>string/null</em><br /><em>Alias for getOption($name, $default=null)</em> |
| public | <strong>setArticles(</strong><em>array</em> <strong>$articles</strong>)</strong> : <em>void</em> |
| public | <strong>setCreated(</strong><em>int</em> <strong>$created</strong>)</strong> : <em>void</em> |
| public | <strong>setId(</strong><em>int</em> <strong>$id</strong>)</strong> : <em>void</em> |
| public | <strong>setMaxlength(</strong><em>int</em> <strong>$maxlength</strong>)</strong> : <em>void</em> |
| public | <strong>setOption(</strong><em>string</em> <strong>$name</strong>, <em>string</em> <strong>$val</strong>)</strong> : <em>void</em> |
| public | <strong>setOptions(</strong><em>array</em> <strong>$options</strong>)</strong> : <em>void</em><br /><em>Set options for the list</em> |
| public | <strong>setPublishedVersions(</strong><em>array</em> <strong>$versions</strong>)</strong> : <em>void</em> |
| public | <strong>setScheduledVersions(</strong><em>array</em> <strong>$scheduled_versions</strong>)</strong> : <em>void</em> |
| public | <strong>setSlug(</strong><em>string</em> <strong>$slug</strong>)</strong> : <em>void</em> |
| public | <strong>setTitle(</strong><em>string</em> <strong>$title</strong>)</strong> : <em>void</em> |
| public | <strong>setVersion(</strong><em>array</em> <strong>$version_data</strong>)</strong> : <em>void</em> |
| public | <strong>setVersions(</strong><em>array</em> <strong>$versions</strong>)</strong> : <em>void</em> |
| public | <strong>toArray()</strong> : <em>array</em> |

<hr /> 
### Class: Arlima_ListBuilder

> Class that can put together list objects following a set of instructions. See https://github.com/victorjonsson/Arlima/wiki/Server-side,-in-depth

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Arlima_ListRepository](#class-arlima_listrepository)</em> <strong>$list_repo=null</strong>, <em>[\Arlima_ListVersionRepository](#class-arlima_listversionrepository)</em> <strong>$version_repo=null</strong>)</strong> : <em>void</em> |
| public | <strong>build()</strong> : <em>[\Arlima_List](#class-arlima_list)</em><br /><em>This function will always return a List object even if it might not exists, thus you should call $list->exists() on returned list to verify that the list actually do exist</em> |
| public | <strong>fromPage(</strong><em>int</em> <strong>$page_id</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em> |
| public | <strong>id(</strong><em>int</em> <strong>$in</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em> |
| public | <strong>import(</strong><em>string/bool</em> <strong>$in</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em><br /><em>URL of external RSS-feed or Arlima list export (set to false to not import anything)</em> |
| public | <strong>includeFutureArticles(</strong><em>bool</em> <strong>$toggle=true</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em> |
| public | <strong>loadPreview(</strong><em>bool</em> <strong>$toggle=true</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em> |
| public | <strong>saveImportedList(</strong><em>bool</em> <strong>$toggle=true</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em> |
| public | <strong>slug(</strong><em>string</em> <strong>$in</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em> |
| public | <strong>version(</strong><em>int/bool</em> <strong>$in</strong>)</strong> : <em>[\Arlima_ListBuilder](#class-arlima_listbuilder)</em><br /><em>Omit calling this function or set $in to false to load the latest published version of the list</em> |
| protected | <strong>assembleExternalList()</strong> : <em>[\Arlima_List](#class-arlima_list)</em> |
| protected | <strong>assembleList()</strong> : <em>[\Arlima_List](#class-arlima_list)</em> |

<hr /> 
### <strike>Class: Arlima_ListFactory</strike>

> **DEPRECATED** This class is deprecated

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>\wpdb</em> <strong>$db=null</strong>, <em>mixed/null</em> <strong>$cache=null</strong>)</strong> : <em>void</em> |
| public static | <strong>createArticleDataArray(</strong><em>array</em> <strong>$override=array()</strong>)</strong> : <em>array/[\Arlima_Article](#class-arlima_article)</em><br /><em>The article data is in fact created with javascript in front-end so you can't see this function as the sole creator of article objects. For that reason it might be good to take look at this function once in a while, making sure it generates a similar object as generated with javascript in front-end.</em> |
| public | <strong>createList(</strong><em>mixed</em> <strong>$title</strong>, <em>mixed</em> <strong>$slug</strong>, <em>array</em> <strong>$options=array()</strong>, <em>mixed/int</em> <strong>$max_length=50</strong>)</strong> : <em>[\Arlima_List](#class-arlima_list)</em><br /><em>Creates a new article list</em> |
| public static | <strong>databaseUpdates(</strong><em>mixed</em> <strong>$version</strong>)</strong> : <em>void</em> |
| public | <strong>deleteList(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em> |
| public | <strong>deleteListVersion(</strong><em>mixed</em> <strong>$version_id</strong>)</strong> : <em>void</em> |
| public | <strong>getLatestArticle(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>array</em><br /><em>Get latest article teaser created that is related to given post</em> |
| public | <strong>getListId(</strong><em>mixed</em> <strong>$slug</strong>)</strong> : <em>int/bool</em> |
| public | <strong>install()</strong> : <em>void</em><br /><em>Database installer for this plugin.</em> |
| public | <strong>loadLatestPreview(</strong><em>int</em> <strong>$id</strong>)</strong> : <em>[\Arlima_List](#class-arlima_list)</em><br /><em>Load latest preview version of article list with given id.</em> |
| public | <strong>loadList(</strong><em>int/string</em> <strong>$id</strong>, <em>bool/mixed</em> <strong>$version=false</strong>, <em>bool</em> <strong>$include_future_posts=false</strong>)</strong> : <em>[\Arlima_List](#class-arlima_list)</em><br /><em>Future posts will always be included in the list if you're loading a specific version of the list. Otherwise you can use the argument $include_future_posts to control if the list should contain future posts as well. Setting $include_future_posts to true will how ever disable the caching of the article data</em> |
| public | <strong>loadListSlugs()</strong> : <em>array</em><br /><em>will return an array looking like array( stdClass(id => ... title => ... slug => ...) )</em> |
| public | <strong>loadListsByArticleId(</strong><em>int</em> <strong>$post_id</strong>)</strong> : <em>array</em><br /><em>Loads an array with objects containing list id and options that have teasers that are linked to the post with $post_id</em> |
| public static | <strong>postToArlimaArticle(</strong><em>mixed</em> <strong>$post</strong>, <em>mixed/string/null</em> <strong>$text=null</strong>, <em>array</em> <strong>$override=array()</strong>)</strong> : <em>array</em><br /><em>Takes a post and returns an Arlima article object</em> |
| public | <strong>saveNewListVersion(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>mixed</em> <strong>$articles</strong>, <em>mixed</em> <strong>$user_id</strong>, <em>int</em> <strong>$schedule_time</strong>, <em>bool</em> <strong>$preview=false</strong>)</strong> : <em>int</em> |
| public | <strong>uninstall()</strong> : <em>void</em><br /><em>Removes the database tables created when plugin was installed</em> |
| public | <strong>updateArticle(</strong><em>int</em> <strong>$id</strong>, <em>array</em> <strong>$data</strong>)</strong> : <em>void</em> |
| public | <strong>updateArticlePublishDate(</strong><em>\stdClass/\WP_Post</em> <strong>$post</strong>)</strong> : <em>void</em><br /><em>Updates publish date for all arlima articles related to given post and clears the cache of the lists where they appear</em> |
| public | <strong>updateListProperties(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em><br /><em>Will update name, slug and options of given list in the database</em> |
| public | <strong>updateListVersion(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>array</em> <strong>$articles</strong>, <em>int</em> <strong>$version_id</strong>)</strong> : <em>void</em> |

<hr /> 
### Class: Arlima_ListRepository

> Repository that is used to perform CRUD-operation on list objects

| Visibility | Function |
|:-----------|:---------|
| public | <strong>create(</strong><em>mixed</em> <strong>$title</strong>, <em>mixed</em> <strong>$slug</strong>, <em>array</em> <strong>$options=array()</strong>, <em>mixed/int</em> <strong>$max_length=50</strong>)</strong> : <em>[\Arlima_List](#class-arlima_list)</em><br /><em>Create a new list</em> |
| public | <strong>createDatabaseTables()</strong> : <em>void</em> |
| public | <strong>delete(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em><br /><em>Remove a list from the database</em> |
| public | <strong>getDatabaseTables()</strong> : <em>array</em> |
| public | <strong>getListId(</strong><em>mixed</em> <strong>$slug</strong>)</strong> : <em>int/bool</em> |
| public | <strong>load(</strong><em>int/string</em> <strong>$id_or_slug</strong>)</strong> : <em>[\Arlima_List](#class-arlima_list)</em> |
| public | <strong>loadListSlugs()</strong> : <em>array</em><br /><em>Will return an array with info (slug, id and title) about all the lists in the database array( stdClass(id => ... title => ... slug => ...), ... )</em> |
| public | <strong>update(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em><br /><em>Update a list in the database</em> |
| public | <strong>updateDatabaseTables(</strong><em>\float</em> <strong>$currently_installed_version</strong>)</strong> : <em>void</em> |

*This class extends [\Arlima_AbstractRepositoryDB](#class-arlima_abstractrepositorydb-abstract)*

<hr /> 
### Class: Arlima_ListTemplateRenderer

> Class that can render an Arlima article list using a template engine. The class uses templates available in the path given on construct, if template not found it falls back on templates available in this plugin directory (arlima/templates)

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Arlima_List](#class-arlima_list)/\stdClass</em> <strong>$list</strong>, <em>mixed/string</em> <strong>$template_path=null</strong>)</strong> : <em>void</em><br /><em>Class constructor</em> |
| protected | <strong>generateArticleHtml(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>int</em> <strong>$index</strong>, <em>mixed</em> <strong>$post</strong>)</strong> : <em>array</em> |
| protected | <strong>generateListHtml(</strong><em>bool</em> <strong>$echo_output=true</strong>)</strong> : <em>string</em><br /><em>Will render all articles in the arlima list using templates. The template to be used is an option in the article list object (Arlima_List). If no template exists in declared template paths we will fall back on default templates (plugins/arlima/template/[name].tmpl)</em> |
| protected | <strong>getTemplateToUse(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>)</strong> : <em>null/string</em> |
| protected | <strong>includeArticleFile(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>int</em> <strong>$index</strong>)</strong> : <em>string</em> |

*This class extends [\Arlima_AbstractListRenderingManager](#class-arlima_abstractlistrenderingmanager-abstract)*

<hr /> 
### Class: Arlima_ListVersionRepository

> Repository that is used to perform CRUD-operation on list versions

| Visibility | Function |
|:-----------|:---------|
| public | <strong>addArticles(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>bool/int/bool</em> <strong>$version=false</strong>, <em>bool</em> <strong>$include_future_articles=false</strong>)</strong> : <em>array</em><br /><em>Add articles and current version to given list object</em> |
| public | <strong>addPreviewArticles(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em><br /><em>Add articles and version of latest preview version to given list object</em> |
| public | <strong>addVersionHistory(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>array</em><br /><em>Add version history data to list and return an array with all published versions</em> |
| public | <strong>clear(</strong><em>int</em> <strong>$version_id</strong>)</strong> : <em>void</em><br /><em>Removes all articles in a version.</em> |
| public | <strong>create(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>array</em> <strong>$articles</strong>, <em>int</em> <strong>$user_id</strong>, <em>bool</em> <strong>$preview=false</strong>)</strong> : <em>int</em> |
| public static | <strong>createArticle(</strong><em>array</em> <strong>$override=array()</strong>)</strong> : <em>[\Arlima_Article](#class-arlima_article)</em><br /><em>The article data is in fact created with javascript in front-end so you can't see this function as the sole creator of article objects. For that reason it might be good to take look at this function once in a while, making sure it generates a similar object as generated with javascript in front-end.</em> |
| public | <strong>createDatabaseTables()</strong> : <em>void</em> |
| public | <strong>createScheduledVersion(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>array</em> <strong>$articles</strong>, <em>int</em> <strong>$user_id</strong>, <em>int</em> <strong>$schedule_time</strong>)</strong> : <em>int</em> |
| public | <strong>delete(</strong><em>int</em> <strong>$version_id</strong>)</strong> : <em>void</em><br /><em>Calls clear() internally</em> |
| public | <strong>deleteListVersions(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em> |
| public | <strong>findListsByPostId(</strong><em>int</em> <strong>$post_id</strong>)</strong> : <em>array</em><br /><em>Loads an array with objects containing list id and options that have teasers that are connected to the post with $post_id</em> |
| public | <strong>getDatabaseTables()</strong> : <em>array</em> |
| public | <strong>getLatestArticle(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>array</em><br /><em>Get latest article teaser created that is related to given post</em> |
| public | <strong>loadListVersions(</strong><em>[\Arlima_List](#class-arlima_list)/int</em> <strong>$list</strong>)</strong> : <em>array</em><br /><em>Get an array with all versions that a list has</em> |
| public | <strong>update(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>array</em> <strong>$articles</strong>, <em>int</em> <strong>$version_id</strong>)</strong> : <em>void</em><br /><em>Change the article collection belonging to a list version</em> |
| public | <strong>updateArticle(</strong><em>int</em> <strong>$id</strong>, <em>array/[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>)</strong> : <em>array</em><br /><em>This function will return array with those columns that were updated</em> |
| public | <strong>updateArticlePublishDate(</strong><em>int</em> <strong>$time</strong>, <em>int</em> <strong>$post_id</strong>)</strong> : <em>void</em><br /><em>Updates publish date for all arlima articles related to given post and clears the cache of the lists where they appear</em> |
| public | <strong>updateDatabaseTables(</strong><em>\float</em> <strong>$currently_installed_version</strong>)</strong> : <em>void</em> |
| public | <strong>versionBelongsToList(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>int</em> <strong>$version_id</strong>)</strong> : <em>bool</em> |
| protected | <strong>toArrayWithUpdatedPublishDate(</strong><em>array/[\Arlima_Article](#class-arlima_article)[]</em> <strong>$articles</strong>)</strong> : <em>mixed</em><br /><em>Get an array containing all given articles, converted from objects to arrays. The publish date of each article will also be updated with the publish date of possibly connected post</em> |
###### Examples of Arlima_ListVersionRepository::updateArticle()
```php
<?php
  $article_arr = array(...);
  $updated = $repo->updateArticle($article->getId(), $article_arr);
  $not_updated = array_diff($article_arr, $updated);
````

*This class extends [\Arlima_AbstractRepositoryDB](#class-arlima_abstractrepositorydb-abstract)*

<hr /> 
### <strike>Class: Arlima_Plugin</strike>

> **DEPRECATED** Use Arlima_WP_Plugin instead

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |

*This class extends [\Arlima_WP_Plugin](#class-arlima_wp_plugin)*

<hr /> 
### Class: Arlima_PostSearchModifier

> Class working as a container from where you can get or set rules that modifies a search form using WP_Query. (Maybe this class should reside within the WP namespace...)

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>filterWPQuery(</strong><em>mixed</em> <strong>$args</strong>, <em>mixed</em> <strong>$post_data</strong>)</strong> : <em>mixed</em> |
| public static | <strong>invokeFormCallbacks()</strong> : <em>array</em> |
| public static | <strong>modifySearch(</strong><em>mixed</em> <strong>$form_callback</strong>, <em>mixed</em> <strong>$query_callback</strong>)</strong> : <em>void</em> |

<hr /> 
### Class: Arlima_SimpleListRenderer

> The most simple type of list renderer

###### Example
```php
<?php
     $list = Arlima_List::builder()->slug('frontpage')->build();
     $renderer = new Arlima_SimpleListRenderer($list);
     $renderer->setDisplayPostCallback(function($article_counter, $article, $post, $list) {
  return '...';
     });
     $renderer->renderList();
````

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>bool</em> <strong>$echo=true</strong>)</strong> : <em>void</em> |
| public static | <strong>defaultPostDisplayCallback(</strong><em>int</em> <strong>$article_counter</strong>, <em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>\WP_Post/bool</em> <strong>$post</strong>, <em>[\Arlima_AbstractListRenderingManager](#class-arlima_abstractlistrenderingmanager-abstract)</em> <strong>$renderer</strong>, <em>bool</em> <strong>$echo</strong>)</strong> : <em>string</em> |
| public | <strong>generateListHtml(</strong><em>bool</em> <strong>$echo_output=true</strong>)</strong> : <em>string</em> |
| public | <strong>setDisplayArticleCallback(</strong><em>\Closure</em> <strong>$func</strong>)</strong> : <em>void</em> |
| protected | <strong>generateArticleHtml(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>int</em> <strong>$index</strong>, <em>null/\stdClass/\WP_Post</em> <strong>$post</strong>)</strong> : <em>mixed</em> |

*This class extends [\Arlima_AbstractListRenderingManager](#class-arlima_abstractlistrenderingmanager-abstract)*

<hr /> 
### Class: Arlima_TemplateEngine

> Facade in front of the template engine (mustasche) used by Arlima.

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>create(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>mixed/null/string</em> <strong>$template_path=null</strong>)</strong> : <em>[\Arlima_TemplateEngineInterface](#interface-arlima_templateengineinterface)</em><br /><em>Factory method for creating instances of the template engine</em> |
| public | <strong>renderArticle(</strong><em>string</em> <strong>$template_name</strong>, <em>int</em> <strong>$article_counter</strong>, <em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>object</em> <strong>$post</strong>, <em>string</em> <strong>$child_articles=`''`</strong>, <em>bool</em> <strong>$child_split_state=false</strong>)</strong> : <em>string</em> |
| public | <strong>setDefaultTemplate(</strong><em>mixed</em> <strong>$tmpl_name</strong>)</strong> : <em>bool</em><br /><em>Set which template that should be used as default. Will return false if given template can't be found</em> |
| protected | <strong>__construct(</strong><em>[\Arlima_TemplatePathResolver](#class-arlima_templatepathresolver)</em> <strong>$tmpl_path_resolver</strong>, <em>[\Arlima_TemplateObjectCreator](#class-arlima_templateobjectcreator)</em> <strong>$obj_creator</strong>, <em>\Mustache_Engine</em> <strong>$mustache</strong>)</strong> : <em>void</em> |
| protected | <strong>loadTemplateObject(</strong><em>string</em> <strong>$template_name</strong>)</strong> : <em>\Mustache_Template</em><br /><em>Load template that should be used for given article.</em> |

*This class implements [\Arlima_TemplateEngineInterface](#interface-arlima_templateengineinterface)*

<hr /> 
### Interface: Arlima_TemplateEngineInterface

> Interface for classes that can render Arlima_Article objects

| Visibility | Function |
|:-----------|:---------|
| public | <strong>abstract renderArticle(</strong><em>string</em> <strong>$template_name</strong>, <em>int</em> <strong>$article_counter</strong>, <em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>object/mixed</em> <strong>$post</strong>, <em>string</em> <strong>$child_articles=`''`</strong>, <em>bool</em> <strong>$child_split_state=false</strong>)</strong> : <em>string</em> |
| public | <strong>abstract setDefaultTemplate(</strong><em>string</em> <strong>$tmpl_name</strong>)</strong> : <em>bool</em><br /><em>Set which template that should be used as default. Will return false if given template can't be found</em> |

<hr /> 
### Class: Arlima_TemplateObjectCreator

> Class with all the knowledge about how to convert a typical arlima article array to an object used when the TemplateEngine constructs the articles view (template)

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |
| public static | <strong>applyImageFilters(</strong><em>mixed</em> <strong>$article</strong>, <em>mixed</em> <strong>$article_counter</strong>, <em>mixed</em> <strong>$post</strong>, <em>mixed</em> <strong>$list</strong>, <em>bool</em> <strong>$is_child_split=false</strong>)</strong> : <em>string</em><br /><em>Deprecated since 3.0.beta.37</em> |
| public | <strong>create(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>mixed</em> <strong>$post</strong>, <em>mixed</em> <strong>$article_counter</strong>, <em>mixed/null</em> <strong>$template_name=null</strong>)</strong> : <em>array</em> |
| public | <strong>getChildSplitState()</strong> : <em>null/array</em> |
| public static | <strong>getFilterSuffix()</strong> : <em>string</em> |
| public | <strong>getIsChild()</strong> : <em>bool/string</em> |
| public | <strong>getList()</strong> : <em>[\Arlima_List](#class-arlima_list)</em> |
| public | <strong>setAfterTitleHtml(</strong><em>string</em> <strong>$after_title_html</strong>)</strong> : <em>void</em> |
| public static | <strong>setArticleWidth(</strong><em>int</em> <strong>$width</strong>)</strong> : <em>void</em> |
| public | <strong>setBeforeTitleHtml(</strong><em>string</em> <strong>$after_title_html</strong>)</strong> : <em>void</em> |
| public | <strong>setChildSplitState(</strong><em>null/array</em> <strong>$split_state</strong>)</strong> : <em>void</em> |
| public static | <strong>setFilterSuffix(</strong><em>string</em> <strong>$s</strong>)</strong> : <em>void</em> |
| public | <strong>setIsChild(</strong><em>bool/string</em> <strong>$is_child</strong>)</strong> : <em>void</em> |
| public | <strong>setList(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>)</strong> : <em>void</em> |
| protected | <strong>generateImageData(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>mixed</em> <strong>$article_counter</strong>, <em>mixed</em> <strong>$data</strong>, <em>mixed</em> <strong>$img_opt_size</strong>, <em>mixed</em> <strong>$post</strong>)</strong> : <em>void</em> |
| protected | <strong>generateStreamerData(</strong><em>mixed</em> <strong>$has_streamer</strong>, <em>mixed</em> <strong>$data</strong>, <em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>)</strong> : <em>void</em> |

<hr /> 
### Class: Arlima_TemplatePathResolver

> Class with all the know how about template paths

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>mixed/array</em> <strong>$paths=null</strong>, <em>bool</em> <strong>$apply_path_filter=true</strong>)</strong> : <em>void</em> |
| public | <strong>fileToUrl(</strong><em>string</em> <strong>$template_file</strong>)</strong> : <em>string</em><br /><em>Takes a file path to somewhere within the CMS directory and turns it into an url.</em> |
| public | <strong>find(</strong><em>string</em> <strong>$template_name</strong>)</strong> : <em>bool/string</em><br /><em>Find the path of a template file with given name.</em> |
| public | <strong>getDefaultTemplate()</strong> : <em>string</em> |
| public | <strong>getPaths()</strong> : <em>array</em><br /><em>Returns all registered template paths</em> |
| public | <strong>getTemplateFiles()</strong> : <em>array</em><br /><em>Returns all files having the extension .tmpl located in registered template paths</em> |
| public static | <strong>isTemplateFile(</strong><em>string</em> <strong>$path</strong>)</strong> : <em>bool</em> |
###### Examples of Arlima_TemplatePathResolver::find()
```php
<?php
  $resolver = new Arlima_TemplatePathResolver();
  $abs_path = $resolve->find('article.tmpl');
````

<hr /> 
### Class: Arlima_Utils

> General functions

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>getTitleHtml(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>array</em> <strong>$options</strong>, <em>array</em> <strong>$header_classes=array()</strong>)</strong> : <em>string</em> |
| public static | <strong>linkWrap(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>, <em>string</em> <strong>$content</strong>, <em>array</em> <strong>$classes=array()</strong>)</strong> : <em>string</em><br /><em>Wrap given content with an a-element linking to the URL of the article</em> |
| public static | <strike><strong>resolveURL(</strong><em>[\Arlima_Article](#class-arlima_article)</em> <strong>$article</strong>)</strong> : <em>null/string</em></strike><br /><em>DEPRECATED - Use Arlima_Article::getURL instead ($article->getURL())</em> |
| public static | <strong>shorten(</strong><em>mixed</em> <strong>$text</strong>, <em>mixed/int</em> <strong>$num_words=24</strong>, <em>string</em> <strong>$allowed_tags=`''`</strong>)</strong> : <em>string</em><br /><em>Shortens any text to number of words.</em> |
| public static | <strong>timeStamp()</strong> : <em>int</em><br /><em>Get unix timestamp</em> |
| public static | <strong>versionNumberToFloat(</strong><em>string</em> <strong>$num</strong>)</strong> : <em>\float</em> |
| public static | <strong>warnAboutDeprecation(</strong><em>string</em> <strong>$func</strong>, <em>string</em> <strong>$new</strong>)</strong> : <em>void</em> |

<hr /> 
### Class: Arlima_WP_AbstractAdminPage (abstract)

> Base class extended by classes representing admin pages in wordpress. Using this class reduces the amount of code that needs to be written when wanting to have several admin pages in a plugin. It also reduces code duplication that often appears when having several admin pages in one plugin.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Arlima_WP_Plugin](#class-arlima_wp_plugin)</em> <strong>$arlima_plugin</strong>)</strong> : <em>void</em> |
| public | <strong>capability()</strong> : <em>string</em><br /><em>User capability needed to visit this page</em> |
| public | <strong>enqueueScripts()</strong> : <em>void</em><br /><em>Enqueue's the scripts returned by Arlima_AbstractAdminPage::scripts()</em> |
| public | <strong>enqueueStyles()</strong> : <em>void</em><br /><em>Enqueue's the stylesheets returned by Arlima_AbstractAdminPage::styleSheets()</em> |
| public | <strong>abstract getMenuName()</strong> : <em>mixed</em><br /><em>Menu name of this plugin</em> |
| public | <strong>abstract getName()</strong> : <em>mixed</em><br /><em>Name of this page</em> |
| public | <strong>getPlugin()</strong> : <em>[\Arlima_WP_Plugin](#class-arlima_wp_plugin)</em> |
| public | <strong>icon()</strong> : <em>string</em><br /><em>Only used when parentSlug() returns empty string</em> |
| public | <strong>loadPage()</strong> : <em>mixed</em><br /><em>Loads the view of this page</em> |
| public static | <strong>outputLessJS()</strong> : <em>void</em> |
| public | <strong>abstract parentSlug()</strong> : <em>string</em><br /><em>Menu slug of parent page. Return empty string to set as parent page</em> |
| public | <strong>registerPage()</strong> : <em>void</em><br /><em>Registers the page and enqueue's the js and css in case this page is being visited.</em> |
| public | <strong>scripts()</strong> : <em>array</em> |
| public | <strong>setPlugin(</strong><em>mixed</em> <strong>$plugin</strong>)</strong> : <em>void</em> |
| public | <strong>abstract slug()</strong> : <em>string</em><br /><em>slug used for this admin page</em> |
| public | <strong>styleSheets()</strong> : <em>array</em> |
| protected | <strong>requestedAdminPage()</strong> : <em>string/bool</em> |

<hr /> 
### Class: Arlima_WP_Ajax

> Class that has all wp ajax functions used by this plugin. Important that you don't use closures or any other php features that isn't available in php 5.2 in this file

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Arlima_WP_Plugin](#class-arlima_wp_plugin)</em> <strong>$arlima_plugin</strong>)</strong> : <em>void</em> |
| public | <strong>checkForLaterVersion()</strong> : <em>void</em><br /><em>Checks if there is a later version of the list that's about to be saved</em> |
| public | <strong>connectAttachmentToPost()</strong> : <em>void</em><br /><em>Connect wordpress attachment to post</em> |
| public | <strong>deleteListVersion()</strong> : <em>void</em><br /><em>Deletes a list version</em> |
| public | <strong>duplicateImage()</strong> : <em>void</em><br /><em>Make copy of an wordpress attachment</em> |
| public | <strong>getAttachedImages()</strong> : <em>mixed</em><br /><em>Get post attachments</em> |
| public | <strong>getListSetup()</strong> : <em>mixed</em><br /><em>Get the list setup for currently logged in user</em> |
| public | <strong>getPost()</strong> : <em>mixed</em><br /><em>Get wordpress posts in json format</em> |
| public | <strong>getScissors()</strong> : <em>mixed</em> |
| public | <strong>importList()</strong> : <em>void</em><br /><em>Import an external arlima list or RSS feed</em> |
| public | <strong>initActions()</strong> : <em>void</em><br /><em>Setup all ajax functions</em> |
| public | <strong>loadListData()</strong> : <em>mixed</em><br /><em>Fetches an arlima list and outputs it in widget form</em> |
| public | <strong>prependArticle()</strong> : <em>void</em><br /><em>Prepend an article to the top of a list</em> |
| public | <strong>printCustomTemplates()</strong> : <em>void</em><br /><em>Get arlima templates</em> |
| public | <strong>queryPosts()</strong> : <em>void</em><br /><em>Search for posts</em> |
| public | <strong>removeImageVersions()</strong> : <em>void</em><br /><em>Removes all arlima image versions (nothing will happen if WP version < 3.5)</em> |
| public | <strong>saveExternalImage()</strong> : <em>void</em><br /><em>Side load an external image and attach it to post</em> |
| public | <strong>saveImage()</strong> : <em>void</em> |
| public | <strong>saveJsLog()</strong> : <em>void</em> |
| public | <strong>saveList()</strong> : <em>void</em><br /><em>Save a new version of a list</em> |
| public | <strong>saveListSetup()</strong> : <em>void</em><br /><em>Saves the user setup (lists to load on startup and their position and size)</em> |
| public | <strong>updateArticle()</strong> : <em>void</em> |
| public | <strong>updateListVersion()</strong> : <em>void</em><br /><em>Update a specific version of a list</em> |
| protected | <strong>getArticlesFromRequest()</strong> : <em>array/mixed</em> |
| protected | <strong>listToJSON(</strong><em>[\Arlima_List](#class-arlima_list)</em> <strong>$list</strong>, <em>string</em> <strong>$preview_url</strong>, <em>int</em> <strong>$preview_width</strong>)</strong> : <em>mixed/string/void</em> |

<hr /> 
### Class: Arlima_WP_Cache

> Wrapper for wp_cache functions.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>delete(</strong><em>string</em> <strong>$key</strong>)</strong> : <em>bool</em> |
| public | <strong>get(</strong><em>string</em> <strong>$key</strong>)</strong> : <em>bool/mixed</em> |
| public | <strong>set(</strong><em>mixed</em> <strong>$key</strong>, <em>mixed</em> <strong>$val</strong>, <em>int</em> <strong>$expires</strong>)</strong> : <em>void</em> |

*This class implements [\Arlima_CacheInterface](#interface-arlima_cacheinterface)*

<hr /> 
### Class: Arlima_WP_Facade

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>mixed/null</em> <strong>$wpdb=null</strong>)</strong> : <em>void</em> |
| public | <strong>applyFilters()</strong> : <em>void</em> |
| public | <strong>currentVisitorCanEdit()</strong> : <em>void</em> |
| public | <strong>dbEscape(</strong><em>mixed</em> <strong>$input</strong>)</strong> : <em>void</em> |
| public | <strong>dbTableExists(</strong><em>mixed</em> <strong>$tbl</strong>)</strong> : <em>void</em> |
| public | <strong>doAction()</strong> : <em>void</em> |
| public | <strong>flushCaches()</strong> : <em>void</em> |
| public | <strong>generateImageVersion(</strong><em>mixed</em> <strong>$file</strong>, <em>mixed</em> <strong>$attach_url</strong>, <em>mixed</em> <strong>$max_width</strong>, <em>mixed</em> <strong>$img_id</strong>)</strong> : <em>void</em> |
| public | <strong>getArlimaArticleImageFromPost(</strong><em>mixed</em> <strong>$id</strong>)</strong> : <em>mixed</em> |
| public | <strong>getBaseURL()</strong> : <em>mixed</em> |
| public | <strong>getContentOfPostInGlobalScope()</strong> : <em>mixed</em> |
| public | <strong>getDBPrefix()</strong> : <em>mixed</em> |
| public | <strong>getDefaultListAttributes()</strong> : <em>mixed</em> |
| public | <strong>getExcerpt(</strong><em>mixed</em> <strong>$post_id</strong>, <em>mixed</em> <strong>$excerpt_length=35</strong>, <em>string</em> <strong>$allowed_tags=`''`</strong>)</strong> : <em>mixed</em> |
| public | <strong>getFileURL(</strong><em>mixed</em> <strong>$file</strong>)</strong> : <em>mixed</em> |
| public | <strong>getImageData(</strong><em>mixed</em> <strong>$img_id</strong>)</strong> : <em>mixed</em> |
| public | <strong>getImageURL(</strong><em>mixed</em> <strong>$img_id</strong>)</strong> : <em>mixed</em> |
| public | <strong>getImportedLists()</strong> : <em>mixed</em> |
| public | <strong>getListEditURL(</strong><em>mixed</em> <strong>$id</strong>)</strong> : <em>mixed</em> |
| public | <strong>getPageEditURL(</strong><em>mixed</em> <strong>$page_id</strong>)</strong> : <em>mixed</em> |
| public | <strong>getPageIdBySlug(</strong><em>mixed</em> <strong>$slug</strong>)</strong> : <em>mixed</em> |
| public | <strong>getPostIDInLoop()</strong> : <em>mixed</em> |
| public | <strong>getPostInGlobalScope()</strong> : <em>mixed</em> |
| public | <strong>getPostTimeStamp(</strong><em>mixed</em> <strong>$p</strong>)</strong> : <em>mixed</em> |
| public | <strong>getPostURL(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>mixed</em> |
| public | <strong>getQueriedPageId()</strong> : <em>mixed</em> |
| public | <strong>getRelationData(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>mixed</em> |
| public | <strong>havePostsInLoop()</strong> : <em>bool</em> |
| public | <strong>humanTimeDiff(</strong><em>mixed</em> <strong>$time</strong>)</strong> : <em>void</em> |
| public static | <strong>initLocalization()</strong> : <em>void</em> |
| public | <strong>isPreloaded(</strong><em>mixed</em> <strong>$id</strong>)</strong> : <em>bool</em> |
| public | <strong>loadExternalURL(</strong><em>mixed</em> <strong>$url</strong>)</strong> : <em>mixed</em> |
| public | <strong>loadRelatedPages(</strong><em>mixed</em> <strong>$list</strong>)</strong> : <em>mixed</em> |
| public | <strong>loadRelatedWidgets(</strong><em>mixed</em> <strong>$list</strong>)</strong> : <em>mixed</em> |
| public | <strong>postToArlimaArticle(</strong><em>mixed</em> <strong>$post</strong>, <em>array</em> <strong>$override=array()</strong>)</strong> : <em>void</em> |
| public | <strong>preLoadPosts(</strong><em>mixed</em> <strong>$post_ids</strong>)</strong> : <em>void</em> |
| public | <strong>prepare(</strong><em>mixed</em> <strong>$sql</strong>, <em>mixed</em> <strong>$params</strong>)</strong> : <em>void</em> |
| public | <strong>prepareForPostLoop(</strong><em>mixed</em> <strong>$list</strong>)</strong> : <em>void</em> |
| public | <strong>relate(</strong><em>mixed</em> <strong>$list</strong>, <em>mixed</em> <strong>$post_id</strong>, <em>mixed</em> <strong>$attr</strong>)</strong> : <em>void</em> |
| public | <strong>removeAllRelations(</strong><em>mixed</em> <strong>$list</strong>)</strong> : <em>void</em> |
| public | <strong>removeImportedList(</strong><em>mixed</em> <strong>$url</strong>)</strong> : <em>void</em> |
| public | <strong>removeRelation(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>void</em> |
| public | <strong>resetAfterPostLoop()</strong> : <em>void</em> |
| public | <strong>resolveFilePath(</strong><em>mixed</em> <strong>$path</strong>, <em>bool</em> <strong>$relative=false</strong>)</strong> : <em>void</em> |
| public | <strong>runSQLQuery(</strong><em>mixed</em> <strong>$sql</strong>)</strong> : <em>void</em> |
| public | <strong>sanitizeText(</strong><em>mixed</em> <strong>$txt</strong>, <em>string</em> <strong>$allowed=`''`</strong>)</strong> : <em>void</em> |
| public | <strong>saveImportedLists(</strong><em>mixed</em> <strong>$lists</strong>)</strong> : <em>void</em> |
| public | <strong>scheduleEvent(</strong><em>mixed</em> <strong>$schedule_time</strong>, <em>mixed</em> <strong>$event</strong>, <em>mixed</em> <strong>$args</strong>)</strong> : <em>void</em> |
| public | <strong>setPostInGlobalScope(</strong><em>mixed</em> <strong>$post</strong>)</strong> : <em>void</em> |
| public | <strong>translate(</strong><em>mixed</em> <strong>$str</strong>)</strong> : <em>void</em> |

*This class implements [\Arlima_CMSInterface](#interface-arlima_cmsinterface)*

<hr /> 
### Class: Arlima_WP_ImageVersionManager

> Class that creates image versions (of any size) on the fly.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>int</em> <strong>$id</strong>, <em>mixed/[\Arlima_WP_Plugin](#class-arlima_wp_plugin)/int</em> <strong>$plugin_or_img_quality=100</strong>)</strong> : <em>void</em> |
| public | <strong>getVersionFile(</strong><em>int</em> <strong>$max_width</strong>)</strong> : <em>array With relative file path and timestamp when first image version was created</em> |
| public | <strong>getVersionURL(</strong><em>int</em> <strong>$max_width</strong>)</strong> : <em>string</em><br /><em>Generates a new version</em> |
| public | <strong>getVersions(</strong><em>mixed/array/null</em> <strong>$meta=null</strong>, <em>bool</em> <strong>$as_url=false</strong>)</strong> : <em>array</em><br /><em>Get paths to all generated arlima version</em> |
| public static | <strong>registerFilters()</strong> : <em>void</em><br /><em>Removes all generated versions when attachments gets deleted</em> |
| public static | <strong>removeVersions(</strong><em>mixed/int</em> <strong>$attach_id=null</strong>)</strong> : <em>void</em><br /><em>Removes all image versions created for given attachment</em> |
| public static | <strong>uploadDirData(</strong><em>mixed/string</em> <strong>$key=null</strong>)</strong> : <em>array</em> |

<hr /> 
### Class: Arlima_WP_Page_Edit

| Visibility | Function |
|:-----------|:---------|
| public | <strong>addFormValidation()</strong> : <em>void</em> |
| public | <strong>capability()</strong> : <em>void</em> |
| public | <strong>enqueueScripts()</strong> : <em>void</em> |
| public | <strong>getMenuName()</strong> : <em>mixed</em> |
| public | <strong>getName()</strong> : <em>mixed</em> |
| public | <strong>parentSlug()</strong> : <em>void</em> |
| public | <strong>scripts()</strong> : <em>void</em> |
| public | <strong>slug()</strong> : <em>void</em> |
| public | <strong>styleSheets()</strong> : <em>void</em> |

*This class extends [\Arlima_WP_AbstractAdminPage](#class-arlima_wp_abstractadminpage-abstract)*

<hr /> 
### Class: Arlima_WP_Page_Main

| Visibility | Function |
|:-----------|:---------|
| public | <strong>addTemplateLoadingJS()</strong> : <em>void</em><br /><em>Will output javascript that loads all jQuery templates from backend</em> |
| public | <strong>echoFormatColorStyleTag()</strong> : <em>void</em> |
| public | <strong>enqueueScripts()</strong> : <em>void</em> |
| public | <strong>enqueueStyles()</strong> : <em>void</em> |
| public | <strong>getMenuName()</strong> : <em>mixed</em> |
| public | <strong>getName()</strong> : <em>mixed</em> |
| public | <strong>icon()</strong> : <em>void</em> |
| public | <strong>mceButtons1(</strong><em>mixed</em> <strong>$buttons</strong>)</strong> : <em>mixed</em> |
| public | <strong>mceButtons2(</strong><em>mixed</em> <strong>$buttons</strong>)</strong> : <em>mixed</em> |
| public | <strong>mcePlugin(</strong><em>mixed</em> <strong>$plugin_array</strong>)</strong> : <em>mixed</em> |
| public | <strong>parentSlug()</strong> : <em>void</em> |
| public | <strong>scripts()</strong> : <em>void</em> |
| public | <strong>slug()</strong> : <em>void</em> |
| public | <strong>styleSheets()</strong> : <em>void</em> |

*This class extends [\Arlima_WP_AbstractAdminPage](#class-arlima_wp_abstractadminpage-abstract)*

<hr /> 
### Class: Arlima_WP_Page_Settings

| Visibility | Function |
|:-----------|:---------|
| public | <strong>capability()</strong> : <em>void</em> |
| public | <strong>getMenuName()</strong> : <em>mixed</em> |
| public | <strong>getName()</strong> : <em>mixed</em> |
| public | <strong>parentSlug()</strong> : <em>void</em> |
| public | <strong>scripts()</strong> : <em>void</em> |
| public | <strong>slug()</strong> : <em>void</em> |
| public | <strong>styleSheets()</strong> : <em>void</em> |

*This class extends [\Arlima_WP_AbstractAdminPage](#class-arlima_wp_abstractadminpage-abstract)*

<hr /> 
### Class: Arlima_WP_Plugin

> Utility class for the Arlima plugin.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>mixed</em> <strong>$sys=null</strong>)</strong> : <em>void</em> |
| public | <strong>addAdminJavascriptVars(</strong><em>mixed</em> <strong>$handle</strong>)</strong> : <em>void</em> |
| public | <strong>addAttachmentMetaBox()</strong> : <em>void</em><br /><em>Adds attachment meta box</em> |
| public | <strong>addExportFeeds()</strong> : <em>void</em><br /><em>Adds arlima export feed to Wordpress</em> |
| public | <strong>addMetaBox()</strong> : <em>void</em><br /><em>Adds meta box to post edit/create page</em> |
| public | <strong>addTemplateCSS()</strong> : <em>void</em><br /><em>Will enqueue the css for the presentation of articles in an arlima list.</em> |
| public | <strong>adminBar()</strong> : <em>void</em> |
| public | <strong>adminInitHook()</strong> : <em>void</em><br /><em>Function called on init in wp-admin</em> |
| public | <strong>adminMenu()</strong> : <em>void</em><br /><em>Creates the menu in wp-admin for this plugin</em> |
| public | <strong>arlimaListShortCode(</strong><em>array</em> <strong>$attr</strong>)</strong> : <em>string</em><br /><em>Short code for arlima</em> |
| public | <strong>attachmentMetaBox()</strong> : <em>void</em><br /><em>Outputs HTML content of arlima versions meta box</em> |
| public static | <strong>bodyClassFilter(</strong><em>array</em> <strong>$classes</strong>)</strong> : <em>void</em> |
| public static | <strong>classLoader(</strong><em>string</em> <strong>$class</strong>)</strong> : <em>void</em><br /><em>Class loader that either tries to load the class from arlima class directory or template engine directory</em> |
| public | <strong>commonInitHook()</strong> : <em>void</em><br /><em>Init hook taking place in both wp-admin and theme</em> |
| public static | <strong>deactivate()</strong> : <em>void</em><br /><em>Deactivation procedure for this plugin - Removes feed from wp_rewrite</em> |
| public static | <strong>displayArlimaList(</strong><em>mixed</em> <strong>$content</strong>)</strong> : <em>void</em> |
| public | <strong>getSetting(</strong><em>string</em> <strong>$name</strong>, <em>bool</em> <strong>$default=false</strong>)</strong> : <em>mixed</em> |
| public static | <strong>getTemplateCSS()</strong> : <em>string</em><br /><em>Get the path to the CSS file that controls the presentation of articles in an arlima list</em> |
| public | <strong>getTemplateStylesheets()</strong> : <em>array</em> |
| public static | <strong>hasCreatedDBTables(</strong><em>[\Arlima_AbstractRepositoryDB](#class-arlima_abstractrepositorydb-abstract)[]</em> <strong>$repos</strong>)</strong> : <em>bool</em> |
| public | <strong>init()</strong> : <em>void</em> |
| public | <strong>initAdminActions()</strong> : <em>void</em><br /><em>Actions added in wp-admin</em> |
| public | <strong>initThemeActions()</strong> : <em>void</em><br /><em>Actions added in the theme</em> |
| public static | <strong>install()</strong> : <em>void</em><br /><em>Install procedure for this plugin - Adds database tables - Adds version number in db - Adds arlima export feed and flushed wp_rewrite - Adds initial settings</em> |
| public static | <strong>isScissorsInstalled()</strong> : <em>bool</em><br /><em>Tells whether or not the plugin ScissorsContinued is installed</em> |
| public | <strong>loadExportFeed()</strong> : <em>mixed</em><br /><em>Will try to export arlima list from currently visited page</em> |
| public static | <strong>loadRepos()</strong> : <em>[\Arlima_AbstractRepositoryDB](#class-arlima_abstractrepositorydb-abstract)[]</em> |
| public | <strong>loadSettings()</strong> : <em>array</em><br /><em>Settings of any kind related to this plugin</em> |
| public static | <strong>loadStreamerColors()</strong> : <em>mixed</em><br /><em>Will output a set of option elements containing streamer background colors.</em> |
| public | <strong>pageMetaBox()</strong> : <em>void</em> |
| public | <strong>postMetaBox()</strong> : <em>void</em><br /><em>Content of meta box used to send a wordpress post immediately from post edit page in wp-admin to an arlima list</em> |
| public | <strong>printUserAllowedLists()</strong> : <em>void</em><br /><em>Prints the html for editing allowed lists for a user</em> |
| public static | <strong>publishScheduledList(</strong><em>int</em> <strong>$list_id</strong>, <em>int</em> <strong>$version_id</strong>)</strong> : <em>void</em><br /><em>Publishes a scheduled arlima list</em> |
| public static | <strong>saveImageAsAttachment(</strong><em>string</em> <strong>$base64_img</strong>, <em>string</em> <strong>$file_name</strong>, <em>string</em> <strong>$connected_post=`''`</strong>)</strong> : <em>int The attachment ID</em><br /><em>Create a wordpress attachment out of a string with base64 encoded image binary</em> |
| public static | <strong>saveImageFileAsAttachment(</strong><em>mixed</em> <strong>$img_file</strong>, <em>mixed</em> <strong>$file_name</strong>, <em>mixed</em> <strong>$connected_post</strong>)</strong> : <em>int</em> |
| public | <strong>savePageMetaBox(</strong><em>mixed</em> <strong>$post_id</strong>)</strong> : <em>void</em> |
| public | <strong>saveSettings(</strong><em>array</em> <strong>$setting</strong>)</strong> : <em>void</em> |
| public | <strong>saveUserAllowedLists(</strong><em>mixed</em> <strong>$user_id</strong>)</strong> : <em>void</em><br /><em>Saves the allowed lists settings</em> |
| public | <strong>settingsLinkOnPluginPage(</strong><em>array</em> <strong>$links</strong>)</strong> : <em>array</em><br /><em>Add a settings link to given links</em> |
| public static | <strong>setupArlimaListRendering()</strong> : <em>void</em> |
| public | <strong>setupWidgets()</strong> : <em>void</em><br /><em>Register our widgets and widget filters</em> |
| public static | <strong>supportsImageEditor()</strong> : <em>bool</em> |
| public static | <strong>tearDownArlimaListRendering()</strong> : <em>void</em> |
| public | <strong>themeInitHook()</strong> : <em>void</em><br /><em>function called on init in the theme</em> |
| public static | <strong>uninstall()</strong> : <em>void</em><br /><em>Uninstall procedure for this plugin - Removes plugin settings - Removes database tables</em> |
| public static | <strong>update()</strong> : <em>void</em><br /><em>Update procedure for this plugin. Since wordpress is lacking this feature we should call this function on a regular basis.</em> |

<hr /> 
### Class: Arlima_WP_Widget

> Widget displaying an article list

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>mixed/string</em> <strong>$id=null</strong>, <em>string</em> <strong>$name=`'Arlima Widget'`</strong>)</strong> : <em>void</em> |
| public | <strong>form(</strong><em>array</em> <strong>$instance</strong>)</strong> : <em>string/void</em> |
| public | <strong>update(</strong><em>array</em> <strong>$new_instance</strong>, <em>array</em> <strong>$old_instance</strong>)</strong> : <em>array</em> |
| public | <strong>widget(</strong><em>array</em> <strong>$args</strong>, <em>array</em> <strong>$instance</strong>)</strong> : <em>void</em> |

*This class extends \WP_Widget*

<hr /> 
### <strike>Class: Arlima_WPLoop</strike>

> **DEPRECATED** Use Arlima_CMSLoop instead

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>mixed</em> <strong>$in</strong>)</strong> : <em>void</em> |

*This class extends [\Arlima_CMSLoop](#class-arlima_cmsloop)*

