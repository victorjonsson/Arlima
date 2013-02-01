jQuery(function($) {

    // Streamer color
    var $colorInput = $('#streamer-color');
    var $colorButton = $('#streamer-button');
    var $colorContainer = $('#streamer-wrapper');

    var addColorField = function(color) {
        color = color.toUpperCase();
        var colors = $colorContainer.attr('data-colors');
        if( colors.indexOf(color) == -1 ) {
            var $div = $('<div></div>');
            $div.append('<span style="background:#'+color+'" class="color"></span>');
            $div.append('<span style="color:#999; margin-left: 3px">#'+color+'</span>');
            $div.append('<input type="hidden" name="settings[streamer_colors][]" value="'+color+'" />');
            $div.append('<a href="#" class="del">&times;</a>');
            $colorContainer.append($div);
            $colorContainer.attr('data-colors', colors+','+color);
            $div.find('.del').click(function() {
                $(this).parent().remove();
                return false;
            })
        }
    };

    // Add new color
    $colorButton.click(function() {
        var color = $.trim($colorInput.val());
        if( color.substr(0, 1) == '#' )
            color = color.substr(1);
        if( color != '' && color.match('^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$') !== null ) {
            addColorField(color);
            $colorInput.val('');
        } else {
            alert(ArlimaJS.lang.notValidColor);
        }
    });

    // Setup existing colors
    var colors = $colorContainer.attr('data-colors').split(',');
    $colorContainer.attr('data-colors', '');
    $.each(colors, function(i, color) {
        if( color ) {
            addColorField(color);
        }
    });

    // Delete imported lists
    $('#imported-lists .del').live('click', function() {
        removeImportedList($(this));
        return false;
    });

});

var isCheckingListImport = false;
function importExternalList($input, $form) {
    var url = jQuery.trim($input.val());
    if(url) {
        if(!isCheckingListImport) {
            $input.css('opacity', 0.5);
            isCheckingListImport = true;
            jQuery.ajax({
                url : ajaxurl,
                type: 'POST',
                data : {
                    action : 'arlima_import_arlima_list',
                    type: 'do_import',
                    url : url,
                    _ajax_nonce : ArlimaJSAdmin.arlimaNonce
                },
                success : function(response) {
                    $input.css('opacity', 1);
                    isCheckingListImport = false;
                    var $html = jQuery(response);
                    if($html.hasClass('error')) {
                        alert($html.text());
                    }
                    else {
                        $input.val('');
                        $form.append($html);
                    }
                },
                error: function(a) {
                    alert('Status: '+ a.status+"\nMessage: "+ a.statusText);
                }
            });
        }
    }
}

/**
 *
 * @param {jQuery} $link
 */
function removeImportedList($link) {
    var url = $link.attr('data-link');
    var $div = $link.parent();
    $div.html('<input type="hidden" name="remove_imported[]" value="'+url+'" />');
}