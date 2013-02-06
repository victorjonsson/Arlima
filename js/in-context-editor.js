(function($, ArlimaJSAdmin) {

    'use strict';

    /**
     * @param {String} str
     * @returns {String}
     */
    var sanitize = function(str) {
        return str.replace(/\&nbsp\;/g, '');
    };

    /**
     * @param $el
     * @returns {Number}
     */
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

    /**
     * @param {Object} update
     * @param {Object} data
     */
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
                    }
                });
            }
            else {
                alert('Your arlima templates does not support editing');
            }
        }
    };

    /**
     * @param {String} querySelector Elements that should be editable
     * @param {String} elemType Either 'title' or 'body'
     */
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

                    data.$el
                        .removeAttr('data-about-to-edit')
                        .css('cursor', data.$el.get(0).nodeName == 'A' ? 'pointer':'default')
                        .css('background', data.$el.attr('data-default-bg'));
                }
            })
            .bind('mousemove', function(e) {
                var $elem = $(this);
                if( (e.ctrlKey || e.metaKey) &&
                    $elem.attr('data-about-to-edit') === undefined &&
                    !$elem.is(':editing')
                    ) {

                    $elem
                        .attr('data-default-bg', $elem.css('background'))
                        .attr('data-about-to-edit', 1)
                        .css('cursor', 'url('+ArlimaJSAdmin.imageurl+'/pen-icon.png?upd=2), url('+ArlimaJSAdmin.imageurl+'/pen-icon.png?upd=2), auto')
                        .css('background-color', 'lightyellow');
                }
            })
            .bind('mouseleave', function() {
                var $elem = $(this);
                if( $elem.attr('data-about-to-edit') ) {
                    $elem
                        .removeAttr('data-about-to-edit')
                        .css('cursor', this.nodeName == 'A' ? 'pointer':'default')
                        .css('background', $elem.attr('data-default-bg'));
                }
            })
            .bind('mousedown', function() {
                var $this = $(this);
                if( $this.attr('data-about-to-edit') ) {

                    $this.editable('open');
                    $this.find('textarea')
                        .css({
                            background:'none',
                            width: '100%',
                            display :'inline-block'
                        });

                    $this.trigger('mouseleave');

                    return false;
                }
            })
            .bind('click', function() {
                if( $(this).is(':editing') ) {
                    return false;
                }
            });
    };

    makeEditable('.arlima-ice-title a', 'title');
    makeEditable('.arlima-ice-title:not(:has(>a))', 'title');
    makeEditable('.arlima-ice-content', 'text');

})(jQuery, ArlimaJSAdmin);