var ArlimaArticlePreset = (function($, window, ArlimaBackend, ArlimaUtils) {

    return {

        init : function($elem) {

            var $table = $elem.find('tbody');

            ArlimaUtils.makeCollapsing($elem);

            ArlimaBackend.loadCustomTemplateData(function(json) {
                if(json) {
                    $.each(json, function(i, articleData) {
                        var $row = $('<tr><td><div>'+articleData.name+'</div></td></tr>');
                        $row.find('div').get(0).arlimaArticle = new ArlimaArticle(articleData);
                        $table.append($row);
                        ArlimaUtils.makeDraggable($row.find('div').eq(0));
                    });
                }
            });

        }

    };

})(jQuery, window, ArlimaBackend, ArlimaUtils);