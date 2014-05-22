var ArlimaKeyBoardShortCuts = (function($, window, ArlimaArticlePreview, ArlimaVersionManager, ArlimaArticleForm) {

    'use strict';

    var shortCuts = {
        p : {
            key : 80,
            run : function() {
                if( ArlimaArticleForm.article ) {
                    ArlimaArticlePreview.toggle();
                }
            }
        },
        s : {
            key : 83,
            run : function() {
                var list = window.ArlimaUtils.getUnsavedListInFocus();
                if( list )
                    list.save();
            }
        },
        l : {
            key : 76,
            run : function() {
                var list = window.ArlimaUtils.getUnsavedListInFocus();
                if( list ) {
                    list.preview();
                } else if( ArlimaArticleForm.article ) {
                    window.ArlimaListContainer.list(ArlimaArticleForm.article.listID).preview();
                }
            }
        },
        y : {
            key : 89,
            run : function() {
                if( ArlimaArticleForm.article ) {
                    ArlimaVersionManager.stepForward();
                }
            }
        },
        z :  {
            key : 90,
            run : function() {
                if( ArlimaArticleForm.article ) {
                    ArlimaVersionManager.stepBack();
                }
            }
        },

        /**
         * Looks at the keyUp event and returns which type of command that should be invoked
         * @param e
         * @param keyList
         * @returns {*}
         */
        getCommandFromEvent : function(e, keyList) {
            var key = e.keyCode || e.which;
            if( (e.ctrlKey || e.metaKey) && $.inArray(key, keyList) > -1 ) {
                return key;
            }
            return false;
        }
    };

    return {

        /**
         * Executes commands from key list if given event indicates that
         * one of the commans should be invoked.
         * @param evt
         * @param keyList
         * @returns {boolean} Returns true if a command was invoked
         */
        call : function(evt, keyList) {
            var key = shortCuts.getCommandFromEvent(evt, keyList);
            if( key ) {
                switch (key) {
                    case shortCuts.p.key:
                        shortCuts.p.run();
                        break;
                    case shortCuts.s.key:
                        shortCuts.s.run();
                        break;
                    case shortCuts.l.key:
                        shortCuts.l.run();
                        break;
                    case shortCuts.y.key:
                        if( !evt.target || evt.target.nodeName != 'INPUT' ) {
                            shortCuts.y.run();
                        } else {
                            return false; // don't prevent native undo/redo in inputs
                        }
                        break;
                    case shortCuts.z.key:
                        if( !evt.target || evt.target.nodeName != 'INPUT' ) {
                            if( evt.shiftKey ) {
                                shortCuts.y.run();
                            } else {
                                shortCuts.z.run();
                            }
                        } else {
                            return false; // don't prevent native undo/redo in inputs
                        }

                        break;
                }

                return true;
            }

            return false;
        },

        getKeyList : function(letters) {
            var keys = [];
            if( !letters ) {
                $.each(this, function(i, obj) {
                    if( typeof obj != 'function' ) {
                        keys.push(obj.key);
                    }
                });
            } else {
                $.each(letters.split(''), function(i, letter) {
                    keys.push(shortCuts[letter].key);
                });
            }
            return keys;
        },

        init : function() {
            var _this = this,
                allCommandKeys = this.getKeyList('plszy');
            $(window.document).bind('keydown', function(evt) {
                if( _this.call(evt, allCommandKeys) ) {
                    evt.preventDefault();
                    return false;
                }
            });
        }

    };

})(jQuery, window, ArlimaArticlePreview, ArlimaVersionManager, ArlimaArticleForm);