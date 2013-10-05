/**
 * This file contains everything that happens when the page for
 * the list manager gets loaded
 *
 * @todo Fix all insufficient use of jQuery
 */
jQuery(function($) {

    // Initiate Arlima and plupload
    Arlima.Manager.init('#arlima-container-area');
    Arlima.ArticleEditor.init();
    ArlimaUploader.init();

    // Load custom templates
    Arlima.Manager.loadCustomTemplates();

    // Setup file includes
    Arlima.Manager.setupFileIncludes();

    // Cast boolean telling us if current user is admin
    if( ArlimaJS.is_admin && $.isNumeric(ArlimaJS.is_admin) ) {
        ArlimaJS.is_admin = parseInt(ArlimaJS.is_admin);
    }

    // Load setup (the lists user has on page when page loaded)
    Arlima.Manager.loadSetup(function() {
        // List requested with url parameter on page
        if(typeof loadArlimListOnLoad != 'undefined') {
            Arlima.Manager.addList(loadArlimListOnLoad);
        }
    });

    // Hax the height of tinyMCE, setting the height as you're supposed
    // to makes it look messed up for some reason...
    setTimeout(function() {
        $('#tinyMCE_ifr').css('height', '120px');
    }, 800);

    // Convert all title attributes to tooltips
    $('[title].tooltip').qtip({
        position: {
            my: 'left top',
            at: 'center right'
        },
        style: Arlima.qtipStyle
    });
    $('[title].tooltip-left').qtip({
        position: {
            corner: {
                tooltip: 'bottomLeft',
                target: 'topLeft'
            }
        },
        style: Arlima.qtipStyle
    });

    // initiate fancy boxes
    $('.fancybox').fancybox( {
        speedIn		:	300,
        speedOut	: 	300,
        titlePosition :	'over'
    });

    // Initiate scissors fancy box popup
    var $imgOptions = $('#arlima-article-image-options');
    $('#arlima-article-image-scissors-popup').fancybox( {
        autoResize: 1,
        fitToView: 1,
        margin: new Array(40,0,0,0),
        speedIn: 300,
        speedOut: 300,
        titlePosition: "over",
        autoDimensions : true,
        afterClose	:	function( ) {
            $('#arlima-article-image-container')
                .removeClass('arlima-fancybox media-item-info')
                .addClass('media-item-info')
				.removeAttr("style");
            $('#arlima-article-image img').removeClass('thumbnail');
            $('#arlima-article-image-scissors').html('').hide();
            Arlima.ArticleEditor.updateArticleImage({updated : Math.round(new Date().getTime() / 1000)});
            Arlima.ArticleEditor.removeImageVersions(); // todo: make sure scissors actually did any changes to the image
        },
        beforeLoad : function(){
            var imgOptions = $imgOptions.data('image_options');
            Arlima.Backend.loadScissorsHTML(imgOptions.attach_id, function(html) {
                if(html) {
                    $('#arlima-article-image-scissors').html(html).show();
                }
            });
            $('#arlima-article-image-container').addClass('arlima-fancybox media-item-info');
            $('#arlima-article-image img')
                .addClass('thumbnail')
                .removeAttr('width')
                .removeAttr('height');
        }
    });


    //
    // Post connection fancy box
    //
    var $postConnectionBox = Arlima.ArticleEditor.PostConnector.$fancyBox;
    var $connectionButtons = $postConnectionBox.find('.button');
    var hasChangedConnection = false;
    $('#arlima-article-connected-post-change').fancybox({
        speedIn		:	300,
        speedOut	: 	300,
        titlePosition :	false,
        autoResize: 1,
        fitToView: 1,
        afterClose : function( ) {
            if( hasChangedConnection !== false ) {
                var target = undefined;
                var connection = '';
                if( hasChangedConnection === 'external' ) {
                    target = $postConnectionBox.find('select').val();
                    connection = $postConnectionBox.find('input.url').val();
                } else {
                    connection = $postConnectionBox.find('input.post-connection').val();
                }

                Arlima.ArticleEditor.PostConnector.connect(connection, target);
                hasChangedConnection = false;
            }
        },
        beforeLoad : function() {
            var connector = Arlima.ArticleEditor.PostConnector;
            var articleData = Arlima.ArticleEditor.$item.data('article');
            hasChangedConnection = false;
            $postConnectionBox.find('.connection').text(connector.getConnectionLabel());
            $postConnectionBox.find('.invalid-info').remove();
            $postConnectionBox.find('.connection-containers').hide();
            $connectionButtons.filter('.open').css('opacity', 1);
            $postConnectionBox.find('input,select').val('');
            $postConnectionBox.find('option').removeAttr('selected');
            if( !articleData.post_id ) {
                $postConnectionBox.find('.url').val(articleData.options.overriding_url || '');
                var target = articleData.options.target;
                if( target ) {
                    $postConnectionBox.find('option[value="'+target+'"]').attr('selected', 'selected');
                }
            }
        }
    });
    $connectionButtons.filter('.open').click(function() {
        $connectionButtons.filter('.open').css('opacity', 1);
        this.style.opacity = '0.6';
        $postConnectionBox.find('.connection-containers').hide();
        $postConnectionBox.find('.'+this.href.split('#')[1]).show();
        return false;
    });
    $postConnectionBox.find('input,select').bind('change', function() {
        hasChangedConnection = $postConnectionBox.find('.external-url').is(':visible') ? 'external':'post-id';
        var $in = $(this);
        // warn if url invalid
        if( $in.hasClass('url') ) {
            var url = $in.val();
            if( url.indexOf('#') !== 0 &&
                url != '' &&
                url.toLowerCase().indexOf('javascript: ') !== 0 &&
                !url.match(/(^|\s)((https?:\/\/)?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/gi)) {
                $in.after('<em style="color:darkred" class="invalid-info"><br />'+ArlimaJS.lang.invalidURL+'</em>');
            }
            else {
                $in.parent().find('.invalid-info').remove();
            }
        }
    });
    $postConnectionBox.find('.do-search').click(function() {
        var search = $.trim($postConnectionBox.find('input[type="search"]').val());
        Arlima.Backend.queryPosts({search:search}, function(data) {
            var $resultContainer = $postConnectionBox.find('.search-result');
            if( data.posts.length == 0 ) {
                $resultContainer.html('<p>'+ArlimaJS.lang.nothingFound+'</p>');
            }
            else {
                var result = '';
                $.each(data.posts, function(i, post) {
                    result += '<p><a href="#" data-post="'+post.post_id+'">'+post.title+'</a></p>';
                });

                $resultContainer.html(result);
                $resultContainer.find('a').click(function() {
                    hasChangedConnection = 'post-id';
                    $postConnectionBox.find('.post-connection').val($(this).attr('data-post'));
                    $('#fancybox-close').click();
                    return false;
                });
            }
        });
        return false;
    });


    //
    // Sticky interval picker
    //
    $('.sticky-interval-fancybox').fancybox({
        autoResize: 1,
        fitToView: 1,
        speedIn		:	300,
        speedOut	: 	300,
        titlePosition :	false,
        afterClose	:	function( ) {
            var data = Arlima.ArticleEditor.$item.data('article');
            var currentInterval = data.options.sticky_interval;
            var $container = $('#sticky-interval-fancybox');
            var _findCheckedValued = function(className) {
                var values = '*';
                var $inputs = $container.find('.'+className);
                if($inputs.filter(':checked').length != $inputs.length) {
                    values = '';
                    $inputs.filter(':checked').each(function() {
                        values += ','+ this.value;
                    });
                    if(values == '')
                        values = '*';
                    else
                        values = values.substr(1);
                }
                return values;
            };

            var newInterval = _findCheckedValued('day') +':'+ _findCheckedValued('hour');
            if(newInterval != currentInterval) {
                data.options.sticky_interval = newInterval;
                Arlima.ArticleEditor.$item.data('article', data);
                $('#arlima-interval').val(data.options.sticky_interval);
                Arlima.ArticleEditor.updateArticle(true, false);
            }
        },
        beforeLoad : function(){
            var articleData = Arlima.ArticleEditor.$item.data('article');
            var $inputs = $('#sticky-interval-fancybox').find('input');
            $inputs.removeAttr('checked');
            $.each(articleData.options.sticky_interval.split(':'), function(i, interval) {
                if($.trim(interval) == '*') {
                    var className = i == 0 ? '.day':'.hour';
                    $inputs.filter(className).attr('checked', 'checked');
                }
                else {
                    $.each(interval.split(','), function(i, val) {
                        $inputs.filter('[value="'+val+'"]').attr('checked', 'checked');
                    });
                }
            })
        }
    });

    // Generate sticky hours
    var $stickyHours = $('#sticky-hour-container').children().eq(0);
    for(var i=1; i < 25; i++) {
        var val = i < 10 ? '0'+i : i;
        var br = i % 8 === 0 ? '<br />':'';
        $('<label><input type="checkbox" class="hour" value="'+val+'" /> '+val+'</label>'+br).insertBefore($stickyHours);
    }

    // Initiate font size slider in article editor
    var hasSliderFocus = false;
    var $fontSizeInput = $("#arlima-edit-article-title-fontsize");
    var $fontSizeSlider = $("#arlima-edit-article-title-fontsize-slider").slider({
        value:18,
        min: 8,
        max: 100,
        slide: function( event, ui ) {
            hasSliderFocus = true;
            $fontSizeInput.val( ui.value );
            Arlima.ArticleEditor.updateArticle();
        }
    })
        .mousedown(function() {
            hasSliderFocus = true;
        });


    // Initiate colour picker in article editor
    $('#arlima-edit-article-options-streamer-color').colourPicker({
        ico:    '',
        title:    false
    });

    // Refresh imported lists every 90 second
    setInterval(function() {
        Arlima.Manager.iterateLists(function(list) {
            if(list.isImported && !Arlima.ArticleEditor.isEditingList(list.id))
                Arlima.Manager.reloadList(list);
        });
    }, 90000);



    /* * * * * * * * * Event handlers * * * * * * * */

    // Sanitize file include query string
    $('#file-include-info input').bind('change', function() {
        if( this.value.indexOf('?') === 0 ) {
            this.value = this.value.substr(1);
        }
        this.value = this.value.replace(/ \& /g, '&');
        this.value = this.value.replace(/\& /g, '&');
        this.value = this.value.replace(/ \&/g, '&');
        this.value = this.value.replace(/ \= /g, '=');
        this.value = this.value.replace(/\= /g, '=');
        this.value = this.value.replace(/ \=/g, '=');
        this.value = $.trim(this.value);
    });

    // Toggle available formats when changing template
    $('#arlima-edit-article-options-template').change(function() {
        Arlima.ArticleEditor.toggleAvailableFormats( this.value );
        Arlima.ArticleEditor.toggleEditorFeatures( this.value );
        Arlima.ArticleEditor.updatePreview();
        Arlima.Manager.triggerEvent('templateChange');
    });

    // Make arlima list searchable
    $('#arlima-search-lists').arlimaListSearch('#arlima-lists .arlima-list-link');
    $('.arlima-list-link').on('click', function() {
        Arlima.Manager.addList($(this).attr('data-alid'));
    });

    // Reload all lists on page
    $('#arlima-refresh-all-lists').click(function(e) {
        var doReload = true;
        if(!e.metaKey && Arlima.Manager.getUnsavedLists().length > 0) {
            doReload = confirm(ArlimaJS.lang.unsaved);
        }
        if(doReload) {
            Arlima.Manager.iterateLists(function(list) {
                Arlima.Manager.reloadList(list);
            });
        }
        return false;
    });

    // Choose an article image from post attachments
    $('#arlima-article-image')
        .click(function(e) {
            e.preventDefault();
            var postId = Arlima.ArticleEditor.data('post_id');

            if( !$.isNumeric(postId) ) {
                alert(ArlimaJS.lang.noImages);
            }
            else {

                $('#arlima-article-attachments').html('');
                Arlima.Backend.getPostAttachments(postId, function(json) {
                    var $attachmentConatiner = $('#arlima-article-attachments');
                    $.each(json, function(idx, img) {
                        $('<div></div>')
                            .addClass('arlima-article-attachment')
                            .html(img.thumb)
                            .on('click', function() {
                                var imgData = Arlima.ArticleEditor.createArlimaArticleImageObject(img.large, 'center', 'full', img.attach_id);
                                imgData.connected = 1;
                                Arlima.ArticleEditor.updateArticleImage(imgData);
                                $('#fancybox-close').trigger('click');
                            })
                            .appendTo($attachmentConatiner);
                    });
                });

                $.fancybox({
                    minHeight: 200,
                    href: "#arlima-article-attachments"
                });
            }
        });

    // Show article preview
    $('#arlima-toggle-preview').click(function() {
        Arlima.ArticleEditor.togglePreview();
        return false;
    });

    // Preview focused list
    $('#arlima-preview-active-list').click(function() {
        Arlima.Manager.previewFocusedList();
        return false;
    });

    // Publish focused list
    $('#arlima-save-active-list').click(function() {
        Arlima.Manager.saveFocusedList();
        return false;
    });

    // Add new list
    $('#arlima-add-list-btn').click(function(e) {
        var id = $('#arlima-add-list-select').val();
        if(id)
            Arlima.Manager.addList(id);
        return false;
    });

    // Time checkbox toggle
    $('.time-checkbox-toggler').on('click', function() {
        var $inputs = $(this).parent().parent().find('input[type=checkbox]');
        if( $inputs.filter('*:checked').length == 0) {
            $inputs.attr('checked', 'checked');
        }
        else {
            $inputs.removeAttr('checked');
        }
        return false;
    });

    // Make it possible to reset certain things when changing "focus". Since most element
    // does not have a real focus/blur event we try to solve this by hiding elements that
    // may be looked at when clicking somewhere on page...
    $('html').click(function() {
        $('.arlima-list-version-select').hide();
        $('.arlima-list-version-info').show();
        hasSliderFocus = false;
    });

    // Make sure we're not reloading page while having unsaved lists
    // and close possibly opened preview window
    window.onbeforeunload = function(){
        if(Arlima.Manager.previewWindow) {
            Arlima.Manager.previewWindow.close();
        }
        if(Arlima.Manager.getUnsavedLists().length > 0)
            return ArlimaJS.lang.unsaved;
    };

    // Save list setup (the lists loaded on page load)
    $("#arlima-save-setup-btn").click(function() {
        Arlima.Manager.saveSetup();
    });

    // Update article and preview when changing data in article form
    $('#arlima-edit-article-form').change( function(e) {
        var $target = $(e.target);
        var changedInput = $target.attr('name');

        // Some inputs will update article in another function when changed
        if( changedInput != 'image_align' && changedInput != 'post_id' && $target.attr('id') != 'arlima-article-image-size' ) {

            // Some inputs doesn't require that we update the preview
            var updatePreview = $.inArray(changedInput, ['title', 'options-pre_title', 'options-streamer_content',
                'options-hiderelated', 'url']) == -1;

            Arlima.ArticleEditor.updateArticle(true, updatePreview);
        }
    })

        // Update some inputs immediately when they get changed (http://wordpress.org/support/topic/manage-list-issue)
        .find('input').bind('keyup', function() {
            if($.inArray(this.name, ['title', 'options-pre_title', 'options-streamer_content', 'post_id', 'url']) > -1) {
                Arlima.ArticleEditor.updateArticle( this, $.inArray(this.name, ['post_id', 'url']) == -1 ); // update article with only that is changed in this input
            }
        });

    // Update article when doing changes to the image
    $imgOptions.find('input').click(function() { Arlima.ArticleEditor.updateArticleImage({updated : Math.round(new Date().getTime() / 1000)}); });
    $imgOptions.find('select').change(function() { Arlima.ArticleEditor.updateArticleImage({updated : Math.round(new Date().getTime() / 1000)}); });
    $('#arlima-article-image-remove').click(function() {
        $('.hide-if-no-image').hide();
        Arlima.ArticleEditor.removeArticleImage();
    });

    // Update article when doing changes to the streamer
    $('#arlima-edit-article-options-streamer-image-list img').click( function() {
        $("[name='options-streamer_image']").val($(this).attr('alt'));
        Arlima.ArticleEditor.updateArticle();
        $.fancybox.close();
    });

    // Change button appearance when checking check boxes (streamer button, sticky button)
    $('.arlima-button input[type="checkbox"]').on('change', function() {
        var $input = $(this);
        if($input.is(':checked'))
            $input.parent().addClass('checked');
        else
            $input.parent().removeClass('checked');
    });

    // Remember where sticky articles should stick
    $('#arlima-option-sticky').on('change', function() {
        var stick = $(this).is(':checked');
        var articleData = Arlima.ArticleEditor.$item.data('article');
        if(stick) {
            articleData.options.sticky_pos = Arlima.ArticleEditor.$item.prevAll().length;
            $('#arlima-option-sticky-pos').val(articleData.options.sticky_pos);
        }
        else if(articleData.options && articleData.options.sticky_pos) {
            $('#arlima-option-sticky-pos').val('');
            articleData.options.sticky_pos = '';
        }

        Arlima.ArticleEditor.$item.data('article', articleData);
    });

    // Put editor blocker back into place when resizing the window
    $(window).bind('resize', function() {
        var list = Arlima.Manager.getFocusedList();
        if( list && list.isImported && Arlima.ArticleEditor.isEditingArticle() ) {
            Arlima.ArticleEditor.toggleEditorBlocker(true);
        }
    });

    // Toggle editor, search and custom templates
    $('.handlediv').click( function() {
        $(this).parent().find('.inside').slideToggle(200);
    });

    // Search wordpress posts
    $('#arlima-post-search').submit( function() {
        Arlima.Manager.searchWordpressPosts(0);
        return false;
    });

    // paging wordpress search
    var $doc = $(document);
    $doc.on('click', '.arlima-get-posts-paging', function() {
        Arlima.Manager.searchWordpressPosts($(this).attr('alt'));
        return false;
    });

    // tinyMCe events (update, focus, preview). This will not work
    // when loading the page with tinyMCE being in HTML mode, therefor
    // we put the initiation in a interval that runs until visual mode
    // is activated
    var tinyMCEEventInterval = setInterval(function() {
        if(tinyMCE !== undefined) {
            clearInterval(tinyMCEEventInterval);

            if(tinyMCE.editors && tinyMCE.editors.length > 0) {

                // Set focus on list when editor is focused
                $(tinyMCE.editors[0].getDoc()).contents().find('body').focus(function(){
                    Arlima.Manager.setFocusedList(Arlima.ArticleEditor.currentlyEditedList);
                });

                // listen to keyboard short cuts
                var numSpaceBarClicks = 1;
                tinyMCE.editors[0].onKeyDown.add(function(editor, e) {
                    var key = e.keyCode ? e.keyCode : e.which;
                    switch (key) {
                        case 80: // p
                            if(e.ctrlKey || e.metaKey) {
                                Arlima.ArticleEditor.togglePreview();
                                e.preventDefault();
                                return false;
                            }
                            break;
                        case 32: // space, update preview every third time
                            if(numSpaceBarClicks % 3 === 0) {
                                arlimaTinyMCEChanged();
                                numSpaceBarClicks = 1;
                            }
                            else {
                                numSpaceBarClicks++;
                            }
                            break;
                        case 76: // l
                            if(e.ctrlKey || e.metaKey) {
                                Arlima.Manager.previewFocusedList();
                                e.preventDefault();
                                return false;
                            }
                            break;
                        // no point in trying to listen to ctrl + s ... it never gets triggered for some reason, probably
                        // a collision with some other javascript
                    }
                });
            }
        }
    }, 500);

    // Disconnect post image
    $('#arlima-article-image-disconnect').click( function(e) {
        var attachId = $('#arlima-article-image-attach_id').val();
        if($.isNumeric(attachId)) {
            Arlima.Backend.duplicateImage(attachId, function(json) {
                if(json) {
                    var args = {attach_id: json.attach_id, html: json.html, connected: 0, updated : Math.round(new Date().getTime() / 1000) };
                    Arlima.ArticleEditor.updateArticleImage(args, true);
                }
            });
        }
        else {
            throw new Error('Trying to disconnect image that is not connected');
        }
        return false;
    });


    // Listen for scissors startup, and uncheck the aspect ratio checkbox
    var modifyScissorsSettings = function(event) {
        var $elem = $(event.target);
        var elemID = $elem.attr('id');

        // Cropped image
        if ( elemID && elemID.indexOf('scissorsCrop') == 0 ) {

            var attachmentID = $('#arlima-article-image-attach_id').val();

            /**
             * @param {String} name
             * @param {Number} rx
             * @param {Number} ry
             */
            var createRatioButton = function(name, rx, ry) {
                $('<button></button>')
                    .html(name)
                    .addClass('button')
                    .appendTo('#scissorsCropPane-' + attachmentID)
                    .bind('click', function() {
                        $('#scissorsLockBox-' + attachmentID).prop("checked", true);
                        scissorsAspectChange(attachmentID);
                        $('#scissorsLockX-' + attachmentID).val(rx);
                        $('#scissorsLockY-' + attachmentID).val(ry);
                        scissorsManualAspectChange(attachmentID);
                        return false;
                    });
            };

            // Create ratio buttons
            createRatioButton('Widescreen', 16, 9);
            createRatioButton('19:9', 19, 9);
            createRatioButton('Cinema', 21, 9);
            createRatioButton('Square', 666, 666);

            // Modify settings in crop form
            $elem.find('input[type="checkbox"]').each(function() {
                if(this.id && this.id.indexOf('scissorsLockBox') == 0){
                    $(this).prop("checked", false);
                }
            });
            $elem.find('div').each(function() {
                if (this.id && this.id.indexOf('scissorsReir') == 0) {
                    $('#'+ this.id).hide();
                }
            });
        }

        else if ( elemID && elemID.indexOf('scissorsWatermark') === 0 ) {
            $elem.find('input[type="checkbox"]').each(function() {
                if( this.id && this.id.indexOf('scissors_watermark_target') == 0 ) {
                    var split = this.id.split("_");
                    if( split[3] !== undefined ) {
                        split = split[3].split("-");
                        $(this).prop("checked", true);
                        scissorsWatermarkStateChanged( split[split.length-1], split[0] );
                    }
                }
            });
        }
    };
    document.addEventListener("DOMNodeInserted", modifyScissorsSettings);


    /* * * * * Keyboard short cuts using jquery.hotkeys plugin * * * * * */


    $doc.bind('keydown', function(e) {
        var key = e.keyCode ? e.keyCode : e.which;

        if((e.ctrlKey || e.metaKey) && $.inArray(key, [80, 83, 76]) > -1) {

            switch (key) {
                case 80: // p
                    Arlima.ArticleEditor.togglePreview();
                    break;
                case 83: // s
                    Arlima.Manager.saveFocusedList();
                    break;
                case 76: // l
                    Arlima.Manager.previewFocusedList();
                    break;
            }

            return false;
        }

        // Increase font size, this should be taken care of by jquery-ui but
        // for some reason we have to create this feature
        else if( $.inArray(key, [39,37]) > -1 && hasSliderFocus ) {
            if( Arlima.ArticleEditor.isEditingArticle() ) {
                var size = parseInt($fontSizeInput.val(), 10);
                size += key == 37 ? -1:1;
                $fontSizeSlider.slider('value', size);
                $fontSizeInput.val(size);
                Arlima.ArticleEditor.updateArticle();
                return false;
            }
        }
    });
});



/* * * * * * * tinyMCE functions that needs to be in global scope * * * * * * */

/**
 * Event callback used in tinyMCE when update of article is needed.
 * This function is applied to tinyMCE event onchange_callback and
 * every third time the space bar i pressed in the editor
 */
function arlimaTinyMCEChanged() {
    Arlima.ArticleEditor.updateArticle();
}