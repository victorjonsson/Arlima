var ArlimaArticleConnection = (function($, ArlimaUtils, ArlimaArticleForm, ArlimaBackend) {

    'use strict';

    var $document = $(document),

        _this = {

        posts : {},

        currentPost : {},

        $fancybox : null,

        $elem : false,

        $futureNotice : false,

        article : false,

        searchedPosts : {},

        isPublishedPost : false,

        /**
         * @param {ArlimaArticle} article
         */
        setup : function(article) {

            this.article = article;
            this.isPublishedPost = article.isPublished();

            var postIDs = [];
            if( article.data.post )
                postIDs.push(article.data.post);

            $.each(article.getChildArticles(), function(i, childArticle) {
                if( childArticle.data.post )
                    postIDs.push(childArticle.data.post);
            });

            if( postIDs.length == 0 || (postIDs.length == 1 && postIDs[0] == 0) ) {
                this.posts = {};
                this.currentPost = {};
                this.setupLinks();
            } else if( _hasAllPostsLoaded(postIDs) ) {
                this.currentPost = this.posts[article.data.post] || {};
                this.setupLinks();
            } else {
                ArlimaBackend.getPost(postIDs.join(','), function(json) {
                    if( json.posts ) {
                        _this.posts = json.posts;
                        _this.currentPost = json.posts[article.data.post] || {};
                        _this.setupLinks();
                        $document.trigger('postLoaded', [json.posts]);
                    } else {
                        throw new Error('Backend did not return a list of posts ('+postIDs.join(',')+')');
                    }
                });
            }

        },

        /**
         * What we call this connection, either the post title or the overriding URL
         * @returns {String}
         */
        getConnectionLabel : function() {
            return this.currentPost.ID ? this.currentPost.post_title : this.article.opt('overridingURL');
        },

        /**
         * Arrange stuff (links, inputs, labels etc...)
         */
        setupLinks : function() {
            var url = this.currentPost.ID ? this.currentPost.url : this.article.opt('overridingURL'),
                label = this.getConnectionLabel();

            this.$fancybox.find('.connection').text(label);

            this.$elem.find('.url')
                .attr('href', url)
                .text(label);

            if( this.currentPost.ID ) {
                this.$elem.find('.wp-admin-edit').show();
                this.$fancybox.find('.overriding-url').val('');
                this.$fancybox.find('.container').hide().filter('.wp-post').show();
                this.$fancybox.find('.button').removeClass('active').filter('.wp-post-btn').addClass('active');
            } else {
                this.$fancybox.find('.overriding-url').val(url);
                this.$fancybox.find('.container').hide().filter('.overriding-url').show();
                this.$fancybox.find('.button').removeClass('active').filter('.overriding-url-btn').addClass('active');
                ArlimaUtils.selectVal(this.$fancybox.find('select.target'), this.article.opt('target') || '');
            }

            if( this.isPublishedPost ) {
                this.$futureNotice.hide();
            } else {
                this.$futureNotice.show();
            }
        },


        /* * * * * * INIT * * * * * */


        init : function($container, $fancybox) {

            this.$elem = $container;
            this.$futureNotice = $container.find('.future-notice').hide();
            this.$fancybox = $fancybox;

            // Open link
            $container.find('.url').click(function() {
                if( this.href != '' && this.href != '#' ) {
                    window.open(this.href);
                }
                return false;
            });

            // Change connection
            $container.find('.change').click(function() {
                $.fancybox({
                    transitionIn: 'elastic',
                    transitionOut: 'elastic',
                    speedIn: 500,
                    speedOut: 300,
                    autoDimensions: true,
                    centerOnScroll: true,
                    href : '#article-connection'
                });
                return false;
            });

            // Edit in wp-admin
            $container.find('.wp-admin-edit').click(function() {
                if( ArlimaArticleForm.article || ArlimaArticleForm.article.data.post ) {
                    window.open('post.php?post=' + ArlimaArticleForm.article.data.post + '&action=edit')
                }
                return false;
            });

            // Fancybox buttons
            this.$fancybox.find('.button').click(function() {
                var $btn = $(this);
                if( $btn.hasClass('save') ) {

                    // Save an overriding url
                    var overridingURL = _this.$fancybox.find('input.overriding-url').val(),
                        target = _this.$fancybox.find('select.target').val();

                    if( overridingURL != ArlimaArticleForm.$form.find('input.overriding-url').val() ) {
                        _this.currentPost = {};
                        ArlimaArticleForm.change('input.post', '');
                        ArlimaArticleForm.change('input.overriding-url', overridingURL);
                        _this.$fancybox.find('.connection').text(overridingURL);
                        _this.article.data.published = Math.round((new Date().getTime() / 1000) - 10);
                        _this.$futureNotice.hide();
                        if( _this.article.data.image && _this.article.data.image.connected ) {
                            delete _this.article.data.image.connected;
                        }
                        _this.article.updateItemPresentation();
                    }
                    if( ArlimaArticleForm.$form.find('input.overriding-url-target').val() != target) {
                        ArlimaArticleForm.change('input.overriding-url-target', target);
                    }
                    $('.fancybox-close').trigger('click');

                    _this.$elem.find('.url')
                            .attr('href', overridingURL)
                            .text(overridingURL);

                } else if( $btn.hasClass('search-btn') ) {

                    var search = $.trim(_this.$fancybox.find('input.search').val());
                    $btn.parent().css('opacity', '0.5');
                    _this.searchedPosts = {};

                    window.ArlimaBackend.queryPosts({search:search}, function(data) {

                        $btn.parent().css('opacity', '1');
                        var $resultContainer = _this.$fancybox.find('.search-result');
                        if( !data.articles ) {
                            $resultContainer.html('<p>'+ArlimaJS.lang.nothingFound+'</p>');
                        }
                        else {
                            var result = '';
                            $.each(data.articles, function(i, postData) {
                                _this.searchedPosts[postData.post.ID] = postData.post;
                                var futureText = ArlimaUtils.isFutureDate(postData.data.published * 1000) ? ' <em>('+ArlimaJS.lang.future+')</em>':'';
                                    result += '<p><a href="#" data-post="'+postData.data.post+'">'+postData.post.post_title+'</a> '+futureText+'</p>';
                            });

                            $resultContainer.html(result);
                            $resultContainer.find('a').click(function() {
                                // Change to another post
                                var post = _this.searchedPosts[$(this).attr('data-post')];
                                _this.currentPost = post;
                                _this.posts[post.ID] = post;
                                ArlimaArticleForm.change('input.overriding-url', '');
                                ArlimaArticleForm.change('input.overriding-url-target', '');
                                ArlimaArticleForm.change('input.post', post.ID);
                                _this.$fancybox.find('.connection').text(post.post_title);
                                _this.$elem.find('.url')
                                    .attr('href', post.url)
                                    .text(post.post_title);

                                // Update article publish date
                                _this.article.data.published = post.published;
                                _this.article.updateItemPresentation();
                                if( _this.article.isPublished() ) {
                                    _this.$futureNotice.hide();
                                } else {
                                    _this.$futureNotice.show();
                                }

                                $document.trigger('postLoaded', [_this.posts]);

                                $('.fancybox-close').trigger('click');
                                return false;
                            });
                        }
                    });

                } else if( $btn.hasClass('overriding-url-btn') ) {
                    $btn.siblings().removeClass('active');
                    $btn.addClass('active');
                    _this.$fancybox.find('.container').hide().filter('.overriding-url').show();
                } else if( $btn.hasClass('wp-post-btn') ) {
                    $btn.siblings().removeClass('active');
                    $btn.addClass('active');
                    _this.$fancybox.find('.container').hide().filter('.wp-post').show();
                } else {
                    ArlimaUtils.log('Pushing button in fancybox that does not exist', 'error');
                }

                return false;
            });

        }

    };


    var _hasAllPostsLoaded = function(postIDs) {
        var found = true;
        $.each(postIDs, function(i, id) {
            if( !(id in _this.posts) ) {
                found = false;
                return false;
            }
        });
        return found;
    };

    return _this;


})(jQuery, ArlimaUtils, ArlimaArticleForm, ArlimaBackend);