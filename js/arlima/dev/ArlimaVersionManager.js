var ArlimaVersionManager = (function($, ArlimaArticleForm) {

    var _this = {

        versions : false,

        versionsInFront : false,

        setArticle : function(article) {
            this.clear();
            this.addVersion(article);
        },

        addVersion : function(article) {
            this.versions.push( JSON.stringify(article.data) );
            if( this.versions.length > 50 ) {
                this.versions.splice(0, 1);
            }
        },

        stepBack : function() {
            if( this.versions.length > 1 ) {
                this.versionsInFront.push(this.versions.splice(-1));
                _updateToCurrentVersion();
            }
        },

        stepForward : function() {
            if( this.versionsInFront.length > 0 ) {
                this.versions.push(this.versionsInFront.splice(-1));
                _updateToCurrentVersion();
            }
        },

        clear : function() {
            // http://jsperf.com/array-destroy/61
            this.versions = [];
            this.versionsInFront = [];
        }
    };

    var _updateToCurrentVersion = function() {
        ArlimaArticleForm.article.setData( JSON.parse(_this.versions[_this.versions.length-1]) );
        ArlimaArticleForm.setupForm();
        ArlimaArticleForm.toggleUnsavedState('unsaved');
    };

    return _this;

})(jQuery, ArlimaArticleForm);