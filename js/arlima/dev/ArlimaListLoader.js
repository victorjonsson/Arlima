var ArlimaListLoader = (function($, window, ArlimaBackend, ArlimaJS) {

    var _this = {

        /**
         * @param {Number|String|ArlimaList} listID
         * @param {Function} callback
         * @param {Number} [version]
         */
        load : function(listID, callback, version) {
            var list;

            if( typeof listID == 'object' ) {
                list = listID;
            }

            ArlimaBackend.loadListData(list ? list.data.id : listID, version || '', function(json) {

                if(json && json.exists) {

                    var hasChanged = true;
                    if( json.version && !json.isImported ) {
                        json.version.scheduled = parseInt(json.version.scheduled, 10);
                        if( !version && list && json.version.id == list.data.version.id && !list.hasUnsavedChanges() ) {
                            hasChanged = window.JSON.stringify(json.scheduledVersions || {}) != window.JSON.stringify(list.data.scheduledVersions || {});
                        }
                    }

                    if( !list ) {
                        // Create list object and it's element
                        list = new ArlimaList(json);
                    } else {
                        // Only overwrite the latest current version of the list and when editing scheduled
                        if ( version && json.version.status != 3 ) {
                            json.loadedVersion = json.version.id;
                            json.version = list.data.version;
                        }
                        if( hasChanged ) {
                            list.setData(json);
                        }
                    }

                    if( hasChanged )
                        list.setArticles(json.articles);

                    callback(list);

                } else {
                    callback(false);
                }

            });
        },

        /**
         * @param {ArlimaList} list
         * @param {Number} scheduleTime
         * @param {Function} [callback]
         */
        save : function(list, scheduleTime, callback) {
            ArlimaBackend.saveList(list.data.id, list.getArticleData(), scheduleTime, function(json) {
                if( typeof callback == 'function' )
                    callback(json);
            });
        },

        /**
         * @param {Number} version
         */
        deleteScheduledVersion : function(version, callback) {
            ArlimaBackend.deleteScheduledVersion(version, function(json) {
                if( typeof callback == 'function' )
                    callback(json);
            });
        },

        /**
         * Load a list from backend and add it to the list container
         * @param {Number} listID
         */
        addListToContainer : function(listID) {
            if( listID ) {
                var container = window.ArlimaListContainer;
                if( listID in container.lists ) {
                    container.focus(container.lists[listID]);
                    ArlimaUtils.shake(container.lists[listID]);
                } else {
                    _this.load(listID, function(list) {
                        if( list ) {
                            container.add(list, {
                                left : '25px',
                                top: '25px',
                                width: '300px',
                                height: '400px'
                            });
                        } else {
                            throw new Error('Trying to add list '+listID+' to container but it does not exist');
                        }
                    })
                }
            }
        },

        /* * * * * *  INIT * * * * * */

        init : function($elem) {

            var $lists = $elem.find('select');

            // Choose a list from select
            $elem.find('.action').click(function() {
                this.blur();
                var listID = $lists.val();
                _this.addListToContainer($lists.val());
            });

            // Search for a list
            _arlimaListSearch($elem.find('.list-search input'), $elem.find('.list-search ul'));
            $elem.find('.list-search .list').on('click', function() {
                _this.addListToContainer($(this).attr('data-alid'));
            });

        }

    };

    // Make contains function case sensitive
    $.expr[':'].Contains = function(a, i, m) {
        return $(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };

    var _arlimaListSearch = function($input, $lists) {
        $lists.hide();
        return $input.keyup(function(e) {
            var key = e.keyCode ? e.keyCode : e.which;
            if (key == '13') {
                e.preventDefault();
            }
            var search = $.trim($(this).val());
            if(search.length > 0) {
                var found = $lists.children().filter(":Contains('" + search + "')").show().length;
                if( found ) {
                    $lists.children().not(":Contains('" + search + "')").hide();
                    $lists.show();
                }
            } else {
                $lists.hide();
            }
        })
        .blur(function() {
            setTimeout(function() {
                $lists.hide();
            }, 500); // this has to be done after a while, otherwise the children wont be possible to click
        })
        .focus(function() {
            $(this).trigger('keyup');
        });
    };

    return _this;

})(jQuery, window, ArlimaBackend, ArlimaJS);