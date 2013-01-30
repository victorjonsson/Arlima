jQuery(document).ready(function($) {

    //
    // Send post to arlima list
    //
    $('#arlima-send-to-list-btn').click( function(e) {

        var $metabox = $('#arlima-meta-box');
        var alid = $('#arlima-listid').val();
        var postid = $('#arlima-postid').val();
        var list = $("#arlima-listid option:selected").text();

        if( !confirm(ArlimaJSAdmin.lang.notice) ) return false;

        $('.ajax-loader', $metabox).show();

        var data = {
            action: 'arlima_prepend_article',
            alid: alid,
            postid: postid,
            _ajax_nonce : ArlimaJSAdmin.arlimaNonce
        };

        $.post( ajaxurl, data, function(json) {
            $('.ajax-loader', $metabox).hide();
            $('.inside', $metabox).append('<p>'+ArlimaJSAdmin.lang.wasSentTo+' &quot;'+ list + '&quot;</p>');
        }, 'json');

    });

    //
    // Navigate between lists on edit list page
    //
    var $arlimaEditLink = $('#arlima-edit');
    $('#arlima-lists').change(function() {
        if( this.value ) {
            $arlimaEditLink.attr('href', 'admin.php?page=arlima-main&open_list='+this.value);
            $arlimaEditLink.show();
        } else {
            $arlimaEditLink.hide();
        }
    }).trigger('change');

    //
    // Remove image versions from attachment editor
    //
    $('#delete-arlima-versions').bind('click', function() {
        var $link = $(this);
        var data = {
            action: 'arlima_remove_image_versions',
            attachment: $(this).attr('data-post-id'),
            _ajax_nonce : ArlimaJSAdmin.arlimaNonce
        };

        $.post( ajaxurl, data, function(json) {
            if( json.error ) {
                alert(json.error);
            } else {
                $link.parent().remove();
                $('#arlima-versions').hide();
                $('#arlima-no-versions-info').show();
            }
        }, 'json');
    });
});

