var ArlimaTinyMCE = (function($, window, ArlimaArticlePreview, ArlimaVersionManager, ArlimaArticleForm, ArlimaKeyBoardShortCuts) {

    var tinyMCECommandKeys = ArlimaKeyBoardShortCuts.getKeyList('pls');

    return {

        init : function() {

            // tinyMCe events. This will not work
            // when loading the page with tinyMCE being in HTML mode, therefor
            // we put the initiation in a interval that runs until visual mode
            // is activated
            var editorContent = '',
                onTinyMCEContentChange = function() {
                    var newContent = $.trim(ArlimaArticleForm.getEditorContent());
                    if( newContent != editorContent ) {
                        ArlimaArticleForm.change('input.text', ArlimaArticleForm.getEditorContent());
                    }
                },
                tinyMCEEventInterval = setInterval(function() {
                    if(window.tinyMCE !== undefined && window.tinyMCE.editors && window.tinyMCE.editors.length > 0) {

                        // tinymce is initiated, stop interval
                        clearInterval(tinyMCEEventInterval);

                        // Capture initial content to determine when content change onkeyup
                        window.tinyMCE.editors[0].onSetContent.add(function() {
                            editorContent = $.trim(ArlimaArticleForm.getEditorContent());
                        });

                        // Trigger change in form when content changes
                        window.tinyMCE.editors[0].onKeyUp.add(onTinyMCEContentChange);

                        // listen to keyboard short cuts
                        window.tinyMCE.editors[0].onKeyDown.add(function(editor, evt) {
                            if( ArlimaKeyBoardShortCuts.call(evt, tinyMCECommandKeys) ) {
                                evt.preventDefault();
                                return false;
                            }
                        });
                    }
                }, 500);

            $('#tinyMCE').on('keyup', onTinyMCEContentChange);

        }

    }


})(jQuery, window, ArlimaArticlePreview, ArlimaVersionManager, ArlimaArticleForm, ArlimaKeyBoardShortCuts);