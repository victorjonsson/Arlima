/**
 * Upload images using arlima and plupload
 */
var ArlimaImageUploader = (function($, window, ArlimaArticleForm, ArlimaJS, plupload) {

    'use strict';

    var acceptedTypes = ['jpeg', 'jpg', 'gif', 'png'],
        maxMegaBytes = 5,

        _this = {

        $notifyElement : false,

        $mainUploadElement : false,

        showPreloader : function() {
            var html = '<i class="fa fa-cog fa-spin large fa-5x"></i>';
            this.$mainUploadElement.find('img').css('opacity', '0.75');
            if( !this.$notifyElement )
                this.showNotice(html);
            else
                this.$notifyElement.html(html);
        },

        removeNotice : function() {
            if( this.$notifyElement ) {
                this.$mainUploadElement.find('img').css('opacity', '1');
                this.$notifyElement.fadeOut('slow', function() {
                    $(this).remove();
                });
                this.$notifyElement = false;
            }
        },

        showNotice : function(content) {
            if( !this.$notifyElement ) {
                this.$notifyElement = $('<div class="file-progress"></div>');
                this.$notifyElement.appendTo(this.$mainUploadElement);
            }

            if( content.indexOf('<') !== 0 ) {
                content = '<p>'+content+'</p>';
            }

            this.$notifyElement.html('').append(content);
        },

        init : function($uploadElem, dropZoneID) {

            if (!('FileReader' in window) )
                return;

            this.$mainUploadElement = $uploadElem.find('.image');

            var uploader = new plupload.Uploader({
                runtimes : 'html5,html4',
                drop_element : dropZoneID,
                container : 'fake-container',
                dragdrop : true
            });

            uploader.bind('Init', function(up, params) {
                if (uploader.features.dragdrop) {
                    var $target = $("#"+dropZoneID);
                    $target[0].ondragover = function(event) {
                        event.dataTransfer.dropEffect = "copy";
                    };

                    $target[0].ondragenter = function() {
                        $target.addClass("dragover");
                    };

                    $target[0].ondragleave = function() {
                        $target.removeClass("dragover");
                    };

                    $target[0].ondrop = function() {
                        $target.removeClass("dragover");
                    };
                }
            });

            uploader.bind('FilesAdded', function(up, files) {

                var denied = true;
                $.each(acceptedTypes, function(i, imgType) {
                    if( files[0].native.type.toLowerCase().indexOf(imgType) > -1 ) {
                        denied = false;
                        return false;
                    }
                });

                // Validate file
                if( denied ) {
                    _this.showNotice('File was not an image');
                    setTimeout(function() {
                        _this.removeNotice();
                    }, 2000);
                    return;
                }

                if( (maxMegaBytes * 1024 * 1024) < files[0].native.size ) {
                    _this.showNotice('Image is to big (max '+maxMegaBytes+' MB)');
                    setTimeout(function() {
                        _this.removeNotice();
                    }, 2000);
                    return;
                }


                var reader = new window.FileReader();

                // Upload file once its in memory
                reader.onloadend = function () {
                    var content = reader.result;
                    if( content.indexOf('data:') === 0 ) {
                        content = content.substr(content.indexOf(',')+1);
                    }
                    _this.showPreloader();
                    window.ArlimaBackend.saveImage(
                        content,
                        ArlimaArticleForm.article ? ArlimaArticleForm.article.data.post:'',
                        files[0].native.name,
                        function(json) {
                            _this.removeNotice();
                            if( json )
                                ArlimaImageManager.setNewImage(json.url, json.attachment, ArlimaArticleForm.article.data.post ? true:false);
                        }
                    )
                };

                // When reader fails for some reason
                reader.onerror = function (event) {
                    var mess = '';
                    switch (event.target.error.code) {
                        case event.target.error.NOT_FOUND_ERR:
                            mess = 'File not found!';
                            break;
                        case event.target.error.NOT_READABLE_ERR:
                            mess = 'File not readable!';
                            break;
                        case event.target.error.ABORT_ERR:
                            mess = 'Aborted';
                            break;
                        default:
                            mess = 'Unkown...';
                            break;
                    }
                    _this.showNotice(mess);
                };

                // Read file into memory
                reader.readAsDataURL(files[0].native);
            });

            uploader.init();
        }
    };

    return _this;

})(jQuery, window, ArlimaArticleForm, ArlimaJS, plupload);