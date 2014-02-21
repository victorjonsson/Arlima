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
        this.addClickEvents();
    }

    /**
     * Bind (or re-bind) click events that opens the article
     * form and that removes the article from the list
     */
    ArlimaArticle.prototype.addClickEvents = function() {
        var _self = this,
            $remove = this.$elem.find('.remove');

        this.$elem.unbind('click').click(function() {
            window.ArlimaArticleForm.edit( _self );
        });

        if( $remove.length == 0 ) {
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
            this.updateTitleElement();
        } else {
            this.data = data;
        }
        if( !this.isPublished() ) {
            this.$elem.addClass('future');
        } else {
            this.$elem.removeClass('future');
        }
    };

    /**
     * Update the title in the item element
     */
    ArlimaArticle.prototype.updateTitleElement = function(checkDate) {
        var title = '';

        if(this.data.title)
            title = this.data.title.replace(/__/g, '');
        else if(this.data.content)
            title += '[' + this.data.content.replace(/(<.*?>)/ig,"").substring(0,30) +'...]';

        if( this.opt('preTitle') ) {
            title = this.opt('preTitle') + ' ' + title;
        }

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

        if( this.opt('sectionDivider') ) {
            this.$elem.addClass('section-divider');
            title = '&ndash;&ndash;&ndash; '+title+' &ndash;&ndash;&ndash;';
        }

        if( this.opt('adminLock') )
            title = '<span class="fa fa-lock"></span>' + title;
        if( this.opt('scheduled') )
            title = '<span class="fa fa-clock-o"></span>' + title;
        if( this.opt('fileInclude') )
            title = '<span class="fa fa-bolt"></span>' + title;
        if( !this.isPublished() )
            title = '<span class="fa fa-calendar"></span>' + title;

        this.$elem.find('.article-title').html(title);

        if( checkDate ) {
            if( !this.isPublished() ) {
                this.$elem.addClass('future');
            } else {
                this.$elem.removeClass('future');
            }
        }
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
        var elemIndex = this.$elem.prevAll().not('.list-item-depth-1').length,
            $next = this.$elem.next(),
            children = [];

        while($next.length > 0 && $next[0].arlimaArticle && $next[0].arlimaArticle.data.parent == elemIndex) {
            children.push($next[0].arlimaArticle);
            $next = $next.next();
        }

        return children;
    };

    /**
     * Returns the mustache template that should be used for this article
     * @return {String}
     */
    ArlimaArticle.prototype.getTemplate = function() {
        return this.opt('template') || window.ArlimaListContainer.list(this.listID).data.options.template;
    };

    /**
     * @return {ArlimaArticle|undefined}
     */
    ArlimaArticle.prototype.getParentArticle = function() {
        if( this.isChild() ) {
            return this.$elem.parent().find('.article').not('.list-item-depth-1').get(this.data.parent).arlimaArticle;
        }
    };

    /**
     * Get an option value. This is a short-hand function that can be
     * used instead of article.data.option.myOption
     * @param {String} name
     * @return {*}
     */
    ArlimaArticle.prototype.opt = function(name) {
        return this.data.options[name];
    };

    /**
     * Says that the article supports templates
     * @returns {Boolean}
     */
    ArlimaArticle.prototype.canPreview = function() {
        return !this.opt('fileInclude') && !this.opt('sectionDivider');
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
    ArlimaArticle.prototype.canBeChild = function() {
        return !this.opt('sectionDivider');
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
                newData.options.adminLock != oldData.options.adminLock ||
                newData.options.scheduled != oldData.options.scheduled ||
                newData.options.streamerColor != oldData.options.streamerColor ||
                newData.options.streamerType != oldData.options.streamerType;
    };


    return ArlimaArticle;

})(jQuery, window, ArlimaJS, ArlimaUtils);
