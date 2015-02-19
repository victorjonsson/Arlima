var ArlimaFormBlocker = (function($, window, ArlimaArticleForm) {

    var _this = {

        $formBlocker : false,
        $streamerBlocker : false,
        $preTitleBlocker : false,
        $titleSizeBlocker : false,
        $imgAlignBlocker : false,

        toggleImageAlignBlocker : function(toggle) {
            if( toggle ) {
                this.$imgAlignBlocker.css({
                    top : (window.ArlimaImageManager.$alignButtons.eq(0).position().top - 10) +'px'
                });
                this.$imgAlignBlocker.show();
            } else {
                this.$imgAlignBlocker.hide();
            }
        },

        toggleFormBlocker : function(toggle, message) {
            if( !toggle ) {
                this.$formBlocker.hide();
                this.$formBlocker.find('.block-message').remove();
            } else {
                if( message ) {
                    $('<div class="block-message">'+message+'</div>').appendTo(this.$formBlocker);
                }
                this.$formBlocker.show();
                _this.updateFormBlockerSize();
                setTimeout(function() {
                    _this.updateFormBlockerSize();
                }, 500);
            }
        },

        updateFormBlockerSize : function() {
            this.$formBlocker.css({
                width : ArlimaArticleForm.$form.parent().outerWidth() + 20,
                height : ArlimaArticleForm.$form.parent().outerHeight() + ArlimaArticleForm.$controls.outerHeight()
            });
        },

        toggleStreamerBlocker : function(toggle) {
            if( !toggle ) {
                this.$streamerBlocker.hide();
            } else {
                this.$streamerBlocker.show();
                ArlimaArticleForm.change('.streamer-type', '', false);
                ArlimaArticleForm.change('.streamer.content', '', false);
                var $streamerButton = ArlimaArticleForm.$form.find('.button.streamer');
                if( $streamerButton.hasClass('active') )
                    $streamerButton.trigger('click');
            }
        },

        toggleTitleBlocker : function(toggle) {
            var $preTitle = ArlimaArticleForm.$form.find('input.pre-title');
            if( !toggle ) {
                this.$preTitleBlocker.hide();
                this.$titleSizeBlocker.hide();
                $preTitle.removeAttr('disabled');
            } else {
                this.$preTitleBlocker.show().css('top', $preTitle.position().top+'px');
                this.$titleSizeBlocker.show().css('top', $preTitle.position().top+'px');
                $preTitle.attr('disabled', 'disabled').val('');
            }
        },

        removeBlockers : function() {
            this.toggleStreamerBlocker(false);
            this.toggleTitleBlocker(false);
            this.toggleFormBlocker(false);
            this.toggleImageAlignBlocker(false);
            ArlimaArticleForm.$form.find('input.pre-title').removeAttr('disabled');
        },

        /**
         * @param typeOfBlock
         * @returns {Boolean} Whether or not a block could be added
         */
        addBlocker : function(typeOfBlock) {
            switch (typeOfBlock) {
                case 'streamer':
                    this.toggleStreamerBlocker(true);
                    break;
                case 'title':
                    this.toggleTitleBlocker(true);
                    break;
                case 'image-align':
                    this.toggleImageAlignBlocker(true);
                    break;
                default:
                    return false;
            }
            return true;
        },

        init : function() {
            this.$formBlocker = $('<div class="form-blocker blocker"></div>').appendTo(ArlimaArticleForm.$form);
            this.$streamerBlocker = $('<div class="streamer-blocker blocker"></div>').appendTo(ArlimaArticleForm.$form);
            this.$preTitleBlocker = $('<div class="pre-title-blocker blocker"></div>').appendTo(ArlimaArticleForm.$form);
            this.$titleSizeBlocker = $('<div class="title-size-blocker blocker"></div>').appendTo(ArlimaArticleForm.$form);
            this.$imgAlignBlocker = $('<div class="img-align-blocker blocker"></div>').appendTo(ArlimaArticleForm.$form);

            ArlimaArticleForm.$form.bind('open', function() {
                if( _this.$formBlocker.is(':visible') ) {
                    _this.updateFormBlockerSize();
                } else if( _this.$titleSizeBlocker.is(':visible') ) {
                    var $preTitle = ArlimaArticleForm.$form.find('input.pre-title');
                    _this.$preTitleBlocker.show().css('top', $preTitle.position().top+'px');
                    _this.$titleSizeBlocker.show().css('top', $preTitle.position().top+'px');
                }
            });

            $(window).bind('arlimaFormImageLoaded', function() {
                if( _this.$formBlocker.is(':visible') ) {
                    _this.updateFormBlockerSize();
                }
            });
        }

    };

    return _this;

})(jQuery, window, ArlimaArticleForm);