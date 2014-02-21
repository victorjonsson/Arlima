jQuery(function($) {

    ArlimaListContainer.init($('#list-container-body'),$('#list-container-footer'));
    ArlimaArticlePreview.init($('#arlima-preview'));
    ArlimaListLoader.init($('#list-container-header'));
    ArlimaPostSearch.init($('#arlima-post-search'));
    ArlimaFileIncludes.init($('#arlima-article-file-includes'));
    ArlimaArticleForm.init($('#article-form'), $('#article-controls'));
    ArlimaArticleConnection.init( ArlimaArticleForm.$form.find('.connection-container'), $('#article-connection') );
    ArlimaImageManager.init(ArlimaArticleForm.$form.find('.image-container'));
    ArlimaImageUploader.init(ArlimaArticleForm.$form.find('.image-container'), 'arlima-image');
    ArlimaFormBlocker.init();
    ArlimaKeyBoardShortCuts.init();
    ArlimaScheduledIntervalPicker.init($('#scheduled-interval-fancybox'));
    ArlimaArticleTemplates.init($('#arlima-custom-templates'));

    // Fix future notices in all lists
    setTimeout(function() {
        $.each(ArlimaListContainer.lists, function(i, list) {
            list.fixFutureNotices();
        });
    }, 600000);

    // Leaving the list manager
    window.onbeforeunload = function(){
        if(ArlimaListPreview.previewWindow) {
            Arlima.Manager.previewWindow.close();
        }

        var hasUnsavedList = false;
        $.each(ArlimaListContainer.lists, function(i, list) {
            if( list.hasUnsavedChanges() ) {
                ArlimaUtils.shake(list);
                hasUnsavedList = true;
            }
        });
        if( hasUnsavedList ) {
            return ArlimaJS.lang.unsaved;
        }
    };

});

// Changes in tinymce should update article
function arlimaTinyMCEChanged() {
    ArlimaArticleForm.change('input.text', ArlimaArticleForm.getEditorContent());
}

// Add qtip style
var qtipStyle = {
    name: 'dark',
    tip:true,
    padding : '1px 3px',
    fontSize: 11,
    background : '#111',
    border: {
        width: 2,
        radius: 5,
        color: '#111'
    }
};


/* * * DEPRECATED.... * * * */

// Backwards compat
var Arlima = {
    Manager : {
        addEventListener : function(when, callback) {
            jQuery(document).bind(when, callback);
        }
    },
    ArticleEditor : {
        previewElement : function() {
            return ArlimaArticlePreview.$iframeBody;
        },
        isShowingPreview : function() {
            return ArlimaArticlePreview.isVisible();
        },
        articleTemplate : function() {
            return ArlimaArticleForm.currentTemplate();
        }
    }
};