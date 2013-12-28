=== Article List Manager (arlima) ===

Contributors: @chredd, @znoid, @victor_jonsson, @lefalque
Tags: CMS, e-paper, e-magazine, magazine, newspaper, front page, wysiwyg
Requires at least: 3.0
Tested up to: 3.8
Stable tag: 2.8.6
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is a plugin suitable for online newspapers that's in need of a fully customizable front page.

== Description ==

*This plugin was created by Swedish newspaper **Västerbottens-Kuriren** to give its editorial staff an easy to use tool
for customizing the front pages of their online magazines. It's used on a daily basis at www.folkbladet.nu and www.vk.se,
websites that together has over 200 000 unique visitors per week.*

Here you can see a [screencast with a quick demonstration of the plugin](http://www.screenr.com/vB48). Here's
[another screencast](http://www.screenr.com/E048) where you can see how Arlima works together with the plugins "Scissors Continued" and "Microkid's Related Posts".

= Requirements =

- Wordpress version >= 3.0
- PHP version >= 5.2
- Modern web browser, preferably Google Chrome. Arlima is tested successfully in the latest versions of Mozilla FF, Safari, Opera and
Internet Explorer 9 (image upload does not work in internet explorer)

= Wiki =

Now you can read [the Arlima wiki](https://github.com/victorjonsson/Arlima/wiki/) to get more information about how to use this plugin


== Installation ==

1. Install Arlima via the WordPress.org plugin directory.
2. Go to "Article lists" -> "Edit lists" in wp-admin and create your first article list.
3. Open up a page (or create it) in wp-admin. Down to the right you will see a meta box labeled "Arlima" where you choose
the list that you created on step 2.
4. Go to "Article lists" -> "Manage lists" and start stuffing your article list with interesting content.


== Changelog ==

= 2.8.6 =

- The plugin is successfully tested with WordPress 3.8
- Fixed bug that sometimes prevented articles from rendering
- Sticky articles now works in lists with support for sections
- It's now possible to hide templates for particular lists in the list manager
- Fancybox used to connect articles to posts is now larger
- Fixed bug affecting article connected to future posts
- Added support for two more image versions (fifth and sixth) that can be enabled using template tag <a href="https://github.com/victorjonsson/Arlima/wiki/Custom-jQuery-templates#image-support">image-support</a>

= 2.8 =

- Arlima is now compatible with 3.6.1 (big thanks to [Johan Fredriksson](https://github.com/DUAB-Johan))
- jQuery-tmpl function {{include}} now supports relative paths.
- A new jQuery-tmpl function named {{image-support}} is introduced (see wiki for more info https://github.com/victorjonsson/Arlima/wiki/Custom-jQuery-templates#image-support).
- It's now possible to choose whether or not editors should be able to change template for articles via the list manager.
- It's now possible to include php files inside articles (see wiki for more info https://github.com/victorjonsson/Arlima/wiki/File-includes).
- Arlima now supports lists that has articles divided in sections (see wiki for more info https://github.com/victorjonsson/Arlima/wiki/Article-lists-with-sections).
- The template functions is_arlima_preview(), get_arlima_list() and has_arlima_list() has become deprecated, replaced with arlima_is_preview(), arlima_get_list() and arlima_has_list().
- The template function arlima_load_list($slug_or_id) is added to the public API (see wiki for more details https://github.com/victorjonsson/Arlima/wiki/Writing-a-custom-page-template).
- Image filter "arlima_generate_image_version" added. By using this filter you can replace the image manipulation function in Arlima with a function of your own (see wiki for more info https://github.com/victorjonsson/Arlima/wiki/Filters-and-actions).
- The CSS for child articles has changed. If you have custom CSS for your arlima articles you should take a look at the section for child articles in css/template.css.
- Arlima_ListFactory::loadListBySlug() is deprecated. Use Arlima_ListFactory::loadList() instead, it can now handle both id number, slug name and URL of external list or RSS feed
- Now you can publish Arlima lists and RSS-feeds from remote website directly on a page via Arlima's meta box.
- When previewing a list the preview window will scroll down to currently edited article.


= 2.7.21 =

- Now possible to add your own streamer classes.
- Article preview is now rendered in an iframe which makes it easier to write custom CSS without overwriting the CSS in wp-admin.

= 2.7.17 =

- Fixed preview window bug in chrome.
- Now possible to filter which post types that should be included in the post search (arlima_search_post_types).
- Now possible to set whether or not related posts should be displayed or hidden by default.
- Fixed important bug that sometimes caused article lists to get rendered multiple times on one page.

= 2.7.10 =

- Previewing a list that doesn't have any unsaved changes now opens the page its related to.
- The connection between article images and posts now works as supposed to.
- Some other minor bugs fixed.

= 2.7.7 =

- A bunch of minor bugs that came with the last release is now fixed.

= 2.7 =

- New feature: "Quick Edit — Edit articles in front-end". It's now possible to edit the title and body text of your articles directly on your front page.
- Improved article form in the list manager, connecting articles to posts or external URL´s have been given a new interface.
- Fixed bug that made it impossible to add watermark images using "Scissors Continued".
- Fixed bug in jQuery hot keys.
- Font size slider can now be controlled using arrow keys.
- The admin page "Web Services" is renamed to "Settings" and has been given more configuration options.


= 2.6.23 =

- Important bug fix that sometimes made the list manager crash.

= 2.6.22 =

- Fixed bug that made it hard to add child articles in the list manager when having several lists open at once.
- Fixed bug that sometimes caused articles to generate the wrong image version.
- Fixed bug that sometimes prevented the article from updating url and title.
- Fixed bug that prevented Arlima from expiring the list cache if using a custom implementation of Arlima_CacheManager.
- Added action "arlima_register_format" that you can hook into when using any of the arlima_register/deregister functions.
- General improvements.


= 2.6 =

- New feature: Display an article list in a widget.
- New feature: Now possible to delete image versions (created by Arlima) on the same page where you edit attachments (only WordPress version >= 3.5).
- New template: This release brings along a new template that's meant to be used when displaying a list in a widget. This template is
 practically the same as the default template except that it does not display related articles nor sub articles.
- Now possible to use filters to prevent stuff from being sent to the jQuery template (thanks for helping us sort this out <a href="http://dotunited.se/">Mattias</a>).
- Minor bug fixes and general improvements.
- Added missing translations.


= 2.5.8 (Christmas release) =

Santa claus won't be bringing you any new features this time but his sack is filled with a whole lot of nice bug fixes.

- Fixed bug that made it impossible to check if the currently rendered article was a child article in the jQuery template.
- Fixed bugs that appears when running Arlima on older versions of WordPress.
- Fixed bug that removed content from a page if the page had a related article list (github issue #6).
- Added filter arlima_template_object.
- New js events and improved js performance in the list manager.

= 2.5.7 =

- Arlima now compatible with WP version 3.5

= 2.5.5 =

- Several small bugs in yesterday's release now fixed

= 2.5 =
- Total remake of the theme implementation. The page template should no longer be used. It will still work
but is considered deprecated.
- The plugin is now compatible with PHP version 5.2.
- Now possible to insert article lists using a short code.
- Direct use of the list rendering class is now considered deprecated. Use [arlima filters](https://github.com/victorjonsson/Arlima/wiki/Template-filters) instead.
- Image upload with files having the extension .jpeg is now supported
- Improved drag and drop image uploads
- Improved responsiveness in the default CSS
- Source code and documentation is now available on [github](https://github.com/victorjonsson/Arlima/)


= 2.3.2 =
- New feature: Click on the image container in the article editor and you will be able to select one of the
post attachments as image for the Arlima article.
- Fixed bug in example template. If your first installation of the Arlima plugin was version 2.3 you should switch the
code in page-arlima.php (located in your theme) with the code in page-arlima-example.php



= 2.3 =
- New feature: Custom templates. It's now possible to change template on articles in a list.
- New feature: Article formats (see section "Article formats" in the documentation).
- Minor UI improvements (features becomes hidden if not supported by template)
- Now using wordpress built in functions when resizing images in the example template.
- CSS fixes, page template now works with theme twentytwelve.


= 2.2.4 =
- Final bug fix for corrupt publish dates on articles.
- New feature: Sticky Articles. This feature makes it possible to create an article that always remains on the same position in the article list. You can also
schedule when you want the article to be displayed for your visitors. The use case could be that you have a nice set of articles about
your local restaurants. You know that your visitors interest in these articles is highest on Mondays around lunch time. Then you create a sticky article
that you place on a suiteable spot in your article list and schedule the article to be displayed on Mondays 11-13.
- New feature: Admin lock. This feature makes it possible for administrators to lock articles, preventing editors from changing the article.
- and some other minor bug fixes...


= 2.2.3 =
- Important bug fix, article publish date corruption
- Fixed minor bugs
- Improved name of imported lists (import the lists once again to get the new name)
- Bug fix in arlima article search


= 2.2.2 =
- Fixed bug that gave Arlima articles incorrect publish date
- Fixed bug when importing RSS-feeds as Arlima lists
- Now possible to see which articles that isn't published yet when looking at a list in arlima admin
- It's now easier to modify the post search in arlima admin
- and some other minor bug fixes...


= 2.2 =

- List editor informs users when login session has expired.
- Improved performance, removed needless call to setup_postdata() when rendering articles.
- Improved performance, the list rendering class will not load posts that is of type future unless you're previewing a list.
- Added functions setOffset() and setLimit() to list rendering classes that makes it possible to limit the number of articles in a list that should be rendered.
- Added property "created" to arlima articles holding a timestamp of when the article was created.
- Future post callback will only fire when rendering a preview version of a list.
- Removed the link "Read more..." from default jQuery template.
- Minor bug fixes.

= 2.1 =

- Fixed bug that made it impossible to search for articles lists.
- Fixed bug in Arlima_WPLoop.
- Added an example template that can be copied to the theme directory when wanting to use jQuery templates in the ordinary Wordpress category loop.
- Added callback that makes it possible to insert content in the end of the article.
- A link to the list editor is now present in the menu bar.
- Template variable "before_related" is no longer available in the jQuery template, now substituted with variable "article_end".


= 2.0.2 =

- Fixed bug that made it impossible to disconnect attachments from wordpress posts in the list editor
- Removed log messages
- Added event listener to Arlima.Manager(). Themes can now listen to certain events taking place in the list editor with javascript.

= 2.0.1.2 =

- Improved preview window

= 2.0.1 =

- Fixed bug that gave incorrect version info when reloading article lists in the list editor.
- Fixed bug that sometimes prevented the live update from taking place in the article preview.
- Fixed bug that made it impossible to search for posts written by a certain author when excluding other authors from the search.
- Added missing translations.

= 2.0 =

- Now possible to import article lists from other websites using Arlima.
- Now possible to import RSS feeds as article lists.
- Now possible to choose your own lists to be available for export.
- You can now delete article lists.
- Keyboard short cut for previewing an article list has changed from ctrl|cmd + v to ctrl|cmd + l.
- Database interaction is now cached using Wordpress object cache (tip: install a cache plugin that overrides wordpress object cache for better performance).
- Instantiation of the class Arlima_List is now considered deprecated, unless you want an empty list object. Use Arlima_ListFactory::load() to get existing Arlima lists.
- arlima_get_version_info() is now deprecated, use Arlima_list::getVersionInfo().
- Big parts of the code is now refactored for easier maintenance in the future.
- The "Arlima" prefix used in all class names is now changed to a more namespace like prefix. Some classes is still available using the old class names but is considered deprecated, those classes are ArlimaList, ArlimaWPLoop, ArlimaTemplateRenderer.
- Several small bugs in the list editor is now fixed (keyboard short cuts in tinyMCE editor, incorrect list focus etc...).
- Now possible to save list directly from preview window by pressing ctrl|cmd + s.
- Changing post id for an article automatically updates the URL of the article, it also alerts info in case the post doesn't exist.
- Added missing translations.

= 1.0.6 - 1.1.9 =

*Changelog removed...*

= 1.0.5 =
- First stable release.

== Other notes ==

All documentation has moved to a user manual and developer manual that you can can read in [the Arlima wiki](https://github.com/victorjonsson/Arlima/wiki/)


== Screenshots ==

1. Article list editor in wp-admin