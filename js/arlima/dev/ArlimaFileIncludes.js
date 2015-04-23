var ArlimaFileIncludes= (function($, window, ArlimaUtils, ArlimaFormBuilder) {

    return {

        $elem : false,

        getFormFieldsDefinition : function(file) {
            file = file.replace('//', '/'); // legacy fix
            return $.extend(true, {}, this.$elem.find('.file-include[data-file="'+file+'"]').get(0).arlimaFormFields);
        },


        parseQueryString : function(query) {
            var obj = {};
            $.each( query.split('&'), function(i, param) {
                obj[param.split('&')[0]] = param.split('&')[1];
            });
            return obj;
        },


        /* * * * * *  INIT * * * * * */

        init : function($elem) {

            this.$elem = $elem;

            ArlimaUtils.makeCollapsing($elem);

            this.$elem.find('.file-include').each(function(i, fileElement) {

                var $file = $(fileElement),
                    fileArgs = $.parseJSON($file.attr('data-args'));

                // Create arlima article object, monkey pathc
                fileElement.arlimaArticle = new ArlimaArticle({
                    title : $file.data('label'),
                    options : {
                        fileInclude : $(fileElement).data('file')
                    }
                });

                // Parse args into field definitions.. and monkey patch
                $.each(fileArgs, function(name, val) {
                    var field = {
                            type : ArlimaFormBuilder.TYPES.TEXT,
                            property : name,
                            value : val,
                            width : '80%',
                            label : {
                                text : name
                            }
                        };

                    if( typeof val == 'boolean' ) {
                        field.type = ArlimaFormBuilder.TYPES.BOOL;
                    } else if($.isNumeric(val)) {
                        field.type = ArlimaFormBuilder.TYPES.NUMBER;
                    } else if( typeof val !== 'string' ) {
                        field = val;
                    }

                    fileArgs[name] = $.extend(true, {}, ArlimaFormBuilder.defaultFieldDefinition, field);
                });

                fileElement.arlimaFormFields = fileArgs;


                // Make draggable
                $file.draggable({
                    appendTo: 'body',
                    helper:'clone',
                    sender:'postlist',
                    connectToSortable:'.article-list .articles',
                    revert:'invalid',
                    start: function(event, ui) {
                        ui.helper.html('<div class="article-title-container"><span>' + ui.helper.html() + '</span></div>');
                        ui.helper.addClass('article');
                        ui.helper.css('z-index', '99999');
                    }
                });
            });

            // Make include groups collapsible
            $elem.find('.include-group thead td').click(function() {
                var $groupToggler = $(this),
                    $group = $groupToggler.closest('.include-group');

                if( $group.hasClass('open') ) {
                    $group.removeClass('open');
                    $groupToggler.closest('table').find('tbody').hide();
                } else {
                    $group.addClass('open');
                    $groupToggler.closest('table').find('tbody').show();
                }
            });
        }

    };

})(jQuery, window, ArlimaUtils, ArlimaFormBuilder);