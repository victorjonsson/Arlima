var ArlimaPostSearch = (function($, window, ArlimaBackend, ArlimaJS, ArlimaUtils, ArlimaArticleForm) {

    var _this = {

        $elem : false,
        isSearching : false,
        currentOffset : 0,

        /**
         * Do the search and add search result to result container
         * @param {Number} [offset]
         */
        search : function(offset) {
            if( this.isSearching )
                return false;
            if( offset === undefined )
                offset = 0;

            this.currentOffset = offset;
            _setSearchState(this, true);

            var data = {
                offset: offset
            };
            this.$elem.find('input,select').each(function() {
                if(this.name) {
                    if(this.type) {
                        if(this.type == 'checkbox' && $(this).is(':checked')) {
                            data[this.name] = this.value;
                        } else if(this.type != 'checkbox') {
                            data[this.name] = this.value;
                        }
                    } else {
                        data[this.name] = $(this).val();
                    }
                }
            });

            var $result = this.$elem.find('.search-result'),
                $resultBody = $result.find('tbody');

            ArlimaBackend.queryPosts(data, function(json) {
                _setSearchState(_this, false);

                if(json) {
                    $resultBody.html('');
                    $result.show();
                    $result.find('.previous').hide();
                    $result.find('.next').hide();

                    if( json.articles.length == 0 ) {
                        $resultBody.append('<tr><td colspan="3"><em>'+ArlimaJS.lang.noPostsFound+'...</em></td></tr>');
                    } else {
                        $.each(json.articles, function(i, article) {

                            var futureText = ArlimaUtils.isFutureDate(article.data.published * 1000) ? ' <em>('+ArlimaJS.lang.future+')</em>':'',
                                $row = $('<tr><td><div>'+article.post.post_title+futureText+'</div></td><td>'+article.post.display_author+'</td><td>'+article.post.post_date+'</td></tr>'),
                                articleContainer = $row.find('div').get(0);

                            $row.find('td').get(0).arlimaArticle = new ArlimaArticle(article.data);

                            articleContainer.arlimaArticle = new ArlimaArticle(article.data);

                            ArlimaUtils.makeDraggable($(articleContainer));

                            $resultBody.append($row);
                        });

                        if( offset == 0 ) {
                            $result.find('.previous').hide();
                        } else {
                            $result.find('.previous').show();
                        }

                        if( json.articles.length >= 10 )
                            $result.find('.next').show();
                    }
                }
            });

            return false;
        },


        /* * * * * *  INIT * * * * * */

        init : function($elem) {

            this.$elem = $elem;

            ArlimaUtils.makeCollapsing($elem);

            $elem.find('form').on('submit', function() {
                _this.search(0);
                return false;
            });

            $elem.find('tfoot .previous').on('click', function() {
                _this.search(_this.currentOffset-10);
            });
            $elem.find('tfoot .next').on('click', function() {
                _this.search(_this.currentOffset+10);
            });
        }

    };

    var _setSearchState = function(form, toggle) {
        form.isSearching = toggle;
        if( form.isSearching ) {
            form.$elem.find('.ajax-loader').show();
            form.$elem.find('.search-result').hide();
        } else {
            form.$elem.find('.ajax-loader').hide();
        }
    };

    return _this;

})(jQuery, window, ArlimaBackend, ArlimaJS, ArlimaUtils, ArlimaArticleForm);