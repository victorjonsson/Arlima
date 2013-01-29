(function($, ArlimaJSAdmin) {

    var sanitize = function(str) {
        return str.replace(/\&nbsp\;/g, '');
    };

    var findArticleID = function( $el ) {
        var id = false;
        var classes = $el.closest('.arlima-editable-article').attr('class');
        $.each(classes.split(' '), function(i, val) {
            if( val.indexOf('article-') == 0 ) {
                id = val.substr(8);
                if($.isNumeric(id) ) {
                    return false;
                }
                id = false;
            }
        });
        return id;
    };

    var updateArticle = function(update, data) {
        if( !$.isEmptyObject( update ) ) {

            var args = {
                action : 'arlima_update_article',
                ala_id : findArticleID( data.$el),
                _ajax_nonce : ArlimaJSAdmin.arlimaNonce,
                update : update
            };

            if( args.ala_id ) {
                $.ajax({
                    url : ArlimaJSAdmin.ajaxurl,
                    type : 'POST',
                    dataType : 'json',
                    data : args,
                    success : function( json ) {
                        if( !json || json.error ) {
                            throw new Error( json.error );
                        }
                        console.log(json);
                    }
                });
            }
            else {
                alert('Your arlima templates does not support editing');
            }
        }
    };

    var makeEditable = function(querySelector, elemType) {
        $(querySelector)
            .editable({
                event: 'none',
                toggleFontSize : elemType == 'title',
                callback : function( data ) {
                    if( elemType == 'title' ) {
                        var update = {};
                        if( data.content ) {
                            update.title = sanitize(data.content);
                        }
                        if( data.fontSize ) {
                            update.title_fontsize = data.fontSize;
                        }
                        updateArticle(update, data);
                    }
                    else if( data.content ) {
                        updateArticle({text : sanitize(data.content)}, data);
                    }
                }
            })
            .on('mousemove', function(e) {
                var $elem = $(this);
                if( (e.ctrlKey || e.metaKey) &&
                    $elem.attr('data-about-to-edit') === undefined &&
                    !$elem.is(':editing')
                ) {
                    $elem
                        .attr('data-about-to-edit', 1)
                        .css('background-color', 'lightyellow');
                }
            })
            .on('mouseleave', function() {
                $(this)
                    .removeAttr('data-about-to-edit')
                    .css('background', 'none');
            })
            .on('edit', function() {
                $(this)
                    .removeAttr('data-about-to-edit')
                    .removeAttr('style')
                    .css('background', 'none');
            })
            .on('mousedown', function(e) {
                if( e.ctrlKey || e.metaKey ) {
                    $(this).editable('open');
                    return false;
                }
            })
            .on('click', function() {
                if( $(this).is(':editing') ) {
                    return false;
                }
            });
    };

    makeEditable('.arlima-ice-title a', 'title');
    makeEditable('.arlima-ice-content', 'text');

})(jQuery, ArlimaJSAdmin);