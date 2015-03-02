/**
 * Upload images using arlima and plupload
 */
var ArlimaImageUploader = (function($, window, ArlimaArticleForm, ArlimaJS, plupload) {

    'use strict';

    var acceptedTypes = ['jpeg', 'jpg', 'gif', 'png'],
        maxMegaBytes = 5, _uploader,

        _addDroppableHoverEffects = function(elem) {
            elem.ondragover = function(event) {
                event.dataTransfer.dropEffect = "copy";
            };

            elem.ondragenter = function() {
                $(elem).addClass("dragover");
            };

            elem.ondragleave = function() {
                $(elem).removeClass("dragover");
            };

            elem.ondrop = function() {
                $(elem).removeClass("dragover");
            };
        },

        _onFileDropped = function(type, size, name, imgContent) {
            var denied = true;
            $.each(acceptedTypes, function(i, imgType) {
                if( type.toLowerCase().indexOf(imgType) > -1 ) {
                    denied = false;
                    return false;
                }
            });

            // Validate file
            if( denied ) {
                ArlimaImageUploader.showNotice('File was not an image');
                setTimeout(function() {
                    ArlimaImageUploader.removeNotice();
                }, 2000);
                return;
            }

            if( (maxMegaBytes * 1024 * 1024) < size ) {
                ArlimaImageUploader.showNotice('Image is to big (max '+maxMegaBytes+' MB)');
                setTimeout(function() {
                    ArlimaImageUploader.removeNotice();
                }, 2000);
                return;
            }


            var reader = new window.FileReader(),
                onContentReadyForSending = function(content) {
                    if( content.indexOf('data:') === 0 ) {
                        content = content.substr(content.indexOf(',')+1);
                    }
                    ArlimaImageUploader.showPreloader();
                    window.ArlimaBackend.saveImage(
                        content,
                        ArlimaArticleForm.article ? ArlimaArticleForm.article.data.post:'',
                        name,
                        function(json) {
                            ArlimaImageUploader.removeNotice();
                            if( json )
                                ArlimaImageManager.setNewImage(json.url, json.attachment, ArlimaArticleForm.article.data.post ? true:false);
                        }
                    )
                };

            if( typeof imgContent == 'string' ) {
                onContentReadyForSending(imgContent);
            } else {

                // Upload file once its in memory
                reader.onloadend = function () {
                    onContentReadyForSending(reader.result);
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
                    ArlimaImageUploader.showNotice(mess);
                };

                // Read file into memory
                reader.readAsDataURL(imgContent);
            }
        },

        ArlimaImageUploader = {

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

            createDropZone : function(elem) {
                if( !('mOxie' in window) )
                    return; // Not possible yet...

                var dropzone = new window.mOxie.FileDrop({
                    drop_zone: elem
                });
                new mOxie.Image();
                // When the event is fired, the context (ie, this)
                // is the actual dropzone. As such, you can access
                // the files using any of the following:
                // --
                // * this.files
                // * dropzone.files
                // * event.target.files
                dropzone.ondrop = function( event ) {
                    var preloader = new mOxie.Image();
                    preloader.onload = function() {
                        _onFileDropped(dropzone.files[0].type, dropzone.files[0].size, dropzone.files[0].name, preloader.getAsDataURL());
                    };
                    preloader.load( dropzone.files[0] );
                };

                dropzone.bind('init', function(up, params) {
                    if (_uploader.features.dragdrop) {
                        _addDroppableHoverEffects(elem);
                    }
                });

                dropzone.init();

                _addDroppableHoverEffects(elem);
            },

            init : function($uploadElem, dropZoneID) {

                if (!('FileReader' in window) )
                    return;

                this.$mainUploadElement = $uploadElem.find('.image');

                _uploader = new plupload.Uploader({
                    runtimes : 'html5,html4',
                    drop_element : dropZoneID,
                    container : 'fake-container',
                    dragdrop : true
                });

                _uploader.bind('Init', function(up, params) {
                    if (_uploader.features.dragdrop) {
                       _addDroppableHoverEffects($("#"+dropZoneID).get(0));
                    }
                });

                _uploader.bind('FilesAdded', function(up, files) {
                    return _onFileDropped(files[0].native.type, files[0].native.size, files[0].native.name, files[0].native);
                });

                _uploader.init();
            }
        };

    return ArlimaImageUploader;

})(jQuery, window, ArlimaArticleForm, ArlimaJS, plupload);