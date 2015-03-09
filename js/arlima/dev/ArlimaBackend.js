var ArlimaBackend = (function($, ArlimaUtils, ArlimaJS) {

    var log = ArlimaUtils.log,
        $body;

    $(function() {
        // This has to be done when body element is completely rendered
        $body = $('body');
    });

    return {

        backendVersion : false,

        /**
         * @param {Object} data
         * @param {Function} [callback]
         */
        queryPosts : function(data, callback) {
            this._ajax('arlima_query_posts', data, callback);
        },

        /**
         * @param {Number} listId
         * @param {Number} version
         * @param {Function} [callback]
         */
        getLaterVersion : function(listId, version, callback) {
            this._ajax('arlima_check_for_later_version', {alid:listId, version:version}, callback);
        },

        /**
         * Get URL for post with given id
         * @param {Number} postId
         * @param [callback]
         */
        getPost : function(postId, callback) {
            this._ajax('arlima_get_post', {postid:postId}, callback, 'json', false);
        },

        /**
         * Get all attachments related to wordpress post with given id
         * @param {Number} postId
         * @param {Function} [callback]
         */
        getPostAttachments : function(postId, callback) {
            this._ajax('arlima_get_attached_images', {postid: postId}, callback);
        },

        /**
         * @param {Number} postId
         * @param {Number} attachId
         * @param {Function} [callback]
         */
        connectAttachmentToPost : function(postId, attachId, callback) {
            this._ajax('arlima_connect_attach_to_post', {attachment:attachId, post:postId}, callback);
        },

        /**
         * @param {Number} listId
         * @param {Array|Object} articles
         * @param {Number} scheduleTime
         * @param {Function} [callback]
         */
        saveList : function(listId, articles, scheduleTime, callback) {
            this._ajax(
                "arlima_save_list",
                'alid='+listId+'&articles='+encodeURIComponent(JSON.stringify(articles))+'&scheduleTime='+scheduleTime,
                callback,
                'json',
                true,
                false
            );
        },

        /**
         * @param listId
         * @param versionId
         * @param articles
         * @param callback
         */
        updateListVersion : function(listId, versionId, articles, callback) {
            this._ajax(
                "arlima_update_list_version",
                'alid='+listId+'&articles='+encodeURIComponent(JSON.stringify(articles))+'&version='+versionId,
                callback,
                'json',
                true,
                false
            );
        },

        /**
         *
         * @param {Number} versionId
         * @param {Function} [callback]
         */
        deleteScheduledVersion : function(versionId, callback) {
            this._ajax(
                    "arlima_delete_list_version",
                    'alvid='+versionId,
                    callback,
                    'json',
                    true,
                    false
            );
        },

        /**
         * @param {Number} listId
         * @param {Array} articles
         * @param {Function} [callback]
         */
        savePreview : function(listId, articles, callback) {
            this._ajax(
                "arlima_save_list",
                'alid='+listId+'&preview=1&articles='+encodeURIComponent(JSON.stringify(articles)),
                callback,
                'json',
                true,
                false
            );
        },

        /**
         * Removes image versions (only needed in WP >= 3.5)
         * @param {Number} attachID
         * @param {Function} [callback]
         */
        removeImageVersions : function(attachID, callback) {
            this._ajax('arlima_remove_image_versions', {attachment : attachID}, callback);
        },

        /**
         * @todo: Rename to loadArticlePresets
         * @param {Function} [callback]
         */
        loadCustomTemplateData : function(callback) {
            this._ajax('arlima_print_custom_templates', {}, callback);
        },

        /**
         * Load information about a list and its articles
         * @param {Number} listID
         * @param {Number|String} version - Optional, empty string to get latest version
         * @param {Function} [callback]
         */
        loadListData : function(listID, version, callback) {
            this._ajax('arlima_add_list_widget', {alid:listID, version:version}, callback);
        },

        /**
         * Get the lists supposed to be loaded on page load for currentyl
         * visited user
         * @see Backend.saveListSetup()
         * @param {Function} [callback]
         */
        loadListSetup : function(callback) {
            this._ajax('arlima_get_list_setup', {}, callback);
        },

        /**
         * Save external image to wordpress
         * @param {String} url
         * @param {Number} postId - Optional
         * @param {Function} [callback]
         */
        saveExternalImage : function(url, postId, callback) {
            var extension = url.substr(url.lastIndexOf('.')+1).toLowerCase();
            var queryBegin = extension.indexOf('?');
            if(queryBegin > -1) {
                extension = extension.substr(0, queryBegin);
            }
            this._ajax('arlima_save_external_img', { imgurl : url, postid: postId }, callback);
        },

        /**
         * @param base64Image
         * @param postID
         * @param fileName
         * @param callback
         */
        saveImage : function(base64Image, postID, fileName, callback) {
            this._ajax('arlima_save_image', {image:base64Image, postid: postID, name:fileName}, callback);
        },

        /**
         * Save the lists that should be available on page load for currently logged in user
         * @param {Array} lists
         * @param {Function} [callback]
         */
        saveListSetup : function(lists, callback) {
            this._ajax('arlima_save_list_setup', {lists: lists}, callback);
        },

        /**
         * @param {Number} attachmentId
         * @param {Function} [callback]
         */
        loadScissorsHTML : function(attachmentId, callback) {
            this._ajax('arlima_get_scissors', {attachment: attachmentId}, callback, 'html');
        },

        /**
         * @param {Number} attachId
         * @param {Function} [callback]
         */
        duplicateImage : function(attachId, callback) {
            this._ajax('arlima_duplicate_image', {attachment:attachId}, callback);
        },

        /**
         * Send a log message to server log
         * @param {String} mess
         * @param {String} stack
         * @param {Function} [callback]
         */
        logJSError : function(mess, stack, url, lineNumber, callback) {
            this._ajax('arlima_log_js', {message:mess, stack:stack, file:url, line:lineNumber}, callback);
        },

        /**
         * Show info about updated arlima version
         */
        displayNewVersionMessage : function(newVersion) {
            if( !newVersion )
                newVersion = this.backendVersion;

            var $message = $('#arlima-version-message');
            if( !$message.is(':visible') ) {
                $message.find('.version').text(newVersion);
                $.fancybox({
                    href : '#arlima-version-message',
                    height: 400,
                    width: 400
                });
            }
        },

        /**
         * @param {String} action
         * @param {Object} postArgs
         * @param {Function} callback - Optional
         * @param {String} [dataType]
         * @param {Boolean} [addBodyAjaxPreloader]
         * @param {Boolean} [processData]
         * @private
         */
        _ajax : function(action, postArgs, callback, dataType, addBodyAjaxPreloader, processData) {
            var _this = this;
            if( typeof postArgs == 'string' ) {
                postArgs += '&action='+action+'&_ajax_nonce='+ArlimaJS.arlimaNonce;
            } else {
                postArgs['action'] = action;
                postArgs['_ajax_nonce'] = ArlimaJS.arlimaNonce;
            }

            if(addBodyAjaxPreloader === undefined)
                addBodyAjaxPreloader = true;
            if(dataType === undefined)
                dataType = 'json';

            processData = processData === undefined ? true:processData;

            if( $body && addBodyAjaxPreloader )
                $body.addClass('wait-loading');

            $.ajax({
                url : ArlimaJS.ajaxURL,
                type : 'POST',
                data : postArgs,
                processData : processData,
                dataType : dataType,
                success : function(json, status, http) {
                    var version = (http.getResponseHeader('X-Arlima-Version') || '').split('__')[0];
                    if( version ) {
                        if( _this.backendVersion && _this.backendVersion != version ) {
                            _this.displayNewVersionMessage(version);
                            setInterval(function() {
                                _this.displayNewVersionMessage(version);
                            }, 150000);
                        }
                        _this.backendVersion = version;
                    }

                    if( $body )
                        $body.removeClass('wait-loading');

                    if(!json || json == -1) {
                        alert(ArlimaJS.lang.loggedOut);
                        json = false;
                    }
                    else if(json.error) {
                        alert(json.error);
                        json = false;
                    }

                    if(typeof callback == 'function') {
                        callback(json);
                    }
                },
                error : function(err, xhr) {
                    $body.removeClass('wait-loading');
                    if(err.status == 0) {
                        log('The request is refused by browser, most probably because '+
                            'of fast reloading of the page before ajax call was completed', 'warn');
                        return;
                    }

                    var mess = err.responseText;
                    if(typeof JSON != 'undefined') {
                        var json = false;
                        try {
                            json = JSON.parse(mess);
                        } catch(e) { }

                        if(json && typeof json.error != 'undefined')
                            mess = json.error;
                    }
                    alert("ERROR:\n------------\n"+mess);
                    log(err, 'error');
                    log(xhr, 'error');
                    if(typeof callback == 'function')
                        callback(false);
                }
            });
        }

    };

})(jQuery, ArlimaUtils, ArlimaJS);