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
                preventKeyboardShortCuts = function(editor, evt) {
                    var eventObj = (evt || editor);
                    if( ArlimaKeyBoardShortCuts.call(eventObj, tinyMCECommandKeys) ) {
                        eventObj.preventDefault();
                        return false;
                    }
                    return true;
                },
                pullOutEditorContent = function(evt) {
                    if( !evt.selection ) {
                        editorContent = $.trim(_this.getEditorContent());
                    }
                },
                onTinyMCEContentChange = function() {
                    var newContent = $.trim(_this.getEditorContent());
                    if( newContent != editorContent ) {
                        ArlimaArticleForm.change('input.text', _this.getEditorContent(), true);
                        editorContent = newContent;
                    }
                },
                tinyMCEEventInterval = setInterval(function() {
                    if( _this.hasActiveEditor() ) {

                        // tinymce is initiated, stop interval
                        clearInterval(tinyMCEEventInterval);

                        if( window.tinyMCE.majorVersion > 3 ) {
                            window.tinyMCE.editors[0].on('setContent', pullOutEditorContent);
                            window.tinyMCE.editors[0].on('change', onTinyMCEContentChange);
                            window.tinyMCE.editors[0].on('keyUp', onTinyMCEContentChange);
                            window.tinyMCE.editors[0].on('keyDown', preventKeyboardShortCuts);
                        } else {
                            window.tinyMCE.editors[0].onSetContent.add(pullOutEditorContent);
                            window.tinyMCE.editors[0].onKeyUp.add(onTinyMCEContentChange);
                            window.tinyMCE.editors[0].onChange.add(onTinyMCEContentChange);
                            window.tinyMCE.editors[0].onKeyDown.add(preventKeyboardShortCuts);
                        }


                    }
                }, 500);

            $('#tinyMCE').on('keyup', onTinyMCEContentChange);

        }

    }


})(jQuery, window, ArlimaArticlePreview, ArlimaVersionManager, ArlimaArticleForm, ArlimaKeyBoardShortCuts);