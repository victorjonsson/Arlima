var ArlimaScissors = (function($, window, ArlimaArticleForm, ArlimaBackend, ArlimaImageManager) {

    var _this = {

        init : function($btn) {

            var $imgFancyBox = $('#image-scissors-popup'),
                hasEditedImage = false,
                imgData = false;

            $btn.fancybox({
                autoResize: 1,
                fitToView: 1,
                margin: new Array(40,0,0,0),
                speedIn: 300,
                speedOut: 300,
                titlePosition: "over",
                autoDimensions : true,
                afterClose	:	function( ) {
                    if( hasEditedImage ) {
                        // Update image in form
                        var $img = ArlimaImageManager.$imageWrapper.find('img');
                        $img.attr('src', $img.attr('src')+'?'+Math.random());

                        // Remove old image versions
                        ArlimaBackend.removeImageVersions(imgData.attachment);

                        // Save a new url of the attachment on the article (to break caching of old image)
                        ArlimaImageManager.setNewImage(
                            imgData.url.split('?')[0] + '?edited=' + (new Date().getTime()),
                            imgData.attachment,
                            imgData.connected,
                            imgData.size,
                            imgData.alignment
                        );

                        if( window.ArlimaArticlePreview.isVisible() ) {
                            window.ArlimaArticlePreview.reload();
                        }
                    }
                },
                beforeLoad : function() {

                    imgData = ArlimaArticleForm.article.data.image;
                    hasEditedImage = false;

                    // Add preview image html
                    $imgFancyBox.html('<div id="media-item-'+imgData.attachment+'">' +ArlimaImageManager.$imageWrapper.html()+ '</div>');

                    // this is needed for scissors to update the image preview when crop is made
                    $imgFancyBox
                        .find('img')
                        .addClass('thumbnail')
                        .css('max-width', '200px');

                    // Load scissors html
                    ArlimaBackend.loadScissorsHTML(imgData.attachment, function(html) {
                        if(html) {
                            $imgFancyBox.append(html);
                            $imgFancyBox.find('#scissorsShowBtn-'+imgData.attachment+' button').click(function() {
                                hasEditedImage = true;
                            })
                        }
                    });
                }
            });

            // Listen to when scissors is opened up
            document.addEventListener("DOMNodeInserted", function(evt) {
                if( evt.target && evt.target.id ) {
                    if( evt.target.id.indexOf('scissorsCrop') === 0 ) {
                        _this.modifyScissorsEditor($(this));
                    } else if( evt.target.id.indexOf('scissorsWatermark') === 0 ) {
                        _this.modifyScissorsWatermarkEditor($(this));
                    }
                }
            });

        },

        modifyScissorsEditor : function($elem) {

            var attach = ArlimaArticleForm.article.data.image.attachment;
            $.each(ArlimaJSAdmin.scissorsCropTemplates, function(key, val) {
                _createRatioButton(key, val[0], val[1], attach);
            });

            // Modify settings in crop form
            $elem.find('input[type="checkbox"]').each(function() {
                if(this.id && this.id.indexOf('scissorsLockBox') == 0 ){
                    $(this).prop("checked", false);
                }
            });
            $elem.find('div').each(function() {
                if (this.id && this.id.indexOf('scissorsReir') == 0 ) {
                    $('#'+ this.id).hide();
                }
            });
        },

        modifyScissorsWatermarkEditor : function($elem) {
            $elem.find('input[type="checkbox"]').each(function() {
                if( this.id && this.id.indexOf('scissors_watermark_target') == 0 ) {
                    var split = this.id.split("_");
                    if( split[3] !== undefined ) {
                        split = split[3].split("-");
                        $(this).prop("checked", true);
                        scissorsWatermarkStateChanged( split[split.length-1], split[0] );
                    }
                }
            });
        }

    };

    /**
     * @param {String} name
     * @param {Number} rx
     * @param {Number} ry
     * @param {Number} attachmentID
     */
    var _createRatioButton = function(name, rx, ry, attachmentID) {
        $('<button></button>')
            .html(name)
            .addClass('button')
            .appendTo('#scissorsCropPane-' + attachmentID)
            .bind('click', function() {
                $('#scissorsLockBox-' + attachmentID).prop("checked", true);
                scissorsAspectChange(attachmentID);
                $('#scissorsLockX-' + attachmentID).val(rx);
                $('#scissorsLockY-' + attachmentID).val(ry);
                scissorsManualAspectChange(attachmentID);
                return false;
            });
    };

    return _this;

})(jQuery, window, ArlimaArticleForm, ArlimaBackend, ArlimaImageManager);