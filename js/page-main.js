/**
 * This file contains everything that happens when the page for the article
 * list editor gets loaded
 */
jQuery(function($) {

    // Initiate Arlima and plupload
    Arlima.Manager.init('#arlima-container-area');
    Arlima.ArticleEditor.init();
    initPlUopload();

    // Load custom templates
    Arlima.Manager.loadCustomTemplates();

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

    // Convert all title attributes to tooltips
    $('[title].tooltip').qtip({
        position: {
            my: 'left top',
            at: 'center right'
        },
        style: { classes: 'ui-tooltip-tipsy ui-tooltip-blue'}
    });

    // initiate fancy boxes
    $('.fancybox').fancybox( {
        speedIn		:	300,
        speedOut	: 	300,
        titlePosition :	'over'
    });

    // Initiate scissors fancy box popup
    $('#arlima-article-image-scissors-popup').fancybox( {
        autoDimensions :	true,
        speedIn		:	300,
        speedOut	: 	300,
        titlePosition :	'over',
        onComplete	:	function() { },
        onClosed	:	function( ) {
            $('#arlima-article-image-container')
                .removeClass('arlima-fancybox media-item-info')
                .addClass('media-item-info');

            $('#arlima-article-image img').removeClass('thumbnail');
            $('#arlima-article-image-scissors').html('').hide();
            Arlima.ArticleEditor.updateArticleImage({updated : Math.round(new Date().getTime() / 1000)});
        },
        onStart : function(){
            var imgOptions = $('#arlima-article-image-options').data('image_options');
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

    // Sticky interval picker
    $('.sticky-interval-fancybox').fancybox({
        autoDimensions :	true,
        speedIn		:	300,
        speedOut	: 	300,
        titlePosition :	false,
        onComplete	:	function() { },
        onClosed	:	function( ) {
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
                Arlima.ArticleEditor.updateArticle();
            }
        },
        onStart : function(){
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
    $("#arlima-edit-article-title-fontsize-slider").slider({
        value:18,
        min: 8,
        max: 100,
        slide: function( event, ui ) {
            $( "#arlima-edit-article-title-fontsize" ).val( ui.value );
            Arlima.ArticleEditor.updateArticle();
        }
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
    $('#arlima-refresh-all-lists').click(function() {
        var doReload = true;
        if(Arlima.Manager.getUnsavedLists().length > 0) {
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
            var postId = $('#arlima-article-post_id').val();

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
                                Arlima.ArticleEditor.updateArticleImage(imgData);
                                $('#fancybox-close').trigger('click');
                            })
                            .appendTo($attachmentConatiner);
                    });
                });

                $.fancybox({
                    'href' : '#arlima-article-attachments'
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

    // Publish current list
    $('#arlima-save-active-list').click(function() {
        Arlima.Manager.saveFocusedList();
        return false;
    });

    // Add new list
    $('#arlima-add-list-btn').click(function(e) {
        var id = $('#arlima-add-list-select').val();
        if(id)
            Arlima.Manager.addList(id);

        e.preventDefault();
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

    // Update article preview when changing data in article form
    $('#arlima-edit-article-form').change( function(e) {
        var $target = $(e.target);
        if( $target.attr('name') != 'image_align' && $target.attr('id') != 'arlima-article-image-size' ) {
            Arlima.ArticleEditor.updateArticle();
        }
    });

    // Update article when doing changes to the image
    $('#arlima-article-image-options input').click(function() { Arlima.ArticleEditor.updateArticleImage({updated : Math.round(new Date().getTime() / 1000)}); });
    $('#arlima-article-image-options select').change(function() { Arlima.ArticleEditor.updateArticleImage({updated : Math.round(new Date().getTime() / 1000)}); });
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
        Arlima.ArticleEditor.updateArticle();
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

    // changing post connection
    $('#arlima-article-connected-post-change').click( function(e) {
        e.preventDefault();
        $(this).hide();
        $('#arlima-article-connected-post').html('');
        $('#arlima-article-post_id').show().focus();
    });

    // Update with new url when changing post connection
    var currentPostConnection = null;
    $('#arlima-article-post_id')
        .focus(function() {
           currentPostConnection = $(this).val();
        })
        .blur(function() {
            var $input = $(this);
            var postId = $.trim($input.val());
            if($.isNumeric(postId) && currentPostConnection != postId) {
                Arlima.Backend.getPost(postId, function(json) {
                    var articleData = Arlima.ArticleEditor.$item.data('article');
                    if(json && json.url) {
                        $('#arlima-edit-article-url').val(json.url);
                        articleData.publish_date = json.publish_date;
                    }
                    else {
                        articleData.publish_date = 0;
                        alert('This post does not exist');
                    }

                    Arlima.ArticleEditor.$item.data('article', articleData);
                    Arlima.ArticleEditor.updateArticle();
                });
            }
            else {
                var articleData = Arlima.ArticleEditor.$item.data('article');
                articleData.publish_date = 0;
                Arlima.ArticleEditor.$item.data('article', articleData);
                Arlima.ArticleEditor.updateArticle();
            }
        });

    // Real time update of article title
    $('#arlima-edit-article-title').keyup(function() {
        var el = Arlima.ArticleEditor.currentlyEditedList.titleElement;
        if(el != '') {
            var articleData = Arlima.ArticleEditor.$item.data('article');
            var entryWord = articleData.options.pre_title;
            var title = (entryWord ? '<span class="arlima-pre-title">'+entryWord+'</span> ':'') +this.value;
            if(articleData.parent == -1) {
                Arlima.ArticleEditor._$preview.find(el).eq(0).html(title);
            }
            else if(articleData.id) {
                $('#teaser-'+articleData.id).find(el).eq(0).html(title);
            }
        }
    });

    // tinyMCe events (update, focus, preview), will not work
    // when loading page with tinyMCE being in HTML mode
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
                    var args = {attach_id: json.attach_id, html: json.html, connected: 'false', updated : Math.round(new Date().getTime() / 1000) };
                    Arlima.ArticleEditor.updateArticleImage(args, true);
                }
            });
        }
        else {
            throw new Error('Trying to disconnect image that is not connected');
        }
        e.preventDefault();
    });



    // Listen for scissors startup, and uncheck the aspect ratio checkbox
    document.addEventListener("DOMNodeInserted", function(event) {
        var node_id = $(event.target).attr('id')+"";

        //String function, self explanatory
        String.prototype.startsWith = function(str) {
            return this.match("^"+str)==str;
        };

        function setScissorsRatio( attach_id, rx, ry ){
            //alert( attach_id + " " + rx + ry);
            $('#scissorsLockBox-' + attach_id).prop("checked", true);
            scissorsAspectChange(attach_id);
            $('#scissorsLockX-' + attach_id).val(rx);
            $('#scissorsLockY-' + attach_id).val(ry);
            scissorsManualAspectChange(attach_id);
        }
        
        if ( node_id.startsWith('scissorsCrop') ){
            $('#'+ node_id +' input[type="checkbox"]').each( function( i, e ){
                var aspect = e.id+"";
                if(aspect.startsWith('scissorsLockBox')){
                    $(e).prop("checked", false);
                }
            });

            $('#'+ node_id +' div').each( function( i, e ){
                var div = e.id+"";
                if ( div.startsWith('scissorsReir') ){
                    $('#'+ div).hide();
                }
            });

            // Attach ratio buttons
            var arlima_attach_id = $('#arlima-article-image-attach_id').val();
            var ratio_ws = $('<button />').click( function(e){
                e.preventDefault();
                setScissorsRatio(arlima_attach_id, '16', '9');
            })
            .html('Widescreen')
            .addClass('button');

            var ratio_cinema = $('<button>').click( function(e){
                e.preventDefault();
                setScissorsRatio(arlima_attach_id, '21', '9');
            })
            .html('Cinema')
            .addClass('button');

            var ratio_square = $('<button>').click( function(e){
                e.preventDefault();
                setScissorsRatio(arlima_attach_id, '666', '666');
            })
            .html('Kvadrat')
            .addClass('button');

            $('#scissorsCropPane-' + arlima_attach_id).append(ratio_ws);
            $('#scissorsCropPane-' + arlima_attach_id).append(ratio_cinema);
            $('#scissorsCropPane-' + arlima_attach_id).append(ratio_square);
        }

        if ( node_id.startsWith('scissorsWatermark') ){
            var vkpt_attach_id = $('#vkpt-attach-id').val();

            $('#'+ node_id +' input[type="checkbox"]').each( function( i, e ){
                var node = e.id+"";
                if(node.startsWith('scissors_watermark_target')){
                    var split = node.split("_");
                    split = split[3].split("-");
                    $(e).prop("checked", true);
                    scissorsWatermarkStateChanged( split[1], split[0] );
                }
            });
        }
    });


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

            e.preventDefault();
            return false;
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