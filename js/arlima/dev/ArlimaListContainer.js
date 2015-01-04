var ArlimaListContainer = (function($, window, ArlimaBackend, ArlimaListLoader, ArlimaJS, ArlimaUtils) {

    'use strict';

    var listReloadTime = parseInt(ArlimaJS.scheduledListReloadTime, 10),
        $window = $(window);

    if( listReloadTime && listReloadTime < 30 ) {
        ArlimaUtils.log('You can not set lists to be reloaded more often than every 30 seconds', 'warn');
        listReloadTime = 30;
    }

    return {

        /**
         * @var {ArlimaList[]}
         */
        lists : {},

        /**
         * @var jQuery
         */
        $elem : null,

        lastTouchedList : null,

        /**
         * @param {ArlimaList} list
         * @param {Object} [pos]
         */
        add : function(list, pos) {
            if( !pos ) {
                pos = {
                    left : '5px',
                    top: '5px',
                    width: '300px',
                    height: '400px'
                };
            }
            this.$elem.append(list.$elem);
            list.$elem.css(pos);
            this.lists[list.data.id.toString()] = list;

            list.$elem.trigger('Arlima.addedToContainer');
            $window.trigger("Arlima.listAddedToContainer", list);

            var _self = this;
            list.$elem.bind('change click dragged resized', function() {
                _self.lastTouchedList = list.data.id;
            });


            // Handle automatic reloading of lists
            if( listReloadTime && !list.data.isImported) {
                var reloadManager = function() {
                    var nextReloadTime,
                        resetReloading = function() {
                            list.displayTitleMessage(false);
                            list.reloadStep = 0;
                            nextReloadTime = listReloadTime - 10;
                        },
                        shouldIgnoreReload = list.hasUnsavedChanges() ||
                                            list.hasLoadedScheduledVersion() ||
                                            window.stopListReload ||
                                            (list.reloadStep == 2 && ArlimaArticleForm.isEditing(list.data.id));

                    if( shouldIgnoreReload ) {
                        ArlimaUtils.log('Ignoring background reload of list '+list);
                        resetReloading();
                    } else {
                        list.reloadStep++;
                        if( list.reloadStep == 1 ) {
                            list.displayTitleMessage(ArlimaJS.lang.willReload +' 10 '+ArlimaJS.lang.seconds, false);
                            nextReloadTime = 5;
                        } else if( list.reloadStep == 2 ) {
                            list.displayTitleMessage(ArlimaJS.lang.willReload +' 5 '+ArlimaJS.lang.seconds, false);
                            nextReloadTime = 5;
                        } else {
                            resetReloading();
                            var currentVersion = list.data.version.id;
                            list.reload(false, function(list) {
                                if( list.data.version.id != currentVersion ) {
                                    // hack to get name of last updating author
                                    var parts = list.data.versionDisplayText.split(' '),
                                        name = parts.splice(-2).join(' ');
                                    list.displayTitleMessage(ArlimaJS.lang.updatedBy +' '+ name, 8, '#e1b621');
                                }
                            });
                        }
                    }

                    list.reloadInterval = setTimeout(reloadManager, nextReloadTime * 1000);
                };

                list.reloadStep = 0;
                list.reloadInterval = setTimeout(reloadManager, (listReloadTime-10) * 1000);
            }
        },

        /**
         * @param {ArlimaList|Number} list
         * @param {Event} [evt]
         */
        remove : function(list, evt) {
            if( !(typeof list == 'object') ) {
                list = this.list(list);
            }

            if( window.ArlimaArticleForm.isEditing(list.data.id) )
                window.ArlimaArticleForm.clear();

            var doRemove = true;
            if( list.hasUnsavedChanges() && !ArlimaUtils.hasMetaKeyPressed(evt) ) {
                doRemove = confirm(ArlimaJS.lang.changesBeforeRemove);
            }

            if( doRemove ) {

                if( list.reloadInterval ) {
                    clearInterval(list.reloadInterval);
                }

                list.$elem.trigger('removedFromContainer');
                list.$elem.fadeOut(function() {
                    $(this).remove();
                });
                delete this.lists[list.data.id.toString()];
            }
        },

        /**
         * @param {Function} [callback]
         */
        saveListSetup : function(callback) {
            var lists = [];
            $.each(this.lists, function(listID, list) {
                var pos = list.$elem.position();
                lists.push({
                    alid : listID,
                    top : pos.top,
                    left : pos.left,
                    width : list.$elem.width(),
                    height : list.$elem.height()
                });
            });

            ArlimaBackend.saveListSetup(lists, function() {
                if( typeof callback == 'function' ) {
                    callback();
                }
            });
        },

        /**
         * @param {Function} callback
         */
        loadListSetup : function(callback) {

            ArlimaBackend.loadListSetup(function(json) {
                if(json) {
                    var numLists = json.length;

                    if(numLists == 0) {
                        callback();
                    }
                    else {

                        var _addList = function(id, pos) {
                            ArlimaListLoader.load(id, function(list) {
                                if( list ) {
                                    window.ArlimaListContainer.add(list, pos);
                                }
                                numLists--;
                                if(numLists == 0) {
                                    callback();
                                }
                            });
                        };

                        $.each(json, function(i, listData) {
                            _addList(listData.alid, {
                                top: parseInt(listData.top, 10),
                                left: parseInt(listData.left, 10),
                                height: parseInt(listData.height, 10),
                                width: parseInt(listData.width, 10)
                            });
                         });
                    }
                }
                else {
                    callback();
                }
            });
        },

        /**
         * @param {Number} [listID]
         */
        showAsActive : function(listID) {
            $.each(this.lists, function(i, list) {
                if( list.$elem.hasClass('active') ) {
                    list.$elem.removeClass('active');
                    return false;
                }
            });
            if( listID && !this.list(listID).data.isImported ) {
                this.list(listID).$elem.addClass('active');
            }
        },

        /**
         * @param {ArlimaArticle|Number|String} input
         * @return {ArlimaList}
         */
        list : function(input) {
            if( typeof input == 'object' && 'listID' in input ) {
                return this.lists[input.listID];
            } else {
                return this.lists[input];
            }
        },


        /* * * * * *  INIT * * * * * */


        init : function($listContainer, $footer) {

            this.$elem = $listContainer;

            var _self = this;

            // Load list setup
            $listContainer.find('.ajax-loader').show();
            this.loadListSetup(function() {
                $listContainer.find('.ajax-loader').hide();
                $window.trigger('Arlima.listSetupLoaded');
            });

            // Reload all list button
            $footer.find('.refresh-lists').on('click', function(e) {
                var hasUnsavedList = false;
                $.each(_self.lists, function(i, list) {
                    if( list.hasUnsavedChanges() ) {
                        hasUnsavedList = true;
                        return false;
                    }
                });

                var doReload = true;
                if( hasUnsavedList && !ArlimaUtils.hasMetaKeyPressed(e) ) {
                    doReload = confirm(ArlimaJS.lang.unsaved);
                }
                if( doReload ) {
                    $.each(_self.lists, function(i, list) {
                        list.reload();
                    });
                }
                return false;
            });

            // Save list setup button
            $footer.find('.save').on('click', function() {
                $footer.find('.ajax-loader').show();
                ArlimaListContainer.saveListSetup(function() {
                    $footer.find('.ajax-loader').hide();
                });
            });
        }
    };

})(jQuery, window, ArlimaBackend, ArlimaListLoader, ArlimaJS, ArlimaUtils);