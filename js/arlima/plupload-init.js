function initPlUopload() {

    var $ = jQuery.noConflict();

    $('#arlima-article-image')

        // Update the drop zone class on drag enter/leave
        .bind('dragenter', function(ev) {
            $(ev.target).addClass('dragover');
            return false;
        })
        .bind('dragleave', function(ev) {
            $(ev.target).removeClass('dragover');
            return false;
        })

        // Allow drops of any kind into the zone.
        .bind('dragover', function(ev) {
            return false;
        })

        // Handle the final drop...
        .bind('drop', function(ev) {

            // this is for images dragged from the browser, not from the filesystem.
            ev.preventDefault();
            var dt = ev.originalEvent.dataTransfer;

            var types = [];
            for (var i=0,type; type=dt.types[i]; i++)
                types.push(type);

            // uploading from filesystem, return false and let plupload handle it instead
            try {
                if(dt.types.indexOf('Files') != -1){
                    return false;
                }
            } catch(e){}

            try {
                if(dt.types.contains("application/x-moz-file")){
                    return false;
                }
            } catch(e){}

            var url = dt.getData('URL');
            if( !url || (url.toLowerCase().indexOf('.jpg') == -1 && url.toLowerCase().indexOf('.png') == -1) ) url = dt.getData('text/x-moz-url')
            if( !url || (url.toLowerCase().indexOf('.jpg') == -1 && url.toLowerCase().indexOf('.png') == -1) ) {
                var html = dt.getData('text/html');
                var tmp = $(html);
                if( $(tmp).is('img') )
                    url = $(tmp).attr('src');
                else if( $('img', tmp).length > 0 )
                    url = $('img', tmp).attr('src');
                else
                    url = null;
            }
            if( !url || (url.toLowerCase().indexOf('.jpg') == -1 && url.toLowerCase().indexOf('.png') == -1) ) return false;
            if(url.indexOf('http://trip/') === 0 || url.indexOf('http://trip.vk.se/') === 0) {
                url = url.replace('/preview/', '/hires/');
            }

            Arlima.Backend.plupload(url, $('#arlima-article-post_id').val(), function(json) {
                var args = { html : json.html, size : 'full', attach_id : json.attach_id };
                Arlima.ArticleEditor.updateArticleImage(args);
                Arlima.ArticleEditor.updateArticle();
            });

            ev.stopPropagation();
            return false;
        });

    var uploader = new plupload.Uploader({
        runtimes : 'html5',
        container : 'arlima-article-image-container',
        max_file_size : '10mb',
        url : ArlimaJS.ajaxurl,
        drop_element : 'arlima-article-image',
        filters : [
            {title : "Image files", extensions : "jpg,gif,png,jpeg"}
        ],
        browse_button: 'arlima-article-image-browse',
        multi_selection: false,
        multipart : true,
        multipart_params : {
            action: 'arlima_upload',
            postid : null,
            _ajax_nonce : ArlimaJS.arlimaNonce
        }
    });

    uploader.bind('FileUploaded', function(up, file, res) {
        var json = $.parseJSON(res.response);
        var args = { html : json.html, size : 'full', attach_id : json.attach_id };
        Arlima.ArticleEditor.updateArticleImage(args);
        Arlima.ArticleEditor.updateArticle();
    });

    uploader.init();

    uploader.bind('FilesAdded', function(up, files) {
        uploader.settings.multipart_params.postid = $('#arlima-article-post_id').val();
        $.each(files, function(i, file) {
            $('#arlima-article-image').append(
                '<div id="' + file.id + '" class="file-progress">' +
                    file.name.substring(0, 10) + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
                    '</div>');
        });
        up.refresh(); // Reposition Flash/Silverlight
        up.start();
    });

    uploader.bind('UploadProgress', function(up, file) {
        $('#' + file.id + " b").html(file.percent + "%");
    });

    uploader.bind('Error', function(up, err) {
        $('#arlima-article-image').append("<div>Error: " + err.code +
            ", Message: " + err.message +
            (err.file ? ", File: " + err.file.name : "") +
            "</div>"
        );

        up.refresh(); // Reposition Flash/Silverlight
    });

    uploader.bind('FileUploaded', function(up, file) {
        $('#' + file.id + " b").html("100%");
    });
}