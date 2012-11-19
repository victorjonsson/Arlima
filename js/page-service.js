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
 * @param {String} url
 * @param {jQuery} $form
 */
function removeImportedList(url, $form) {
    $form.append('<input type="hidden" name="remove" value="'+url+'" />');
    $form.get(0).submit();
}