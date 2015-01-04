var ArlimaArticle = (function($, window, ArlimaJS, ArlimaUtils) {

    var articleHTML =
        '<div class="article">' +
            '<div class="article-title-container">' +
                '<span class="article-title"></span>' +
                '<a href="#" class="remove">&times;</a>' +
            '</div>' +
            '<div class="children-transport"></div>' + 
        '</div>';

    /**
     * @param {Object} data
     * @param {Number} listID
     * @param {jQuery} [$elem]
     * @param {Boolean} [addRemoveButton]
     * @constructor
     */
    function ArlimaArticle(data, listID, $elem, addRemoveButton) {
        if( $elem ) {
            this.$elem = $elem;
        } else {
            this.$elem = $(articleHTML);
        }
        if( addRemoveButton === false ) {
            this.$elem.find('.remove').remove();
        }

        this.setData($.extend(true, {}, ArlimaArticle.defaultData, data));
        this.listID = listID;
        this.$elem[0].arlimaArticle = this;
        this.$elem.attr('title', new Date(data.published * 1000));
        this.addClickEvents(addRemoveButton);
    }

    /**
     * Bind (or re-bind) click events that opens the article
     * form and that removes the article from the list
     *
     * @param {Boolean} [addRemoveButtonIfMissing]
     */
    ArlimaArticle.prototype.addClickEvents = function(addRemoveButtonIfMissing) {
        var _self = this,
            $remove = this.$elem.find('.remove');

        this.$elem.unbind('click').click(function() {
            window.ArlimaArticleForm.edit( _self );
        });

        if( $remove.length == 0 && addRemoveButtonIfMissing ) {
            // The article may not have a remove button, so lets add it
            $remove = $('<a href="#" class="remove">&times;</a>').appendTo(this.$elem.find('.article-title-container'))
        }

        $remove.unbind('click').click(function(evt) {
            var confirmMessage = ArlimaJS.lang.wantToRemove + _self.$elem.find('.article-title').text() + ArlimaJS.lang.fromList;
            if(ArlimaUtils.hasMetaKeyPressed(evt) || confirm(confirmMessage)) {
                _self.remove();
            }
            evt.stopPropagation();
            return false;
        });
    };

    /**
     * @param {Object} data
     */
    ArlimaArticle.prototype.setData = function(data) {
        if( _needsItemTitleUpdate(data, this.data) ) {
            this.data = data;
            this.updateItemPresentation();
        } else {
            this.data = data;
            if( !this.isPublished() ) {
                this.$elem.addClass('future');
            } else {
                this.$elem.removeClass('future');
            }
        }
    };

    /**
     * Update the title and the style of the item element
     */
    ArlimaArticle.prototype.updateItemPresentation = function() {

        var title = '',
            _this = this;

        /*
          Construct the title string
         */
        if(this.data.title)
            title = this.data.title.replace(/__/g, '');
        else if(this.data.content)
            title += '[' + this.data.content.replace(/(<.*?>)/ig,"").substring(0,30) +'...]';
        if( this.opt('preTitle') ) {
            title = this.opt('preTitle') + ' ' + title;
        }

        /*
         Add format and template classes to item
         */
        var extraClasses = [
            {classPrefix: 'format-', attr: 'data-format-class', opt: 'format'},
            {classPrefix: 'template-', attr: 'data-template-class', opt: 'template'}
        ];
        $.each(extraClasses, function(i, data) {
            var currentClassName = _this.$elem.attr(data.attr),
                newClassName = data.classPrefix + _this.opt(data.opt),
                hasChanged = currentClassName != newClassName;

            if( hasChanged && _this.opt(data.opt) ) {
                if( currentClassName ) {
                    _this.$elem.removeClass(currentClassName);
                }
                _this.$elem
                    .addClass(newClassName)
                    .attr(data.attr, newClassName);

            } else if( currentClassName && hasChanged ) {
                _this.$elem
                    .removeAttr(data.attr)
                    .removeClass(currentClassName);
            }
        });

        /*
          Is this a section divider
         */
        if( this.opt('sectionDivider') ) {
            this.$elem.addClass('section-divider');
            title = '&ndash;&ndash;&ndash; '+title+' &ndash;&ndash;&ndash;';
            if(this.opt('streamerType') == 'text' ) {
                this.$elem.css('background', '#'+this.opt('streamerColor'));
                if (_isColorLight(this.opt('streamerColor'))) {
                    this.$elem.addClass('light-streamer');
                } else {
                    this.$elem.removeClass('light-streamer');
                }
            } else {
                this.$elem.css('background', '');
            }
        } else {

            /*
              Display that it has a streamer
             */
            if(this.opt('streamerType')) {
                var color;
                switch (this.opt('streamerType')) {
                    case 'extra':
                        color = 'rgba(0,0,0, .5)';
                        break;
                    case 'image':
                        color = 'rgba(0,0,0, .5)';
                        break;
                    default:
                        color = '#'+this.opt('streamerColor');
                        break;
                }
                if( color == '#' )
                    color = 'black';

                title = '<span class="streamer-indicator" style="background:'+color+'"></span> '+title ;
            }
        }

        /*
          Add some icons
         */
        if( this.opt('adminLock') )
            title = '<span class="fa fa-lock"></span>' + title;
        if( this.opt('scheduled') )
            title = '<span class="fa fa-clock-o"></span>' + title;
        if( this.opt('fileInclude') )
            title = '<span class="fa fa-bolt"></span>' + title;

        /*
          Display if its a future article
         */
        if( !this.isPublished() ) {
            title = '<span class="future-push-date">'+ _getDatePresentation(this.data.published * 1000) +'</span>' + title;
            this.$elem.addClass('future');
        } else {
            this.$elem.removeClass('future');
        }

        // Update item
        this.$elem.find('.article-title').html(title);
    };

    /**
     * @return {Boolean}
     */
    ArlimaArticle.prototype.isPublished = function() {
        return !ArlimaUtils.isFutureDate(this.data.published * 1000);
    };

    /**
     * @returns {Boolean}
     */
    ArlimaArticle.prototype.isChild = function() {
        return parseInt(this.data.parent, 10) > -1;
    };

    /**
     * Remove this article form the list it belongs to
     */
    ArlimaArticle.prototype.remove = function() {
        var reloadPreview = false,
            list = window.ArlimaListContainer.list(this.listID);

        if( this.opt('adminLock') && !ArlimaJS.isAdmin ) {
            alert(ArlimaJS.lang.adminLock);
            return;
        }

        if( window.ArlimaArticleForm.isEditing(this.$elem) ) {
            // clear form
            window.ArlimaArticleForm.clear();
        } else if( this.isChild() ) {
            // reload preview if we're editing the
            reloadPreview = window.ArlimaArticlePreview.isPreviewed(this);
        }

        // Remove children
        $.each(this.getChildArticles(), function(i, childArticle) {
            childArticle.remove();
        });

        // Remove this element
        this.$elem.remove();

        if( reloadPreview ) {
            window.ArlimaArticlePreview.reload();
        }

        // Update list
        list.toggleUnsavedState(true);
        list.updateParentProperties();
        list.$elem.trigger('change');
    };

    /**
     * @return {Number} - Will return -1 if this article isn't a child
     */
    ArlimaArticle.prototype.getChildIndex = function() {
        var index = -1,
            parent = this.getParentArticle(),
            _self = this;

        if( parent ) {
            $.each(parent.getChildArticles(), function(i, art) {
                if( art.$elem.get(0) == _self.$elem.get(0) ) {
                    index = i;
                    return false;
                }
            });
        }

        return index;
    };

    /**
     * @return {ArlimaList[]}
     */
    ArlimaArticle.prototype.getChildArticles = function() {
        if( this.isChild() ) {
            return []; // Notice: this logic must be looked over if allowing deeper child levels
        }

        var children = [],
            $next = this.$elem.next(),
            parentIndex = $next.length && $next[0].arlimaArticle ? $next[0].arlimaArticle.data.parent : -1;

        if( parentIndex > -1 ) {
            while($next.length && $next[0].arlimaArticle.data.parent == parentIndex ) {
                children.push($next[0].arlimaArticle);
                $next = $next.next();
            }
        }

        return children;
    };

    /**
     * Returns the mustache template that should be used for this article
     * @return {String}
     */
    ArlimaArticle.prototype.getTemplate = function() {
        var tmpl = this.opt('template');
        if( !tmpl ) {
            if( !this.listID ) {
                ArlimaUtils.log('Trying to get template of an article that is not yet related to any list', 'warn');
                tmpl = undefined;
            } else {
                tmpl = window.ArlimaListContainer.list(this.listID).data.options.template;
            }
        }
        return tmpl;
    };

    /**
     * @return {ArlimaArticle|undefined}
     */
    ArlimaArticle.prototype.getParentArticle = function() {
        if( this.isChild() ) {
            var $allArticles = this.$elem.parent().find('.article').not('.list-item-depth-1');
            return $allArticles.get(this.data.parent).arlimaArticle;
        }
    };

    /**
     * Get an option value. This is a short-hand function that can be
     * used instead of article.data.option.myOption
     * @param {String} name
     * @return {*}
     */
    ArlimaArticle.prototype.opt = function(name) {
        return this.data.options[name] || '';
    };

    /**
     * Says that the article supports templates that is possible to preview
     * in the list manager
     * @see ArlimaArticle.canHaveTemplate()
     * @returns {Boolean}
     */
    ArlimaArticle.prototype.canPreview = function() {
        return !this.opt('fileInclude') && !this.opt('sectionDivider');
    };

    /**
     * Tells whether or not this article support switching of the template (as long as its allowed by the list it's in).
     * @returns {Boolean}
     */
    ArlimaArticle.prototype.canHaveTemplate = function() {
        if( this.opt('sectionDivider') ) {
            return ArlimaJS.sectionDivsSupportTemplate;
        } else {
            return !this.opt('fileInclude');
        }
    };

    /**
     * @returns {Boolean}
     */
    ArlimaArticle.prototype.canHaveChildren = function() {
        return this.canPreview(); // only an alias so far
    };

    /**
     * @return {Boolean}
     */
    ArlimaArticle.prototype.isDivider = function() {
        return this.opt('sectionDivider');
    }

    /**
     * @return {Boolean}
     */
    ArlimaArticle.prototype.canBeChild = function() {
        return !this.isDivider();
    };


    /* * * * * * Private functions * * * * * */


    /**
     * @param {Object} newData
     * @param {Object} oldData
     * @returns {boolean}
     * @private
     */
    var _needsItemTitleUpdate = function(newData, oldData) {
        return !oldData ||
                newData.title != oldData.title ||
                newData.options.preTitle != oldData.options.preTitle ||
                newData.options.format != oldData.options.format ||
                newData.options.adminLock != oldData.options.adminLock ||
                newData.options.scheduled != oldData.options.scheduled ||
                newData.options.streamerColor != oldData.options.streamerColor ||
                newData.options.streamerType != oldData.options.streamerType;
    };

    /**
     * Simple function to determine if a color is light or dark.
     * @param {String} color
     * @returns {boolean}
     * @private
     */
    var _isColorLight = function(color) {
        var r = parseInt(color.substr(0, 2), 16),
            g = parseInt(color.substr(2, 2), 16),
            b = parseInt(color.substr(4, 2), 16);
        return (r + g + b) > 382;
    };

    /**
     * @param ts
     * @returns {String}
     * @private
     */
    var _getDatePresentation = function(ts) {
        var getFullDate = function(d) {
                    return d.getYear()+'-'+d.getMonth()+'-'+ d.getDate();
            },
            unitFix = function(unit) {
                return unit < 10 ? '0'+unit:unit;
            },
            date = new Date(),
            givenDate = new Date(ts);

        if( getFullDate(date) == getFullDate(givenDate) ) {
            return unitFix(givenDate.getHours()) +':'+ unitFix(givenDate.getMinutes());
        } else {
            return unitFix(givenDate.getMonth()+1) +'/'+ unitFix(givenDate.getDate());
        }
    };

    return ArlimaArticle;

})(jQuery, window, ArlimaJS, ArlimaUtils);
