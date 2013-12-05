<?php
/**
 * Page where you manage content of the lists
 *
 * @package Arlima
 * @since 1.0
 */
$factory = new Arlima_ListFactory();
$arlima_plugin = new Arlima_Plugin();
?>
    <div id="col-container">

    <div id="col-right">

    <div class="col-wrap">
    <div id="arlima-toplinks">
        <a href="#" id="arlima-toggle-preview" title="ctrl + p"><?php _e('Toggle article preview', 'arlima') ?></a>
        <a href="#" id="arlima-save-active-list" title="ctrl + s"><?php _e('Publish list', 'arlima') ?></a>
        <a href="#" id="arlima-preview-active-list" title="ctrl + l"><?php _e('Preview list', 'arlima') ?></a>
    </div>

    <div id="arlima-preview"></div>

    <div id="arlima-edit-article" class="arlima-postbox">

    <div class="handlediv"><br /></div><h3><span><?php _e('Edit', 'arlima') ?></span></h3>
    <div class="inside" style="display:none;">

    <form method="post" action="/wp-admin/admin-ajax.php" id="arlima-edit-article-form">

    <ul class="arlima-streamer">
        <li>

            <div class="arlima-button arlima-streamer-activate cupid-green">
                <input type="checkbox" value="1" name="options-streamer" id="arlima-edit-article-options-streamer" />
                <label for="arlima-edit-article-options-streamer"><?php _e('Streamer', 'arlima') ?></label>
            </div>

            <div id="arlima-edit-article-options-streamer-content" >

                <select name="options-streamer_type" id="arlima-edit-article-options-streamer-type">
                    <option value="extra"><?php _e('Extra', 'arlima') ?></option>
                    <?php if( apply_filters('arlima_support_text_streamers', true) === true ): ?>
                        <option value="text"><?php _e('Custom', 'arlima') ?></option>
                    <?php endif; ?>
                    <option value="image"><?php _e('Image', 'arlima') ?></option>
                    <?php foreach( apply_filters('arlima_streamer_classes', array()) as $streamer_class => $label ): ?>
                        <option value="text-<?php echo $streamer_class ?>"><?php echo $label ?></option>
                    <?php endforeach; ?>
                </select>

                <div style="display:none;" class="arlima-edit-article-options-streamer-choice" id="arlima-edit-article-options-streamer-text">
                    <input type="text" name="options-streamer_content" />
                    <select name="options-streamer_color" id="arlima-edit-article-options-streamer-color">
                        <?php Arlima_Plugin::loadStreamerColors(); ?>
                    </select>
                </div>

                <div style="display:none;" class="arlima-edit-article-options-streamer-choice" id="arlima-edit-article-options-streamer-image">
                    <a class="fancybox" id="arlima-edit-article-options-streamer-image-link" href="#arlima-edit-article-options-streamer-image-list"><?php _e('Choose streamer', 'arlima') ?></a>
                    <div style="display:none">
                        <div id="arlima-edit-article-options-streamer-image-list">
                            <?php
                            $streamers = apply_filters('arlima_streamer_images', array( ARLIMA_PLUGIN_URL .'/images/streamer-example.png'));
                            if( !empty($streamers) ):
                                foreach( $streamers as $streamer ): ?>
                                    <img src="<?php echo $streamer ?>" alt="<?php echo $streamer; ?>" title="<?php echo ucfirst(str_replace(array('-', '_'), ' ', pathinfo($streamer, PATHINFO_FILENAME ))); ?>" /><br />
                                <?php endforeach;
                            endif;
                            ?>
                        </div>
                    </div>
                    <input type="hidden" name="options-streamer_image" />
                </div>
                <br style="clear:both;" />
            </div><!-- #arlima-edit-article-options-streamer-content -->
        </li>
    </ul>

    <ul>

    <li>

        <div id="arlima-article-image-options">
            <input type="hidden" id="arlima-article-image-attach_id" name="attach_id" />
            <input type="hidden" id="arlima-article-image-updated" name="image_updated" />
            <input type="hidden" id="arlima-article-image-connected_to_post_thumbnail" name="connected_to_post_thumbnail" />
            <select id="arlima-article-image-size" name="image_size">
                <option value="full"><?php _e('Full width', 'arlima') ?> &nbsp;&nbsp;</option>
                <option value="half"><?php _e('50% wide', 'arlima') ?></option>
                <option value="third"><?php _e('33% wide', 'arlima') ?></option>
                <option value="quarter"><?php _e('25% wide', 'arlima') ?></option>
                <option value="fifth"><?php _e('20% wide', 'arlima') ?></option>
                <option value="sixth"><?php _e('15% wide', 'arlima') ?></option>
            </select>
            <div id="arlima-article-image-alignment" style="display:inline;">
                <input type="radio" name="image_align" id="image-align-left" value="alignleft" /> <label for="image-align-left"><?php _e('Align left', 'arlima') ?></label>
                <input type="radio" name="image_align" id="image-align-center" value="aligncenter" /> <label for="image-align-center"><?php _e('Align center', 'arlima') ?></label>
                <input type="radio" name="image_align" id="image-align-right" value="alignright" /> <label for="image-align-right"><?php _e('Align right', 'arlima') ?></label>
            </div>
        </div>


        <div id="arlima-article-image-container">
            <div id="arlima-article-image" class="empty tooltip-left" title="<?php _e('Drag images to this container', 'arlima') ?>"></div>
            <div id="arlima-article-image-links">
                <ul>
                    <li><button id="arlima-article-image-browse" class="cupid-blue"><?php _e('Browse', 'arlima') ?></button></li>
                    <?php if( Arlima_Plugin::isScissorsInstalled() ): ?>
                        <li class="hide-if-no-image"><button id="arlima-article-image-scissors-popup" class="fancybox scissors_popup cupid-green" href="#arlima-article-image-container"><?php _e('Edit', 'arlima') ?></button></li>
                    <?php endif; ?>
                    <li class="hide-if-no-image"><button id="arlima-article-image-disconnect" class="cupid-orange"><?php _e('Disconnect', 'arlima') ?></button></li>
                    <li class="hide-if-no-image"><a href="#" id="arlima-article-image-remove"> <img src="<?php echo ARLIMA_PLUGIN_URL .'/images/close-icon.png'; ?>" class="remove" alt="remove"  /><?php _e('Remove', 'arlima') ?></a></li>

                </ul>
            </div>

            <div id="arlima-article-image-scissors" style="display:none;"></div>

            <div style="display:none">
                <div id="arlima-article-attachments"></div>
            </div>
            <br style="clear:both;" />
        </div>
        <br style="clear:both;" />

    </li>

    <li style="clear:both;">
        <div id="arlima-article-settings">
            <?php _e('Title entry word', 'arlima') ?>
            <input type="text" id="arlima-edit-article-options-pre_title" name="options-pre_title" placeholder="" />
            <?php
            $formats = Arlima_ArticleFormat::getAll();
            if( !empty($formats) ): ?>
                <?php _e('Format', 'arlima') ?>
                <select id="arlima-edit-article-options-format" name="options-format">
                    <option value=""><?php _e('Default', 'arlima') ?></option>
                    <?php foreach($formats as $format): ?>
                        <option value="<?php echo $format['class'] ?>" data-arlima-template="<?php foreach($format['templates'] as $templates) echo "[$templates]"; ?>">
                            <?php echo $format['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <span id="template-switcher">
            <?php
            _e('Template', 'arlima');
            $hidden = apply_filters('arlima_hidden_templates', array(), false);
            ?>
                <select  id="arlima-edit-article-options-template" name="options-template">
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
            </span>
        </div>
        <input id="arlima-edit-article-title" name="title" value="" placeholder="<?php _e('Title', 'arlima') ?>" />
        <input type="text" style="width:28px" id="arlima-edit-article-title-fontsize" class="arlima-title-fontsize" name="title_fontsize" value="14" />
        <div id="arlima-edit-article-title-fontsize-slider" style="width:120px;display:inline-block"> </div>
        <div id="file-include-info">
            <p>
                <strong><?php _e('File', 'arlima') ?>:</strong><br />
                <span class="file"></span>
            </p>
            <p>
                <strong><?php _e('Arguments (query string)', 'arlima'); ?></strong><br />
                <input type="text" name="options-file_args" />
            </p>
        </div>
    </li>

    <li>
        <?php

        $editor_settings = array(
            'wpautop' => true,
            'media_buttons' => true,
            'textarea_rows' => 90,
            'tinymce' => array(
                'onchange_callback' => 'arlimaTinyMCEChanged'
                // 'save_onsavecallback' => 'arlimaTinyMCESaved' does not work, ctrl + s never gets triggered
            )
        );

        wp_editor( '', 'tinyMCE', $editor_settings );

        ?>
    </li>
    <li>
        <div id="arlima-article-wp-connection">
            <table>
                <tr>
                    <td>
                        <span id="post-connection">
                            <?php _e('Connected to', 'arlima') ?>:
                            <span id="arlima-article-connected-post"></span>
                            <a  href="#post-connect-fancybox" id="arlima-article-connected-post-change">
                                <?php _e('[change]', 'arlima') ?>
                            </a>
                            <a href="#" id="arlima-article-connected-post-open">
                                <?php _e('[open]', 'arlima') ?>
                            </a>
                            <input type="hidden" name="post_id" />
                            <input type="hidden" name="options-overriding_url" />
                            <input type="hidden" name="options-target" />
                            <em id="future-notice">(<?php _e('Future post', 'arlima') ?>)</em>
                        </span>
                    </td>
                    <?php if( Arlima_Plugin::isWPRelatedPostsInstalled() ): ?>
                        <td>
                            <input type="checkbox" id="arlima-edit-article-options-hiderelated" name="options-hiderelated"
                                   value="1" data-default="<?php echo $arlima_plugin->getSetting('hide_related_posts_default') == '1' ? 'checked':'' ?>" />
                            <label for="arlima-edit-article-options-hiderelated"><?php _e('Hide related', 'arlima') ?></label>
                        </td>
                    <?php endif; ?>
                </tr>
            </table>
        </div>
        <div id="arlima-article-functions">

            <div class="arlima-button cupid-green">
                <input type="checkbox" value="1" name="options-sticky" id="arlima-option-sticky" />
                <input type="hidden" value="" name="options-sticky_pos" id="arlima-option-sticky-pos" />
                <input type="hidden" value="" name="options-section_divider" />
                <input type="hidden" value="" name="options-file_include" />
                <label for="arlima-option-sticky"><?php _e('Sticky', 'arlima') ?></label>
            </div>

            <div id="sticky-interval">
                <a href="#sticky-interval-fancybox" class="sticky-interval-fancybox">[<?php _e('Interval', 'arlima') ?>]</a>
                <input type="hidden" name="options-sticky_interval" id="arlima-interval" value="*:*" />
            </div>

            <div class="arlima-button cupid-green">
                <input type="checkbox" value="1" name="options-admin_lock" id="arlima-option-admin-lock" />
                <label for="arlima-option-admin-lock"><?php _e('Admin lock', 'arlima') ?></label>
            </div>

            <div style="display: none;">
                <div id="sticky-interval-fancybox">
                    <h2><?php _e('Days', 'arlima'); ?></h2>
                    <div class="sticky-interval-time">
                        <label><input type="checkbox" class="day" value="Mon" /> <?php _e('Monday') ?></label>
                        <label><input type="checkbox" class="day" value="Tue" /> <?php _e('Tuesday') ?></label>
                        <label><input type="checkbox" class="day" value="Wed" /> <?php _e('Wednesday') ?></label>
                        <label><input type="checkbox" class="day" value="Thu" /> <?php _e('Thursday') ?></label>
                        <label><input type="checkbox" class="day" value="Fri" /> <?php _e('Friday') ?></label>
                        <label><input type="checkbox" class="day" value="Sat" /> <?php _e('Saturday') ?></label>
                        <label><input type="checkbox" class="day" value="Sun" /> <?php _e('Sunday') ?></label>
                        <p>
                            <a href="#" class="time-checkbox-toggler">[<?php _e('Toggle all', 'arlima') ?>]</a>
                        </p>
                    </div>
                    <h2><?php _e('Hours', 'arlima'); ?></h2>
                    <div id="sticky-hour-container" class="sticky-interval-time">
                        <!-- inputs for hours generated with js -->
                        <p>
                            <a href="" class="time-checkbox-toggler">[<?php _e('Toggle all', 'arlima') ?>]</a>
                        </p>
                    </div>
                </div>
            </div>

            <div style="display: none">
                <div id="post-connect-fancybox" style="width: 400px; height: 300px">
                    <p>
                        <strong><?php _e('Connected to', 'arlima') ?>:</strong>
                        <span class="connection"></span>
                    </p>
                    <p>
                        <a href="#wp-post" class="button open"><?php _e('Connect to post', 'arlima') ?></a>
                        <a href="#external-url" class="button open"><?php _e('Connect to external URL', 'arlima') ?></a>
                    </p>
                    <div class="external-url connection-containers">
                        <p style="line-height: 220%">
                            <strong>URL</strong>
                            <input type="text" class="url" style="width: 90%" />
                            <br />
                            <strong><?php _e('Open in a new window', 'arlima') ?>?</strong>
                            <br />
                            <select>
                                <option value="_blank"><?php _e('Yes', 'arlima') ?></option>
                                <option value=""><?php _e('No', 'arlima') ?></option>
                            </select>
                        </p>
                    </div>
                    <div class="wp-post connection-containers">
                        <p>
                            <input type="search" placeholder="<?php _e('Search for title or post ID...', 'arlima') ?>" />
                            <a href="#" class="do-search button"><?php _e('Search', 'arlima') ?></a>
                            <input type="hidden" class="post-connection" />
                        </p>
                        <div class="search-result"></div>
                    </div>
                </div>
            </div>

        </div>
    </li>

    </ul>

    </form>
    </div><!-- .inside -->

    </div><!-- #arlima-edit-article -->


    <div id="arlima-get-posts" class="arlima-postbox">
        <div class="handlediv"><br /></div><h3><span><?php _e('Articles', 'arlima') ?></span></h3>
        <div class="inside">
            <div class="tablenav">
                <div class="alignleft">
                    <form method="post" action="" id="arlima-post-search">
                        <?php
                        $args = array(
                            'orderby'			=> 'name',
                            'show_option_all'   => __('All categories', 'arlima'),
                            'name'              => 'catid',
                            'id'                => 'arlima-posts-category'
                        );
                        wp_dropdown_categories( $args );

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
                        <input class="button-secondary action" type="submit" value="<?php _e('Search', 'arlima') ?>" /> <img src="<?php echo ARLIMA_PLUGIN_URL .'/images/ajax-loader-trans.gif'; ?>" id="arlima-get-posts-loader" class="ajax-loader" />
                        <div class="search-modifications">
                            <?php Arlima_PostSearchModifier::invokeFormCallbacks() ?>
                        </div>
                    </form>
                    <br class="clear" />
                </div>
            </div><!-- .tablenav -->

            <div class="clear"></div>

            <div id="arlima-posts">

            </div><!-- #arlima-posts -->
        </div><!-- .inside -->

    </div><!-- #arlima-get-posts -->

    <div id="arlima-custom-templates" class="arlima-postbox">
        <div class="handlediv"><br /></div><h3><span><?php _e('Teaser templates', 'arlima') ?></span></h3>
        <div class="inside" style="display:none;">
            <div id="arlima-templates">

            </div><!-- #arlima-templates -->
        </div><!-- .inside -->

    </div><!-- #arlima-custom-templates -->

    <?php if( $file_includes = apply_filters('arlima_article_includes', array()) ):
        $arlima_file_include = new Arlima_FileInclude(); ?>
        <div id="arlima-article-file-includes" class="arlima-postbox">
            <div class="handlediv"><br /></div><h3><span><?php _e('File includes', 'arlima') ?></span></h3>
            <div class="inside" style="display:none;">
                <table class="widefat">
                    <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php _e('File', 'arlima') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php foreach( $file_includes as $label => $file ): if( is_numeric($label) ) $label = basename($file); ?>
                            <tr>
                                <td>
                                    <li class="dragger listitem <?php echo str_replace(array('/', '\\', '.'), '-', $file) ?>"
                                        data-args='<?php echo json_encode($arlima_file_include->getFileArgs($file)) ?>'
                                        data-file="<?php echo $file; ?>"
                                        data-label="<?php echo $label ?>">
                                        <div>
                                                <span class="arlima-listitem-title"><img
                                                        src="<?php echo ARLIMA_PLUGIN_URL . '/images/arrow.png'; ?>" class="handle" alt="move"
                                                        height="16" width="16"/></span>
                                            <img class="arlima-listitem-remove" alt="remove"
                                                 src="<?php echo ARLIMA_PLUGIN_URL . '/images/close-icon.png'; ?>"/>
                                        </div>
                                    </li>
                                </td>
                                <td>
                                    <?php echo $label; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php _e('File', 'arlima') ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div><!-- .inside -->

        </div><!-- #arlima-article-functions -->
    <?php endif; ?>

    </div><!-- .col-wrap -->

    </div><!-- #col-right -->

    <div id="col-left" style="position:relative;">
        <div class="col-wrap">
            <div class="tablenav">
                <div class="alignright">
                    <div style="display:inline;">
                        <select name="arlima-add-list-select" id="arlima-add-list-select">
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
                        <input id="arlima-add-list-btn" class="button-secondary action" type="button" name="arlima-add-list-btn" value="<?php _e('Add', 'arlima') ?>" />
                    </div>

                    <div id="arlima-lists">
                        <input type="text" name="arlima-search-lists" id="arlima-search-lists" placeholder="<?php _e('Search', 'arlima') ?>..."/>
                        <ul>
                            <?php
                            foreach($available_lits as $list_data) { ?>
                                <li class="arlima-list-link" style="display:none;" data-alid="<?php echo $list_data->id;?>"><?php echo $list_data->title; ?></li>
                            <?php } ?>
                        </ul>
                    </div>
                    <br class="clear" />
                </div>
            </div><!-- .tablenav -->
            <div class="clear"></div>
            <div class="arlima-container-area" id="arlima-container-area">
                <img src="<?php echo ARLIMA_PLUGIN_URL .'/images/ajax-loader-100.gif'; ?>" id="setup-loader" class="ajax-loader" />
            </div><!-- #arlima-container-area-->
            <div>
                <input id="arlima-save-setup-btn" class="button-secondary action" type="button" name="arlima-save-setup-btn" value="<?php _e('Save setup', 'arlima') ?>" />
                <img src="<?php echo ARLIMA_PLUGIN_URL .'/images/ajax-loader-trans.gif'; ?>" id="save-setup-loader" class="ajax-loader" />
                <a href="#" class="arlima-refresh-all-lists" id="arlima-refresh-all-lists" title="<?php _e('Update', 'arlima') ?>">
                    <?php _e('Reload all lists', 'arlima') ?>
                    <img src="<?php echo ARLIMA_PLUGIN_URL .'/images/reload-icon-16.png'; ?>"  />
                </a>
            </div>
        </div><!-- .col-wrap -->
    </div><!-- #col-left -->
    </div><!-- #col-container -->
<?php
