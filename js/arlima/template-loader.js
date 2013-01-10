/**
 * Class responsible of loading jquery templates
 */
var ArlimaTemplateLoader = {

    _templatesToLoad : 0,

    finishedLoading : false,

    templates : {},

    extractFileNameFromURL : function(url) {
        var baseUrl = url.substr(url.lastIndexOf('/')+1, url.length);
        return baseUrl.substr(0, baseUrl.indexOf('.'));
    },

    load : function(paths) {
        var counter = new this._countDown(paths.length, function() {ArlimaTemplateLoader.finishedLoading = true;});

        for(var i = 0; i < paths.length; i++) {
            this._requestTemplate(
                    paths[i],
                    function(content, url) {
                        var templateName = ArlimaTemplateLoader.extractFileNameFromURL(url);
                        var includes = content.match(/\{\{include [0-9a-z\/A-Z\-\_\.]*\}\}/g);

                        if(includes && includes.length > 0) {
                            var includesCountDown = new ArlimaTemplateLoader._countDown(
                                                        includes.length,
                                                        function() {
                                                            ArlimaTemplateLoader.templates[templateName] = content;
                                                            counter.countDown();
                                                        });

                            var loadTemplatePart = function(tmplUrl, tmplName) {
                                ArlimaTemplateLoader._requestTemplate(
                                    tmplUrl,
                                    function(tmplContent, url) {
                                        content = content.replace('{{include '+tmplName+'}}', tmplContent);
                                        includesCountDown.countDown();
                                    },
                                    function(url) {
                                        content = content.replace('{{include '+tmplName+'}}', '<p>## ERROR LOADING TMPL '+tmplUrl+' ##</p>');
                                        includesCountDown.countDown();
                                    });
                            };

                            for(var j=0; j < includes.length; j++) {
                                var templatePartName = includes[j].replace('{{include ', '').replace('}}', '');
                                var templatePartUrl = url.substr(0, url.lastIndexOf('/')) +'/'+ templatePartName;
                                loadTemplatePart(templatePartUrl, templatePartName);
                            }
                        }
                        else {
                            ArlimaTemplateLoader.templates[templateName] = content;
                            counter.countDown();
                        }
                    },
                    function() {
                        counter.countDown();
                    }
                );
        }
    },

    _requestTemplate : function(url, successCallback, errorCallback) {
        jQuery.ajax({
            url : url,
            success : function(content) {
                successCallback( content, url );
            },
            error : function() {
                errorCallback(url);
                throw new Error('Unable to load '+url);
            }
        });
    },

    _countDown : function(length, finishCallback) {
        this.countDownLength = length;

        this.countDown = function() {
            this.countDownLength--;
            if(this.countDownLength == 0)
                finishCallback();
        };
    }
};