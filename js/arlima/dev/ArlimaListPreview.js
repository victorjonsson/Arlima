var ArlimaListPreview = (function($, window, ArlimaUtils, ArlimaArticleConnection) {

    var $mainWindow = $(window),
        _this = {

        /**
         * @var {ArlimaList}
         */
        list : null,

        /**
         * @var {Window}
         */
        previewWindow : false,

        /**
         * @param {ArlimaList} list
         */
        preview : function(list) {
            if( !list.data.previewURL ) {
                alert(ArlimaJS.lang.missingPreviewPage);
                return;
            }

            if( !list.hasUnsavedChanges() ) {
                // add preview arg only so that Arlima adds jQuery to page
                var listURL = _appendPreviewArgToURL(list.data.previewURL, new Date().getTime(), '_');
                _putWindowInfront(_createWindow(listURL));
                return;
            }

            list.toggleAjaxPreLoader(true);
            this.list = list;

            if( this.previewWindow ) {
                try {
                    this.previewWindow.close();
                } catch(e) {
                    // This actually can happen....
                }
            }

            window.ArlimaBackend.savePreview(list.data.id, list.getArticleData(), function(json) {
                _this.list.toggleAjaxPreLoader(false);
                if(json) {

                    var url = _appendPreviewArgToURL(list.data.previewURL, list.data.id);
                    _this.previewWindow = _createWindow(url, function(previewjQuery) {

                        _addSaveMessage(previewjQuery('body'));

                        previewjQuery(_this.previewWindow.document).keydown(function(e) {
                            var key = e.keyCode ? e.keyCode : e.which;
                            if(key == 83 && ArlimaUtils.hasMetaKeyPressed(e)) {
                                _this.list.save();
                                _this.previewWindow.close();
                                window.focus();
                                _this.previewWindow = false;
                                e.preventDefault();
                                return false;
                            }
                        });

                        if( ArlimaArticleConnection.currentPost && ArlimaArticleConnection.currentPost.ID ) {
                            var $editedArticle = previewjQuery("*[data-post='" + ArlimaArticleConnection.currentPost.ID + "']").first();
                            if( $editedArticle.length > 0 ) {
                                // Add 80px to prevent the "save by ctrl + s"-bar covering the teaser
                                previewjQuery(_this.previewWindow).scrollTop( $editedArticle.position().top - 80 );
                            }
                        }
                    });

                    _putWindowInfront(_this.previewWindow);
                }
            });
        }
    };

    var _createWindow = function(url, onloadCallback) {
        var name = 'arlima'+(new Date().getTime());
        var win = window.open(url, name, 'toolbar=1,scrollbars=1,width=10,height=10');

        if( !win ) {
            alert('Your browser has blocked the pop-up window!');
        } else {
            var loadCheckInterval = setInterval(function() {
                if( win.jQuery ) {
                    clearInterval(loadCheckInterval);
                    win.jQuery(function($) {
                        $('body').css('margin-top', '-32px');
                        $('#wpadminbar').remove();
                        if( typeof onloadCallback == 'function' ) {
                            onloadCallback($);
                        }
                    });
                }
            }, 300);
        }
        return win;
    };

    var _putWindowInfront = function(win) {
        if( win ) {
            win.resizeTo($mainWindow.width(), $mainWindow.height());
            win.focus();
        }
    };

    var _appendPreviewArgToURL = function(url, preview, arg) {
        arg = arg || window.ArlimaJS.previewQueryArg;
        return url +(url.indexOf('?') > -1 ? '&':'?')+ arg +'='+preview;
    };

    var _addSaveMessage = function($windowBody) {
        var $div = $('<div></div>');
        $div
            .css({
                position: 'fixed',
                top: '20px',
                left: '0',
                width: '100%',
                zIndex : '9999'
            })
            .appendTo($windowBody);

        var ctrlKey = window.navigator.userAgent.indexOf('Mac') == -1 ? 'ctrl':'cmd';
        $('<div></div>')
            .text(ctrlKey+' + s '+window.ArlimaJS.lang.savePreview+' "'+_this.list.data.title+'"')
            .css({
                background: '#222',
                backgroundColor: 'rgba(0,0,0, .85)',
                fontSize : '13px',
                color: '#FFF',
                margin: '16px',
                padding : '10px',
                webkitborderRadius : '12px',
                mozBorderRadius : '13px',
                borderRadius : '12px',
                fontWeight:'bold',
                webkitBoxShadow : '0 0 7px #333',
                mozBoxShadow : '0 0 7px #333',
                boxShadow : '0 0 7px #333'
            })
            .appendTo($div);

    };

    return _this;

})(jQuery, window, ArlimaUtils, ArlimaArticleConnection);