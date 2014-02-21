var ArlimaImageManager = (function($, window, ArlimaArticleForm) {

    'use strict';

    var _this = {

        $elem : false,
        $imageWrapper : false,
        $buttons : false,
        $selects : false,
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
            _this.article.data.image = {
                size : size || 'full',
                url : url,
                alignment : alignment || 'alignleft',
                attachment : attachment
            };
            if( connected ) {
                _this.article.data.image.connected = 1;
            }
            ArlimaArticleForm.change('.image-attach', attachment);
            _setupForm();
        },

        init : function($container) {
            this.$elem = $container;
            this.$imageWrapper = $container.find('.image');
            this.$buttons = $container.find('.button');
            this.$selects = $container.find('select');

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

                // If the media frame already exists, reopen it.
                if ( window.wpMediaModal ) {
                    window.wpMediaModal.open();
                    return;
                }

                // Create the media frame.
                window.wpMediaModal = wp.media.frames.file_frame = wp.media({
                    title: 'a title',
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

            // Disconnect button
            this.$buttons.filter('.disconnect').click(function() {
                $(this).hide();
                _this.article.data.image.connected = '';
                // manually trigger a change of the list (normally you would call ArlimaArticleForm.change())
                ArlimaArticleForm.$form.find('.image-attach').trigger('change');
                ArlimaBackend.duplicateImage(_this.article.data.image.attachment, function(json) { });
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

    _setupForm = function() {
        if( _this.article.data.image && _this.article.data.image.url ) {

            // toggle visibility
            _toggleImageDisplay(true);
            _this.$buttons.show();
            _this.$selects.show();

            // Add data to form
            ArlimaUtils.selectVal(_this.$selects.filter('.img-align'), _this.article.data.image.alignment, false);
            ArlimaUtils.selectVal(_this.$selects.filter('.img-size'), _this.article.data.image.size, false);

            if( !_this.article.data.image.connected ) {
                _this.$buttons.filter('.disconnect').hide();
            }

        } else {
            // Hide most of the stuff when there's no image
            _toggleImageDisplay(false);
            _this.$buttons.filter(':not(.browse)').hide();
            _this.$selects.hide();
        }
    };

    return _this;

})(jQuery, window, ArlimaArticleForm);