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
                if( template.length == 3) {
                    // included template
                    template = template[1];
                }

                this._loadTemplate(template, function(content, baseURL) {
                    _self._templateParts[template] = content;
                    $.each(_self.parseIncludes(content, baseURL), function(i, templateData) {
                        _self._templatesToLoad.push(templateData);
                    });
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
                var _self = this;
                $.each(includes, function(i, includeTag) {
                    var path = includeTag.replace('{{include ', '').replace('}}', '');
                    includes[i] = [includeTag, _self.makeTemplateURL(baseURL, path), baseURL];
                });
                return includes;
            } else {
                return [];
            }
        },

        makeTemplateURL : function(baseURL, path) {
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
                    callback( content, _dirname(template)+'/' );
                },
                error : function() {
                    throw new Error('Unable to load template '+template);
                }
            });
        }
    };


})(jQuery);