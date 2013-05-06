/**
 * Arlima template loader
 */
var ArlimaTemplateLoader = (function($) {

    'use strict';

    return {

        isRequestingTemplate : false,
        finishedLoading : false,
        templates : {},

        _templatesToLoad : [],
        _templateParts : {},

        load : function(paths) {
            this.finishedLoading = false;
            this._templatesToLoad = paths;
            this.loadNextTemplate();
        },

        loadNextTemplate : function() {
            var _self = this;

            if( this.isRequestingTemplate ) {
                setTimeout(function() {
                    _self.loadNextTemplate();
                }, 200);
            }
            else if(this._templatesToLoad.length > 0) {
                var template = this._templatesToLoad.splice(0,1)[0];
                var baseURL;
                if( template.length == 3) {
                    // included template
                    baseURL = template[2];
                    template = template[1];
                }
                else {
                    baseURL = template.substr(0, template.lastIndexOf('/')) +'/';
                }

                this._loadTemplate(template, function(content) {
                    _self._templateParts[template] = content;
                    var includes = _self.parseIncludes(content, baseURL);
                    for( var i=0; i < includes.length; i++ ) {
                        _self._templatesToLoad.push(includes[i]);
                    }
                    _self.loadNextTemplate();
                });
            }
            else {
                this.buildTemplates();
                this.finishedLoading = true;
            }
        },

        parseIncludes : function(content, baseURL) {
            var includes = content.match(/\{\{include [0-9a-z\/A-Z\-\_\.]*\}\}/g);
            if( includes ) {
                for(var i=0; i < includes.length; i++) {
                    var includeData = includes[i];
                    includes[i] = [includeData, baseURL + includeData.replace('{{include ', '').replace('}}', ''), baseURL];
                }
                return includes;
            } else {
                return [];
            }
        },

        buildTemplates : function() {
            for(var url in this._templateParts) {
                if( this._templateParts.hasOwnProperty(url) ) {
                    var templateName = this.extractFileNameFromURL(url);
                    var baseURL = url.substr(0, url.lastIndexOf('/')) +'/';
                    this.templates[templateName] = this._templateParts[url];
                    var includes = this.parseIncludes(this.templates[templateName], baseURL);
                    while( includes && includes.length > 0 ) {
                        for(var i=0; i < includes.length; i++) {
                            this.templates[templateName] = this.templates[templateName].replace(includes[i][0], this._templateParts[includes[i][1]]);
                        }
                        includes = this.parseIncludes(this.templates[templateName], baseURL);
                    }
                }
            }

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
                url : template,
                success : function(content) {
                    _self.isRequestingTemplate = false;
                    callback( content );
                },
                error : function() {
                    throw new Error('Unable to load template '+template);
                }
            });
        }
    };


})(jQuery);