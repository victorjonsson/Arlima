/**
 * Arlima template loader
 */
var ArlimaTemplateLoader = (function($) {

    'use strict';

    var _dirname = function(path) {
        if( path.substr(path.length-1, 1) == '/' ) {
            path = path.substr(0, path.length-1);
        }
        return path.substr(0, path.lastIndexOf('/'));
    };

    return {

        isRequestingTemplate : false,
        finishedLoading : false,
        templates : {},
        _templateSupport : {},

        _templatesToLoad : [],
        _templateParts : {},

        load : function(paths) {
            this.finishedLoading = false;
            this._templatesToLoad = paths;
            this._loadNextTemplate();
        },

        /**
         * Get the object describing the feature support of a certain template.
         * @example
         *  ArlimaTemplateLoader.templateSupport('my-article-template'); // Returns object with all feature support configuration for the template my-article-template.tmpl
         *  ArlimaTemplateLoader.templateSupport('my-article-template', 'image'); // Returns the support for image in the template my-article-template.tmpl
         *
         * @param name
         * @param type
         * @returns {*}
         */
        templateSupport : function(name, type) {
            if( !type ) {
                return this._templateSupport[name];
            }
            return this._templateSupport[name] === undefined ? undefined : this._templateSupport[name][type];
        },

        /**
         * Get an object describing the feature support of the template chosen for given article
         * @param {ArlimaArticle} article
         * @returns {Object}
         */
        getTemplateSupport : function(article) {
            return this.templateSupport(article.getTemplate()) || {};
        },

        _loadNextTemplate : function() {
            var _self = this;

            if( this.isRequestingTemplate ) {
                setTimeout(function() {
                    _self._loadNextTemplate();
                }, 200);
            }
            else if(this._templatesToLoad.length > 0) {
                var template = this._templatesToLoad.splice(0,1)[0];
                if( template.length == 3) {
                    // included template
                    template = template[1];
                }

                this._loadTemplate(template, function(content, baseURL) {
                    _self._templateParts[template] = content;
                    $.each(_self._parseIncludes(content, baseURL), function(i, templateData) {
                        _self._templatesToLoad.push(templateData);
                    });
                    _self._loadNextTemplate();
                });
            }
            else {
                this._buildTemplates();
                this.finishedLoading = true;
            }
        },

        _parseIncludes : function(templateContent, baseURL) {
            var includes = templateContent.match(/\{\{include [0-9a-z\/A-Z\-\_\.]*\}\}/g);
            if( includes ) {
                var _self = this;
                $.each(includes, function(i, includeTag) {
                    var path = includeTag.replace('{{include ', '').replace('}}', '');
                    includes[i] = [includeTag, _self._makeTemplateURL(baseURL, path), baseURL];
                });
                return includes;
            } else {
                return [];
            }
        },

        _makeTemplateURL : function(baseURL, path) {
            var url = baseURL + path;
            if( url.indexOf('../') > -1 ) {
                var parts = url.substr(url.indexOf('://')+3).split('/');
                var newPath = [];
                $.each(parts, function(i, dir) {
                    if( dir == '..' ) {
                        newPath.splice(-1);
                    } else {
                        newPath.push(dir);
                    }
                });
                return url.substr(0, url.indexOf('://')) +'://'+ newPath.join('/');
            }
            return url;
        },

        _buildTemplates : function() {

            var _self = this;

            // Merge together templates and included templats
            $.each(this._templateParts, function(url, templatePart) {
                var templateName = _self.extractFileNameFromURL(url);
                var baseURL = url.substr(0, url.lastIndexOf('/')) +'/';
                _self.templates[templateName] = templatePart;
                var includes = _self._parseIncludes(_self.templates[templateName], baseURL);
                while( includes && includes.length > 0 ) {
                    for(var i=0; i < includes.length; i++) {
                        _self.templates[templateName] = _self.templates[templateName].replace(includes[i][0], _self._templateParts[includes[i][1]]);
                    }
                    includes = _self._parseIncludes(_self.templates[templateName], baseURL);
                }
            });

            // Extract (and remove) template support from templates
            $.each(this.templates, function(name, templateContent) {

                var supports = {};

                // Image sizes
                var imageSupport = templateContent.match( new RegExp('(\{\{image-support .*\}\})', 'g') );
                if( imageSupport && imageSupport[0] ) {
                    _self.templates[name] = templateContent.replace( new RegExp('(\{\{image-support .*\}\})', 'g'), '' );
                    var attr = $.trim(imageSupport[0].substr( imageSupport[0].indexOf(' ')).replace('}}', ''));

                    supports.imageSize = {};
                    $.each( $('<div '+attr+'></div>').get(0).attributes, function(i, attribute) {
                        if( attr.indexOf(attribute.name) > -1 ) { // check for IE's sake
                            supports.imageSize[attribute.name] = attribute.value;
                        }
                    });
                }

                supports['image'] = templateContent.indexOf('{{image.url}}') > -1 || templateContent.indexOf('{{{html_image}}}') > -1;
                supports['streamer'] = templateContent.indexOf('{{html_streamer}}') > -1;
                supports['editor'] = templateContent.indexOf('{{html_content}}') > -1 || templateContent.indexOf('{{content}}') > -1;
                supports['title'] = templateContent.indexOf('{{html_title}}') > -1;

                _self._templateSupport[name] = supports;

            });

            delete this._templateParts;
        },

        extractFileNameFromURL : function(url) {
            var baseUrl = url.substr(url.lastIndexOf('/')+1, url.length);
            return baseUrl.substr(0, baseUrl.indexOf('.'));
        },

        _loadTemplate : function(template, callback) {
            this.isRequestingTemplate = true;
            var _self = this;
            $.ajax({
                dataType : 'text',
                url : template,
                success : function(content) {
                    _self.isRequestingTemplate = false;
                    callback( content, _dirname(template)+'/' );
                },
                error : function() {
                    ArlimaUtils.log('Unable to load template '+template, 'error');
                    _self.isRequestingTemplate = false;
                    callback('<!-- ! Missing template '+template+' -->', _dirname(template)+'/' );
                }
            });
        }
    };

})(jQuery);