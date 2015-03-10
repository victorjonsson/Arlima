var ArlimaImageManager = (function($, window, ArlimaArticleForm, ArlimaTemplateLoader, ArlimaFormBlocker) {

    'use strict';

    var _this = {

        $elem : false,
        $imageWrapper : false,
        $sizeSelect : false,
        $alignButtons : false,
        $buttons : false,
        $selects : false,

        /**
         * @var {ArlimaArticle}
         */
        article : false,

        setup : function(article) {
            this.article = article;
            _setupForm();
        },

        removeImage : function() {
            this.article.data.image = {};
            ArlimaArticleForm.change('.image-attach', '');
            _setupForm();
        },

        setNewImage : function(url, attachment, connected, size, alignment) {
            size = size || _getDefaultImageSize();
            alignment = alignment || '';
            if( size == 'full' && alignment != '') {
                alignment = '';
            } else if( size != 'full' && alignment == '') {
                alignment = 'alignleft';
            }

             _this.article.data.image = {
                size : size,
                url : url,
                alignment : alignment,
                attachment : attachment,
                connected : connected ? 1:''
            };

            ArlimaArticleForm.change('.image-attach', attachment);
            setTimeout(_setupForm, 200); // cant rush it...
        },

        init : function($container) {
            this.$elem = $container;
            this.$imageWrapper = $container.find('.image');
            this.$buttons = $container.find('.button');
            this.$alignButtons = $container.find('.align-button');
            this.$sizeSelect = $container.find('select.img-size');

            var $attachFancyBox = $container.find('.attachments-fancybox');

            // Scissors button
            if( !window.ArlimaJS.hasScissors ) {
                this.$buttons.filter('.scissors').remove();
            } else {
                ArlimaScissors.init(this.$buttons.filter('.scissors'));
            }

            // Remove image button
            this.$buttons.filter('.remove').click(function() {
                _this.removeImage();
                return false;
            });

            // Browser media library
            this.$buttons.filter('.browse').click(function() {

                var postID = _this.article ? parseInt(_this.article.data.post, 10) : null;

                // If the media frame already exists, reopen it.
                if ( window.wpMediaModal ) {
                    window.wpMediaModal.uploader.uploader.param( 'post_id', postID );
                    window.wpMediaModal.open();
                    return;
                }else{
                    wp.media.model.settings.post.id = postID;
                }

                // Create the media frame.
                window.wpMediaModal = wp.media.frames.file_frame = wp.media({
                    title: window.ArlimaJS.lang.chooseImage,
                    button: {
                        text: window.ArlimaJS.insertImage
                    },
                    multiple: false  // Set to true to allow multiple files to be selected
                });

                // When an image is selected, run a callback.
                window.wpMediaModal.on('select', function() {
                    var attachment = window.wpMediaModal.state().get('selection').first().toJSON(),
                        connected = postID ? true:false;

                    if( !window.ArlimaUtils.isImagePath(attachment.url) ) {
                        alert(window.ArlimaJS.lang.onlyImages);
                    } else {

                        if( postID && !attachment.uploadedTo ) {
                            // Not connected via upload
                            ArlimaBackend.connectAttachmentToPost(postID, attachment.id);
                        }

                        _this.setNewImage(attachment.url, attachment.id, connected);
                    }
                });

                // Finally, open the modal
                window.wpMediaModal.open();

                return false;
            });

            // Adjust alignment when changing size
            _this.$sizeSelect.on('change', function() {
                _setAlignmentButtons( _this.$sizeSelect.val(), true);
            });

            // Changing alignment
            _this.$alignButtons.on('change', function() {
                ArlimaArticleForm.change('.data.img-align', $(this).val(), true);
            });

            // Disconnect button
            this.$buttons.filter('.disconnect').click(function() {
                $(this).hide();
                _this.article.data.image.connected = '';
                ArlimaBackend.duplicateImage(_this.article.data.image.attachment, function(json) {
                    if(!json.error) {
                        _this.setNewImage(json.attach_url, json.attach_id, false, _this.article.data.image.size, _this.article.data.image.alignment);
                    }
                });
                return false;
            });

            // Choose image that is connected to post
            this.$imageWrapper.click(function() {
                if( _this.article && parseInt(_this.article.data.post, 10) ) {
                    window.ArlimaBackend.getPostAttachments(_this.article.data.post, function(json) {
                        $attachFancyBox.html('');
                        $.each(json, function(idx, img) {
                            $('<div></div>')
                                .addClass('arlima-article-attachment')
                                .html(img.thumb)
                                .on('click', function() {
                                    _this.setNewImage(img.url[0], img.attachment, true);
                                    $('.fancybox-close').trigger('click');
                                })
                                .appendTo($attachFancyBox);
                        });

                        $.fancybox({
                            minHeight: 200,
                            href: "#arlima-article-attachments"
                        });
                    });
                }
                return false;
            });
        }

    };

    var _toggleImageDisplay = function(toggle) {
        if( toggle ) {
            var $img = $('<img src="'+_this.article.data.image.url+'" />');
            $img.load(function() {
                $(window).trigger('arlimaFormImageLoaded');
                _makeImageElemDroppable(this);
            });
            _this.$imageWrapper
                .html($img)
                .removeClass('no-image');

        } else {
            if( !_this.$imageWrapper.hasClass('no-image') ) {
                _this.$imageWrapper
                    .html('<i class="fa fa-camera-retro fa-5x"></i>')
                    .addClass('no-image');
            }
        }
    },

    _setAlignmentButtons = function(size, triggerChange) {
        if( !size )
            size = _this.article.data.image.size;
        if( triggerChange === undefined )
            triggerChange = false;

        var $checked = _this.$alignButtons.filter(':checked');

        if( size == 'full' ) {

            if( $checked.length != 0 )
                $checked[0].checked = false;

            ArlimaFormBlocker.toggleImageAlignBlocker(true);
            ArlimaArticleForm.change('.data.img-align', '', triggerChange);

        } else {
            var align = _this.article.data.image.alignment;
            if( align ) {
                _this.$alignButtons.filter('[value="'+align+'"]').get(0).checked = true;
            } else {
                _this.$alignButtons[0].checked = true;
                triggerChange = true;
            }

            ArlimaFormBlocker.toggleImageAlignBlocker(false);
            if( triggerChange ) {
                ArlimaArticleForm.change('.data.img-align', _this.$alignButtons.eq(0).val(), true);
            }
        }
    },

    _getDefaultImageSize = function() {
        var imageSizeSupport = ArlimaTemplateLoader.getTemplateSupport(_this.article).imageSize;
        if( imageSizeSupport ) {
            if( _this.article.isChild() && imageSizeSupport['children-size'] && imageSizeSupport['children-size'] != '*' ) {
                return $.trim(imageSizeSupport['children-size'].split(',')[0]);
            } else if( imageSizeSupport.size && imageSizeSupport.size != '*' ) {
                return $.trim(imageSizeSupport.size.split(',')[0]);
            }
        }
        return 'full';
    },

    _makeImageElemDroppable = function(img) {
        window.ArlimaImageUploader.createDropZone(img);
    },

    _setupForm = function() {
        try {

            if( _this.article.data.image && _this.article.data.image.url ) {
                var img = _this.article.data.image; // shorten code pls...

                window.ArlimaUtils.log('Setting up image form for '+_this.article.data.id);

                // toggle visibility
                _toggleImageDisplay(true);
                _this.$buttons.show();
                _this.$alignButtons.parent().show();
                _this.$sizeSelect.show();

                var imageSizeSupport = ArlimaTemplateLoader.getTemplateSupport(_this.article).imageSize,
                    sizes = false;

                if( imageSizeSupport ) {

                    if( _this.article.isChild() && imageSizeSupport['children-size'] && imageSizeSupport['children-size'] != '*' ) {
                        sizes = imageSizeSupport['children-size'].split(',');
                    } else if( !_this.article.isChild() && imageSizeSupport['size'] && imageSizeSupport['size'] != '*' ) {
                        sizes = imageSizeSupport.size.split(',');
                    }

                    if( sizes ) {
                        var currentSizeIsAvailable = false,
                            $sizeOptions = _this.$sizeSelect.find('option');

                        $sizeOptions.attr('disabled', 'disabled');

                        $.each(sizes, function(i, size) {
                            size = $.trim(size);
                            $sizeOptions.filter('[value="'+size+'"]').removeAttr('disabled');
                            if( size == img.size ) {
                                currentSizeIsAvailable = true;
                            }
                        });
                        if( !currentSizeIsAvailable ) {
                            img.size = _getDefaultImageSize();
                        }
                    } else {
                        _this.$sizeSelect.find('option').removeAttr('disabled');
                    }
                } else {
                    _this.$sizeSelect.find('option').removeAttr('disabled');
                }

                if( !img.connected ) {
                    _this.$buttons.filter('.disconnect').hide();
                }

                // Fix alignment if incorrect
                img.alignment = img.alignment || '';
                if( img.size == 'full' && img.alignment != '' )
                    img.alignment = '';
                else if( img.size != 'full' && img.alignment == '' )
                    img.alignment = 'alignleft';

                // Add data to form
                if( img.alignment ) {
                    _this.$alignButtons.filter('[value='+img.alignment+']')[0].checked = true;
                }
                ArlimaUtils.selectVal(_this.$sizeSelect, img.size, false);

                // Disable alignment options on full articles (need to be done twice)
                _setAlignmentButtons();

            } else {
                // Hide most of the stuff when there's no image
                _toggleImageDisplay(false);
                _this.$buttons.filter(':not(.browse)').hide();
                _this.$alignButtons.parent().hide();
                _this.$sizeSelect.hide();
            }

        } catch(e) {
            window.ArlimaUtils.log(e);
        }
    };

    return _this;

})(jQuery, window, ArlimaArticleForm, ArlimaTemplateLoader, ArlimaFormBlocker);