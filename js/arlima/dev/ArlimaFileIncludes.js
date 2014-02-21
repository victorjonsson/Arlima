var ArlimaFileIncludes= (function($, window, ArlimaBackend, ArlimaJS, ArlimaUtils) {

    var _self = {

        $elem : false,

        /**
         * Do the search and add search result to result container
         * @param {Number} [offset]
         */
        search : function(offset) {

            this.$elem.find('.file-include').each(function(i, file) {

                $(file).get(0).arlimaArticle = new ArlimaArticle({
                    title : $(file).data('label'),
                    options : {
                        fileInclude : $(file).data('file')
                    }
                });

                $(file).draggable({
                    appendTo: 'body',
                    helper:'clone',
                    sender:'postlist',
                    connectToSortable:'.article-list .articles',
                    revert:'invalid',
                    start: function(event, ui) {
                        ui.helper.html('<div class="article-title-container"><span>' + ui.helper.html() + '</span></div>');
                        ui.helper.addClass('article');
                        ui.helper.css('z-index', '99999');
                    }
                });
            });
        },


        /* * * * * *  INIT * * * * * */

        init : function($elem) {

            this.$elem = $elem;

            ArlimaUtils.makeCollapsing($elem);
            this.search();
        }

    };

    return _self;

})(jQuery, window, ArlimaBackend, ArlimaJS, ArlimaUtils, ArlimaArticleForm);