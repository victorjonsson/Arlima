var ArlimaTinyMCE = (function($, window, ArlimaArticlePreview, ArlimaVersionManager, ArlimaArticleForm, ArlimaKeyBoardShortCuts) {

    var tinyMCECommandKeys = ArlimaKeyBoardShortCuts.getKeyList('pls');

    return {

        /**
         * @returns {Boolean}
         */
        hasActiveEditor : function() {
            return window.tinyMCE && window.tinyMCE.activeEditor && !window.tinyMCE.activeEditor.isHidden();
        },

        /**
         * @returns {String}
         */
        getEditorContent : function() {
            if(this.hasActiveEditor())
                return window.tinyMCE.activeEditor.getContent();
            else
                return $('#tinyMCE').val();
        },

        /**
         * @param {String} content
         */
        setEditorContent : function(content) {
            if(this.hasActiveEditor())
                window.tinyMCE.activeEditor.setContent(content);
            else
                $('#tinyMCE').val( content );
        },

        init : function() {

            // tinyMCe events. This will not work
            // when loading the page with tinyMCE being in HTML mode, therefor
            // we put the initiation in a interval that runs until visual mode
            // is activated
            var _this = this,
                editorContent = '',
                onTinyMCEContentChange = function() {
                    var newContent = $.trim(ArlimaArticleForm.getEditorContent());
                    if( newContent != editorContent ) {
                        ArlimaArticleForm.change('input.text', ArlimaArticleForm.getEditorContent());
                    }
                },
                tinyMCEEventInterval = setInterval(function() {
                    if( _this.hasActiveEditor() ) {

                        // tinymce is initiated, stop interval
                        clearInterval(tinyMCEEventInterval);

                        // Capture initial content to determine when content change onkeyup
                        window.tinyMCE.editors[0].onSetContent.add(function() {
                            editorContent = $.trim(ArlimaArticleForm.getEditorContent());
                        });

                        // Trigger change in form when content changes
                        window.tinyMCE.editors[0].onKeyUp.add(onTinyMCEContentChange);
                        window.tinyMCE.editors[0].onChange.add(onTinyMCEContentChange);

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