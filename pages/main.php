<?php
/**
 * Page where you manage content of the lists
 *
 * @package Arlima
 * @since 1.0
 */
$factory = new Arlima_ListFactory();
$arlima_plugin = new Arlima_Plugin();
$settings = $arlima_plugin->loadSettings();
?>
<div id="col-container">

<div id="col-right">

    <div class="col-wrap">

        <div id="article-controls">
            <a href="#" class="preview disabled" title="ctrl + p">
                <i class="fa fa-eye"></i>
                <?php _e('Toggle article preview', 'arlima') ?>
            </a>
            <a href="#" class="save disabled" title="ctrl + s">
                <i class="fa fa-save"></i>
                <?php _e('Publish list', 'arlima') ?>
            </a>
            <a href="#" class="preview-list disabled" title="ctrl + l">
                <i class="fa fa-eye"></i>
                <?php _e('Preview list', 'arlima') ?>
            </a>
        </div>

        <div id="arlima-preview"></div>

        <div id="article-form" class="arlima-postbox">

            <div class="collapse-toggle"><br /></div>
            <h3><span><?php _e('Edit', 'arlima') ?></span></h3>

            <div class="inside" style="display:none;">

                <div class="settings-buttons">

                    <button class="button streamer template-feature" data-feature="streamer">
                        <i class="fa fa-square-o"></i>
                        <?php _e('Streamer', 'arlima') ?>
                    </button>

                    <div class="streamer-container template-feature" data-feature="streamer">
                        <select style="display: inline" class="streamer-type-select">
                            <option value="extra"><?php _e('Extra', 'arlima') ?></option>
                            <?php if( apply_filters('arlima_support_text_streamers', true) === true ): ?>
                                <option value="text">Text</option>
                            <?php endif; ?>
                            <option value="image"><?php _e('Image', 'arlima') ?></option>
                            <?php foreach( apply_filters('arlima_streamer_classes', array()) as $streamer_class => $label ): ?>
                                <option value="text-<?php echo $streamer_class ?>"><?php echo $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="streamer-images fancybox" id="streamer-images" style="display: none">
                            <?php
                            $streamers = apply_filters('arlima_streamer_images', array( ARLIMA_PLUGIN_URL .'/images/streamer-example.png'));
                            if( !empty($streamers) ):
                                foreach( $streamers as $streamer ): ?>
                                    <img src="<?php echo $streamer ?>" alt="<?php echo $streamer; ?>" title="<?php echo ucfirst(str_replace(array('-', '_'), ' ', pathinfo($streamer, PATHINFO_FILENAME ))); ?>" /><br />
                                <?php endforeach;
                            endif;
                            ?>
                        </div>

                        <?php if( !empty($settings['streamer_pre']) ) : ?>
                            <input class="data streamer content pre" data-prop="options:streamerPre" />
                        <?php endif; ?>

                        <input class="data streamer content" data-prop="options:streamerContent" />
                        <input type="hidden" class="data streamer-type" data-prop="options:streamerType" />
                        <select class="streamer-color" name="options:streamerColor">
                            <?php Arlima_Plugin::loadStreamerColors(); ?>
                        </select>
                    </div>

                    <button class="button settings">
                        <i class="fa fa-chevron-down"></i>
                        <?php _e('Settings') ?>
                    </button>
                    <div class="settings-menu"></div>

                    <?php

                    // ARTICLE FORMATS

                    $formats = Arlima_ArticleFormat::getAll();
                    if( !empty($formats) ): ?>
                        <select class="data formats" data-prop="options:format" data-label="<?php _e('Format', 'arlima') ?>">
                            <option value=""><?php _e('Default', 'arlima') ?></option>
                            <?php foreach($formats as $format): ?>
                                <option value="<?php echo $format['class'] ?>" data-template="<?php if( isset($format['templates']) && is_array($format['templates'])) echo join(',', $format['templates']); ?>">
                                    <?php echo $format['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif;

                    // ARTICLE TEMPLATES

                    $hidden = apply_filters('arlima_hidden_templates', array(), false);
                    ?>
                    <select class="data templates" data-prop="options:template" data-label="<?php _e('Template', 'arlima') ?>">
                        <option value=""><?php _e('Default', 'arlima') ?></option>
                        <?php
                        $path_resolver = new Arlima_TemplatePathResolver();
                        foreach($path_resolver->getTemplateFiles() as $file):
                            if( in_array($file['name'], $hidden) )
                                continue; ?>
                            <option value="<?php echo $file['name'] ?>">
                                <?php echo $file['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php // SCHEDULED ARTICLE ?>
                    <select class="data scheduled-settings" data-prop="options:scheduled" data-label="<?php _e('Scheduled', 'arlima') ?>">
                        <option value=""><?php _e('No') ?></option>
                        <option value="1"><?php _e('Yes') ?></option>
                    </select>
                    <input type="hidden" class="data scheduled-interval" data-prop="options:scheduledInterval" />

                    <div id="scheduled-interval-fancybox" style="display: none">
                        <h2><?php _e('Days', 'arlima'); ?></h2>
                        <div class="scheduled-interval-time">
                            <label><input type="checkbox" class="day" value="Mon" /> <?php _e('Monday') ?></label>
                            <label><input type="checkbox" class="day" value="Tue" /> <?php _e('Tuesday') ?></label>
                            <label><input type="checkbox" class="day" value="Wed" /> <?php _e('Wednesday') ?></label>
                            <label><input type="checkbox" class="day" value="Thu" /> <?php _e('Thursday') ?></label>
                            <label><input type="checkbox" class="day" value="Fri" /> <?php _e('Friday') ?></label>
                            <label><input type="checkbox" class="day" value="Sat" /> <?php _e('Saturday') ?></label>
                            <label><input type="checkbox" class="day" value="Sun" /> <?php _e('Sunday') ?></label>
                            <p>
                                <a href="#" class="toggler">[<?php _e('Toggle all', 'arlima') ?>]</a>
                            </p>
                        </div>
                        <h2><?php _e('Hours', 'arlima'); ?></h2>
                        <div class="hours">
                            <!-- inputs for hours generated with js -->
                            <p>
                                <a href="#" class="toggler">[<?php _e('Toggle all', 'arlima') ?>]</a>
                            </p>
                        </div>
                    </div>

                    <?php // ADMIN LOCK ?>
                    <select class="data admin-lock" data-prop="options:adminLock" data-label="<?php _e('Admin lock', 'arlima') ?>">
                        <option value=""><?php _e('No') ?></option>
                        <option value="1"><?php _e('Yes') ?></option>
                    </select>

                </div>

                <div class="image-container template-feature" data-feature="image">
                    <div id="fake-container"></div>
                    <div class="image tooltip-left" id="arlima-image" title="<?php _e('Drag images to this container', 'arlima') ?>">
                        <i class="fa fa-camera-retro fa-5x"></i>
                    </div>
                    <div class="image-controls">
                        <button class="browse button">
                            <i class="fa fa-search"></i>
                            <?php _e('Browse', 'arlima') ?>
                        </button>
                        <button data-fancybox-href="#image-scissors-popup" class="scissors button fancybox">
                            <i class="fa fa-crop"></i>
                            <?php _e('Edit', 'arlima') ?>
                        </button>
                        <button class="disconnect button">
                            <i class="fa fa-chain-broken"></i>
                            <?php _e('Disconnect', 'arlima') ?>
                        </button>
                        <button class="remove button">
                            <i class="fa fa-times"></i>
                            <?php _e('Remove', 'arlima') ?>
                        </button>
                        <select class="data img-size" data-prop="image:size">
                            <option value="full"><?php _e('Full width', 'arlima') ?></option>
                            <option value="half"><?php _e('50% wide', 'arlima') ?></option>
                            <option value="third"><?php _e('33% wide', 'arlima') ?></option>
                            <option value="quarter"><?php _e('25% wide', 'arlima') ?></option>
                            <option value="fifth"><?php _e('20% wide', 'arlima') ?></option>
                            <option value="sixth"><?php _e('15% wide', 'arlima') ?></option>
                        </select>
                        <div class="img-align-buttons">
                            <label>
                                <?php _e('Align left', 'arlima') ?>
                                <input type="radio" class="align-button" name="image-align" value="alignleft" />
                            </label>
                            <label>
                                <?php _e('Align right', 'arlima') ?>
                                <input type="radio" class="align-button" name="image-align" value="alignright" />
                            </label>
                        </div>
                        <!--
                        <select class="data img-align" data-prop="image:alignment">
                            <option value="alignleft"><?php _e('Align left', 'arlima') ?></option>
                            <option value="alignright"><?php _e('Align right', 'arlima') ?></option>
                            <option value=""><?php _e('Align none', 'arlima') ?></option>
                        </select>
                        -->
                        <input type="hidden" class="data img-align" data-prop="image:alignment" />
                        <input type="hidden" class="data image-attach" data-prop="image:attachment" />
                    </div>
                    <div id="arlima-article-attachments" class="fancybox attachments-fancybox" style="width: 400px"></div>
                </div>
                <div id="image-scissors-popup" style="width: 600px; height: 600px; display: none"></div>

                <div class="title-container">
                    <input class="pre-title data template-feature" data-feature="title" data-prop="options:preTitle" placeholder="<?php _e('Entry word', 'arlima') ?>" />
                    <input class="title data" data-prop="title" placeholder="<?php _e('Title', 'arlima') ?>" />
                    <?php if( !empty($settings['newsbill_tag']) ) : ?>
                        <input class="newsbill-tag data template-feature" data-feature="newsbill_tag" data-prop="options:newsbillTag" placeholder="<?php _e('Newsbill tag', 'arlima') ?>" />
                    <?php else: ?>
                    <span class="template-feature" data-feature="title">
                        <input class="font-size data" data-prop="size" />
                        <div class="font-size-slider"></div>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="template-feature" data-feature="editor">
                    <?php
                    $editor_settings = array(
                        'wpautop' => true,
                        'media_buttons' => true,
                        'textarea_rows' => 90,
                        'height' => 200
                    );
                    wp_editor( '', 'tinyMCE', $editor_settings );
                    ?>
                    <input type="hidden" class="data text" data-prop="content" />
                </div>

                <div class="connection-container template-feature" data-feature="connection">
                    <?php _e('Connected to', 'arlima') ?>:
                    <input type="hidden" class="data post" data-prop="post" />
                    <input type="hidden" class="data overriding-url" data-prop="options:overridingURL" />
                    <input type="hidden" class="data overriding-url-target" data-prop="options:target" />
                    <a href="#" class="url"></a>
                    <em class="future-notice">(<?php _e('Future post', 'arlima') ?>)</em>
                    <a href="#" class="change">[<?php _e('change', 'arlima') ?>]</a>
                    <a href="#" class="wp-admin-edit">[<?php _e('edit', 'arlima') ?>]</a>
                    <?php do_action('arlima_list_manager_article_connection'); ?>
                </div>

                <div class="file-include-container">
                    <p>
                        <strong><?php _e('File', 'arlima') ?>:</strong><br />
                        <span class="file"></span>
                    </p>
                    <div class="file-arguments"></div>
                    <input type="hidden" class="data file-args" data-prop="options:fileArgs" />
                </div>

            </div>
        </div>

        <div id="article-connection" class="fancybox" style="display: none; width: 560px; height: 300px">
            <p>
                <strong><?php _e('Connected to', 'arlima') ?>:</strong>
                <span class="connection"></span>
            </p>
            <p>
                <a href="#wp-post" class="button wp-post-btn">
                    <i class="fa fa-pencil-square-o"></i>
                    <?php _e('Connect to post', 'arlima') ?>
                </a>
                <a href="#external-url" class="button overriding-url-btn">
                    <i class="fa fa-external-link"></i>
                    <?php _e('Connect to external URL', 'arlima') ?>
                </a>
            </p>
            <hr />
            <div class="container overriding-url">
                <p class="label">
                    <strong>URL</strong><br />
                    <input type="text" class="overriding-url" />
                </p>
                <p class="label">
                    <strong><?php _e('Open in a new window', 'arlima') ?>?</strong><br />
                    <select class="target">
                        <option value="_blank"><?php _e('Yes', 'arlima') ?></option>
                        <option value=""><?php _e('No', 'arlima') ?></option>
                    </select>
                </p>
                <p class="label">
                    <a href="#" class="button button-primary save"><?php _e('Save'); ?></a>
                </p>
            </div>
            <div class="container wp-post">
                <p>
                    <input type="text" class="search" placeholder="<?php _e('Search for title or post ID...', 'arlima') ?>" />
                    <a href="#" class="search-btn button"><?php _e('Search', 'arlima') ?></a>
                    <input type="hidden" class="post-connection" />
                </p>
                <div class="search-result"></div>
            </div>
        </div>

        <div id="arlima-post-search" class="arlima-postbox">
            <div class="collapse-toggle open"><br /></div>
            <h3><span><?php _e('Articles', 'arlima') ?></span></h3>
            <div class="inside">
                <div class="search-wrapper">
                    <form action="#">
                        <?php
                        $args = array(
                            'orderby'			=> 'name',
                            'show_option_all'   => __('All categories', 'arlima'),
                            'name'              => 'catid',
                            'id'                => 'arlima-posts-category'
                        );
                        wp_dropdown_categories( apply_filters('arlima_dropdown_categories', $args) );

                        $args = array(
                            'show_option_all' 	=> __('All authors', 'arlima'),
                            'name' 				=> 'author',
                            'id' 				=> 'arlima-posts-author',
                            'who'				=> 'authors',
                            'show'              => 'display_name'
                        );
                        wp_dropdown_users( $args );
                        ?>
                        <input type="text" name="search" id="arlima-posts-search" placeholder="<?php _e('Search word', 'arlima') ?>" />
                        <input class="button-secondary action" type="submit" value="<?php _e('Search', 'arlima') ?>" />
                        <img src="<?php echo ARLIMA_PLUGIN_URL .'/images/ajax-loader-trans.gif'; ?>" class="ajax-loader" />
                        <div class="search-opts">
                            <?php Arlima_PostSearchModifier::invokeFormCallbacks() ?>
                        </div>
                    </form>
                </div>
                <table class="widefat search-result">
                    <thead>
                    <tr>
                        <th><?php _e('Title', 'arlima') ?></th>
                        <th><?php _e('Author', 'arlima') ?></th>
                        <th><?php _e('Date', 'arlima') ?></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th>
                            <a href="#" class="previous">
                                <i class="fa fa-arrow-left"></i>
                                <?php _e('Previous', 'arlima') ?>
                            </a>
                        </th>
                        <th>&nbsp;</th>
                        <th>
                            <a href="#" class="next">
                                <?php _e('Next', 'arlima') ?>
                                <i class="fa fa-arrow-right"></i>
                            </a>
                        </th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div id="arlima-article-presets" class="arlima-postbox">
            <div class="collapse-toggle"><br /></div>
            <h3><span><?php _e('Article presets', 'arlima') ?></span></h3>
            <div class="inside" style="display:none;">
                <table class="widefat">
                    <thead>
                    <tr>
                        <th><?php _e('Article', 'arlima') ?></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div><!-- .inside -->
        </div><!-- #arlima-custom-templates -->

        <?php
        $file_includes = apply_filters('arlima_article_includes', array(__('Count Down','arlima') => dirname(__FILE__).'/count-down.php'));
        ksort($file_includes);
        $arlima_file_include = new Arlima_FileInclude();
        ?>
        <div id="arlima-article-file-includes" class="arlima-postbox">
            <div class="collapse-toggle"><br /></div><h3><span><?php _e('File includes', 'arlima') ?></span></h3>
            <div class="inside" style="display:none;">
                <table class="widefat">
                    <thead>
                    <tr>
                    <th>
                        <?php _e('File', 'arlima') ?></th>
                        <th>&nbsp;</th>    
                    </tr>
                    </thead>
                    <tbody>
                        <?php foreach( $file_includes as $label => $file ): if( is_numeric($label) ) $label = basename($file); ?>
                            <tr>
                                <td colspan="2">
                                    <div class="file-include" 
                                        data-args='<?php
                                        // Add prop name to key for faster lookups in js
                                        $args = array();
                                        foreach($arlima_file_include->getFileArgs($file) as $name => $data) {
                                            if( is_numeric($name) ) {
                                                $args[$data['property']] = $data;
                                            } else {
                                                $args[$name] = $data; // Backwards compat
                                            }
                                        }
                                        echo json_encode($args);
                                        ?>'
                                        data-file="<?php echo $file; ?>"
                                        data-label="<?php echo $label ?>">
                                        <?php echo $label; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div><!-- .inside -->

        </div><!-- #arlima-article-functions -->

    </div><!-- .col-wrap -->

</div><!-- #col-right -->

<div id="col-left">

    <div class="col-wrap">

        <div class="tablenav">

            <div id="list-container-header">
                <div class="lists">
                    <select>
                        <option value=""><?php _e('Choose article list', 'arlima') ?></option>
                        <?php
                        $available_lits = $factory->loadListSlugs();
                        foreach($available_lits as $list_data): ?>
                            <option value="<?php echo $list_data->id; ?>"><?php echo $list_data->title; ?></option>
                        <?php endforeach;
                        $import_manager = new Arlima_ImportManager(new Arlima_Plugin());
                        $imported = $import_manager->getImportedLists();
                        if(!empty($imported)): ?>
                            <optgroup label="<?php _e('Imported lists', 'arlima') ?>">
                                <?php foreach($imported as $list_data): ?>
                                    <option value="<?php echo $list_data['url'] ?>">
                                        <?php echo $list_data['title'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif ?>
                    </select>
                    <input class="button-secondary action" type="button" value="<?php _e('Add', 'arlima') ?>" />
                </div>
                <div class="list-search">
                    <input type="text" placeholder="<?php _e('Search', 'arlima') ?>..."/>
                    <ul>
                        <?php foreach($available_lits as $list_data): ?>
                            <li class="list" style="display:none;" data-alid="<?php echo $list_data->id;?>">
                                <?php echo $list_data->title; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div id="list-container-body">
            <i class="fa fa-cog fa-spin ajax-loader large fa-5x"></i>
        </div>

        <div id="list-container-footer">
            <input class="button-secondary action save" type="button" value="<?php _e('Save setup', 'arlima') ?>" />
            <img src="<?php echo ARLIMA_PLUGIN_URL .'/images/ajax-loader-trans.gif'; ?>" class="ajax-loader" />
            <a href="#" class="refresh-lists" title="<?php _e('Update', 'arlima') ?>">
                <?php _e('Reload all lists', 'arlima') ?>
                <i class="fa fa-refresh"></i>
            </a>
        </div>

    </div><!-- .col-wrap -->

</div><!-- #col-left -->

</div><!-- #col-container -->

<div id="arlima-version-message" class="fancybox" style="">
    <h1><?php _e('New version available', 'arlima') ?>!</h1>
    <p><?php printf(__('We have upgraded Arlima to version %s', 'arlima'), '<strong class="version">XX.ZZ</strong>') ?>
        <br />
        <?php _e('Please save your work and reload this browser tab.', 'arlima') ?>
    </p>
    <div class="logo">
        <img src="<?php echo ARLIMA_PLUGIN_URL.'/images/logo.png' ?>" width="71" alt="Arlima" />
    </div>
</div>

<div id="arlima-schedule-modal" class="fancybox">
    <h2><?php _e('Schedule list', 'arlima') ?></h2>
    <div class="message message-notice hidden"><?php _e('Scheduled date must be in the future.', 'arlima') ?></div>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="schedule-date"><?php _e('Publish date', 'arlima') ?>:</label>
                </th>
                <td>
                    <input id="schedule-date" type="date" value="<?php echo date( 'Y-m-d' ) ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="schedule-time"><?php _e('Publish time', 'arlima') ?>:</label>
                </th>
                <td>
                    <input id="schedule-time" type="time" value="<?php echo date( 'H' , current_time( 'timestamp' ) + 60 * 60) ?>:00">
                </td>
            </tr>
        </table>
    <button class="button schedule"><?php _e('Schedule', 'arlima') ?></button>
</div>