var ArlimaList = (function($, window, ArlimaJS, ArlimaBackend, ArlimaUtils) {

    'use strict';

    var listHTML =
        '<div class="article-list">'+
            '<div class="header">' +
                '<span>'+
                    '<a href="#" class="remove">&times;</a>'+
                    '<a href="#" class="add-section">+</a>'+
                    '<span class="title"></span>'+
                '</span>' +
            '</div>' +
            '<div class="articles"></div>'+
            '<div class="footer">'+
                '<a href="#" class="preview" title="'+ArlimaJS.lang.preview+'">' +
                    '<i class="fa fa-eye"></i>' +
                '</a>'+
                '<a href="#" class="save" title="'+ArlimaJS.lang.publish+'">' +
                    '<i class="fa fa-save"></i>' +
                '</a>'+
                '<img src="'+ArlimaJS.pluginURL+'/images/ajax-loader-trans.gif" class="ajax-loader" />'+
                '<a href="#" class="refresh" title="'+ArlimaJS.lang.reload+'">' +
                    '<i class="fa fa-refresh"></i>' +
                '</a>'+
                '<span class="version">' +
                    '<div class="number"></div>'+
                    '<select class="previous-versions"></select>' +
                '</span>' +
            '</div>'+
        '</div>';


    /**
     * @param {Object} data
     * @constructor
     */
    function ArlimaList(data) {
        this.$elem = $(listHTML);
        this._isUnsaved = false;
        var _self = this,
            $articles = this.$elem.find('.articles');


        this.$elem
            .resizable({
                containment: 'parent',
                start : function() {
                    _self.$elem.css('overflow', 'hidden');
                    $articles.css('overflow-y', 'hidden');
                    $articles.find('.article').css('visibility', 'hidden');
                },
                stop : function() {
                    _self.$elem.css('overflow', 'visible');
                    _self.$elem.trigger('resized');
                    $articles.find('.article').css('visibility', 'visible');
                    $articles
                        .css('height', '100%')
                        .css({
                            height : $articles.height()+'px',
                            overflowY : 'auto'
                        });
                }
            })
            .draggable({
                containment: 'parent',
                snap: 20,
                handle: '.header',
                stop : function() {
                    _self.$elem.trigger('dragged');
                }
            });

        if( data.isImported ) {
            this.$elem.addClass('imported');

            setTimeout(function() {
                _self.$elem.find('.article .remove').remove();
                _self.$elem.find('.footer .save').remove();
                _self.$elem.find('.footer .preview').remove();
            }, 50);

            var reloadInterval = setInterval(function() {
                _self.reload();
            }, 90000);
            this.$elem.bind('removedFromContainer', function() {
                clearInterval(reloadInterval);
            })

        }

        this.$elem
            .bind('addedToContainer', function() {
                // Needed for scroll to work
                $articles.css('height', $articles.height()+'px');
                arlimaNestedSortable(_self);
            });

        this.setData(data);

        _addEventListeners(this);

        this.$elem.get(0).arlimaList = this;
    }

    /**
     * @return {Array}
     */
    ArlimaList.prototype.getArticleData = function() {
        var articles = [];
        this.$elem.find('.article').each(function() {
            if( this.arlimaArticle.isChild() ) {
                articles[parseInt(this.arlimaArticle.data.parent, 10)].children.push(this.arlimaArticle.data);
            } else {
                this.arlimaArticle.data.children = []; // will become populated by this iteration
                articles.push(this.arlimaArticle.data);
            }
        });
        return articles;
    };

    /**
     * Returns the number of articles in the list
     * @returns {Number}
     */
    ArlimaList.prototype.size = function() {
        return this.$elem.find('.article').length;
    };

    /**
     * @returns {Number}
     */
    ArlimaList.prototype.numSections = function() {
        return this.$elem.find('.article.section-divider').length;
    };

    /**
     * @param {Array} articles
     */
    ArlimaList.prototype.setArticles = function(articles) {
        var _self = this;
        $.each(articles, function(i , articleData) {
            _self.addArticle(new ArlimaArticle(articleData, _self.data.id), false);
            if( articleData.children.length > 0 ) {
                $.each(articleData.children, function(j, childArticleData) {
                    var childArticle = new ArlimaArticle(childArticleData, _self.data.id, false);
                    childArticle.$elem.addClass('list-item-depth-1');
                    _self.addArticle(childArticle, false);
                });
            }
        });
    };

    /**
     * @param {ArlimaArticle} article
     * @param {Boolean} [toggleUnsavedState]
     */
    ArlimaList.prototype.addArticle = function(article, toggleUnsavedState) {
        this.$elem.find('.articles').append(article.$elem);
        article.listID = this.data.id;
        if( toggleUnsavedState )
            this.toggleUnsavedState(true);
    };

    /**
     * Create and open a preview version of this list
     */
    ArlimaList.prototype.preview = function() {
        window.ArlimaListPreview.preview(this);
    };

    /**
     * @param {Object} data
     */
    ArlimaList.prototype.setData = function(data) {
        this.data = data;

        var title = data.title;

        var $titleNode = this.$elem.find('.header .title');
        if($.trim($titleNode.text()) != title ) {
            $titleNode.text(title);
        }

        if( ArlimaJS.isAdmin && data.options.supports_sections && !data.isImported ) {
            this.$elem.find('.add-section').show();
        } else {
            this.$elem.find('.add-section').hide();
        }

        _displayVersionInfo(this);
    };

    /**
     * @param {Boolean} isUnsaved
     */
    ArlimaList.prototype.toggleUnsavedState = function(isUnsaved) {
        isUnsaved = isUnsaved === true; // typecast
        if(isUnsaved != this._isUnsaved) { // state changed
            this._isUnsaved = isUnsaved;
            var $title = this.$elem.find('.header .title');
            $title.find('.dot').remove();
            if(this._isUnsaved) {
                this.$elem.addClass('unsaved');
                $title.prepend('<span class="dot">&nbsp;</span>');
            }
            else {
                this.$elem.removeClass('unsaved');
            }
        }
    };

    /**
     * @param {Boolean} toggle
     */
    ArlimaList.prototype.toggleAjaxPreLoader = function(toggle) {
        _toggleAjaxPreloader(this, toggle);
    };

    /**
     * Reload the latest version or a specific version
     * @param {Number} [version]
     */
    ArlimaList.prototype.reload = function(version) {

        // preset
        var _self = this;
        this.loadedVersion = version;
        this.$elem.find('.articles').html('');

        // Clear form perhaps
        if( window.ArlimaArticleForm.isEditing(this.data.id) ) {
            window.ArlimaArticleForm.clear();
        }

        // Toggle state
        this.toggleUnsavedState( version && version != this.data.version.id ? true:false );
        _toggleAjaxPreloader(this, true);

        // Load the version of the list
        window.ArlimaListLoader.load(this, function() {
            _toggleAjaxPreloader(_self, false);
        }, version);
    };

    /**
     * @return {Boolean}
     */
    ArlimaList.prototype.hasUnsavedChanges = function() {
        return this._isUnsaved;
    };

    /**
     * Goes through all articles that is set as future and check
     * if they're still future articles
     */
    ArlimaList.prototype.fixFutureNotices = function() {
        this.$elem.find('.future').each(function() {
            if( this.arlimaArticle.isPublished() ) {
                this.arlimaArticle.updateItemPresentation();
            }
        });
    };

    /**
     * @return {ArlimaArticle}
     */
    ArlimaList.prototype.article = function(index) {
        return this.$elem.find('.article').get(index).arlimaArticle;
    };

    ArlimaList.prototype.dump = function() {
        this.$elem.find('.article').each(function() {
            ArlimaUtils.log(this.arlimaArticle);
        });
    };

    /**
     * Save current list as a new version
     */
    ArlimaList.prototype.save = function() {
        if( this.hasUnsavedChanges() ) {

            this.toggleUnsavedState(false);
            _toggleAjaxPreloader(this, true);

            delete this.loadedVersion; // No specific version loaded means we're on the latest created version

            var _self = this;

            ArlimaBackend.getLaterVersion(this.data.id, this.data.version.id, function(json) {
                if(json) {
                    var saveList = true;
                    if(json.version) {
                        // has newer version
                        saveList = confirm(ArlimaJS.lang.laterVersion + ' \r\n ' + json.versioninfo + '\r\n' + ArlimaJS.lang.overWrite);
                    }
                    if( _self.$elem.find('.streamer-extra').length > 1) {
                        // has many extra
                        saveList = confirm( ArlimaJS.lang.severalExtras + '\r\n' +  ArlimaJS.lang.overWrite);
                    }

                    if( !saveList ) {
                        _toggleAjaxPreloader(_self, false);
                    } else {
                        window.ArlimaListLoader.save(_self, function(data) {
                            _toggleAjaxPreloader(_self, false);
                            if( data ) {
                                _self.setData(data);
                                if( window.ArlimaArticleForm.isEditing(_self.data.id) ) {
                                    window.ArlimaArticleForm.toggleUnsavedState('saved');
                                }
                            }
                        });
                    }
                }
            });
        }
    };

    /**
     * Goes through all articles in the list and updates the parent properties
     * of the child articles
     */
    ArlimaList.prototype.updateParentProperties = function() {
        var parentIndex = -1;
        this.$elem.find('.article').each(function() {
            var $article = $(this);
            if( $article.hasClass('list-item-depth-1') ) {
                this.arlimaArticle.data.parent = parentIndex;
            } else {
                this.arlimaArticle.data.parent = '-1';
                parentIndex++;
            }
            this.arlimaArticle.children = [];
        });
    };

    /* * * * *  Private methods * * * * */

    /**
     * Make version info available in the list element
     * @param {ArlimaList} list
     */
    var _displayVersionInfo = function(list) {
        if(list.data.isImported) {
            list.$elem.find('.version .number').text(list.data.versionDisplayText);
        }
        else {
            var $versionWrapper = list.$elem.find('.version .number'),
                $versionDropDown = list.$elem.find('.previous-versions'),
                loadedVersionID = list.loadedVersion || list.data.version.id;

            $versionWrapper
                .html('v. '+loadedVersionID)
                .attr('title', list.data.versionDisplayText)
                .qtip({
                    position: {
                        my: 'right top',
                        at: 'center left',
                        viewport: jQuery(window)
                    },
                    style: window.qtipStyle
                });

            $versionDropDown.html('');

            $.each(list.data.versions, function(i, version ) {
                var $option = $('<option></option>', {
                    value : version,
                    selected : version == loadedVersionID
                })
                .text('v. ' + version);
                $versionDropDown.append($option);
            });
        }
    };

    var _toggleAjaxPreloader = function(list, toggle) {
        var $preloader = list.$elem.find('.ajax-loader');
        if( toggle ) {
            $preloader.show();
            list.$elem.find('.footer a').addClass('disabled');
        } else {
            list.$elem.find('.footer a').removeClass('disabled');
            $preloader.hide();
        }
    };


    /**
     * @param {ArlimaList} list
     * @private
     */
    var _addEventListeners = function(list) {

        list.$elem.find('.refresh').click(function(evt) {
            var doReload = true;
            if( list.hasUnsavedChanges() && !ArlimaUtils.hasMetaKeyPressed(evt) ) {
                doReload = confirm(ArlimaJS.lang.hasUnsavedChanges);
            }
            if( doReload ) {
                list.reload();
            }
            return false;
        });

        list.$elem.find('.preview').click(function() {
            if( !list.data.isImported ) {
                list.preview();
            }
            return false;
        });
        
        list.$elem.find('.save').click(function(e) {
            list.save();
            return false;
        });

        list.$elem.find('.remove').click(function(evt) {
            window.ArlimaListContainer.remove(list, evt);
            return false;
        });

        list.$elem.find('.add-section').click(function() {
            var sectionDividerData = {
                        title : 'Section divider '+ (list.numSections() + 1),
                        options: {
                            sectionDivider: 1
                        }
                    };

            list.addArticle(new ArlimaArticle(sectionDividerData), true);
            return false;
        });

        // Toggle version dropdown
        var $versionWrapper = list.$elem.find('.version'),
            hasDropDownFocus = false,
            $versionDropDown = list.$elem.find('.previous-versions');

        $versionDropDown
            .bind('mouseenter', function() {
                hasDropDownFocus = true;
            })
            .bind('mouseleave', function() {
                hasDropDownFocus = false;
                setTimeout(function() {
                    if( $versionDropDown.parent().is(':visible') && !hasDropDownFocus ) {
                        $versionWrapper.find('.number').show();
                        $versionDropDown.hide();
                    }
                }, 1200);
            })
            .bind('change', function() {
                list.reload($(this).val());
            });

        // Show version drop down
        $versionWrapper.find('.number').click( function() {
            $(this).hide();
            $versionDropDown.show();
            hasDropDownFocus = true;
            return false;
        });
    };


    return ArlimaList;

})(jQuery, window, ArlimaJS, ArlimaBackend, ArlimaUtils);