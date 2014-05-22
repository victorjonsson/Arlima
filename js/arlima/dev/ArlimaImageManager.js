var ArlimaImageManager = (function($, window, ArlimaArticleForm, ArlimaTemplateLoader) {

    'use strict';

    var _this = {

        $elem : false,
        $imageWrapper : false,
        $sizeSelect : false,
        $alignSelect : false,
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
                connected : connected ? true:false
            };

            ArlimaArticleForm.change('.image-attach', attachment);
            _setupForm();
        },

        init : function($container) {
            this.$elem = $container;
            this.$imageWrapper = $container.find('.image');
            this.$buttons = $container.find('.button');
            this.$alignSelect = $container.find('select.img-align');
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

                var post_id = _this.article ? _this.article.data.post : null;
                // If the media frame already exists, reopen it.
                if ( window.wpMediaModal ) {
                    window.wpMediaModal.uploader.uploader.param( 'post_id', post_id );
                    window.wpMediaModal.open();
                    return;
                }else{
                    wp.media.model.settings.post.id = post_id;
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
                    // We set multiple to false so only get one image from the uploader
                    var attachment = window.wpMediaModal.state().get('selection').first().toJSON();
                    _this.setNewImage(attachment.url, attachment.id);
                });

                // Finally, open the modal
                window.wpMediaModal.open();

                return false;
            });

            // Adjust alignment select when changing size
            _this.$sizeSelect.on('change', function() {
                _setSelectStates( _this.$sizeSelect.val() );
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

    _setSelectStates = function(size) {
        if( !size )
            size = _this.article.data.image.size;

        if( size == 'full' ) {
            if( _this.$alignSelect.val() != '' )
                ArlimaUtils.selectVal(_this.$alignSelect, '', true);

            _this.$alignSelect.attr('disabled', 'disabled');
            _this.$alignSelect.find('option[value=""]').attr('disabled', null);

        } else {
            if( _this.$alignSelect.val() == '' )
                ArlimaUtils.selectVal(_this.$alignSelect, 'alignleft', true);

            _this.$alignSelect.attr('disabled', null);
            _this.$alignSelect.find('option[value=""]').attr('disabled', 'disabled');
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

    _setupForm = function() {
        if( _this.article.data.image && _this.article.data.image.url ) {

            var img = _this.article.data.image; // shorten code pls...

            ArlimaUtils.log('Setting up image form for '+_this.article.data.id);

            // toggle visibility
            _toggleImageDisplay(true);
            _this.$buttons.show();
            _this.$alignSelect.show();
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

            // Fix alignment if incorrect
            img.alignment = img.alignment || '';
            if( img.size == 'full' && img.alignment != '' )
                img.alignment = '';
            else if( img.size != 'full' && img.alignment == '' )
                img.alignment = 'alignleft';

            // Add data to form
            ArlimaUtils.selectVal(_this.$alignSelect, img.alignment, false);
            ArlimaUtils.selectVal(_this.$sizeSelect, img.size, false);

            // Disable alignment options on full articles
            _setSelectStates();

            if( !img.connected ) {
                _this.$buttons.filter('.disconnect').hide();
            }


        } else {
            // Hide most of the stuff when there's no image
            _toggleImageDisplay(false);
            _this.$buttons.filter(':not(.browse)').hide();
            _this.$alignSelect.hide();
            _this.$sizeSelect.hide();
        }
    };

    return _this;

})(jQuery, window, ArlimaArticleForm, ArlimaTemplateLoader);