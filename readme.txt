=== Article List Manager (arlima) ===

Contributors: @chredd, @znoid, @victor_jonsson, @lefalque
Tags: CMS, e-paper, e-magazine, magazine, newspaper, frontpage, wysiwyg
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 2.3.2
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is a plugin suitable for online newspapers that's in need of a fully customizable front page.

== Description ==

*This plugin was created by Swedish newspaper **Västerbottens-Kuriren** to give its editorial staff an easy to use tool
for customizing the front pages of their online magazines. It's used on a daily basis at www.folkbladet.nu and www.vk.se,
websites that together has over 200 000 unique visitors per week.*

Here you can see a [screencast with a quick demonstration of the plugin](http://www.screenr.com/vB48). Here's
[another screencast](http://www.screenr.com/E048) where you can see how Arlima works together with the plugins "Scissors Continued" and "Wordpress Related Posts".

= Requirements =

- Wordpress version >= 3.0
- PHP version >= 5.3
- Modern web browser, preferably Google Chrome. Arlima is tested successfully in the latest versions of Mozilla FF, Safari, Opera and
Internet Explorer 9 (image upload does not work in internet explorer)

= After installation =

This plugin will automatically create a page named "Home" when installed. The page will have the template named "Article List Page".
If your theme directory isn't writable by wordpress you will have to copy *page-arlima-example.php* from the Arlima plugin directory to
the theme directory your self (rename the file to page-arlima.php).

Navigate to "Article lists" -> "Manage lists" in the menu bar of wp-admin where you can edit the article list that was attached to the page "Home",
created by the plugin on installation. If you want to use this page as front page you go to "Settings" -> "Reading" in wp-admin and choose the page
named "Home" as a static front page.


= Features =

Here you can read about [the different features of Arlima and how to customize them](http://wordpress.org/extend/plugins/arlima/other_notes/).
The features includes:

- Creating new article lists
- Custom CSS
- Template hooks
- Custom jQuery templates
- Custom "streamers"
- Using keyboard short cuts in Arlima list editor
- Using Arlima jQuery templates in ordinary Wordpress loops
- Advanced caching
- Unlocking even more features by installing the plugins "Scissors Continued" and "Wordpress Related Posts"


== Installation ==

1. Install Arlima via the WordPress.org plugin directory.
2. The page template "page-arlima.php" will be copied from the Arlima plugin directory to your theme on installation. If your theme directory isn't writeable
by wordpress you will have to copy *page-arlima-example.php* to the theme directory your self (rename the file to page-arlima.php).
3. That's it. You're ready to go! The installation will automatically create a page named "Home" with an attached Arlima list that you can use as front page.


== Changelog ==

= 2.5 =
- Total remake of the theme implementation. The page template should no longer be used. It will work
but is considered deprecated.


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


== Screenshots ==

1. Article list editor in wp-admin


== Creating a new article list ==

1. Go to "Article lists" -> "Edit lists" in the menu bar of wp-admin. Here you can create (and edit) your Arlima lists.
2. Create a new page in wp-admin, choose *"Article List Page"* as template.
3. Add a custom field named "arlima" to your page. The value of the field should be the slug name you entered for your list in the first step.
4. Go to "Article lists" -> "Manage lists" in the menu bar of wp-admin. Here you manage the content in your lists (posts and what not)
5. Visit the page you created and there you will see the beautiful list just waiting to get stuffed with interesting content.


== Using keyboard short cuts ==

The following short cuts is also available using the **CMD-key** instead of the Ctrl-key on Mac.

- **Ctrl + s** - Saves the article list currently being edited (disabled when having focus on the tinyMCE editor).
- **Ctrl + p** - Toggles the article preview for the currently edited article.
- **Ctrl + l** - Saves a preview version of currently edited article list and opens a new window where preview can be seen.
- Hold down **Ctrl-key** when starting to drag an article **to create a copy** of the article.


== Custom CSS ==

Copy the file */css/template.css* located in the Arlima plugin directory to your theme and do the changes you want in that file. Last
but not least you add the following code to the file *functions.php* located in your theme directory:

    add_filter('arlima_template_css', function( $default_stylesheet ) {
        return get_stylesheet_directory_uri() . '/template.css';
    });


== Customized streamers ==
The "Streamer" is what we call a line of text or an image that's positioned above the article image.

= Adding your own background colors to text streamers =

By default you get a wide variety of background colors to choose from. You can how ever add your own colors by using
the filter &quot;arlima\_streamer\_colors&quot; in file *functions.php* located in the theme directory. Your code might look something like:

    add_filter('arlima_streamer_colors', function($default_colors) {
        $my_colors = array(
                '007fc2', // blue
                'b22e2a', // red
                'E2E2E2', // gray
                '894b94' // purple
            );

        return array_merge($default_colors, $my_colors);
    });

= Adding your own streamer images =

You can use the filter &quot;arlima\_streamer\_images&quot; if you want to add your own streamer images. The code might look something like:

    add_filter('arlima_streamer_images', function($default_streamers) {
        $theme_url = get_stylesheet_directory_uri();
        $my_streamers =  array(
                    $theme_url . '/images/my-cool-streamer.png',
                    $theme_url . '/images/another-cool-streamer.png'
                );

        return array_merge($default_streamers, $my_streamers);
    });


== Custom templates ==

Arlima uses jQuery templates, both in back-end (when rendering the list on your front page) and front-end (when you preview your Arlima articles in wp-admin).
Arlima comes with one default template (*templates/article.tmpl*). The template rendering class will search after the template file in your
theme and if not found fall back on template files located in the Arlima template directory. Here's how you create your own templates:

**1)** Copy the directory named *templates* in the Arlima plugin directory to your theme.

**2)** Rename the file article.tmpl located in the copied template directory to something suitable, the file must have the extension *.tmpl*.
The following variables will be present in the template file:

    // Object containing some info describing the configuration
    // of this particular article
    container : {
        id : 'id of this article'
        class : 'class names that describes how this article is configured'
    }

    // Object containing article text content
    article : {
        title : 'The title of this article'
        url : 'The URL of this article'
        html_title : 'The title of this article as html',
        html_text : 'The text content of this article'
    }

    // Object describing a streamer. This variable will be false if you
    // haven't chosen a streamer for this article
    streamer : {
        type : 'the type of streamer you have selected (text|image|extra)',
        style : 'inline css, will only be present if you have chosen a custom streamer',
        content : 'the html content of the streamer'
    }

    // Object containing image data. This variable will be false if
    // you haven't chosen an image for this article
    image : {
        html : 'HTML tag for this image, see the default template for instructions',
        src : 'URL for this image', // only available when template is rendered with javascript in wp-admin
        url : 'URL for this image', // only available when template is rendered with php
        image_class :'Suggested class name for this image',
        image_size : 'The name of the size of this image'
    }

    // Boolean telling us if this particular article is a child article
    is_child : true|false

    // Boolean telling us if this article is a child article and that it has siblings
    is_child_split : true|false

    // HTML string with possible child articles
    sub_articles : '...'

    // HTML content supposed to be added to the end of the article
    article_end : '...'

    // Object with related articles. This variable is false if not articles is related
    related : {
        // Boolean that is true if we only have one related article, will be
        // false if we have several related articles
        is_single : true|false,

        // Array with related articles
        posts : [
            {
                post_title : 'Title of the related article',
                url : 'URL of the related article',
                html_comment_stats : 'HTML string with information about the number of comments'
            },
            ....
        ]
    }

**3)** Add the following code to the file *functions.php* located in your theme directory:

    add_filter('arlima_template_paths', function($paths) {
        array_unshift($paths, __DIR__.'/templates/');
        return $paths;
    });


You can have several different templates, you select which template to use when you create the article list (wp-admin/ -> Article lists -> Edit).
Here you can read more about jQuery TMPL:

- [jQuery TMPL](http://api.jquery.com/jquery.tmpl/)
- [jQuery TMPL PHP](https://github.com/xyu/jquery-tmpl-php)

We have added a jQuery template function that makes it possible to include a template
within another template, making it possible to reuse your template code.
The function is called like *\{\{include parts/my-template-footer.tmpl\}\}*. You can read more about this in the default template
that comes together with the Arlima plugin.


== Article formats ==

An article format is nothing other than a class name that will be added to the class attribute of the DIV element containing the article.
Here's how the code could look like if you wanted to add your own formats:

    // Format that will be possible to choose for all templates
    arlima_register_format('my-format-class', 'My format label');

    // Format that only will be possible to choose for the template giant.tmpl
    arlima_register_format('my-other-format', 'My other format label', array('giant'));



== Using Arlima in ordinary wordpress loops ==

Copy the file *category-arlima-example.php* located in the Arlima plugin directory to your theme directory and rename the copied file to *category.php*
if you want to use the same jQuery templates in the ordinary wordpress loop as you're using in your Arlima lists. *See code documentation for further instructions*


== Advanced cache management ==

Saving an Arlima list will invoke a [wordpress action](http://codex.wordpress.org/Function_Reference/add_action) named "arlima\_save\_list" with the *Arlima_List* object sent as argument to the callback function. Your
cache expiring code might look something like:

    add_action('arlima_save_list', function($list) {
        foreach( explode(',', $list->options['pagestopurge']) as $page_url ) {
            some_slick_cache_purger( sprintf('%s/%s/', home_url(), trim($page_url, '/')) );
        }
    });

== Extending Arlima ==

**Template callbacks** &ndash; Take a look at file *page-arlima-example.php* in the Arlima plugin directory. There you will see all the different functions you can hook
into from your theme to customize the functionality of Arlima.

**Related posts** &ndash; Install the plugin [Microkid's Related Posts for Wordpress](http://wordpress.org/extend/plugins/microkids-related-posts/) to unlock features that makes
it possible to display posts that is related to each other.

**Image management** &ndash; If you install the plugin [Scissors Continued](http://wordpress.org/extend/plugins/scissors-continued/) you will get the possibility to
modify images (crop, resize and a bunch of other cool features) in Arlima admin.

Here you can see a [screencast showing Arlima working together with Scissors Continued and Wordpress Related Posts](http://www.screenr.com/E048)

== Modifying the post search in the article editor ==

You can modify the functionality of the post search in Arlima admin. This is an example code that makes it possible to choose
whether or not to display posts written by the author Johnny when searching for posts in Arlima admin.

    add_action('arlima_post_search', function() {
        Arlima_modify_post_search(
            function() {
                ?>
                Exclude posts written by John
                <input type="checkbox" name="exclude_johnny" value="1" />
                <?php
            },
            function($args, $posted_data) {
                if( isset($posted_data['exclude_johnny']) ) {
                    $args['author'] = '-'.get_user_by( 'login', 'johnny' )->ID;
                }

                return $args;
            }
        );
    });


== Roadmap ==

- Front-end editing. Make it possible to edit titles, text and images in front end
- Article box size, being able to edit the width of an article in arlima admin
- Article slide

== Websites using this plugin ==

- [http://www.vk.se/](http://www.vk.se/)
- [http://www.folkbladet.nu/](http://www.folkbladet.nu/)
- [http://www.sportnu.se/](http://www.sportnu.se/)
