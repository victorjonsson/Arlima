var ArlimaArticlePreview = (function($, window, Mustache, ArlimaUtils, ArlimaJS) {

    var $document = $(document),
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
         * @param {ArlimaArticle} article
         * @param {String} templateContent - The html of the mustache template
         * @param {Number} width
         * @param {String} [titleElem]
         * @param {Boolean} [belongsToImportedList]
         */
        setArticle : function(article, templateContent, width, titleElem, belongsToImportedList) {
            ArlimaUtils.log('Adding article to preview');
            this.article = article;
            if( !article.canPreview() || belongsToImportedList ) {
                if( this.isVisible() ) {
                    this.hide();
                }
            } else {
                this.titleElem = titleElem || 'h2';
                this.setTemplate(templateContent);
                this.$elem.css({
                    width : width+'px',
                    marginLeft : '-'+(width+20)+'px' // adjust 20px for padding
                });
            }
        },

        /**
         * @param {String} templateContent
         */
        setTemplate : function(templateContent) {
            this.templateContent = _prepare(templateContent);
            this.lastHeightChange = null;

            if( this.isVisible() ) {
                _render();
            } else {
                this.isRendered = false;
            }
        },

        /**
         * Reload current preview setup
         */
        reload : function() {
            var template = window.ArlimaTemplateLoader.templates[this.article.getTemplate()];
            this.setArticle(this.article, template, this.$elem.width(), this.titleElem);
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
                    var childrenInPreview = this.article.isChild() ? this.article.getParentArticle().getChildArticles() : this.article.getChildArticles();
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
            return this.$elem.is(':visible');
        },

        /**
         * Show preview
         */
        show : function() {
            if( !this.article.opt('sectionDivider') ) {
                this.$elem.show();
                if( !this.isRendered ) {
                    _render()
                }
            }
        },

        /**
         * Hide preview
         */
        hide : function() {
            this.$elem.hide();
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
            $elem.html('<iframe name="arlima-preview-iframe" id="arlima-preview-iframe" style="overflow: hidden; width: 100%; height:0" scrolling="no" border="0" frameborder="0"></iframe>');

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
    _updateIframeHeight = function() {
        var elementHeight = _this.$iframeBody.children().eq(0).outerHeight();
        if( !elementHeight )
            elementHeight = 400;

        if( _this.lastHeightChange != elementHeight ) {
            _this.lastHeightChange = elementHeight;
            _this.$elem.find('iframe').css('height', elementHeight+'px');
        }
    },

    /**
     * Render the entire preview
     */
    _render = function() {

        ArlimaUtils.log('Rendering preview');

        var $content;

        _this.isRendered = true;
        _this.$iframeBody.html('');

        if( _this.article.isChild() ) {
            $content = _renderArticle(_this.article.getParentArticle(), false, false, _this.article.getChildIndex());
        } else {
            $content = _renderArticle(_this.article, _this.templateContent);
            $content.eq(0).addClass('main-article-preview');
        }

        $content.find('img').bind('load', _updateIframeHeight);
        $content.appendTo(_this.$iframeBody);
        $document.trigger('previewUpdate', 'all');
        _updateIframeHeight();
    },

    /**
     * @param {ArlimaArticle} article
     * @param {String} [templateContent]
     * @param {Boolean} [isChild]
     * @param {Number} [childIndex] - Will be the index of the article that we're editing, in case its a child article
     * @param {Boolean} [asHTML]
     * @param {String} [extraClasses]
     */
    _renderArticle = function(article, templateContent, isChild, childIndex, asHTML, extraClasses) {
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
                children = article.getChildArticles(),
                hasEvenNumberOfChildren = children.length % 2 === 0;

            $.each(children, function(i, childArticle) {
                firstOrLastClass = '';
                // todo: remove this hellisch piece of logic and instead add a feature that makes it possible
                // for the editor to choose which articles that is full/half by dragging the article element sideways
                if( ArlimaJS.groupChildArticles ) {
                    if(
                        (children.length == 4 && (i == 1 || i == 2)) ||
                            (children.length == 6 && (i != 0 && i != 3)) ||
                            (children.length > 1 && children.length != 4 && children.length != 6 && (i != 0 || hasEvenNumberOfChildren) )
                        ) {

                        firstOrLastClass = ((i==1 && children.length > 2) || (i==0 && children.length==2) || i==3 || (i==4 && children.length ==6)? ' first':' last');
                        if( firstOrLastClass == ' first' ) {
                            childrenHTML += '<div class="arlima child-wrapper">';
                            hasOpenChildWrapper = true;
                        }
                    }

                    if( firstOrLastClass ) {
                        firstOrLastClass += ' teaser-split';
                    }
                }

                if( i === childIndex ) {
                    childrenHTML += _renderArticle(childArticle, _this.templateContent, true, false, true, firstOrLastClass+' teaser-child');
                } else {
                    childrenHTML += _renderArticle(childArticle, false, true, false, true, firstOrLastClass+' teaser-child');
                }

                if( ArlimaJS.groupChildArticles) {
                    if( hasOpenChildWrapper && firstOrLastClass == 'last') {
                        childrenHTML += '</div>';
                        hasOpenChildWrapper = false;
                    }
                }
            });

            if( hasOpenChildWrapper && ArlimaJS.groupChildArticles ) {
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
            var $container = _this.$iframeBody.find('.template-placeholder-format');
            $.each(window.ArlimaArticleForm.$form.find('.formats option'), function() {
                $container.removeClass($(this).attr('value'));
            });
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
        return classes.join(' ');
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
    };

    return _this;

})(jQuery, window, Mustache, ArlimaUtils, ArlimaJS);