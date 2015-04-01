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

        if ($.isArray(data.options)) { // php json_encode makes empty objects []. Messes when serializing back to JSON
            data.options = $.extend({}, data.options);
        }

        this.setData($.extend(true, {}, ArlimaArticle.defaultData, data));
        this.listID = listID;
        this.$elem[0].arlimaArticle = this;
        this.$elem.attr('title', new Date(data.published * 1000));
        this.addClickEvents(addRemoveButton);
    }

    /**
     * Should tell if given object most probably is an ArlimaArticle objec
     * @param obj
     * @return {Boolean}
     */
    ArlimaArticle.isArticleObj = function(obj) {
        return obj.$elem && obj.$elem.arlimaArticle && obj == obj.$elem.arlimaArticle;
    };

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
     * @param {String} state (default|editing)
     */
    ArlimaArticle.prototype.setState = function(state) {
        var $wrappedChild = this.getWrappedChildElem();
        if( state == 'editing' ) {
            this.$elem.addClass('editing');
            if( $wrappedChild ) {
                $wrappedChild.addClass('editing');
            }
        } else {
            // default state
            this.$elem.removeClass('editing');
            if( $wrappedChild ) {
                $wrappedChild.removeClass('editing');
            }
        }
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

        var titleText = '',
            titleHTML = '',
            _this = this;

        /*
          Construct the title string
         */
        if(this.data.title)
            titleText = this.data.title.replace(/__/g, '');
        else if(this.data.content)
            titleText += '[' + this.data.content.replace(/(<.*?>)/ig,"").substring(0,30) +'...]';
        if( this.opt('preTitle') ) {
            titleText = this.opt('preTitle') + ' ' + titleText;
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

        // We're done with the title text at this poins
        titleHTML = titleText;

        /*
          Is this a section divider
         */
        if( this.opt('sectionDivider') ) {
            this.$elem.addClass('section-divider');
            titleHTML = '&ndash;&ndash;&ndash; '+titleHTML+' &ndash;&ndash;&ndash;';
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

                titleHTML = '<span class="streamer-indicator" style="background:'+color+'"></span> '+titleHTML ;
            }
        }

        /*
          Add some icons
         */
        if( this.opt('adminLock') )
            titleHTML = '<span class="fa fa-lock"></span>' + titleHTML;
        if( this.opt('scheduled') )
            titleHTML = '<span class="fa fa-clock-o"></span>' + titleHTML;
        if( this.opt('fileInclude') )
            titleHTML = '<span class="fa fa-bolt"></span>' + titleHTML;

        /*
          Display if its a future article
         */
        if( !this.isPublished() ) {
            titleHTML = '<span class="future-push-date">'+ _getDatePresentation(this.data.published * 1000) +'</span>' + titleText;
            this.$elem.addClass('future');
        } else {
            this.$elem.removeClass('future');
        }

        // Update item
        this.$elem.find('.article-title').html(titleHTML);

        // Update title in wrapper
        var $childWrap = this.getWrappedChildElem();
        if( $childWrap ) {
            $childWrap.text(titleText);
        }

    };

    /**
     * @returns {boolean|jQuery}
     */
    ArlimaArticle.prototype.getWrappedChildElem = function() {

        var $span = false,
            _this = this;

        if( this.$elem.hasClass('contains-toggled-children') ) {
            $span = this.$elem.find('.article-children').children().eq(0);
        } else if( this.isWrappedChild() ) {
            var $wrappedChildren = this.getParentArticle().$elem.find('.article-children').children();
            $wrappedChildren.each(function() {
                if( this.arlimaArticle == _this ) {
                    $span = $(this);
                    return false;
                }
            });
        }


        return $span;
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
     * Whether or not this is a floating child that is wrapped
     * @returns {Boolean}
     */
    ArlimaArticle.prototype.isWrappedChild = function() {
        return this.$elem.hasClass('list-item-depth-2') && this.getParentArticle().$elem.find('.article-children').is(':visible');
    };

    /**
     * @return {ArlimaList[]}
     */
    ArlimaArticle.prototype.getChildArticles = function() {

        if( this.isWrappedChild() ) {
             return [];
        }

        var children = [],
            $next = this.$elem.next(),
            parentIndex = $next.length && $next[0].arlimaArticle ? $next[0].arlimaArticle.data.parent : -1,
            isGettingChildrenOfAChild = this.isChild() && !this.isWrappedChild();

        if( parentIndex > -1 && parentIndex != this.data.parent ) {

            while($next.length) {
                if ($next[0].arlimaArticle.data.parent == parentIndex && ($next[0].arlimaArticle.data.options.inlineWithChild === undefined || isGettingChildrenOfAChild) ) {
                    children.push($next[0].arlimaArticle);
                }
                if ($next[0].arlimaArticle.data.parent == -1) {
                    break;
                }
                $next = $next.next();
            }
        }

        return children;
    };

    /**
     * @return {Boolean}
     */
    ArlimaArticle.prototype.isParent = function() {
        if( this.data.parent == '-1' ) {
            var $next = this.$elem.next(),
                parentIndex = $next.length && $next[0].arlimaArticle ? $next[0].arlimaArticle.data.parent : -1,
                isGettingChildrenOfAChild = this.isChild() && !this.isWrappedChild();

            if( parentIndex > -1 && parentIndex != this.data.parent ) {
                while($next.length) {
                    if ($next[0].arlimaArticle.data.parent == parentIndex && ($next[0].arlimaArticle.data.options.inlineWithChild === undefined || isGettingChildrenOfAChild) ) {
                        return true;
                    }
                    if ($next[0].arlimaArticle.data.parent == -1) {
                        break;
                    }
                    $next = $next.next();
                }
            }

        }
        return false;
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
            var $allArticles = this.$elem.parent().find('.article');
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
    };


    /**
     * @return {Boolean}
     */
    ArlimaArticle.prototype.canBeChild = function() {
        return !this.isDivider() && !this.opt('fileInclude') && !this.isParent();
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
