/**
 * Arlima admin library
 *
 * @todo: Look over all insufficient use of jQuery
 * @todo: make compressed version of this file
 * @todo: maybe move each class to its own file and write unit test
 *
 * Dependencies:
 *  - jQuery
 *  - jQuery.effects
 *  - jQuery.qtip
 *  - jQuery.slider
 *  - ArlimaJS
 *  - ArlimaTemplateLoader
 *
 * General coding guide lines:
 *  - All variables, functions written in CamelCase, first letter in uppercase for classes and static object, lowercase otherwise
 *  - Prefix variables referring to a jQuery object with $
 *  - Prefix functions/variables considered private with _
 *  - Prefix variables considered private referring to a jQuery object with _$
 *  - Always declare variables using "var" keyword
 *  - Always add function and variable comments according to JSDoc (http://en.wikipedia.org/wiki/JSDoc)
 *
 */
var Arlima = (function($, ArlimaJS, ArlimaTemplateLoader, window) {

    'use strict';


    /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Communication between backend and client
     * @property {Object}
     */
    var Backend = {

        /**
         * @param {Object} data
         * @param {Function} callback
         */
        queryPosts : function(data, callback) {
            this._ajax('arlima_query_posts', data, callback);
        },

        /**
         * @param {Number} listId
         * @param {Number} version
         * @param {Function} callback
         */
        getLaterVersion : function(listId, version, callback) {
            this._ajax('arlima_check_for_later_version', {alid:listId, version:version}, callback);
        },

        /**
         * Get URL for post with given id
         * @param {Number} postId
         * @param callback
         */
        getPost : function(postId, callback) {
            this._ajax('arlima_get_post', {postid:postId}, callback);
        },

        /**
         * Get all attachments related to wordpress post with given id
         * @param {Number} postId
         * @param {Function} callback
         */
        getPostAttachments : function(postId, callback) {
            this._ajax('arlima_get_attached_images', {postid: postId}, callback);
        },

        /**
         * @param {Number} listId
         * @param {Array|Object} articles
         * @param {Function} callback
         */
        saveList : function(listId, articles, callback) {
            this._ajax("arlima_save_list", {alid:listId, articles:articles}, callback);
        },

        /**
         * @param {Number} listId
         * @param {Array} articles
         * @param {Function} callback
         */
        savePreview : function(listId, articles, callback) {
            this._ajax("arlima_save_list", {alid:listId, articles:articles, preview:1}, callback);
        },

        /**
         * @param {Function} callback
         */
        loadCustomTemplateData : function(callback) {
            this._ajax('arlima_print_custom_templates', {}, callback);
        },

        /**
         * Load information about a list and its articles
         * @param {Number} listID 
         * @param {Number|String} version - Optional, empty string to get latest version
         * @param {Function} callback - Optional
         */
        loadListData : function(listID, version, callback) {
            this._ajax('arlima_add_list_widget', {alid:listID, version:version}, callback);
        },

        /**
         * Get the lists supposed to be loaded on page load for currentyl
         * visited user
         * @see Backend.saveListSetup()
         * @param {Function} callback - Optional
         */
        loadListSetup : function(callback) {
            this._ajax('arlima_get_list_setup', {}, callback);
        },

        /**
         * Save external image (local or remote) to wordpress
         * @param {String} url
         * @param {Number} postId - Optional
         * @param {Function} callback - Optional
         */
        plupload : function(url, postId, callback) {
            var extension = url.substr(url.lastIndexOf('.')+1).toLowerCase();
            var queryBegin = extension.indexOf('?');
            if(queryBegin > -1) {
                extension = extension.substr(0, queryBegin);
            }
            if($.inArray(extension, ['jpg', 'jpeg', 'png', 'gif']) == -1) {
                log('Trying to upload something that\'s not considered to be an image', 'error');
                if(typeof callback == 'function') {
                    callback(false);
                }
            } else {
                this._ajax('arlima_upload', { imgurl : url, postid: postId }, callback);
            }
        },

        /**
         * Save the lists that should be available on page load for currently logged in user
         * @param {Array} lists
         * @param {Function} callback - Optional
         */
        saveListSetup : function(lists, callback) {
            this._ajax('arlima_save_list_setup', {lists: lists}, callback);
        },

        /**
         * @param {Number} attachmentId
         * @param {Function} callback - Optional
         */
        loadScissorsHTML : function(attachmentId, callback) {
            this._ajax('arlima_get_scissors', {attachment_id: attachmentId}, callback, 'html');
        },

        /**
         * @param {Number} attachId
         * @param {Function} callback
         */
        duplicateImage : function(attachId, callback) {
            this._ajax('arlima_duplicate_image', {attachid:attachId}, callback);
        },

        /**
         * @param {String} action
         * @param {Object} postArgs
         * @param {Function} callback - Optional
         * @param {String} [dataType]
         * @private
         */
        _ajax : function(action, postArgs, callback, dataType) {
            postArgs['action'] = action;
            postArgs['_ajax_nonce'] = ArlimaJS.arlimaNonce;
            if(dataType === undefined)
                dataType = 'json';

            $.ajax({
                url : ArlimaJS.ajaxurl,
                type : 'POST',
                data : postArgs,
                dataType : dataType,
                success : function(json) {
                    if(json == -1) {
                        alert(ArlimaJS.lang.loggedOut);
                        json = false;
                    }
                    else if(json.error) {
                        alert(json.error);
                        json = false;
                    }

                    if(typeof callback == 'function') {
                        callback(json);
                    }
                },
                error : function(err, xhr) {
                    if(err.status == 0) {
                        log('The request is refused by browser, most probably because '+
                            'of fast reloading of the page before ajax call was completed', 'warn');
                        return;
                    }

                    var mess = err.responseText;
                    if(typeof JSON != 'undefined') {
                        var json = false;
                        try {
                            json = JSON.parse(mess);
                        } catch(e) { }

                        if(json && typeof json.error != 'undefined')
                            mess = json.error;
                    }
                    alert("ERROR:\n------------\n"+mess);
                    log(err, 'error');
                    log(xhr, 'error');
                    if(typeof callback == 'function')
                        callback(false);
                }
            });
        }
    };



    /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Manages the article form and teaser preview
     * @todo Look over image management, insufficient use of jQuery
     * @property {Object}
     */
    var ArticleEditor = {

        /**
         * @property {jQuery}
         */
        _$blocker : null,

        /**
         * @property {jQuery}
         */
        _$form : null,

        /**
         * @property {jQuery}
         */
        _$preview : null,

        /**
         * @property {ArlimaList}
         */
        currentlyEditedList : false,

        /**
         * @property {jQuery}
         */
        $item : false,

        /**
         * @property {jQuery}
         */
        _$imgContainer : false,

        /**
         * @property {jQuery}
         */
        _$futureNotice : false,

        /**
         * @property {jQuery}
         */
        _$sticky : false,

        /**
         * @property {Boolean}
         */
        _isSlidingForm : false,

        /**
         * @property {Function|Boolean}
         */
        _formSlidingCallback : false,

        /**
         * Setup for article editor
         */
        init : function() {
            this._$preview = $('#arlima-preview');
            this._$form = $('#arlima-edit-article-form');
            this._$imgContainer = $('#arlima-article-image');
            this._$futureNotice = $('#future-notice');
            this._$sticky = $('#sticky-interval');
            this._$blocker = $('<div></div>');
            this._$blocker
                .css({
                    height: 0,
                    width: 0
                })
                .appendTo('body')
                .addClass('arlima-editor-blocker');
        },

        /**
         * @return {jQuery}
         * @protected
         */
        _getFormHeader : function() {
            return this._$form.parent().parent().prev().prev();
        },

        /**
         * Will populate the data object belonging to currently edited list item with
         * information available in the form and then update the article preview. Changes
         * being made in the form should result in this function getting called
         */
        updateArticle : function(toggleSaveState) {
            if(toggleSaveState === undefined)
                toggleSaveState = true;

            if(toggleSaveState) {
                // todo: make sure that we really have made changes....
                this.currentlyEditedList.toggleUnsavedState(true);
            }

            var formData = this._$form.serializeObject();
            formData.options = {};
            $.each(formData, function(key, value) {
                if(key.substr(0, 8) == 'options-') {
                    formData.options[key.substr(8)] = value;
                    delete formData[key];
                }
            });

            formData.text = $.tinyMCEContent();
            formData.image_options = $('#arlima-article-image-options').data('image_options');

            var articleData = this.$item.data('article');
            $.extend(articleData, formData);
            this.$item.data('article', articleData);

            ArlimaList.applyItemPresentation(this.$item);

            if( isFutureDate(articleData.publish_date) ) {
                this._$futureNotice.show();
            }
            else {
                this._$futureNotice.hide();
            }

            if(articleData.options && articleData.options.sticky) {
                $('.sticky-interval-fancybox').attr('title', ArlimaJS.lang.sticky+' ('+articleData.options.sticky_interval+')');
            }

            $('.arlima-listitem-title:first', this.$item).html( ArlimaList.getArticleTitleHTML(articleData) );

            this.updateArticleStreamerPreview();
            this.updatePreview();

            if(articleData.options && articleData.options.sticky) {
                this._$sticky.show();
                var $interval = this._$sticky.find('input');
                if($.trim($interval.val()) == '')
                    $interval.val('*:*');
            }
            else {
                this._$sticky.hide();
            }

            Manager.triggerEvent('articleUpdate');
        },

        /**
         * Returns the name of the template used by currently edited article. This will either
         * be a template chosen for this specific article or the default template of the list
         * that the currently edited article belongs to
         * @return string
         */
        articleTemplate : function(articleData) {
            // Template changed by format value
            var previewTemplate = $('.arlima-list-previewtemplate', this.currentlyEditedList.jQuery).val();
            var customTemplate = articleData.options ? articleData.options.template : undefined;
            if( customTemplate !== undefined && customTemplate ) {
                if( ArlimaTemplateLoader.templates[customTemplate] === undefined ) {
                    log('Use of unknown custom template "'+customTemplate+'"', 'warn');
                }
                else {
                    previewTemplate = customTemplate;
                }
            }
            return previewTemplate;
        },

        /**
         * @return {String}
         */
        currentArticleTemplate : function() {
            return this.articleTemplate( this.$item.data('article') );
        },

        /**
        * Updates article preview, and preview of possible child articles
        */
        updatePreview : function() {

            function _buildPreviewTeaser($container, article, isChildArticle, extraClasses, isChildSplit) {
                if(!ArlimaTemplateLoader.finishedLoading) {
                    // templates not yet loaded
                    setTimeout(function() {
                        _buildPreviewTeaser($container, article, templatename, isChildArticle, extraClasses);
                    }, 500);
                    return;
                }

                // construct the same type of object created on the backend when parsing the template
                var templateArgs = {
                    container : {
                        id : 'teaser-' + article.id,
                        'class' : 'arlima teaser ' + (extraClasses ? extraClasses:'')
                    },
                    article : {
                        html_text : article.text,
                        title : article.title
                    },
                    streamer : false,
                    image : false,
                    related : false,
                    is_child : isChildArticle,
                    is_child_split : isChildSplit === true,
                    sub_articles : false,
                    format : false
                };

                // title
                if( article.title != '') {
                    var title = article.title.replace('__', '<br />');
                    if( article.options && article.options.pre_title )
                        title = '<span class="arlima-pre-title">' + article.options.pre_title + '</span> ' + title;

                    var el = ArticleEditor.currentlyEditedList.titleElement || 'h2';
                    templateArgs.article.html_title = '<'+el+' style="font-size:'+article.title_fontsize+'px">'+title+'</'+el+'>';
                }


                if( article.options ) {

                    // Format class
                    if( article.options.format ) {
                        templateArgs.container['class'] += ' '+article.options.format;
                    }

                    // Streamer
                    if( article.options.streamer ) {
                        templateArgs.streamer = {
                            type : article.options.streamer_type,
                            content : article.options.streamer_type == 'image' ? '<img src="' + article.options.streamer_image + '" />':article.options.streamer_content,
                            style :'background: #'+article.options.streamer_color // bg color in here
                        };

                        if(article.options.streamer_type == 'extra') {
                            templateArgs.streamer.style = '';
                            templateArgs.streamer.content = 'EXTRA';
                        }
                        else if(article.options.streamer_type == 'image') {
                            templateArgs.streamer.style = '';
                        }
                    }
                }

                // image
                if( article.image_options ) {
                    if( article.image_options.html ) {
                        templateArgs.image = {
                            src : $(unescape(article.image_options.html)).attr('src'),
                            image_class : article.image_options.alignment,
                            image_size : article.image_options.size
                        };
                        templateArgs.container['class'] += ' img-'+article.image_options.size;
                    }
                }

                // children
                if(article.children) {
                    var numChildren = article.children.length;
                    if( numChildren > 0 ) {
                        var $childContainer = $('<div />');

                        $childContainer
                            .addClass('teaser-children children-' + numChildren)
                            .appendTo($container);

                        $.each(article.children, function(i, childArticle) {
                            //epic variable name
                            var $childTeaser = $('<div />');
                            var extraClasses = i % 2 == 0 ? 'first':'last';

                            if( numChildren > 1 ) {
                                extraClasses += ' teaser-split';
                            }

                            // render child article
                            _buildPreviewTeaser(
                                $childTeaser,
                                childArticle,
                                true,
                                extraClasses,
                                numChildren > 1
                            );

                            // append child article to document
                            $childContainer.append( $childTeaser.html() );
                        });

                        templateArgs.sub_articles = $childContainer.html();
                    }
                }

                var templateName = ArticleEditor.articleTemplate(article);

                var tmpl = ArlimaTemplateLoader.templates[templateName];
                if(typeof tmpl == 'undefined')   {
                    tmpl = ArlimaTemplateLoader.templates['article'];
                    log('Trying to use template "'+templateName+'" but it does not exist, now using article.tmpl instead', 'warn');
                }

                // remove links
                tmpl = tmpl.replace(/href="([a-zA-Z\.\{+\}\$]+)"/g, 'href="Javascript:void(0)"');

                // Don't let jquery-tmpl insert image sources, it will generate 404
                tmpl = tmpl.replace(/{{html image.src}}/g, templateArgs.image.src ? templateArgs.image.src:'');

                var tmplHTML = $('<div>'+tmpl+'</div>').tmpl( templateArgs );

                $container
                    .empty()
                    .append( tmplHTML );
            }

            var article = this.serializeArticle(this.$item, true);
            if(parseInt(article.parent, 10) > -1) {
                article = this.serializeArticle(this.$item.parent().parent(), true);
            }
            // TODO: this function gets called twice when previewing an article!!

            _buildPreviewTeaser(this._$preview, article, false);

            var previewPageWidth = $('.arlima-list-previewpage-width', Manager.getFocusedList().jQuery).val();
            if( previewPageWidth ) {
                ArticleEditor._$preview.children().eq(0).css('width', previewPageWidth+'px');
            }
        },

        /**
         * @param {jQuery} $listItem
         * @param {Boolean} sanitizeEntryWord - Optional
         * @return {Object}
         */
        serializeArticle : function($listItem, sanitizeEntryWord) {
            if(sanitizeEntryWord === undefined)
                sanitizeEntryWord = false;

            var article = $listItem.data('article');
            if(typeof article == 'undefined')
                return {};

            if(!article.title_fontsize)
                article.title_fontsize = 24;

            article.children = [];

            if( sanitizeEntryWord ) {
                article.text = article.text.replace(new RegExp('(<span)(.*class=\".*teaser-entryword.*\")>(.*)(<\/span>)','g'), '<span class="teaser-entryword">$3</span>');
            }

            if($listItem.has('ul')) {
                var self = this;
                $('ul li', $listItem).each(function() {
                    article.children.push(self.serializeArticle($(this), sanitizeEntryWord));
                });
            }
            return article;
        },

        /**
         * @param {jQuery} $listiItem
         * @param {ArlimaList|Object} list
         */
        edit : function($listiItem, list) {

            Manager.setFocusedList(list);
            var self = this;
            var doShowArticlePreview = this.isShowingPreview();
            this.clear();
            this.currentlyEditedList = list;
            this.$item = $listiItem;
            
            // change element classes
            var $listParent = list.jQuery.parent();
            $listParent.find('.edited').removeClass('edited');
            $listParent.find('.active').removeClass('active');
            list.jQuery.addClass('active');
            $listiItem.addClass('edited');

            // Show form if hidden
            var $formContainer = this._$form.parent();
            if( !$formContainer.is(':visible') ) {
                this._isSlidingForm = true;
                $formContainer.slideDown('100', function() {
                    self._isSlidingForm = false;
                    if(typeof self._formSlidingCallback == 'function') {
                        self._formSlidingCallback();
                        self._formSlidingCallback = null;
                    }
                });
            }

            // Font size
            var article = this.$item.data('article');
            if(!article.title_fontsize)
                article.title_fontsize = 24;

            // Post connection
            if(article.post_id == 0)
                article.post_id = null;
            if(article.post_id) {
                $('#tinyMCE-add_media', this._$form).attr('href', 'media-upload.php?post_id=' + article.post_id + '&type=image&TB_iframe=1&send=true');
                $('#arlima-article-connected-post', this._$form).html('<a href="post.php?post=' + article.post_id + '&action=edit" target="_blank">' + article.post_id + '</a>');
            }else{
                $('#tinyMCE-add_media', this._$form).attr('href', 'media-upload.php?type=image&TB_iframe=1&send=true');
                $('#arlima-article-connected-post-change').hide();
                $('#arlima-article-post_id').show();
            }

            // Add title and body text
            $.tinyMCEContent(article.text);
            $('#arlima-edit-article-title-fontsize-slider').slider( "value", article.title_fontsize );

            // Store article data in form
            $.each(article, function(key, value) {
                $("[name='" + key + "']", self._$form).not(':radio').val(value);
            });

            // Disable admin lock
            if( !ArlimaJS.is_admin ) {
                var $lockInput = $("[name='options-admin_lock']", this._$form);

                $lockInput.on('click', function(e) {
                    e.preventDefault();
                    return false;
                });

                $lockInput
                    .parent()
                    .addClass('not_allowed')
                    .attr('title', ArlimaJS.lang.admin_only);

                $lockInput.parent().find('label').on('click', function(e) {
                    e.preventDefault();
                    return false;
                });
            }

            // Disable formats
            var tmplName = this.articleTemplate(article);
            this.toggleAvailableFormats( tmplName );
            this.toggleEditorFeatures( tmplName );

            // Hide default template from template list
            var defaultTemplate = $('.arlima-list-previewtemplate', this.currentlyEditedList.jQuery).val();
            var $tmplOptions = $('#arlima-edit-article-options-template option');
            $tmplOptions.show();
            $tmplOptions.each(function() {
                if(this.value == defaultTemplate) {
                    $(this).hide();
                    return false;
                }
                return true;
            });


            if(article.options) {

                $.each(article.options, function(key, value) {
                    $("[name='options-" + key + "']", self._$form).val(value);
                });

                if(article.options.streamer)
                    $("[name='options-streamer']", this._$form).prop('checked', true).parent().addClass('checked');
                if(article.options.sticky)
                    $("[name='options-sticky']", this._$form).prop('checked', true).parent().addClass('checked');
                if(article.options.admin_lock)
                    $("[name='options-admin_lock']", this._$form).prop('checked', true).parent().addClass('checked');

                if(article.options.streamer_color)
                    $('#arlima-edit-article-options-streamer-text div', this._$form).css('background', '#' + article.options.streamer_color);
                else
                    $('#arlima-edit-article-options-streamer-text div', this._$form).css('background', '#000');

                if(article.options.hiderelated) {
                    $("[name='options-hiderelated']", this._$form).prop('checked', true);
                }
            }

            this.updateArticleImage(article.image_options, false);
            this.updateArticle(false);

            if(!this.currentlyEditedList.isImported) {
                this.toggleEditorBlocker(false);
                this.updateArticleStreamerPreview();
                this.updatePreview();

                // Lock down article
                if( !ArlimaJS.is_admin && article.options && article.options.admin_lock) {
                    this.toggleEditorBlocker(true, ArlimaJS.lang.admin_lock);
                }

                if(doShowArticlePreview)
                    this.togglePreview(true);
            }
            else {
                this.toggleEditorBlocker(true);
                if(article.image_options.url) {
                    // It takes some time before image is loaded
                    ArticleEditor._$imgContainer.find('img').one('load', function() {
                        setTimeout(function() {
                            self.toggleEditorBlocker(true);
                        }, 100);
                    });
                }

            }
        },

        toggleAvailableFormats : function(articleTemplate) {
            if( articleTemplate == '' )
                articleTemplate = this.DEFAULT_TMPL;

            var $formatOptions = $('#arlima-edit-article-options-format option');
            $formatOptions.removeAttr('disabled');
            $formatOptions.each(function() {
                var $opt = $(this);
                var tmpls = $opt.attr('data-arlima-template');
                if($opt.val() != '' && tmpls && tmpls.indexOf('['+articleTemplate+']') == -1) {
                    $opt.attr('disabled', 'disabled');
                    if( $opt.is(':selected') ) {
                        $formatOptions.get(0).selected = true;
                    }
                }
            });
        },

        /**
         */
        updateArticleStreamerPreview : function() {
            var type = $('#arlima-edit-article-options-streamer-type').val();
            if($('#arlima-edit-article-options-streamer').is(':checked')) {
                if( $("[name='options-streamer_image']").val() != '' ) {
                    $('#arlima-edit-article-options-streamer-image-link').html('<img src="' + $("[name='options-streamer_image']").val() + '" width="170" style="vertical-align:middle;" />');
                }else{
                    $('#arlima-edit-article-options-streamer-image-link').html(ArlimaJS.lang.chooseImage);
                }
                $('#arlima-edit-article-options-streamer-content').show(0);
                $('.arlima-edit-article-options-streamer-choice').not('#arlima-edit-article-options-streamer-' + type).hide(0);
                $('#arlima-edit-article-options-streamer-' + type).show(0);
            }else{
                $('#arlima-edit-article-options-streamer-content').hide(0);
            }
        },

        /**
         * Turns on and off features in the article editor depending on what
         * is supported by curent jquery template
         * @param {String} templateName
         */
        toggleEditorFeatures : function(templateName) {
            if( templateName == '' )
                templateName = $('.arlima-list-previewtemplate', this.currentlyEditedList.jQuery).val();

            var tmpl = ArlimaTemplateLoader.templates[templateName];
            if( tmpl !== undefined ) {

                // Streamer
                var $streamerButton = $('.arlima-streamer');
                if( tmpl.indexOf('${streamer.') > -1 ) {
                    $streamerButton.show();
                }
                else {
                    var $streamerInput = $streamerButton.find('input');
                    if($streamerInput.eq(0).is(':checked')) {
                        $streamerInput[0].click();
                    }
                    $streamerButton.hide();
                }

                // TinyMCE
                if( tmpl.indexOf('article.html_text') > -1 ) {
                    $('#wp-tinyMCE-wrap').show();
                }
                else {
                    $('#wp-tinyMCE-wrap').hide();
                }

                // Post connection and link
                if( tmpl.indexOf('${article.url}') > -1 || tmpl.indexOf('{html article.html_title}') > -1 ) {
                    $('#arlima-article-wp-connection').show();
                }
                else {
                    $('#arlima-article-wp-connection').hide();
                }
            }
        },

        /**
         * @param {Object} args
         * @param {Boolean} [updateArticle] - Optional, defaults to true
         */
        updateArticleImage : function(args, updateArticle) {
            var $size = $('#arlima-article-image-size');
            var $alignment = $('#arlima-article-image-alignment input');
            var $attachId = $('#arlima-article-image-attach_id');
            var $updated = $('#arlima-article-image-updated');
            var $connected = $('#arlima-article-image-connected_to_post_thumbnail');

            if(args) {
                if(args.html)
                    this._$imgContainer.html( unescape( args.html ) ).removeClass('empty');
                if(args.alignment)
                    $alignment.filter('[value=' + args.alignment +  ']').prop('checked', true);
                if(args.size)
                    $size.val( args.size );
                if(args.attach_id)
                    $attachId.val(args.attach_id);
                if(args.updated)
                    $updated.val(args.updated);
                if(args.connected)
                    $connected.val(args.connected);
            }

            if($size.val() == 'full') {
                $alignment.filter('[value=aligncenter]').prop('checked', true);
                $alignment.parent().hide();
            }
            else {
                $alignment.parent().show();
                if( $alignment.filter(':checked').val() == 'aligncenter' ) {
                    $alignment.filter('[value=alignleft]').prop('checked', true);
                }
            }

            var $disconnect = $('#arlima-article-image-disconnect');
            if($connected.val() == 'true') {
                $disconnect.show();
            }
            else {
                $disconnect.hide();
            }

            var $img = this._$imgContainer.find('img');
            var imgOptions = {};
            if( $img.length > 0 ) {
                $img.removeAttr('width');
                $img.removeAttr('height');
                imgOptions = this.createArlimaArticleImageObject($($img).parent().html(), $alignment.filter(':checked').val(), $size.val(), $attachId.val(), $updated.val(), $connected.val());
            }

            var $imgOptionsElemnt = $('#arlima-article-image-options');
            $imgOptionsElemnt.data('image_options', imgOptions);

            if( imgOptions.html ) {
                $imgOptionsElemnt.show();
                $('#arlima-article-image-links .hide-if-no-image').show();
                if(!imgOptions.attach_id)
                    $('#arlima-article-image-scissors-popup').parent('li').hide();
            } else {
                $imgOptionsElemnt.hide();
                $('#arlima-article-image').addClass('empty');
                $('#arlima-article-image-links .hide-if-no-image').hide();
            }

            if(typeof updateArticle == 'undefined' || updateArticle === true)
                this.updateArticle();
        },

        /**
         * Function that can create image object used by backend
         */
        createArlimaArticleImageObject : function(html, align, size, attachId, updated, connected) {
            var $img = $(html);
            return {
                html: escape( html ),
                url : $img.attr('src'),
                alignment : align,
                size : size,
                attach_id : attachId,
                updated : updated,
                connected : connected
            };
        },

        /**
         * @param {Boolean} updateArticle - Optional, defaults to true
         */
        removeArticleImage : function(updateArticle) {
            $('#arlima-article-image').html('').addClass('empty');
            $('#arlima-article-image-options')
                    .data('image_options', {})
                    .hide()
                    .find('.hide-if-no-image')
                        .hide();

            if(typeof updateArticle == 'undefined' || updateArticle === true)
                this.updateArticle();
        },

        /**
         * Tells whether or not we're currently editing an article
         * belonging to the list with given id
         * @param {Number|ArlimaList} id
         * @return {Boolean}
         */
        isEditingList : function(id) {
            if( !$.isNumeric(id) )
                id = id.id;
            return this.currentlyEditedList && this.currentlyEditedList.id == id;
        },

        /**
         * Show or hide article preview
         * @param {Boolean} toggle
         */
        togglePreview : function(toggle) {
            if(this.currentlyEditedList && !this.currentlyEditedList.isImported) {
                if( toggle === undefined) {
                    toggle = !this.isShowingPreview();
                }
                if(toggle) {
                    this._$preview.show();
                }
                else {
                    this._$preview.hide();
                }
            }
            else {
                log('Trying to toggle preview but no article is edited', 'warn');
            }
        },

        /**
         * Tells whether or not article preview is visible
         * @return {Boolean}
         */
        isShowingPreview : function() {
            return this._$preview.is(':visible');
        },

        /**
         * Add or remove editor blocker which makes it impossible to edit
         * the article form
         * @param {Boolean} toggle
         * @param {String} [msg]
         */
        toggleEditorBlocker : function(toggle, msg) {
            if(typeof toggle == 'undefined') {
                toggle = !this._$blocker.is(':visible');
            }
            
            if(!toggle) {
                this._$blocker.hide();
                this._$blocker.find('.block-msg').remove();
            }
            else {
                // We need to look at all offset variables since the window might have been resized
                // which in turn changes the offset variables
                var _self = this;
                var $formContainer = _self._$form.parent().parent();
                var $formContainerHeader = this._getFormHeader();
                var _showBlocker = function() {
                    var formHeaderOffset = $formContainerHeader.offset();
                    var containerWidth = $formContainerHeader.outerWidth();
                    _self._$blocker.css({
                        height : ($formContainer.outerHeight() + $formContainerHeader.outerHeight()) + 'px',
                        width : containerWidth + 'px',
                        top :  formHeaderOffset.top +'px',
                        left : formHeaderOffset.left +'px'
                    });
                    _self._$blocker.show();

                    // Add block message
                    if(msg) {
                        var $blockMess = _self._$blocker.find('.block-msg');
                        if($blockMess.length == 0) {
                            $blockMess = $('<div></div>');
                            $blockMess
                                .addClass('block-msg')
                                .appendTo(_self._$blocker);
                        }
                        $blockMess.text(msg);
                    }
                };
                if(this._isSlidingForm) {
                    _self._formSlidingCallback = _showBlocker;
                }
                else {
                    _showBlocker();
                }
            }
        },

        /**
         * Hide article edit form
         */
        hideForm : function() {
            this._$form.parent().parent().find('.handlediv').trigger('click');
        },

        /**
         * Remove all data from article editor and hide article preview
         */
        clear : function() {
            if(this.currentlyEditedList)
                this.togglePreview(false);
            this.$item = false;
            this.currentlyEditedList = false;
            this.toggleEditorBlocker(false);
            $.tinyMCEContent('');
            $(':input', this._$form).not(':button, :submit, :radio, :checkbox').val('');
            $(':input', this._$form).prop('checked', false).prop('selected', false);
            $('.arlima-button').removeClass('checked');
            $('#arlima-article-image').html('');
            $('#arlima-article-image-options').removeData('image_options').hide();
            $('#arlima-article-connected-post').html('');
            $('#arlima-article-connected-post-change').show();
            $('#arlima-article-post_id').hide();
            $('#arlima-edit-article-options-streamer-content').hide();
        }
    };



    /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * @property {Object}
     */
    var Manager = {

        /**
         * @property {ArlimaList|Object}
         */
        _focusedList : false,

        /**
         * @property {ArlimaList[]}
         */
        _lists : {},

        /**
         * @property {jQuery}
         */
        _$element : null,

        /**
         * @property {window}
         */
        previewWindow : false,

        /**
         * @property {Object}
         */
        _events : {},

        /**
         * @param containerID
         */
        init : function(containerID) {
            this._$element = $(containerID);
        },

        /**
         * @param {String} evt either 'listLoaded', 'articleUpdate', 'articleDropped'
         * @param {Function} func
         */
        addEventListener : function(evt, func) {
            this.removeEventListener(evt, func);
            if( this._events[evt] === undefined ) {
                this._events[evt] = [];
            }

            this._events[evt].push(func);
        },

        /**
         * @param {String} evt
         * @param {Function} func
         * @return {Boolean}
         */
        removeEventListener : function(evt, func) {
            if( this._events[evt] !== undefined ) {
                for(var i=0; i < this._events[evt].length; i++) {
                    if(this._events[evt][i] == func) {
                        this._events[evt].splice(i, 1);
                        return true;
                    }
                }
            }
            return false;
        },

        /**
         * @param {String} evt
         */
        triggerEvent : function(evt) {
            var functions = this._events[evt];
            if($.isArray(functions)) {
                for(var i=0; i < functions.length; i++) {
                    functions[i]();
                }
            }
        },

        /**
         * Load custom templates from backend and dispaly
         * them on page
         */
        loadCustomTemplates : function() {
            Backend.loadCustomTemplateData(function(json) {
                if(json) {
                    var $div = $('#arlima-templates');
                    $div.html(json.html);

                    $('.dragger', $div)
                        .each( function (i, item) {
                            ArlimaList.prepareArticleForListTransactions($(item), json.articles[i]);
                        })
                        .draggable({
                            helper:'clone',
                            sender:'postlist',
                            handle:'.handle',
                            connectToSortable:'.arlima-list',
                            revert:'invalid'
                        });
                }
            });
        },

        /**
         * @param {Function} callback
         */
        iterateLists : function(callback) {
            for(var x in this._lists) {
                if(this._lists.hasOwnProperty(x)) {
                    callback( this._lists[x] );
                }
            }
        },

        /**
         * @return {ArlimaList|Object}
         */
        getFocusedList : function() {
            return this._focusedList;
        },

        /**
         * Add list with given id to the page
         * @param {Number} id - Id of list
         * @param {Object} position - Optional, css properties for the position of the list
         * @param {Function} callback - Optional, will be called when list is added to page
         */
        addList : function(id, position, callback) {
            if(this.hasList(id)) {
                this._lists[id].jQuery.effect("shake", { times:4, distance: 10 }, 50);
            }
            else {
                var self = this;
                Backend.loadListData(id, '', function(json) {
                    if(json && json.exists) { // it may have been removed
                        var list = ArlimaList.create(id, json, self._$element, position);
                        self._lists[id] = list;
                        list.jQuery.find('.arlima-list').hide().slideDown('fast', function() {
                            list.jQuery.trigger('init-list-container');
                        });
                    }

                    if( typeof callback == 'function')
                        callback();
                });
            }
        },

        /**
         * @return {Array|ArlimaList[]}
         */
        getUnsavedLists : function() {
            var lists = [];
            this.iterateLists(function(list) {
                if(list.isUnsaved)
                    lists.push(list);
            });
            return lists;
        },

        /**
         * @param {ArlimaList|Object} list
         * @param {Number} version - Optional, makes it possible to load a certain version of this list. The latest version will be loaded if left empty
         */
        reloadList : function(list, version) {

            if(ArticleEditor.isEditingList(list)) {
                ArticleEditor.clear();
                ArticleEditor.hideForm();
            }

            list.toggleUnsavedState(false);
            list.toggleAjaxLoader(true);
            list.jQuery.find('.arlima-list').fadeOut('fast', function() {
                Backend.loadListData(list.id, version, function(json) {
                    list.toggleAjaxLoader(false);
                    if(json) {
                        Manager.triggerEvent('listLoaded');
                        if(!json.exists) {
                            alert(ArlimaJS.lang.listRemoved);
                            Manager.removeList(list.id);
                        }
                        else {
                            list.fill(json.articles, false, true);
                            list.displayVersionInfo(json.version, json.versioninfo, json.versions);
                            list.jQuery.find('.arlima-list').fadeIn('fast');

                            if(!list.isImported) {
                                $('html').trigger('click'); // Hides version select list
                                list.toggleUnsavedState( json.versions[0] != json.version.id ); // we have changed back to latest version
                            }
                        }
                    }
                });
            });
        },

        /**
         * Tells whether or not this list exists on page
         * @param {Number} id
         * @return {Boolean}
         */
        hasList : function(id) {
            return typeof this._lists[id] != 'undefined';
        },

        /**
         * Removes list from page
         * @param {Number|ArlimaList} idOrList
         */
        removeList : function(idOrList) {
            var id = typeof idOrList == 'object' ? idOrList.id : idOrList.toString();
            if(this.hasList(id)) {
                if(ArticleEditor.isEditingList(id)) {
                    ArticleEditor.clear();
                    ArticleEditor.hideForm();
                }

                var self = this;
                this._lists[id].jQuery.slideUp('fast', function() {
                    if(this._focusedList && this._focusedList.id == id) {
                        ArticleEditor.clear();
                        this._focusedList = false;
                    }

                    delete self._lists[id];
                });
            }
            else {
                log('Trying to remove list that does not exist '+id, 'warn');
            }
        },

        /**
         * @param {Function} callback
         */
        loadSetup : function(callback) {
            $("#setup-loader").show();

            /**
             * Function called when all lists is loaded
             * @private
             */
            var _loadFinished = function() {
                $("#setup-loader").hide();
                if(typeof callback == 'function') {
                    callback();
                }
            };

            Backend.loadListSetup(function(json) {
                if(json) {
                    var numLists = json.length;
                    if(numLists == 0) {
                        _loadFinished();
                    }
                    else {
                        $.each(json, function(i, list) {
                            var pos = {top: parseInt(list.top), left: parseInt(list.left), height: parseInt(list.height), width: parseInt(list.width) };
                            Manager.addList(list.alid, pos, function() {
                                numLists--;
                                if(numLists == 0) {
                                    _loadFinished();
                                }
                            });
                        });
                    }
                }
                else{
                    _loadFinished();
                }
            });
        },

        /**
         * Saves current setup (list on page and their position), to be loaded every time the list
         * editor page is loaded
         */
        saveSetup : function() {
            var $loader = $('#save-setup-loader');
            $loader.show();
            var lists = [];
            this.iterateLists(function(list) {
                var pos = list.jQuery.position();
                lists.push({
                    alid : list.id,
                    top : pos.top,
                    left : pos.left,
                    width : list.jQuery.width(),
                    height : list.jQuery.height()
                });
            });

            Backend.saveListSetup(lists, function() {
                $loader.hide();
            });
        },

        /**
         * Saves a new version of the currently focused list.
         */
        saveFocusedList : function() {
            if( !this._focusedList ) {
                alert(ArlimaJS.lang.noList);
            }
            else if(this.getFocusedList().isUnsaved) {
                var activeList = this.getFocusedList();
                activeList.toggleAjaxLoader(true);
                var version = $('.arlima-version-id', activeList.jQuery).val();
                Backend.getLaterVersion(activeList.id, version, function(json) {
                    if(json) {
                        var saveList = true;
                        if(json.version) {
                            saveList = confirm(ArlimaJS.lang.laterVersion + ' \r\n ' + json.versioninfo + '\r\n' + ArlimaJS.lang.overWrite);
                        }
                        if($('.streamer-extra', activeList.jQuery).length > 1) {
                            saveList = confirm( ArlimaJS.lang.severalExtras + '\r\n' +  ArlimaJS.lang.overWrite);
                        }

                        if(!saveList) {
                            activeList.toggleAjaxLoader(false);
                        }
                        else {
                            var articles = [];
                            activeList.jQuery.find('.arlima-list').children().each( function (i, item) {
                                articles.push(ArticleEditor.serializeArticle( $(item) ));
                            });

                            Backend.saveList(activeList.id, articles, function(json) {
                                activeList.toggleUnsavedState(false);
                                activeList.toggleAjaxLoader(false);
                                activeList.displayVersionInfo(json.version, json.versioninfo, json.versions);
                            });
                        }
                    }
                });
            }
        },

        /**
         * @param {Number} offset
         * @return {Boolean}
         */
        searchWordpressPosts : function(offset) {
            $('#arlima-get-posts-loader').show();

            var data = {offset: offset};
            $('#arlima-post-search input, #arlima-post-search select').each(function() {
                if(this.name) {
                    if(this.type) {
                        if(this.type == 'checkbox' && $(this).is(':checked')) {
                            data[this.name] = this.value;
                        }
                        else if(this.type != 'checkbox') {
                            data[this.name] = this.value;
                        }
                    }
                    else {
                        data[this.name] = $(this).val();
                    }
                }
            });

            Backend.queryPosts(data, function(json) {
                $('#arlima-get-posts-loader').hide();
                if(json) {
                    var $div = $('#arlima-posts');
                    $div.html(json.html);

                    $('.dragger', $div).each( function (i, item) {
                        var $item = $(item);
                        var article = json.articles[i];
                        var content = article.content;
                        delete article.content;
                        ArlimaList.prepareArticleForListTransactions($item, article);
                        var args = {
                            position: {
                                my: 'right top',
                                at: 'center left',
                                viewport: jQuery(window)
                            },
                            style: { classes: 'ui-tooltip-shadow ui-tooltip-light ui-tooltip-480'}
                        };
                        args.content = '<h2 style="margin:0;">' + article.title + '</h2>' + content;
                        $('a', $item.parents('tr')).qtip(args);
                    });

                    $('.dragger', $div).draggable({
                        helper:'clone',
                        sender:'postlist',
                        handle:'.handle',
                        connectToSortable:'.arlima-list',
                        revert:'invalid'
                    });
                }
            });

            return false;
        },

        /**
         * Get the ArlimaList instance of which given item belongs to or list with given id
         * @param {jQuery|Number} i
         * @return {ArlimaList|Boolean}
         */
        getList : function(i) {
            var list;
            if($.isNumeric(i)) {
                list = this._lists[ i.toString() ];
            }
            else {
                list = this._lists[ i.closest('.arlima-list-container').attr('data-list-id') ];
            }
            if(list == undefined)
                return false;
            return list;
        },

        /**
         * @param {Number|ArlimaList} list
         */
        setFocusedList : function(list) {
            var id = $.isNumeric(list) ? list : list.id.toString();
            this._focusedList = this._lists[id];
        },

        /**
         * Saves a preview version of currently focused list and opens a new
         * window where the preview version can been seen in context
         */
        previewFocusedList : function() {
            if( !this._focusedList) {
                alert(ArlimaJS.lang.noList);
            }
            else {
                this.previewList(this._focusedList);
            }
        },

        /**
         * Saves a preview version of given list and opens a new
         * window where the preview version can been seen in context
         * @param {ArlimaList|Object} list
         */
        previewList : function(list) {
            var previewPage = $('.arlima-list-previewpage', list.jQuery).val();

            if( !previewPage ) {
                alert(ArlimaJS.lang.missingPreviewPage);
            }
            else if( !list.isUnsaved ) {
                alert(ArlimaJS.lang.isSaved);
            }
            else {
                list.toggleAjaxLoader(true);

                var _self = this;

                // We have to reopen window in chrome in order to set focus on the window
                if(_self.previewWindow) {
                    _self.previewWindow.close();
                }

                var isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
                var winSize = isChrome ? '':',width=10,height=10';
                _self.previewWindow = window.open(null, 'arlimaPreviewWindow', 'toolbar=1,scrollbars=1' + winSize );

                var _openPreviewWindow = function() {

                    list.toggleAjaxLoader(false);

                    if(_self.previewWindow) {

                        var url = previewPage == '/' || previewPage == ArlimaJS.baseurl+'/' ? ArlimaJS.baseurl+'/' : previewPage;
                        var binder = previewPage.indexOf('?') > -1 ? '&':'?';
                        url += previewPage == '/' ? (binder + ArlimaJS.preview_query_arg) : (binder + ArlimaJS.preview_query_arg);
                        url += '='+list.id;

                        _self.previewWindow.document.location = url;

                        if( !isChrome ) {
                            var $parentWin = $(window);
                            _self.previewWindow.resizeTo($parentWin.width(), $parentWin.height());
                        }

                        setTimeout(function() {
                            if(_self.previewWindow.document && _self.previewWindow.jQuery) {
                                _self.previewWindow.jQuery(_self.previewWindow.document).ready(function() {
                                    _self._addPreviewWindowListeners(_self.previewWindow.jQuery( _self.previewWindow.document ), list);
                                });
                            }
                        }, 1000);

                        _self.previewWindow.focus();
                    }
                    else {
                        alert('Your browser has blocked preview popup');
                    }

                };

                // list has unsaved changes so lets save them in a preview version
                // todo: is there really any reason why we store preview versions in db? suggestion would be to post
                // it do the next page, then remove all logic regarding preview from backend (cleans up a lot of code...)
                if(list.isUnsaved) {
                    var articles = {};
                    $(">li", list.jQuery.find('.arlima-list')).each( function (i, item) {
                        articles[i] = ArticleEditor.serializeArticle( $(item) );
                    });

                    Backend.savePreview(list.id, articles, function(json) {
                        if(json) {
                            _openPreviewWindow();
                        }
                    });
                }

                // We're previewing a list that isn't changed? okey just open window then...
                else {
                    _openPreviewWindow();
                }
            }
        },

        /**
         * Gives info to user that if he presses ctrl+s the currently previewed list
         * will be saved
         * @param {HTMLDocument} $winDoc
         * @param {ArlimaList} list
         * @private
         */
        _addPreviewWindowListeners : function($winDoc, list) {
            $winDoc.ready(function() {
                var $div = $('<div></div>');
                $div
                    .css({
                        position: 'fixed',
                        top: '30px',
                        left: '0',
                        width: '100%',
                        zIndex : '9999'
                    })
                    .appendTo($winDoc.find('body'));


                var ctrlKey = navigator.userAgent.indexOf('Mac') == -1 ? 'ctrl':'cmd';
                $('<div></div>')
                    .text(ctrlKey+' + s '+ArlimaJS.lang.savePreview+' "'+list.getDisplayName()+'"')
                    .css({
                        background: '#222',
                        backgroundColor: 'rgba(0,0,0, .85)',
                        fontSize : '13px',
                        color: '#FFF',
                        margin: '16px',
                        padding : '10px',
                        webkitborderRadius : '12px',
                        mozBorderRadius : '13px',
                        borderRadius : '12px',
                        fontWeight:'bold',
                        webkitBoxShadow : '0 0 7px #333',
                        mozBoxShadow : '0 0 7px #333',
                        boxShadow : '0 0 7px #333'
                    })
                    .appendTo($div);
            });

            var self = this;
            $winDoc.keydown(function(e) {
                var key = e.keyCode ? e.keyCode : e.which;
                if(key == 83 && (e.ctrlKey || e.metaKey)) {
                    self.saveFocusedList();
                    self.previewWindow.close();
                    window.focus();
                    e.preventDefault();
                    return false;
                }
            });
        },

        /**
         * Used to debug, gives you information about current state in console
         */
        dump : function() {
            var numLists = 0;
            this.iterateLists(function() { numLists++; });
            var message = '# Having '+numLists+" lists on page\n";
            var unsavedLists = this.getUnsavedLists();
            if(unsavedLists.length == 0) {
                message += "# Having 0 unsaved lists\n";
            }
            else {
                message += "# Having "+unsavedLists.length+" unsaved lists:\n";
                for(var i=0; i < unsavedLists.length; i++) {
                    var list = unsavedLists[i];
                    message += "   - "+list.getDisplayName()+"\n";
                }
            }
            
            if(!this.getFocusedList()) {
                message += "# Has no focused list\n# Has no article in the editor";
            }
            else {
                message += "# Has focus on list \""+this.getFocusedList().getDisplayName()+
                            "\", the list has "+(this.getFocusedList().isUnsaved ? 'changes':'no changes')+"\n";
                message += "# Article \""+ArticleEditor.$item.find('.arlima-listitem-title').text()+"\" is being edited";
            }

            log(message, "log");
        }
    };

    /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * The class that has knowledge about the HTML code
     * of an ArlimaList
     *
     * @class ArlimaList
     * @see ArlimaList.create()
     *
     * @param {Number} id
     * @param {jQuery} $element
     * @param {Boolean} imported
     * @param {String} titleElement
     * @constructor
     */
    function ArlimaList(id, $element, imported, titleElement) {
        this.jQuery = $element;
        this.id = id;
        this.isImported = imported;
        this.isUnsaved = false;
        this.titleElement = titleElement;
    }

    /**
     * Hide or show loading image
     * @param {Boolean} toggle
     */
    ArlimaList.prototype.toggleAjaxLoader = function(toggle) {
        var $loader = $('.ajax-loader', this.jQuery);
        if(toggle)
            $loader.show();
        else
            $loader.hide();
    };

    /**
     * Goes through all sticky items in the list and makes sure
     * they're in the correct place
     * @param {String} [insertFunc]
     */
    ArlimaList.prototype.rePositionStickyArticles = function(insertFunc) {
        if(insertFunc === undefined)
            insertFunc = 'insertBefore';
        var _self = this;
        this.jQuery.find('.sticky').each(function() {
            var $item = $(this);
            var currentPos = $item.prevAll().length;
            var stickyPos = $item.data('article').options.sticky_pos;
            if(stickyPos != currentPos) {
                var $allItems = _self.jQuery.find('.listitem:not(ul ul > *)');
                $item[insertFunc]( stickyPos > $allItems.length ? $allItems.eq( $allItems.length - 1 ) : $allItems.eq(stickyPos) );
                if( $item.prevAll().length != stickyPos ) {
                    // This happens when chosen insertFunc is incorrect... whats up with this?
                    stickyPos++;
                    $item[insertFunc]( stickyPos > $allItems.length ? $allItems.eq( $allItems.length - 1 ) : $allItems.eq(stickyPos) );
                }
            }
        });
    };

    /**
     * Fill the list with given articles
     * @param {Object[]} articles
     * @param {jQuery} $parentElement - Optional, html node that article elements will be appended to
     * @param {Boolean} applyListBehavior - Optional whether or not to add nestedSortable or dragger functionality to list, default is true
     */
    ArlimaList.prototype.fill = function(articles, $parentElement, applyListBehavior) {
        if(applyListBehavior == undefined)
            applyListBehavior = true;
        var self = this;
        var $itemContainer = $parentElement ? $parentElement : this.jQuery.find('.arlima-list');
        $itemContainer.html('');
        $.each(articles, function ( idx, article ) {

            var $listItem = $('<li />');
            $listItem
                .addClass('listitem')
                .html('<div><span class="arlima-listitem-title"></span>'+
                        '<img class="arlima-listitem-remove" alt="remove" src="' + ArlimaJS.imageurl + 'close-icon.png" /></div>');

            ArlimaList.bindArticleItemEvents($listItem);
            ArlimaList.prepareArticleForListTransactions($listItem, article);

            if(article.children.length > 0) {
                var $sublist = $('<ul />');
                $listItem.append($sublist);
                self.fill(article.children, $sublist, false);
            }

            $itemContainer.append($listItem);
        });

        if(applyListBehavior) {
            this.applyListBehavior();
        }
    };

    /**
     * @param {jQuery} $listItem
     */
    ArlimaList.bindArticleItemEvents = function($listItem) {
        // Event: remove item
        $listItem.find('.arlima-listitem-remove')
            .click(function(e) {
                var $item = $(this).parent().parent();
                Manager.getList($item).removeListItem($item);
                e.stopPropagation();
                return false;
            });

        // Event: show item in article editor
        $listItem.find('div')
            .click(function(e) {
                var $item = $(this).parent();
                var list = Manager.getList($item);
                ArticleEditor.edit($item, list);
                e.stopPropagation();
                return false;
            });
    };

    /**
     * Get the display name of this list
     * @return {String}
     */
    ArlimaList.prototype.getDisplayName = function() {
        return $.trim(this.jQuery.find('.arlima-list-header').text());
    };

    /**
     * Remove given item from list
     * @param {jQuery} $listItem
     */
    ArlimaList.prototype.removeListItem = function($listItem) {

        var articleData = $listItem.data('article');
        if(articleData.options && articleData.options.admin_lock && !ArlimaJS.is_admin) {
            alert(ArlimaJS.lang.admin_lock);
            return;
        }

        if( !confirm(ArlimaJS.lang.wantToRemove + $('span', $listItem).text() + ArlimaJS.lang.fromList) )
            return;

        this.toggleUnsavedState(true);
        Manager.setFocusedList(this.id);

        var _self = this;
        $listItem.fadeOut('fast', function(){
            var itemIsCurrentlyEdited = $listItem.hasClass('edited');
            $(this).remove();
            _self.rePositionStickyArticles('insertAfter');

            if( ArticleEditor.isEditingList(Manager.getFocusedList().id) ) {

                // Remove from form and hide form if we're looking att the article while removing it
                if(itemIsCurrentlyEdited) {
                    ArticleEditor.clear();
                    ArticleEditor.hideForm();
                }
                else {
                    // This may not be necessary, just in case we're removing a child article from an article that we have in preview
                    ArticleEditor.updatePreview();
                }
            }
        });
    };

    /**
     * Make version info available in the list element
     * @param {Object} versionData
     * @param {String} versionInfoText
     * @param {Array} versions
     */
    ArlimaList.prototype.displayVersionInfo = function(versionData, versionInfoText, versions) {
        if(this.isImported) {
            this.jQuery.find('.arlima-list-version-info').text(versionInfoText);
        }
        else {
            var $versionWrapper = this.jQuery.find('.arlima-list-version-info');
            $versionWrapper
                .html('v '+versionData.id)
                .attr('title', versionInfoText)
                .qtip({
                    position: {
                        my: 'right top',
                        at: 'center left',
                        viewport: jQuery(window)
                    },
                    style: qtipStyle
                });

            var $versionDropDown = this.jQuery.find('.arlima-list-version-ddl');
            $versionDropDown.html('');
            $.each(versions, function( idx, version ) {
                var $option = $('<option></option>', {
                                    value : version,
                                    selected : version == versionData.id
                                })
                                .text('v ' + version + ' ');
                $versionDropDown.append($option);
            });

            this.jQuery.find('.arlima-version-id').val(versionData.id);
        }
    };

    /**
     * @param {Boolean} toggle - true meaning the there's changes in the list that isn't saved
     */
    ArlimaList.prototype.toggleUnsavedState = function(toggle) {
        toggle = toggle === true; // typecast
        if(toggle != this.isUnsaved) { // state changed
            this.isUnsaved = toggle;
            if(this.isUnsaved) {
                this.jQuery.addClass('unsaved');
                this.jQuery.find('.arlima-save-list').show();
            }
            else {
                this.jQuery.removeClass('unsaved');
                this.jQuery.find('.arlima-save-list').hide();
            }
        }
    };

    /**
     * Helper method called on elements that will be able to move between lists
     * @param {jQuery} $item
     * @param {Object} articleData
     */
    ArlimaList.prepareArticleForListTransactions = function($item, articleData) {
        if(!$item.hasClass('dragger')) {
            $('.arlima-listitem-title', $item).html(ArlimaList.getArticleTitleHTML(articleData));
        }

        $item.data('article', articleData);
        ArlimaList.applyItemPresentation($item);
    };

    /**
     * Changes the presentation of the list item depending on the
     * data of the article it's referring to
     * @param {jQuery} $item
     * @return {Boolean}
     */
    ArlimaList.applyItemPresentation = function($item) {
        var data = $item.data('article');

        // FUTURE
        if(data.publish_date) {
            if( isFutureDate(data.publish_date) ) {
                $item.addClass('future');
                $item.attr('title', new Date(data.publish_date * 1000));
            }
            else {
                $item.removeAttr('title');
                $item.removeClass('future');
            }
        }
        else {
            $item.removeAttr('title');
            $item.removeClass('future');
        }

        // STICKY
        if(data.options && data.options.sticky) {
            $item.addClass('sticky');
            var title = $item.attr('title');
            if(title === undefined)
                title = '';
            $item.attr('title', title+' '+ArlimaJS.lang.sticky+' ('+data.options.sticky_interval+')');
        }
        else {
            $item.removeClass('sticky');
            if( !$item.hasClass('future') )
                $item.removeAttr('title');
        }

        return false;
    };


    /**
     * Helper method that creates the title of a list element
     * @param {Object} articleData
     * @return {String}
     */
    ArlimaList.getArticleTitleHTML = function(articleData) {
        var title = '';

        if(articleData.title)
            title = articleData.title.replace(/__/g, '');
        else if(articleData.text)
            title += '[' + articleData.text.replace(/(<.*?>)/ig,"").replace(/__/g, '').substring(0,30) +'...]';

        if(articleData.options && articleData.options.pre_title) {
            title = articleData.options.pre_title + ' ' + title;
        }

        if(articleData.options && articleData.options.streamer) {
            var color;
            switch (articleData.options.streamer_type) {
                case 'extra':
                    color = 'black';
                    break;
                case 'image':
                    color = 'blue';
                    break;
                default:
                    color = '#'+articleData.options.streamer_color;
                    break;
            }
            if( color == '#' )
                color = 'black';

            title = '<span class="arlima-streamer-indicator" style="background:'+color+'"></span> '+title ;
        }

        if(articleData.options && articleData.options.sticky)
            title = '<span class="sticky-icon">'+title+'</span>';

        return title;
    };

    /**
     * Factory method used to create ArlimaLists.
     * @param {Object} data
     * @param {jQuery} $parentContainer
     * @param {Object} position - Optional
     * @return {ArlimaList|Object}
     */
    ArlimaList.create = function(id, data, $parentContainer, position) {
        var $element = $('<div></div>');
        $element
            .addClass('arlima-list-container'+(data.is_imported ? ' imported':''))
            .attr('id', 'arlima-list-container-' + id)
            .attr('data-list-id', id)
            .html(data.html)
            .appendTo($parentContainer);

        if(!position) {
            position = {};
            var $lastElem = $parentContainer.find('div:last');
            if($lastElem.length > 0) {
                var pos = $lastElem.position();
                var top = 0;
                var left = 0;
                if( ( pos.left + $lastElem.width() + 300 ) <= $element.width() ) {
                    left = pos.left + $lastElem.width();
                    top = pos.top;
                }
                position = {
                    top : top +'px',
                    left : left +'px'
                };
            }
        }
        $element.css(position);

        //
        // List setup, sortable, draggable etc...
        //
        $element.bind('init-list-container', function() {

            var list = Manager._lists[$(this).attr('data-list-id')];

            list.jQuery.resizable({
                containment: 'parent'
            });

            list.jQuery.draggable({
                containment: 'parent',
                snap: true,
                handle: '.arlima-list-header'
            });

            list.applyListBehavior();
            list.jQuery.disableSelection();

            // Click on reload button
            $(".arlima-refresh-list", this).click( function(e) {
                var reloadList = true;
                if(list.isUnsaved) {
                    reloadList = confirm(ArlimaJS.lang.hasUnsavedChanges);
                }
                if(reloadList)
                    Manager.reloadList(list);

                e.preventDefault();
                return false;
            });

            // Remove list from page
            $(".arlima-list-container-remove", this).click( function(e) {
                e.preventDefault();
                var removeList = true;
                if(list.isUnsaved) {
                    removeList = confirm(ArlimaJS.lang.hasUnsavedChanges);
                }
                if(removeList) {
                    Manager.removeList(list.id);
                }
                
                e.preventDefault();
                return false;
            });

            // Add events for list that isn't imported
            if(!list.isImported) {

                // Click on save button
                $(".arlima-save-list", this).click( function(e) {
                    if(!list.isUnsaved)
                        return false;
                    Manager.setFocusedList(list.id);
                    Manager.saveFocusedList();
                    e.preventDefault();
                    return false;
                });

                // Click preview button
                $(".arlima-preview-list", this).click(function(e) {
                    Manager.previewList(list);
                    e.preventDefault();
                    return false;
                });

                // Change version
                $(".arlima-list-version-ddl", this).change( function() {
                    var loadVersion = true;
                    if(list.isUnsaved) {
                        loadVersion = confirm(ArlimaJS.lang.hasUnsavedChanges);
                    }
                    if(loadVersion) {
                        Manager.reloadList(list, $(this).val());
                    }
                });

                // toggle version list
                $('.arlima-list-version', this).click( function(e) {
                    $('.arlima-list-version-info', this).hide();
                    $('.arlima-list-version-select', this).show();
                    e.stopPropagation();
                    e.preventDefault();
                    return false;
                });
            }
        });

        var list = new ArlimaList(id, $element, data.is_imported, data.title_element);
        list.fill(data.articles, false, false);
        list.displayVersionInfo(data.version, data.versioninfo, data.versions);

        return list;
    };

    /**
     * Array looked at when list transaction stops, holding the id numbers
     * of the lists that was involved in the transaction
     * @property {Array}
     */
    ArlimaList.listsInolvedInTransaction = [];

    /**
     * Variable that is looked at when list transaction stops to tell whether
     * or not current transaction was with a copy of an article
     * @property {Boolean|Number}
     */
    ArlimaList.copyFromList = false;

    /**
     * Will apply jQuery.ui.nestedSortable() or jQuery.ui.draggable to the list,
     * depending on whether or not the list is imported
     */
    ArlimaList.prototype.applyListBehavior = function() {

        var _copyArticleData = function($newElement, $originalElement) {
            $newElement.data($.extend({}, $originalElement.data()) );

            // don't forget to copy all data for child articles as well
            var $children = $newElement.find('ul li');
            if($children.length > 0) {
                var $origChildren = $originalElement.find('ul li');
                for(var i = ($children.length-1); i >= 0; i--) {
                    $children.eq(i).data( $.extend({}, $origChildren.eq(i).data() ) );
                }
            }
        };

        if( !this.isImported ) {
            var self = this;
            var $ul = this.jQuery.find('ul:first');
            if(!$ul.hasClass('ui-sortable')) { // don't apply nestedSortable more than once
                $ul.nestedSortable({
                    items: 'li',
                    listType: 'ul',
                    maxLevels: 2,
                    opacity: .6,
                    tabSize: 30,
                    tolerance: 'pointer',
                    connectWith: ['.arlima-list:not(.imported)'],
                    distance: 15,
                    placeholder: 'arlima-listitem-placeholder',
                    forcePlaceholderSize: true,
                    toleranceElement: '> div',
                    start : function(e, ui) {
                        ArlimaList.copyFromList = false;
                        ArlimaList.listsInolvedInTransaction = [];
                        if(e.ctrlKey || e.metaKey) {
                            ArlimaList.copyFromList = parseInt( $(this).parent().parent().attr('data-list-id') );
                            if($.inArray(ArlimaList.copyFromList, ArlimaList.listsInolvedInTransaction) == -1) {
                                ArlimaList.listsInolvedInTransaction.push(ArlimaList.copyFromList);
                            }
                        }
                    },
                    helper: function(e, $li) {

                        if(e.ctrlKey || e.metaKey) {
                            var $helper = $($li.clone(true).insertAfter($li));
                            _copyArticleData($helper, $li);
                            if($helper.hasClass('edited'))
                                $helper.removeClass('edited');
                            $helper.effect("highlight", {}, 300);
                        }

                        return $li.clone();
                    },
                    receive: function(event, ui) {

                        var $draggedItem = $(ui.item);
                        var hasDragClass = $draggedItem.hasClass('dragger');
                        var hasUIDragClass = $draggedItem.hasClass('ui-draggable'); // coming from imported list
                        if(hasDragClass || hasUIDragClass) {
                            var itemClass = hasDragClass ? 'dragger':'ui-draggable';
						
                            var $newItem = $(this).find('.'+itemClass + ':first');
							_copyArticleData($newItem, $draggedItem);
                            var articleData = $newItem.data('article');
                            $newItem.removeClass( itemClass );

                            // Update item title and ad timestamp if article comes from search or is a template
                            if(hasDragClass) {
                                $('.arlima-listitem-title', $newItem).html(ArlimaList.getArticleTitleHTML(articleData));
                                $newItem.data('article', articleData);
                            }

                            // Add click events to item if it doesn't have them
                            var list = Manager.getList($draggedItem);
                            if(!list || list.isImported) {
                                ArlimaList.bindArticleItemEvents($newItem);
                            }

                            // Side load image from imported list
                            if(list && list.isImported && articleData.image_options.url && !articleData.image_options.attach_id) {
                                ArlimaList.uploadExternalImage(articleData.image_options.url, $newItem);
                            }

                            // Change article in editor if we are looking at this copy of this article
                            if( (!ArlimaList.copyFromList || hasUIDragClass) && $draggedItem[0] == ArticleEditor.$item[0] ) {
                                ArticleEditor.edit($newItem, self);
                            }
                        }
                    },
                    update: function(event, ui) {
                        var $item = $(ui.item);
                        var $itemParent = $item.parent().parent();
                        var articleData = $item.data('article');
                        if( $itemParent.hasClass('listitem') ) {
                            articleData.parent = $itemParent.prevAll().length;
                        }
                        else {
                            articleData.parent = -1;
                        }
                        $item.data('article', $.extend({}, articleData));
                        var listID = parseInt( $(this).parent().parent().attr('data-list-id') );
                        if($.inArray(listID, ArlimaList.listsInolvedInTransaction) == -1) {
                            ArlimaList.listsInolvedInTransaction.push(listID);
                        }
                        $item.effect("highlight", {}, 500);
                        ArlimaList.applyItemPresentation($item);
                    },
                    stop : function(e, ui) {

                        var $item = $(ui.item);
                        var articleData = $item.data('article');

                        // Trying to move sticky but is not admin
                        if( !ArlimaJS.is_admin && articleData.options &&
                            articleData.options.admin_lock &&
                            articleData.options.sticky) {
                            $(this).nestedSortable('cancel');
                            return;
                        }

                        Manager.triggerEvent('articleDropped');

                        // copy to the same list
                        if(ArlimaList.copyFromList && ArlimaList.listsInolvedInTransaction.length == 1) {
                            $item.data('article', $.extend({}, $item.data('article')));
                            var listId = ArlimaList.listsInolvedInTransaction[0];
                            Manager.getList(listId).toggleUnsavedState(true);
                            Manager.setFocusedList(listId);
                        }

                        // Copy from one list to another
                        else if(ArlimaList.copyFromList) {
                            $.each(ArlimaList.listsInolvedInTransaction, function(i, listId) {
                                if(listId != ArlimaList.copyFromList) {
                                    Manager.getList(listId).toggleUnsavedState(true);
                                    Manager.setFocusedList(listId);
                                }
                            });
                        }

                        // Move in lists
                        else {
                            // ArlimaList.listsInolvedInTransaction.length == 1 : move within one list
                            // ArlimaList.listsInolvedInTransaction.length == 2 : move from one list to another
                            $.each(ArlimaList.listsInolvedInTransaction, function(i, listId) {
                                Manager.getList(listId).toggleUnsavedState(true);
                                Manager.setFocusedList(listId);
                            });
                        }

                        // move sticky articles in place
                        if( !$item.hasClass('sticky') ) {
                            $.each(ArlimaList.listsInolvedInTransaction, function(i, listId) {
                                var list = Manager.getList(listId);
                                if(!list.isImported) {
                                    list.rePositionStickyArticles();
                                }
                            });
                        }
                        // Change sticky position
                        else {
                            articleData.options.sticky_pos = $item.prevAll().length;
                            if(ArticleEditor.$item[0] == $item[0]) {
                                $('#arlima-option-sticky-pos').val(articleData.options.sticky_pos);
                            }
                            $item.data('article', articleData);
                        }

                        ArlimaList.listsInolvedInTransaction.length = 0;
                    }
                });
            }
        }
        else {
            this.jQuery.find('li').draggable({
                sender:'importedlist',
                helper : 'clone',
                handle:'.handle',
                connectToSortable:'.arlima-list',
                revert:'invalid',
                zIndex: 40,
                start: function(e, ui) {
                    var $item = $(e.currentTarget);
                    ui.helper.width($item.width());
                }
            });
        }
    };

    /**
     * @param {String} url
     * @param {jQuery} $item
     */
    ArlimaList.uploadExternalImage = function(url, $item) {

        ArticleEditor._$imgContainer.addClass('ajax-loader-icon');

        Backend.plupload(url, '', function(json) {

            ArticleEditor._$imgContainer.removeClass('ajax-loader-icon');

            if(json) {
                if($item[0] == ArticleEditor.$item[0]) {
                    ArticleEditor.updateArticleImage({ html : json.html, size : 'full', attach_id : json.attach_id });
                }
                else {
                    var articleData = $item.data('article');
                    articleData.image_options = ArticleEditor.createArlimaArticleImageObject(json.html, 'aligncenter', 'full', json.attach_id, 0, '');
                    $item.data('article', articleData);
                    Manager.getList($item).toggleUnsavedState(true);
                }
            }
            else
                log('Unable to upload external image', 'error');
        });
    };

    /**
     * Simple debugger for Arlima, a wrapper for console
     * @param {String} mess
     * @param {String} method - Either warn or error
     */
    function log(mess, method) {
        if(method === undefined)
            method = 'log';
        if(typeof console != 'undefined' && typeof console[method] == 'function') {
            console[method](mess);
        }
    }

    /**
     * @param {Number|Object} ts
     * @return {Boolean}
     */
    function isFutureDate(ts) {
        if(!ts)
            return false;
        if( !$.isNumeric(ts) )
            ts = ts.publish_date;
        return ts && (ts*1000) > new Date().getTime();
    }

    /**
     * The style we use for our tooltips
     * @type {Object}
     */
    var qtipStyle = {
        name: 'dark',
        tip:true,
        padding : '1px 3px',
        fontSize: 11,
        background : '#111',
        border: {
            width: 2,
            radius: 5,
            color: '#111'
        }
    };

    // Make our objects and classes available in global scope
    return {
        Backend : Backend,
        ArticleEditor : ArticleEditor,
        Manager : Manager,
        List : ArlimaList,
        qtipStyle : qtipStyle
    };

})(jQuery, ArlimaJS, ArlimaTemplateLoader, window);