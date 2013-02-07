/**
 * Upload images using arlima and plupload
 */
var ArlimaUploader = (function($, window, Arlima, ArlimaJS, plupload) {

    return {

        $notifyElement : false,

        $mainUploadElement : false,

        _mainDropElementID : 'arlima-article-image',

        removeNotifier : function() {
            if( this.$notifyElement ) {
                this.$notifyElement.remove();
                var _self = this;
                setTimeout(function() {
                    _self.$notifyElement = false;
                }, 900);
            }
        },

        init : function() {
            //var $ = jQuery.noConflict();
            this.$mainUploadElement = $('#'+this._mainDropElementID);

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

            var _self = this,
                showNotice = function(mess, id) {
                    if( !_self.$notifyElement ) {
                        _self.$notifyElement = $('<div class="file-progress"></div>');
                        _self.$notifyElement.appendTo(_self.$mainUploadElement);
                    }
                    var $message;
                    if( id ) {
                        $message = _self.$notifyElement.find('#'+id);
                        if( $message.length == 0 ) {
                            $message = $('<p></p>')
                                        .attr('id', id)
                                        .appendTo(_self.$notifyElement);
                        }
                    } else {
                        $message = $('<p></p>');
                        $message.appendTo(_self.$notifyElement);
                    }
                    $message.text(mess);
                };

            uploader.init();

            uploader.bind('FileUploaded', function(up, file, res) {
                _self.removeNotifier();
                var json = $.parseJSON(res.response);
                var args = { html : json.html, size : 'full', attach_id : json.attach_id, connected:1 };
                Arlima.ArticleEditor.updateArticleImage(args);
                Arlima.ArticleEditor.updateArticle();

                // Connect attachment to post
                var post = Arlima.ArticleEditor.$item.data('article').post_id;
                if( post ) {
                    Arlima.Backend.connectAttachmentToPost(post, json.attach_id);
                }
            });

            uploader.bind('FilesAdded', function(up, files) {
                uploader.settings.multipart_params.postid = $('#arlima-article-post_id').val();
                $.each(files, function(i, file) {
                    showNotice(file.name.substring(0, 10) + ' ' + plupload.formatSize(file.size), 'file-'+file.id);
                });
                up.refresh(); // Reposition Flash/Silverlight
                up.start();
            });

            uploader.bind('UploadProgress', function(up, file) {
                showNotice(file.name.substring(0, 10) + ' ' + file.percent + "%", 'file-'+file.id);
            });

            uploader.bind('Error', function(up, err) {
                showNotice(
                    'Error: ' + err.code + ", Message: " + err.message +
                    (err.file ? ", File: " + err.file.name : "")
                );
                up.refresh(); // Reposition Flash/Silverlight
            });

            uploader.bind('FileUploaded', function(up, file) {
                showNotice(file.name.substring(0, 10) + ' 100%', 'file-'+file.id);
            });

            this.makeDropable(this.$mainUploadElement);
        },

        makeDropable : function($element) {
            if( $element.attr('data-dropzone') == 'enabled')
                return;

            var _self = this;

            $element
                .attr('data-dropzone', 'enabled')
                .bind('dragenter', function() {
                    _self.$mainUploadElement.height( _self.$mainUploadElement.height() );
                    _self.$mainUploadElement.children().hide();
                    _self.$mainUploadElement.addClass('empty');
                    _self.$mainUploadElement.addClass('dragover');
                    return false;
                })
                .bind('dragleave', function() {
                    var $children = _self.$mainUploadElement.children();
                    if( $children.length > 0 ) {
                        $children.show();
                        _self.$mainUploadElement.removeClass('empty');
                    }
                    _self.$mainUploadElement.css('height', 'auto');
                    _self.$mainUploadElement.removeClass('dragover');
                })
                .bind('drop', function(ev) {

                    _self.$mainUploadElement.css('height', 'auto');
                    _self.$mainUploadElement.removeClass('dragover');

                    // this is for images dragged from the browser, not from the filesystem.
                    ev.preventDefault();
                    var dt = ev.originalEvent.dataTransfer;

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
                    if( !url || (url.toLowerCase().indexOf('.jpg') == -1 && url.toLowerCase().indexOf('.png') == -1) ) url = dt.getData('text/x-moz-url');
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
        }
    };

})(jQuery, window, Arlima, ArlimaJS, plupload);