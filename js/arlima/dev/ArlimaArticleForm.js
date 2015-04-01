var ArlimaArticleForm = (function($, window, ArlimaArticlePreview, ArlimaUtils, ArlimaArticleSettingsMenu, ArlimaFormBuilder, ArlimaJS) {

    var $document = $(document),
        _this = {

            article : false,

            state : 'saved',

            $form : false,
            $formInputs : false,
            $streamerContainer : false,
            $streamerButton : false,
            $controls : false,
            $fileIncludeContainer: false,

            /**
             * Start to edit an article
             * @param {ArlimaArticle} article
             */
            edit : function(article) {

                window._removeSliderKeyDownEvent();

                // Show form if not visible
                if( !this.$form.is(':visible') ) {
                    this.$form.parent().find('.collapse-toggle').trigger('click');
                }

                // Already editing this article
                if( this.isEditing(article.$elem) ) {
                    return;
                }

                if( this.article ) {
                    // If we're editing another article
                    this.article.setState('default');
                }

                // Show which list we're currently working on
                window.ArlimaListContainer.showAsActive(article.listID);

                // setup
                this.article = article;
                article.setState('editing');
                var list = window.ArlimaListContainer.list(article.listID);

                // show preview/save links
                this.$controls.find('a').not('.save').removeClass('disabled');

                // Save button
                if( list.hasUnsavedChanges() ) {
                    // Starting to edit an article belonging to an unsaved list
                    this.toggleUnsavedState('unsaved');
                } else {
                    this.toggleUnsavedState('saved');
                }

                // you can't preview a section divider or a file include
                if( !article.canPreview() ) {
                    this.$controls.find('.preview').addClass('disabled');
                }

                // Undo/Redo functions
                window.ArlimaVersionManager.setArticle(article);

                // Setup form data and features
                this.setupForm(list);
            },

            /**
             * Put the form in place. Hide/show/reset inputs
             * @param {ArlimaList} [list]
             */
            setupForm : function(list) {

                ArlimaFormBuilder.clear();

                ArlimaUtils.log('Setting up article form for '+this.article.data.id);

                if( !list ) {
                    list = window.ArlimaListContainer.list(this.article.listID)
                }

                // Add article data to form
                this.$formInputs.val('').each(function() {
                    var $input = $(this),
                        property = $input.attr('data-prop'),
                        val;

                    if( property.indexOf(':') > 0 ) {
                        var props = property.split(':');
                        val = props[0] in _this.article.data ? (_this.article.data[props[0]][props[1]] || '') : '';
                    } else {
                        val = _this.article.data[property] || '';
                    }

                    if( this.nodeName == 'SELECT') {
                        try {
                            ArlimaUtils.selectVal($input, val);
                        } catch(e) {
                            if( property != 'options:template' ) {
                                ArlimaUtils.log(e.message, 'error');
                            } else {
                                // you may have an article referring to a template that does not exist
                                // then lets remove it.
                                delete _this.article.data.options.template;
                            }
                        }
                    } else {
                        $input.val(val);
                    }
                });

                // Setup image settings
                window.ArlimaImageManager.setup(this.article);
                window.ArlimaImageUploader.removeNotice();

                // Toggle features that is supported by template
                this.toggleEditorFeatures();

                // block entire form if not possible to edit
                if( list.data.isImported || (this.opt('adminLock') && !ArlimaJS.isAdmin) ) {
                    window.ArlimaFormBlocker.removeBlockers();
                    window.ArlimaFormBlocker.toggleFormBlocker(true, list.data.isImported ? false:ArlimaJS.lang.adminLock);
                } else {
                    window.ArlimaFormBlocker.toggleFormBlocker(false);
                }

                // Update a bunch of stuff in the form
                if( this.opt('streamerType') ) {
                    ArlimaUtils.selectVal(this.$streamerContainer.find('.streamer-type-select'), this.opt('streamerType'), false);
                }
                this.$streamerContainer.find('.image').remove();
                this.updateStreamerInputsContainer(true);
                this.$form.find('.font-size-slider').slider("value", this.article.data.size);
                _editorContent(this.article.data.content);

                if( this.opt('fileInclude') ) {
                    var fileInclude = this.opt('fileInclude'),
                        $paramInput = this.$form.find('input[data-prop="options:fileArgs"]'),
                        fieldDef = window.ArlimaFileIncludes.getFormFieldsDefinition(fileInclude);

                    // Set input values from article object
                    $.each($paramInput.val().split('&'), function(i, val) {
                        var argParts = val.split('=');
                        if( argParts[0] in fieldDef ) {
                            fieldDef[argParts[0]].value = argParts[1];
                        }
                    });

                    this.$fileIncludeContainer.show();
                    this.$fileIncludeContainer.find('.file').text( fileInclude.split('/wp-content')[1] || fileInclude);

                    ArlimaFormBuilder.addFields(fieldDef);
                    var $inputs = ArlimaFormBuilder.inputs();
                    $inputs.on('keyup change', function() {
                        var paramValues = [];
                        $inputs.each(function() {
                            var $input = $(this);
                            paramValues.push($input.attr('data-prop') +'='+ $input.val());
                        });
                        _this.change($paramInput, paramValues.join('&'), true);
                    });

                } else {
                    this.$fileIncludeContainer.hide();
                }

                // Set the correct options in the settings menu
                ArlimaArticleSettingsMenu.setup(this.article);

                // Setup connection between this article and a wp post
                window.ArlimaArticleConnection.setup(this.article);

                // Setup preview
                ArlimaArticlePreview.setArticle(
                    this.article,
                    window.ArlimaTemplateLoader.templates[this.article.getTemplate()] || '!! unknown template "'+this.article.getTemplate()+'"',
                    list.data.previewWidth || 468,
                    false,
                    list.data.isImported
                );
            },

            /**
             * Get value of an option for currently edited article
             * @param {String} name
             * @return {String}
             */
            opt : function(name) {
                return this.article.opt(name) || '';
            },

            /**
             * Clear form inputs and loose the article connection
             */
            clear : function() {

                // No list is now active
                window.ArlimaListContainer.showAsActive(false);

                // Hide article preview
                ArlimaArticlePreview.hide();

                ArlimaFormBuilder.clear();

                // Hide the form
                if( this.$form.is(':visible') ) {
                    this.$form.parent().find('.collapse-toggle').trigger('click');
                }

                this.$controls.find('a').addClass('disabled');
                this.$formInputs.val('');
                ArlimaUtils.selectVal(this.$formInputs.filter('select'), '');
                _editorContent('');
                this.state = 'saved';

                if( this.article && this.article.$elem ) {
                    this.article.setState('default');
                    this.article = false;
                }

                window.ArlimaVersionManager.clear();
            },

            /**
             * Update the streamer inputs according to the streamer options
             * in currently edited article
             */
            updateStreamerInputsContainer : function(updateButton) {
                if( this.opt('streamerType') ) {
                    this.$streamerContainer.show();
                    var $contentInput = this.$streamerContainer.find('.content'),
                        $imageLink = this.$streamerContainer.find('.image'),
                        $colorPicker = this.$streamerContainer.find('div:not(.streamer-images)');

                    switch( this.opt('streamerType') ) {
                        case 'extra':
                            this.change($contentInput, 'Extra', true);
                            $contentInput.hide();
                            $colorPicker.hide();
                            $imageLink.remove();
                            break;
                        case 'image':
                            $contentInput.hide();
                            $colorPicker.hide();
                            if( $imageLink.length == 0) {
                                var linkContent = '['+ArlimaJS.lang.chooseImage+']';
                                if( ArlimaUtils.isImagePath(this.opt('streamerContent')) ) {
                                    linkContent = '<img src="'+this.opt('streamerContent')+'" />';
                                } else if( this.opt('streamerContent') ) {
                                    this.change($contentInput, '');
                                }
                                $imageLink = $('<a href="#" class="image">'+linkContent+'</a>').insertAfter($colorPicker.eq(0));
                                $imageLink.on('click', function() {
                                    $.fancybox({
                                        'autoScale': true,
                                        'transitionIn': 'elastic',
                                        'transitionOut': 'elastic',
                                        'speedIn': 500,
                                        'speedOut': 300,
                                        'autoDimensions': true,
                                        'centerOnScroll': true,
                                        'href' : '#streamer-images'
                                    });
                                    return false;
                                });
                            }
                            break;
                        case 'text':
                            if( ArlimaUtils.isImagePath(this.opt('streamerContent')) ) {
                                this.change($contentInput, '');
                            }
                            $contentInput.show();
                            $imageLink.remove();
                            $colorPicker
                                .show()
                                .css('background', '#' + this.opt('streamerColor'));
                            break;
                        default:
                            // custom streamer class
                            if( ArlimaUtils.isImagePath(this.opt('streamerContent')) ) {
                                this.change($contentInput, '');
                            }
                            $contentInput.show();
                            $imageLink.remove();
                            $colorPicker.hide();
                            break;
                    }

                    if( updateButton ) {
                        this.$streamerButton.find('i').removeClass('fa-square-o').addClass('fa-check-square-o');
                        this.$streamerButton.addClass('active');
                    }

                } else {
                    this.$streamerContainer.hide();
                    if( updateButton ) {
                        this.$streamerButton.removeClass('active');
                        this.$streamerButton.find('i').removeClass('fa-check-square-o').addClass('fa-square-o');
                    }
                }
            },

            /**
             * Looks at current mustasch template and hides/shows features in the editor
             */
            toggleEditorFeatures : function() {
                var blocker = window.ArlimaFormBlocker,
                    support = window.ArlimaTemplateLoader.getTemplateSupport(this.article),
                    $features = this.$form.find('.template-feature');

                if( this.article.opt('sectionDivider') || this.article.opt('fileInclude') ) {
                    $features.filter('[data-feature="image"]').hide();
                    $features.filter('[data-feature="editor"]').hide();
                    $features.filter('[data-feature="connection"]').hide(); // connection is always supported, except for section dividers
                    blocker.toggleStreamerBlocker(false);
                    blocker.toggleImageAlignBlocker(false);
                    blocker.toggleTitleBlocker(true);
                    if( this.article.data.options.fileInclude ) {
                        blocker.toggleStreamerBlocker(true);
                        $features.filter('[data-feature="file-include"]').show();
                    }
                } else {
                    if( !ArlimaListContainer.list(this.article.listID).data.isImported ) {
                        blocker.removeBlockers(); // remove the small blockers since the entire form will be blocked
                    }

                    // Block or hide inputs

                    $features.show();
                    $.each(support, function(featureName, isSupported) {
                        if( featureName != 'imageSize' && !isSupported ) {
                            if( !blocker.addBlocker(featureName) ) {
                                $features.filter('[data-feature="'+featureName+'"]').hide();
                            }
                            if( !isSupported && featureName == 'image' ) {
                                blocker.toggleImageAlignBlocker(false);
                            }
                        }
                    });

                    if( support.image && this.article.data.image && this.article.data.image.size == 'full' ) {
                        blocker.toggleImageAlignBlocker(true);
                    }
                }
            },

            /**
             * Get an object representing the article data residing in the form
             * @returns {Object}
             */
            serialize : function() {
                var newData = $.extend(true, {}, this.article.data);

                this.$formInputs.each(function() {
                    var $input = $(this),
                        property = $input.attr('data-prop');

                    if( property ) {
                        if(property.indexOf(':') > 0) {
                            var props = property.split(':');
                            if( !(props[0] in newData) ) {
                                newData[props[0]] = {};
                            }
                            newData[props[0]][props[1]] = $input.val();
                        } else {
                            newData[property] = $input.val();
                        }
                    }
                });

                return newData;
            },

            /**
             * Checks whether given list ID or article element is being edited right now
             * @param {Number|jQuery} check
             */
            isEditing : function(check) {
                if( this.article ) {
                    if( $.isNumeric(check) ) {
                        return check == this.article.listID;
                    }
                    else if( ArlimaArticle.isArticleObj(check) ) {
                        return check == this.article;
                    }
                    else {
                        return check[0].arlimaArticle && check[0].arlimaArticle == this.article.$elem[0].arlimaArticle;
                    }
                }
                return false;
            },

            /**
             * @param {String} state - Either 'saved' or 'unsaved'
             */
            toggleUnsavedState : function(state) {
                this.state = state;
                if( this.state == 'saved' ) {
                    this.$controls.find('.save').addClass('disabled');
                } else {
                    this.$controls.find('.save').removeClass('disabled');
                    window.ArlimaListContainer.list(this.article.listID).toggleUnsavedState(true);
                }
            },

            /**
             * Change the value of an input in the form
             * @param {String|jQuery} input - Either element query for the input|select or the jQuery object
             * @param {String} val - The new value
             * @param {Boolean} [triggerChange] - If true the preview will be update and list state change (defaults to true)
             */
            change : function(input, val, triggerChange) {
                if( triggerChange === undefined )
                    triggerChange = true;

                if( typeof input == 'string' ) {
                    input = (input.indexOf('streamer') > -1 ? this.$streamerContainer : this.$form).find(input);
                }

                if( input.get(0).nodeName == 'SELECT' ) {
                    ArlimaUtils.selectVal(input, val, triggerChange);
                } else {
                    val = $.trim(val);
                    if( input.val() != val ) {
                        input.val(val);
                        if( triggerChange )
                            input.trigger('change');
                    }
                }
            },

            /**
             * Get the content of the text editor in the form
             * @returns {String}
             */
            getEditorContent : function() {
                return _editorContent();
            },

            /* * * * * Init * * * * */

            init : function($form, $controls) {

                ArlimaUtils.makeCollapsing($form, function() {
                    if( $form.is(':visible') ) {
                        _this.$form.trigger('open');
                    } else {
                        _this.$form.trigger('closed');
                    }
                });

                this.$form = $form.find('.inside');
                this.$streamerButton = $form.find('.button.streamer');
                this.$streamerContainer = $form.find('.streamer-container');
                this.$fileIncludeContainer = $form.find('.file-include-container');
                this.$controls = $controls;

                // Save list from upper right corner
                $controls.find('.save').click(function() {
                    if( _this.state == 'unsaved' ) {
                        _this.toggleUnsavedState('saved');
                        window.ArlimaListContainer.list(_this.article).save();
                    }
                    return false;
                });

                // Preview article
                $controls.find('.preview').click(function() {
                    if( !$(this).hasClass('disabled') ) {
                        ArlimaArticlePreview.toggle();
                    }
                    return false;
                });

                // Preview list
                $controls.find('.preview-list').click(function() {
                    if( !$(this).hasClass('disabled') ) {
                        window.ArlimaListContainer.list(_this.article.listID).preview();
                    }
                    return false;
                });

                // Article settings menu
                var $formatSelect = $form.find('.formats'),
                    $templateSelect = $form.find('.templates'),
                    templateFormatMap = {};

                ArlimaArticleSettingsMenu.init($form.find('button.settings'), $form.find('.settings-menu'));
                if ($formatSelect.length > 0) {
                    ArlimaArticleSettingsMenu.addSelect($formatSelect);
                }
                ArlimaArticleSettingsMenu.addSelect($templateSelect);
                ArlimaArticleSettingsMenu.addSelect($form.find('.scheduled-settings'));

                if( ArlimaJS.isAdmin )
                    ArlimaArticleSettingsMenu.addSelect($form.find('.admin-lock'));

                $templateSelect.find('option').each(function() {
                    templateFormatMap[$(this).attr('value')] = [];
                });
                $formatSelect.find('option').each(function() {
                    var format = $(this).attr('value'),
                        templates = $(this).attr('data-template');
                    if( !templates ) {
                        $.each(templateFormatMap, function(template, formats) {
                            templateFormatMap[template].push(format);
                        })
                    } else {
                        $.each(templates.split(','), function(i, template) {
                            if( !(template in templateFormatMap) )
                                templateFormatMap[template] = [];
                            templateFormatMap[template].push(format);
                        });
                    }
                });
                ArlimaArticleSettingsMenu.templateFormatsMap = templateFormatMap;

                // Font size slider
                var lastSliderEvent = false,
                    $doc = $(document),
                    $fontSizeInput = $form.find('input.font-size'),
                    _onSliderKeyDown = function(e) {
                        var key = e.keyCode ? e.keyCode : e.which;
                        if( $.inArray(key, [39,37]) > -1 && _this.article ) {
                            lastSliderEvent = new Date().getTime();
                            var size = parseInt($fontSizeInput.val(), 10);
                            size += key == 37 ? -1:1;
                            $fontSizeSlider.slider('value', size);
                            _this.change($fontSizeInput, size);
                            _soonRemoveSliderKeyDownEvent();
                            return false;
                        }
                    },
                    _soonRemoveSliderKeyDownEvent = function() {
                        setTimeout(function() {
                            if( !lastSliderEvent || (new Date().getTime() - 5000) > lastSliderEvent ) {
                                _removeSliderKeyDownEvent();
                            }
                        }, 5000);
                    },
                    $fontSizeSlider = $form.find('.font-size-slider').slider({
                        value: 18,
                        min: 8,
                        max: 100,
                        slide: function( event, ui ) {
                            window.hasSliderFocus = true;
                            _this.change($fontSizeInput, ui.value);
                        }
                    })
                        .mousedown(function() {
                            window.hasSliderFocus = true;
                        }),
                    $sliderButton = $form.find('.ui-slider-handle');

                window._removeSliderKeyDownEvent = function() {
                    lastSliderEvent = false;
                    $sliderButton.removeClass('active');
                    $doc.unbind('keydown', _onSliderKeyDown);
                };

                $form.find('.ui-slider-handle,input.font-size').bind('focus click', function() {
                    $sliderButton.addClass('active');
                    $form.find('input').one('focus', _removeSliderKeyDownEvent);
                    _removeSliderKeyDownEvent();
                    $doc.on('keydown', _onSliderKeyDown);
                    _soonRemoveSliderKeyDownEvent();
                    lastSliderEvent = new Date().getTime();
                });

                // Streamer button
                var $streamerSelect = this.$streamerContainer.find('.streamer-type-select');
                this.$streamerButton.click(function() {
                    if( _this.$streamerButton.hasClass('active') ) {
                        _this.$streamerButton.removeClass('active');
                        _this.$streamerButton.find('i').removeClass('fa-check-square-o').addClass('fa-square-o');
                        _this.change('.streamer-type', '');
                        _this.change(_this.$streamerContainer.find('.content'), '');
                        _this.$streamerContainer.find('.image').remove();

                    } else {
                        _this.$streamerButton.addClass('active');
                        _this.$streamerButton.find('i').removeClass('fa-square-o').addClass('fa-check-square-o');
                        if( $streamerSelect.val() == 'extra' ) {
                            _this.$streamerContainer.find('.content').val('Extra');
                        }
                        _this.change('.streamer-type', $streamerSelect.val());
                    }
                    _this.$streamerButton.blur();
                    _this.updateStreamerInputsContainer(false);
                });
                $streamerSelect.bind('change', function() {
                    _this.change('.streamer-type', $(this).val());
                    _this.updateStreamerInputsContainer(false);
                });

                // Streamer color picker
                $form.find('select.streamer-color').colourPicker({
                    ico: '',
                    name : 'apa',
                    title: false
                });

                // Streamer images
                $form.find('.streamer-images img').click(function() {
                    var $img = $(this);
                    _this.$streamerContainer.find('.image').html($img.clone());
                    $('.fancybox-close').trigger('click');
                    _this.change(_this.$streamerContainer.find('.content'), $img[0].src);
                });

                // Make use the naughty user wont write HTML in our inputs.
                $form.find('input').each(function() {
                    if( this.type == 'text' ) {
                        $(this).on('blur', function() {
                            var val = this.value
                                .replace(/<br(|\/| \/)>/gi, '__');

                            val = $('<div></div>').html(val).text();
                            this.value = val;
                        });
                    }
                });

                // Detect change in article form and add data to article
                setTimeout(function() {

                    // Fix for colourPicker plugin
                    $form.find('input[name="options:streamerColor"]')
                        .attr('data-prop', 'options:streamerColor')
                        .addClass('data');

                    // Create a reference to all inputs in the form
                    _this.$formInputs = $form.find('.data');

                    var saveNewVersionOnBlur = false;
                    _this.$formInputs
                        .bind('change', function() {
                            var $input = $(this),
                                prop;

                            if( _this.article && (prop = $input.attr('data-prop'))) {

                                // Add new data to article
                                //_self.article.setData( _self.serialize() );
                                if(prop.indexOf(':') > 0) {
                                    var propParts = prop.split(':');
                                    if( !_this.article.data[propParts[0]] || $.isArray(_this.article.data[propParts[0]]) ) {
                                        // May have become an empty array when json encoded on backend
                                        _this.article.data[propParts[0]] = {};
                                    }
                                    _this.article.data[propParts[0]][propParts[1]] = $input.val();
                                } else {
                                    _this.article.data[prop] = $input.val();
                                }
                                if( _this.state == 'saved' ) {
                                    _this.toggleUnsavedState('unsaved');
                                }

                                if( prop == 'options:template' ) {
                                    // change template in preview
                                    window.ArlimaImageManager.setup(_this.article);
                                    _this.toggleEditorFeatures();
                                    _this.updateStreamerInputsContainer();
                                    ArlimaArticlePreview.setTemplate(window.ArlimaTemplateLoader.templates[_this.article.getTemplate()]);
                                } else {
                                    // update preview
                                    ArlimaArticlePreview.update($input);
                                }

                                // Add a new version to version manager
                                if( $input.get(0).type == 'text' && prop != 'size' ) {
                                    saveNewVersionOnBlur = true;
                                } else {
                                    saveNewVersionOnBlur = false;
                                    window.ArlimaVersionManager.addVersion(_this.article);
                                }

                                // Update title element
                                var dataAffectingTitleAppearance = ['options:preTitle', 'title',
                                    'options:streamerType', 'options:format', 'options:streamerContent',
                                    'options:streamerColor', 'options:template',
                                    'options:adminLock', 'options:scheduled'];

                                if($.inArray(prop, dataAffectingTitleAppearance) > -1) {
                                    _this.article.updateItemPresentation(false);
                                }
                            }
                            $document.trigger("Arlima.articleChanged", _this.article);
                        })
                        .bind('keyup', function() {
                            var $input = $(this),
                                prop = $input.attr('data-prop'),
                                props = prop.split(':'),
                                val = '';

                            if( props.length == 1 && _this.article.data[props[0]]) {
                                val = _this.article.data[props[0]];
                            } else if ( props.length == 2 && _this.article.data[props[0]] ) {
                                val = _this.article.data[props[0]][props[1]] || '';
                            }

                            if( val != $input.val() ) {
                                $input.trigger('change');
                            }
                        })
                        .bind('blur', function() {
                            // Add new version when blurring on some inputs
                            if( saveNewVersionOnBlur && $(this).attr('data-prop') ) {
                                window.ArlimaVersionManager.addVersion(_this.article);
                            }
                        });

                }, 500);
            }
        };


    /**
     * Getter and setter of the tinymce editor
     * @param {String} [content]
     */
    var _editorContent = function(content) {
        if(content !== undefined) {
            window.ArlimaTinyMCE.setEditorContent(content);
        }
        else {
            return window.ArlimaTinyMCE.getEditorContent();
        }
    };

    return _this;

})(jQuery, window, ArlimaArticlePreview, ArlimaUtils, ArlimaArticleSettingsMenu, ArlimaFormBuilder, ArlimaJS);