var ArlimaArticleSettingsMenu = (function($, ArlimaUtils) {

    var _this = {

        hasFocus : false,
        $button : false,
        $dropDown : false,
        defaultTemplate : false,

        templateFormatsMap : {},

        /**
         * Open the menu
         */
        open : function() {
            this.hasFocus = true;
            this.$button.addClass('active');
            this.$dropDown.slideDown('fast', function() {
                _this.hasFocus = true;
            });
        },

        /**
         * Mark the correct options in the form accordingly
         * to the article data
         * @param {ArlimaArticle} article
         */
        setup : function(article) {

            this.$dropDown.find('i.fa-check-square-o')
                .removeClass('fa-check-square-o')
                .addClass('fa-square-o');

            // Disable default template
            var list = window.ArlimaListContainer.list(article.listID);
            this.defaultTemplate = list.data.options.template;

            this.$dropDown.find('.template .nav').show().each(function() {
                if( $(this).attr('data-value') == _this.defaultTemplate ) {
                    $(this).hide();
                    return false;
                }
            });

            if( !list.data.options.allows_template_switching || !article.canHaveTemplate() ) {
                // list does not allow change of template
                this.$dropDown.find('.nav.template .nav').addClass('disabled');
            } else {
                // Make it possible to switch template
                this.$dropDown.find('.nav.template .disabled').removeClass('disabled');
            }

            if( !article.canPreview() ) {
                // section dividers does not have formats
                this.$dropDown.find('.nav.format .nav').addClass('disabled');
            } else {
                this.$dropDown.find('.nav.format .disabled').removeClass('disabled');

                // Disable/enable formats depending on current template
                this.onTemplateChange(window.ArlimaArticleForm.article.getTemplate());
            }


            this.$dropDown.children().each(function() {
                var $input = $(this),
                    property = $input.attr('data-prop'),
                    val = '';

                if(property.indexOf(':') > 0) {
                    var props = property.split(':');
                    val = props[0] in article.data  ? (article.data[props[0]][props[1]] || '') : '';
                } else {
                    val = article.data[property] || '';
                }

                // Find the correct option (item)
                $input.find('.sub .nav').each(function() {
                    if( $(this).attr('data-value') == val ) {
                        $(this).find('i')
                            .removeClass('fa-square-o')
                            .addClass('fa-check-square-o');

                        return false;
                    }
                });
            });
        },

        /**
         * Disables formats not enabled for current template
         * @param template
         */
        onTemplateChange : function(template) {
            var $formatOpts = this.$dropDown.find('.format .nav');
            if( !(template in this.templateFormatsMap) || this.templateFormatsMap[template].length == 0 ) {
                $formatOpts.removeClass('disabled');
            } else {

                // The format set for this article is not supported by the template
                if( $.inArray(window.ArlimaArticleForm.opt('format'), this.templateFormatsMap[template]) == -1 ) {
                    window.ArlimaArticleForm.article.data.options.format = '';
                    _setItemAsChecked($formatOpts.eq(0));
                }

                $formatOpts.addClass('disabled').eq(0).removeClass('disabled');
                $.each(this.templateFormatsMap[template], function(i, format) {
                    $formatOpts.filter('*[data-value="'+format+'"]').removeClass('disabled');
                });
            }
        },

        /**
         * When mouse goes over the menu or sub menu or menu button
         */
        onMouseEnter : function() {
            if( this.isOpen() ) {
                this.hasFocus = true;
            }
        },

        /**
         * When mouse leaves the menu or sub menu or menu button
         */
        onMouseLeave : function() {
            this.hasFocus = false;
            setTimeout(function() {
                if( !_this.hasFocus ) {
                    _this.close();
                }
            }, 1000)
        },

        /**
         * Close menu
         */
        close : function() {
            this.hasFocus = false;
            this.$button.removeClass('active');
            this.$dropDown.slideUp('fast');
        },

        /**
         * Is the menu visible
         * @returns {Boolean}
         */
        isOpen : function() {
            return this.$dropDown.is(':visible');
        },

        /**
         * Add select element to the menu, which then will become transformed into
         * a navigation item in the menu. The real select element will become
         * hidden, the value of the select element will become updated when user
         * clicks on the navigation item
         *
         * @param $select
         */
        addSelect : function($select) {
            var $navItem = $('<div class="nav">'+$select.attr('data-label')+'<div class="sub"></div></div>'),
                $subMenu = $navItem.find('.sub');

            $navItem
                .addClass($select.attr('data-prop').split(':').pop())
                .attr('data-prop', $select.attr('data-prop'))
                .bind('mouseenter', function() {
                    _this.$dropDown.find('.sub').hide();
                    var pos = $navItem.position();
                    $subMenu
                        .css({
                            right : (pos.left + _this.$dropDown.outerWidth() - 2) +'px',
                            top : pos.top +'px'
                        })
                        .show();
                })
                .bind('mouseleave', function() {
                    $subMenu.hide();
                });

            $subMenu
                .bind('mouseover', function() {
                    _this.hasFocus = true;
                })
                .bind('mouseleave', function() {
                    _this.onMouseLeave();
                });

            $select.find('option').each(function() {
                var $subItem = $('<div class="nav" data-value="'+$(this).attr('value')+'">'+
                    '<i class="fa fa-square-o"></i> '+$(this).text()+'</div>');

                // Change option!!
                $subItem.click(function() {
                    $subItem = $(this);
                    if( !$subItem.hasClass('disabled') ) {
                        var $icon = $subItem.find('i');
                        if( !$icon.hasClass('fa-check-square-o') ) {

                            _setItemAsChecked($subItem);

                            // Update the article form!!
                            var property = $subItem.parent().parent().attr('data-prop'),
                                val = $subItem.attr('data-value'),
                                $select = window.ArlimaArticleForm.$form.find('select[data-prop="'+property+'"]');

                            ArlimaUtils.selectVal($select, val, true);
                            if( $select.hasClass('templates') ) {
                                _this.onTemplateChange(val || _this.defaultTemplate);
                            }

                            // Initiate scheduled picker
                            if( property == 'options:scheduled' ) {
                                if( val ) {
                                    window.ArlimaScheduledIntervalPicker.open();
                                } else {
                                    window.ArlimaScheduledIntervalPicker.removePickedInterval();
                                }
                            }
                        } else if( $subItem.parent().parent().attr('data-prop') == 'options:scheduled')  {
                            window.ArlimaScheduledIntervalPicker.open();
                        }
                    }
                });

                $subMenu.append($subItem);
            });

            this.$dropDown.append($navItem);
        },

        /**
         * @param {jQuery} $button
         * @param {jQuery} $dropDown
         */
        init : function($button, $dropDown) {

            this.$dropDown = $dropDown;
            this.$button = $button;

            $dropDown.slideUp('fast');

            $button
                .bind('mouseenter click', function() {
                    if( !_this.isOpen() ) {
                        _this.open();
                    }
                    $(this).blur();
                })
                .bind('mouseleave', function() {
                    _this.onMouseLeave();
                })
                .bind('mouseover', function() {
                    _this.onMouseEnter();
                });

            $dropDown
                .bind('mouseleave', function() {
                    _this.onMouseLeave();
                })
                .bind('mouseenter', function() {
                    _this.onMouseEnter();
                });

        }

    },

    _setItemAsChecked = function($subItem) {
        var $icon = $subItem.find('i');
        if( !$icon.hasClass('fa-check-square-o') ) {
            $subItem.siblings().find('i')
                .removeClass('fa-check-square-o')
                .addClass('fa-square-o');
            $icon
                .removeClass('fa-square-o')
                .addClass('fa-check-square-o');
        }
    };




    return _this;


})(jQuery, ArlimaUtils);