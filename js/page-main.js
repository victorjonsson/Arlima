jQuery(function($) {

    // Load list by url
    if( window.loadArlimaListOnLoad ) {
        $(window).on('arlimaListSetupLoaded', function() {
            ArlimaListLoader.addListToContainer(window.loadArlimaListOnLoad);
        });
    }

    // Initiate all Arlima components
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
    ArlimaTinyMCE.init();
    ArlimaScheduledIntervalPicker.init($('#scheduled-interval-fancybox'));
    ArlimaArticlePreset.init($('#arlima-article-presets'));
    ArlimaFormBuilder.init($('#article-form .file-include-container'));

    // Fix future notices in all lists
    setInterval(function() {
        $.each(ArlimaListContainer.lists, function(i, list) {
            list.fixFutureNotices();
        });
    }, 300000);

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

    // Very unsophisticated Jquery plugin for blinking effect
    $.fn.blink = function(clearAfter) {
        var _this = this;
        this.animate({
            opacity: 0.1
        },'fast', function() {
            _this.animate({
                opacity: 1
            },'fast', function() {
                _this.animate({
                    opacity: 0.1
                },'fast', function() {
                    _this.animate({
                        opacity: 1
                    },'fast', function() {
                        if( clearAfter ) {
                            setTimeout(function() {
                                _this.fadeOut(function() {
                                    _this.html('').fadeIn();
                                })
                            }, clearAfter);
                        }
                    });
                });
            });
        });
        return this;
    };

    // Scheduled lists count-down auto reload
    $(document).on('versionInfoLoadedzzz', function() {
        var $articleLists = $('.article-list');

        // Init count down on scheduled lists
        $.each($articleLists, function(i, list) {
            var seconds = $(this).data('schedule-countdown'),
            $thisList = $(this);

            if(typeof(seconds) != 'undefined' && seconds > 0 && !$thisList.hasClass('counting')) {

                !$thisList.hasClass('counting') ? $thisList.addClass('counting') : '';

                var timer = setInterval(function() {
                    console.log('iun here '+parseInt(ArlimaJS.scheduledListReloadTime)+' >= '+$thisList.attr('data-schedule-countdown'));
                    $thisList.attr('data-schedule-countdown', --seconds);
                    // Show notice
                    if($thisList.attr('data-schedule-countdown') <= parseInt(ArlimaJS.scheduledListReloadTime) ) {
                        console.log('iun here also');
                        $thisList.find('.schedule-notice')
                                .html(' ('+ ArlimaJS.lang.willReload +' '+ seconds +')');
                    }

                    if (seconds == 0) {
                        console.log('cleared');
                       clearInterval(timer);
                       $thisList.find('.schedule-notice').html('');
                       ArlimaListContainer.list($thisList.data('list-id')).reload();
                    }
                }, 1000);
            }
        });
    });
});

if( ArlimaJS.sendJSErrorsToServerLog ) {
    window.onerror = function(errMessage, url, lineNumber) {
        ArlimaBackend.logJSError(errMessage, new Error().stack, url, lineNumber);
        return false;
    };
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