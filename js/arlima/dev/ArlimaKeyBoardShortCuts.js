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
        interpretCommand : function(e, keyList) {
            var key = e.keyCode || e.which;
            if( (e.ctrlKey || e.metaKey) && $.inArray(key, keyList) > -1 ) {
                return key;
            }
            return false;
        }
    };

    return {

        init : function() {

            var allCommandKeys = shortCuts.getKeyList('plszy'),
                tinyMCECommandKeys = shortCuts.getKeyList('pls');

            $(document).bind('keydown', function(evt) {
                var key = shortCuts.interpretCommand(evt, allCommandKeys);
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
                                return true; // don't prevent native undo/redo in inputs
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
                                return true; // don't prevent native undo/redo in inputs
                            }

                            break;
                    }

                    evt.preventDefault();
                    return false;
                }
            });


            // tinyMCe events. This will not work
            // when loading the page with tinyMCE being in HTML mode, therefor
            // we put the initiation in a interval that runs until visual mode
            // is activated
            var tinyMCEEventInterval = setInterval(function() {
                if(window.tinyMCE !== undefined) {

                    // tinymce is initiated, stop interval
                    clearInterval(tinyMCEEventInterval);

                    // We have editors
                    if(window.tinyMCE.editors && window.tinyMCE.editors.length > 0) {

                        // listen to keyboard short cuts
                        window.tinyMCE.editors[0].onKeyDown.add(function(editor, e) {
                            var key = e.keyCode || e.which;
                            if( key == 32 || key == 190 ) {
                                // space or . should trigger an update immediately
                                window.arlimaTinyMCEChanged();
                            }
                            else if( (key = shortCuts.interpretCommand(e, tinyMCECommandKeys)) ) {

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
                                }

                                e.preventDefault();
                                return false;
                            }
                        });
                    }
                }
            }, 500);
        }

    };

})(jQuery, window, ArlimaArticlePreview, ArlimaVersionManager, ArlimaArticleForm);