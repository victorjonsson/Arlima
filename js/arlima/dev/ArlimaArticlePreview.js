var ArlimaArticlePreview = (function($, window, Mustache, ArlimaUtils, ArlimaJS) {

    'use strict';

    var $document = $(document),
        _isAnimating = false,
        _this = {

        /**
         * @var {ArlimaArticle}
         */
        article : null,

        /**
         * The iframe containing the preview
         * @var {jQuery}
         */
        $iframe : null,

        /**
         * The body of the iframe containing the preview
         * @var {jQuery}
         */
        $iframeBody : null,

        /**
         * @var {Boolean}
         */
        isRendered : false,

        /**
         * @var {jQuery}
         */
        $elem : false,

        /**
         * @var {String}
         */
        templateContent : null,

        /**
         * @var {String}
         */
        titleElem : null,

        /**
         * @var {Number}
         */
        lastHeightChange : null,

        /**
         * Int or array, contains width of preview iframe(s)
         * @var {mixed}
         */
        width : null,

        /**
         * Number of preview iframes to display - width elements count
         * @var {Number}
         */
        iframesCount : null,

        /**
         * @param {ArlimaArticle} article
         * @param {String} templateContent - The html of the mustache template
         * @param {Number} width
         * @param {String} [titleElem]
         * @param {Boolean} [belongsToImportedList]
         */
        setArticle : function(article, templateContent, width, titleElem, belongsToImportedList) {
            ArlimaUtils.log('Adding article to preview for '+article.data.id);

            this.article = article;
            this.width = width;

            var sizeMetrics = _getSizeMetrics();

            if ( this.width instanceof Array) {
                this.$elem.find('iframe').eq(0).width(width[0]); // first iframe width
                this.iframesCount = this.width.length;
                this.$elem.css({
                   maxHeight: '800px',
                   overflow: 'scroll'
                });
            } else {
                this.$elem.find('iframe').eq(0).width(width);
                this.iframesCount = 1;
            }


            if( !article.canPreview() || belongsToImportedList ) {
                if( this.isVisible() ) {
                    this.hide();
                }
            } else {
                this.titleElem = titleElem || 'h2';
                this.setTemplate(templateContent);
                _updatePreviewContainerSize(sizeMetrics);
            }
        },

        /**
         * @param {String} templateContent
         */
        setTemplate : function(templateContent) {
            this.templateContent = _prepare(templateContent);
            this.lastHeightChange = null;

            if( this.isVisible() ) {
                this.$elem.find('iframe').animate({opacity: 0}, 'fast', function() {
                    _render(true);
                    _this.$elem.find('iframe').animate({opacity:1}, 'fast');
                });
            } else {
                this.isRendered = false;
            }
        },


        /**
         * Reload current preview setup
         */
        reload : function() {
            var template = window.ArlimaTemplateLoader.templates[this.article.getTemplate()];
            this.setArticle(this.article, template, this.width, this.titleElem);
        },

        /**
         * @param {jQuery} $updated
         */
        update : function($updated) {
            if( this.isRendered ) {
                // update template-placeholder element depending on which input that is updated
                var toUpdate = {
                        streamer : ['options:streamerType', 'options:streamerColor', 'options:streamerContent'],
                        title : ['options:preTitle', 'title', 'size'],
                        post : ['post'],
                        content : ['content'],
                        format : ['options:format'],
                        imageSource:  ['image:attachment'],
                        imageSettings : ['image:size', 'image:alignment']
                    },
                    updatedProp = $updated.attr('data-prop');

                $.each(toUpdate, function(elemType, inputProps) {
                    var found = false;
                    $.each(inputProps, function(i, prop) {
                        if( prop == updatedProp ) {
                            found = true;
                            _updateInTemplate(elemType);
                            return false;
                        }
                    });
                    if( found )
                        return false;
                });
            }
        },

        /**
         * Is given article present in the preview
         * @param {ArlimaArticle} article
         * @returns {Boolean}
         */
        isPreviewed : function(article) {
            var isInPreview = false;
            if( this.article ) {
                if( this.article.isChild() && this.article.getParentArticle().$elem[0] == article.$elem[0] ) {
                    isInPreview = true;
                }
                else if( this.article.$elem[0] == article.$elem[0] ) {
                    isInPreview = true;
                } else if( article.isChild() ) {
                    var childrenInPreview = [],
                        parent = article.getParentArticle().getParentArticle() || article.getParentArticle(),
                        collectChildrenInPreview = function(article) {
                            $.each(article.getChildArticles(), function(i, art) {
                                childrenInPreview.push(art);
                                collectChildrenInPreview(art);
                            })
                        };

                    collectChildrenInPreview(parent);
                    $.each(childrenInPreview, function(i, childArticle) {
                        if( childArticle.$elem[0] == article.$elem[0] ) {
                            isInPreview = true;
                            return false;
                        }
                    });
                }
            }
            return isInPreview;
        },

        /**
         * Are we looking at the preview
         * @returns {Boolean}
         */
        isVisible : function() {
            return this.$elem.is(':visible') && (!_isAnimating || _isAnimating=='in');
        },

        /**
         * Show preview
         */
        show : function() {
            if( _isAnimating ) {
                return;
            }
            _isAnimating = 'in';

            if( !this.article.opt('sectionDivider') ) {
                var sizeMetrics = _getSizeMetrics(),
                    _animateInPlace = function() {
                        _this.$elem.css({
                            width : 0,
                            marginLeft : '-20px'
                        })
                        .show()
                        .animate({
                            width : sizeMetrics.elemWidth,
                            marginLeft: '-'+(sizeMetrics.leftMargin+20)+'px'
                        }, 'normal', function() {
                            _isAnimating = false;
                        });
                    };

                if( !_this.isRendered ) {
                    $document.one('previewUpdate', function() {
                        setTimeout(function() {
                            _this.$elem.show();
                            _updateIframeHeight();
                            _animateInPlace();
                        }, 200);
                    });
                    _render();
                } else {
                    _animateInPlace();
                }
            }
        },

        /**
         * Hide preview
         */
        hide : function() {

            if( _isAnimating ) {
                return;
            }

            _isAnimating = 'out';

            // Capture default width of iframes
            this.$elem.find('iframe').each(function() {
                var $iframe = $(this),
                    defaultWidth = $iframe.width();

                $iframe
                    .attr('data-default-width', defaultWidth)
                    .width( defaultWidth );
            });

            this.$elem.animate({width:0, marginLeft:'-20px', opacity:0} , 'normal', function() {
                _isAnimating = false;
                _this.$elem.css({
                    display : 'none',
                    opacity:1
                });
                _updatePreviewContainerSize(_getSizeMetrics());
                _this.$elem.find('iframe', function() {
                    $(this).width($(this).attr('data-default-width'));
                })
            });
        },

        /**
         * Toggle preview visibility
         */
        toggle : function() {
            if( this.isVisible() ) {
                this.hide();
            } else {
                this.show();
            }
        },

        /**
         * @param {jQuery} $elem
         */
        init : function($elem) {

            this.$elem = $elem;

            // Create preview iframe
            $elem.html('<iframe name="arlima-preview-iframe" id="arlima-preview-iframe" scrolling="no" border="0" frameborder="0"></iframe>');

            // Setup object props
            this.$iframe = this.$elem.find('iframe').contents();

            // Add stylesheets
            if( 'arlimaTemplateStylesheets' in window ) {
                $.each(window.arlimaTemplateStylesheets, function(i, styleSheet) {
                    _this.$iframe.find('head').append('<link rel="stylesheet" type="text/css" href="'+styleSheet+'" />');
                });
            }

            // Setup iframe body
            this.$iframe.find('html,body')
                .css({
                    border: 0,
                    padding: 0,
                    margin: 0,
                    overflow : 'hidden'
                })
                .addClass('arlima-preview');

            this.$iframeBody = this.$iframe.find('body');
        },

        /**
         * Get the content element inside the template
         * @returns {jQuery}
         */
        getContentElement : function() {
            return this.$iframeBody.find('.template-placeholder-content').eq(0);
        },

        /**
         * Get the title element inside the rendered template
         * @returns {jQuery}
         */
        getTitleElement : function() {
            return this.$iframeBody.find('.template-placeholder-title').eq(0);
        }

    },

    _prepare = function(templateContent) {
        return templateContent
                .replace('{{{html_image}}}', '<span class="template-placeholder-image">{{{html_image}}}</span>')
                .replace('{{{html_title}}}', '<span class="template-placeholder-title">{{{html_title}}}</span>')
                .replace('{{title}}', '<span class="template-placeholder-title-plain">{{title}}</span>')
                .replace('{{{html_content}}}', '<span class="template-placeholder-content">{{{html_content}}}</span>')
                .replace('{{{content}}}', '<span class="template-placeholder-content-plain">{{{content}}}</span>')
                .replace('{{class}}', 'template-placeholder-format {{class}}')
                .replace('{{{html_streamer}}}', '<span class="template-placeholder-streamer">{{{html_streamer}}}</span>');
    },

    /**
     * Look at the height of the preview an update its
     * iframe to the same height
     */
    _updateIframeHeight = function(animate) {
        var el;
      ArlimaUtils.log('Updating iframe height');

        for (el in _this.$iframeBody) {
            var elementHeight = _this.$iframeBody.eq(el).children().eq(0).outerHeight(true);
            if( !elementHeight )
                elementHeight = 400;
    
            if( animate ) {
                _this.$elem.find('iframe').eq(el).animate({'height': elementHeight+'px'}, 'fast');
            } else {
                _this.$elem.find('iframe').eq(el).css('height', elementHeight+'px');
            }
        }
    },

    /**
     * Render the entire preview
     */
    _render = function(animateHeightChange) {

        ArlimaUtils.log('Rendering preview');

        var $content,
            iframes = [],
            i;

        _this.isRendered = true;
        _this.$iframeBody.html('');

        try {

            if( _this.article.isChild() ) {
                var parent = _this.article.getParentArticle();
                if (parent.isChild()) {
                    parent = parent.getParentArticle();
                }
                $content = _renderArticle(parent, false, false);
            } else {
                $content = _renderArticle(_this.article, _this.templateContent);
                $content.eq(0).addClass('main-article-preview');
            }

            var adjustIframeHeight = function() {
                  _updateIframeHeight(animateHeightChange);
                },
                updateIframeHeightTimer = setTimeout(adjustIframeHeight, 250);

            $content.find('img').bind('load', function() {
              _updateIframeHeight(animateHeightChange);
              clearTimeout(updateIframeHeightTimer);
            });

            $content.appendTo(_this.$iframeBody);

            //create as many iframes as needed
            if (_this.iframesCount > 1 && _this.$iframe.length < _this.width.length) {
                //add first iframe (created on init) to iframes collection
                iframes.push(_this.$iframeBody[0]);

                for (i=_this.$iframe.length; i<_this.width.length;i++) {
                    _this.$elem.append('<div class="iframe-wrapper"><iframe name="arlima-preview-iframe" id="arlima-preview-iframe-'+i+'" style="width: '+_this.width[i]+'px;" scrolling="no" border="0" frameborder="0"></iframe></div>');
                    var anotherPreview = _this.$elem.find('#arlima-preview-iframe-'+i).contents();

                    // Add stylesheets
                    if( 'arlimaTemplateStylesheets' in window ) {
                        $.each(window.arlimaTemplateStylesheets, function(i, styleSheet) {
                            anotherPreview.find('head').append('<link rel="stylesheet" type="text/css" href="'+styleSheet+'" />');
                        });
                    }

                    // Setup iframe body
                    anotherPreview.find('html,body')
                        .css({
                            border: 0,
                            padding: 0,
                            margin: 0,
                            overflow : 'hidden'
                        })
                        .addClass('arlima-preview');

                    anotherPreview.find('body').html(jQuery('<div />').append($content.clone()).html());

                    iframes.push(anotherPreview.find('body')[0]);
                }

                _this.$iframe = _this.$elem.find('iframe');
                //create jquery collection from iframes array
                _this.$iframeBody = $(iframes);
            } else if (_this.$iframe.length > _this.iframesCount) {
                //too many iframes
                for (i=_this.iframesCount;i<_this.$iframe.length;i++) {
                    _this.$iframeBody.eq(i).remove();
                    _this.$iframe.eq(i).remove();
                    _this.$iframeBody.splice(i,1);
                    _this.$iframe.splice(i,1);
                }
            }

            $document.trigger('previewUpdate', 'all');

        } catch(e) {
            ArlimaUtils.log(e);
        }
    },

    /**
     * @param {ArlimaArticle} article
     * @param {String} [templateContent]
     * @param {Boolean} [isChild]
     * @param {Boolean} [asHTML]
     * @param {String} [extraClasses]
     */
    _renderArticle = function(article, templateContent, isChild, asHTML, extraClasses) {

        if( !templateContent ) {
            templateContent = window.ArlimaTemplateLoader.templates[article.getTemplate()];
            if( !templateContent ) {
                alert('Use of template that does not exist '+article.getTemplate());
                return asHTML ? '' : $('<div></div>');
            }
        }
        if( templateContent.indexOf('{{class}}') == -1 ) {
            alert('Use of template that is missing {{class}} variable');
            return asHTML ? '' : $('<div></div>');
        } else if( templateContent.indexOf('data-post="{{post}}"') == -1 ) {
            alert('Use of template that is missing attribute data-post="{{post}}"');
            return asHTML ? '' : $('<div></div>');
        }

        var data = $.extend(true, {}, article.data);
        data.html_title = _getTitleHTML(article);
        data.html_content = _getContentHTML(article);
        data.html_streamer = _getStreamerHTML(article);
        data.class = _getClasses(article);
        data.html_image = _getImageHTML(article);
        data.child_articles = '';

        if( extraClasses ) {
            data.class += extraClasses;
        }

        if( !isChild && templateContent.indexOf('{{{child_articles}}}') > -1 ) {

            var childrenHTML = '',
                firstOrLastClass = '',
                hasOpenChildWrapper = false,
                children = article.getChildArticles();

            $.each(children, function(i, childArticle) {
                firstOrLastClass = '';

                /* floated children */

                var floatedChildren = childArticle.getChildArticles();
                if (floatedChildren.length) {
                    // TODO  check  to see if it's being edited
                    floatedChildren.unshift(childArticle);

                    childrenHTML += '<div class="arlima child-wrapper child-wrapper-' + floatedChildren.length + '">';

                    $.each(floatedChildren, function(f, floatedChild) {
                        if (f == 0) {
                            firstOrLastClass = 'first';
                        } else if (f == floatedChildren.length - 1) {
                            firstOrLastClass = 'last';
                        }
                        if (floatedChild == _this.article) {
                            childrenHTML += _renderArticle(floatedChild, _this.templateContent, true, true, firstOrLastClass+' teaser-child teaser-split');

                        } else {
                            childrenHTML += _renderArticle(floatedChild, false, true, true, firstOrLastClass+' teaser-child teaser-split');

                        }
                    });

                    childrenHTML += '</div>';
                    return;
                }

                /* normal children */
                if (childArticle == _this.article) {
                    childrenHTML += _renderArticle(childArticle, _this.templateContent, true, true, firstOrLastClass+' teaser-child');
                } else {
                    childrenHTML += _renderArticle(childArticle, false, true, true, firstOrLastClass+' teaser-child');
                }
            });

            if( hasOpenChildWrapper ) {
                childrenHTML += '</div>';
            }

            data.child_articles = '<div class="teaser-children children-'+children.length+'">'+childrenHTML+'</div>';
        }

        var html = Mustache.render(templateContent, data);
        return asHTML ? html : $(html);
    },

    /**
     * Update something in the template
     */
    _updateInTemplate = function(type) {
        if( type == 'imageSource' || type == 'post' ) {
            _this.reload();
            return;
        }
        else if( type == 'imageSettings' ) {
            var $img = _this.$iframeBody.find('.template-placeholder-image img');
            if( $img.length > 0 ) {
                $img.removeClass('alignright alignleft half third quarter fifth sixth');
                $img.addClass(_this.article.data.image.alignment);
                $img.addClass(_this.article.data.image.size);
                _this.$iframeBody.find('.template-placeholder-format').addClass(_getClasses(_this.article));
            }
        }
        else if( type == 'format' ) {
            // remove previous format
            var $container = _this.$iframeBody.find('.template-placeholder-format'),
                newFormat = _this.article.opt('format');
            $.each(window.ArlimaArticleForm.$form.find('.formats option'), function() {
                $container.removeClass($(this).attr('value'));
            });
            if( newFormat ) {
                $container.addClass(newFormat);
            }
        } else {
            var newContent, newContentPlain='';
            switch( type ) {
                case 'title':
                    newContent = _getTitleHTML(_this.article);
                    newContentPlain = _this.article.data.title;
                    break;
                case 'content':
                    newContent = _getContentHTML(_this.article);
                    newContentPlain = _this.article.data.content;
                    break;
                case 'streamer':
                    newContent = _getStreamerHTML(_this.article);
                    break;
            }
            _this.$iframeBody.find('.template-placeholder-'+type).html(newContent);
            _this.$iframeBody.find('.template-placeholder-'+type+'-plain').html(newContentPlain);
        }

        // Have we updated something that forces us to update the teaser classes
        if( type == 'streamer' || type == 'imageSettings' ) {
            _this.$iframeBody.find('.template-placeholder-format')
                .removeClass('img-full img-half img-third img-fourth img-fifth img-sixth no-img has-streamer no-streamer')
                .addClass(_getClasses(_this.article));
        }

        $document.trigger('previewUpdate', [type]);
        _updateIframeHeight();
    },
    _getTitleHTML = function(article) {
        if( article.data.title ) {
            var title = article.data.title.replace('__', '<br />'),
                preTitle = article.opt('preTitle');
            if(  preTitle )
                title = '<span class="arlima-pre-title">' + preTitle + '</span> ' + title;

            return '<'+_this.titleElem+' style="font-size:'+article.data.size+'px">'+title+'</'+_this.titleElem+'>';
        }
        return '';
    },
    _getClasses = function(article) {
        var classes = ['teaser'];
        if( article.data.image && article.data.image.attachment ) {
            classes.push('img-'+article.data.image.size);
        } else {
            classes.push('no-img');
        }
        if( article.opt('streamerType') ) {
            classes.push('has-streamer');
        } else {
            classes.push('no-streamer');
        }
        if( article.opt('format') ) {
            classes.push(article.opt('format'));
        }
        return classes.join(' ') + ' ';
    },
    _getStreamerHTML = function(article) {
        if( article.opt('streamerType') == 'text' ) {
            return '<div class="streamer text color-'+article.opt('streamerColor')+'" style="background: #'+article.opt('streamerColor')+'">'+
                        article.opt('streamerContent')+
                    '</div>';
        } else if( article.opt('streamerType') == 'extra' ) {
            return '<div class="streamer extra">Extra</div>';
        } else if( article.opt('streamerType') == 'image' ) {
            return '<div class="streamer image"><img src="'+article.opt('streamerContent')+'" alt="" /></div>';
        } else if( article.opt('streamerContent') != '') {
            // custom streamer class (described in the wiki)
            return '<div class="streamer '+article.opt('streamerType')+'">'+article.opt('streamerContent')+'</div>';
        } else {
            return '';
        }
    },
    _getImageHTML = function(article) {
        if( article.data.image && article.data.image.url ) {
            return '<img src="'+article.data.image.url+'" class="arlima-preview-img '+article.data.image.size+' '+article.data.image.alignment+'" />';
        }
        return '';
    },
    _getContentHTML = function(article) {
        if( article.data.content != '' ) {
            var html = $.trim(article.data.content);
            if( article.data.content.indexOf('<p') !== 0 ) {
                html = '<p>'+html+'</p>';
            }
            return html;
        }
        return '';
    },
    _getSizeMetrics = function() {
        var metrics = {};
        if ( _this.width instanceof Array ) {
            metrics.elemWidth = _this.width[0]+20;  // 20 px for scrollbar
            metrics.leftMargin = Math.max.apply(Math, _this.width)+20; // 20 px for scrollbar
        } else {
            metrics.elemWidth = _this.width;
            metrics.leftMargin = _this.width;
        }
        return metrics;
    },
    _updatePreviewContainerSize = function(sizeMetrics) {
        _this.$elem.css({
            width : sizeMetrics.elemWidth+'px',
            marginLeft : '-'+(sizeMetrics.leftMargin+20)+'px' // adjust 20px for padding
        });
    };

    return _this;

})(jQuery, window, Mustache, ArlimaUtils, ArlimaJS);