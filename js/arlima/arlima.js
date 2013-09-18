/**
 * Arlima admin library
 *
 * @todo: Look over all insufficient use of jQuery
 * @todo: move each class to its own file and write unit test
 *
 * Dependencies:
 *  - jQuery
 *  - jQuery.effects
 *  - jQuery.qtip
 *  - jQuery.slider
 *  - ArlimaJS
 *  - ArlimaTemplateLoader
 */
var Arlima = function (t, e, i, a) {
    "use strict";

    function r(t, e, i, a) {
        this.jQuery = e, this.id = t, this.isImported = i, this.isUnsaved = !1, this.titleElement = a
    }

    function s(t, e) {
        void 0 === e && (e = "log"), "undefined" != typeof console && "function" == typeof console[e] && console[e](t)
    }

    function n(e) {
        return e ? (t.isNumeric(e) || (e = e.publish_date), e && 1e3 * e > (new Date).getTime()) : !1
    }

    function o(t, e) {
        return e || (e = 30), t = t ? "" + t : "", t.length > e ? t.substr(0, e - 3) + "..." : t
    }

    function l(t) {
        return t.ctrlKey || t.metaKey
    }
    var d = {
        queryPosts: function (t, e) {
            this._ajax("arlima_query_posts", t, e)
        },
        getLaterVersion: function (t, e, i) {
            this._ajax("arlima_check_for_later_version", {
                alid: t,
                version: e
            }, i)
        },
        getPost: function (t, e) {
            this._ajax("arlima_get_post", {
                postid: t
            }, e)
        },
        getPostAttachments: function (t, e) {
            this._ajax("arlima_get_attached_images", {
                postid: t
            }, e)
        },
        connectAttachmentToPost: function (t, e, i) {
            this._ajax("arlima_connect_attach_to_post", {
                attachment: e,
                post: t
            }, i)
        },
        saveList: function (t, e, i) {
            this._ajax("arlima_save_list", {
                alid: t,
                articles: e
            }, i)
        },
        savePreview: function (t, e, i) {
            this._ajax("arlima_save_list", {
                alid: t,
                articles: e,
                preview: 1
            }, i)
        },
        removeImageVersions: function (t, e) {
            this._ajax("arlima_remove_image_versions", {
                attachment: t
            }, e)
        },
        loadCustomTemplateData: function (t) {
            this._ajax("arlima_print_custom_templates", {}, t)
        },
        loadListData: function (t, e, i) {
            this._ajax("arlima_add_list_widget", {
                alid: t,
                version: e
            }, i)
        },
        loadListSetup: function (t) {
            this._ajax("arlima_get_list_setup", {}, t)
        },
        plupload: function (e, i, a) {
            var r = e.substr(e.lastIndexOf(".") + 1).toLowerCase(),
                n = r.indexOf("?");
            n > -1 && (r = r.substr(0, n)), -1 == t.inArray(r, ["jpg", "jpeg", "png", "gif"]) ? (s("Trying to upload something that's not considered to be an image", "error"), "function" == typeof a && a(!1)) : this._ajax("arlima_upload", {
                imgurl: e,
                postid: i
            }, a)
        },
        saveListSetup: function (t, e) {
            this._ajax("arlima_save_list_setup", {
                lists: t
            }, e)
        },
        loadScissorsHTML: function (t, e) {
            this._ajax("arlima_get_scissors", {
                attachment_id: t
            }, e, "html")
        },
        duplicateImage: function (t, e) {
            this._ajax("arlima_duplicate_image", {
                attachid: t
            }, e)
        },
        _ajax: function (i, a, r, n) {
            a.action = i, a._ajax_nonce = e.arlimaNonce, void 0 === n && (n = "json"), t.ajax({
                url: e.ajaxurl,
                type: "POST",
                data: a,
                dataType: n,
                success: function (t) {
                    -1 == t ? (alert(e.lang.loggedOut), t = !1) : t.error && (alert(t.error), t = !1), "function" == typeof r && r(t)
                },
                error: function (t, e) {
                    if (0 == t.status) return s("The request is refused by browser, most probably because of fast reloading of the page before ajax call was completed", "warn"), void 0;
                    var i = t.responseText;
                    if ("undefined" != typeof JSON) {
                        var a = !1;
                        try {
                            a = JSON.parse(i)
                        } catch (n) {}
                        a && a.error !== void 0 && (i = a.error)
                    }
                    alert("ERROR:\n------------\n" + i), s(t, "error"), s(e, "error"), "function" == typeof r && r(!1)
                }
            })
        }
    }, c = {
            _$blocker: null,
            _$form: null,
            _$preview: null,
            currentlyEditedList: !1,
            $item: !1,
            _$imgContainer: !1,
            _$sticky: !1,
            _isSlidingForm: !1,
            _formSlidingCallback: !1,
            _$previewIframe: !1,
            init: function () {
                if (this._$preview = t("#arlima-preview"), this._$form = t("#arlima-edit-article-form"), this._$imgContainer = t("#arlima-article-image"), this._$sticky = t("#sticky-interval"), this._$blocker = t("<div></div>"), this._$blocker.css({
                    height: 0,
                    width: 0
                }).appendTo("body").addClass("arlima-editor-blocker"), t.fn.effect === void 0 && (s("This wordpress application is outdated. Please update to the newest version", "warn"), t.fn.effect = function () {}), "undefined" != typeof arlimaTemplateStylesheets) {
                    this._$preview.html('<iframe style="width:100%; height: 200px; overflow: hidden" border="0" frameborder="0"></iframe>'), this._$previewIframe = t(this._$preview.find("iframe").eq(0).contents());
                    var e = this;
                    t.each(arlimaTemplateStylesheets, function (t, i) {
                        e._$previewIframe.find("head").append('<link rel="stylesheet" type="text/css" href="' + i + '" />')
                    }), this._$previewIframe.find("body").addClass("arlima-preview-iframe").css({
                        border: 0,
                        padding: 0,
                        margin: 0,
                        overflow: "hidden"
                    })
                }
                this.PostConnector.init(this._$form)
            },
            _getFormHeader: function () {
                return this._$form.parent().parent().prev().prev()
            },
            updateArticle: function (i, a) {
                if (void 0 === a && (a = !0), void 0 === i && (i = !0), i && this.currentlyEditedList.toggleUnsavedState(!0), "object" == typeof i) {
                    var s = i.name;
                    "options-" == s.substr(0, 8) ? this.$item.data("article").options[s.substr(8)] = i.value : this.$item.data("article")[s] = i.value, this.updateArticleStreamerPreview(), a && this.isShowingPreview() && this.updatePreview()
                } else {
                    var o = this._$form.serializeObject();
                    o.options = {}, t.each(o, function (t, e) {
                        "options-" == t.substr(0, 8) && (o.options[t.substr(8)] = e, delete o[t])
                    }), o.text = t.tinyMCEContent(), o.image_options = t("#arlima-article-image-options").data("image_options");
                    var l = this.$item.data("article");
                    if (t.extend(l, o), this.$item.data("article", l), r.applyItemPresentation(this.$item, l), this.PostConnector.toggleFutureNotice(n(l.publish_date)), l.options && l.options.sticky && t(".sticky-interval-fancybox").attr("title", e.lang.sticky + " (" + l.options.sticky_interval + ")"), t(".arlima-listitem-title:first", this.$item).html(r.getArticleTitleHTML(l)), this.updateArticleStreamerPreview(), a && this.isShowingPreview() && this.updatePreview(), l.options && l.options.sticky) {
                        this._$sticky.show();
                        var d = this._$sticky.find("input");
                        "" == t.trim(d.val()) && d.val("*:*")
                    } else this._$sticky.hide()
                }
                p.triggerEvent("articleUpdate", this.$item)
            },
            articleTemplate: function (t) {
                var e = this.currentlyEditedList.defaultTemplate(),
                    a = t.options ? t.options.template : void 0;
                return a && (void 0 === i.templates[a] ? s('Use of unknown custom template "' + a + '"', "warn") : e = a), e
            },
            currentArticleTemplate: function () {
                return this.articleTemplate(this.$item.data("article"))
            },
            updatePreview: function () {
                function e(a, r, n, o, l) {
                    if (!i.finishedLoading) return setTimeout(function () {
                        e(a, r, n, o, l)
                    }, 500), void 0;
                    var d = {
                        container: {
                            id: "teaser-" + r.id,
                            "class": "arlima teaser " + (o ? o : "")
                        },
                        article: {
                            html_text: r.text,
                            title: r.title
                        },
                        streamer: !1,
                        image: !1,
                        related: !1,
                        is_child: n,
                        is_child_split: l === !0,
                        sub_articles: !1,
                        format: !1
                    };
                    if ("" != r.title) {
                        var p = r.title.replace("__", "<br />");
                        r.options && r.options.pre_title && (p = '<span class="arlima-pre-title">' + r.options.pre_title + "</span> " + p);
                        var h = c.currentlyEditedList.titleElement || "h2";
                        d.article.html_title = "<" + h + ' style="font-size:' + r.title_fontsize + 'px">' + p + "</" + h + ">"
                    }
                    if (r.options && (r.options.format && (d.container["class"] += " " + r.options.format, d.container.format = r.options.format), r.options.streamer && (d.streamer = {
                        type: r.options.streamer_type,
                        content: "image" == r.options.streamer_type ? '<img src="' + r.options.streamer_image + '" />' : r.options.streamer_content,
                        style: "background: #" + r.options.streamer_color
                    }, "extra" == r.options.streamer_type ? (d.streamer.style = "", d.streamer.content = "EXTRA") : "image" == r.options.streamer_type && (d.streamer.style = ""))), r.image_options && r.image_options.html) {
                        switch (d.image = {
                            src: t(unescape(r.image_options.html)).attr("src"),
                            image_class: r.image_options.alignment,
                            image_size: r.image_options.size,
                            width: "auto"
                        }, r.image_options.size) {
                        case "half":
                            d.image.width = "50%";
                            break;
                        case "third":
                            d.image.width = "33%";
                            break;
                        case "fourth":
                            d.image.width = "25%"
                        }
                        d.container["class"] += " img-" + r.image_options.size
                    }
                    if (r.children) {
                        var m = r.children.length;
                        if (m > 0) {
                            var u = t("<div />");
                            u.addClass("teaser-children children-" + m).appendTo(a), t.each(r.children, function (i, a) {
                                var r = t("<div />"),
                                    s = 0 == i % 2 ? "first" : "last";
                                m > 1 && (s += " teaser-split"), e(r, a, !0, s, m > 1), u.append(r.html())
                            }), d.sub_articles = u.html()
                        }
                    }
                    var v = c.articleTemplate(r),
                        f = i.templates[v];
                    f === void 0 && (f = i.templates.article, s('Trying to use template "' + v + '" but it does not exist, now using article.tmpl instead', "warn")), f = f.replace(/href="([a-zA-Z\.\{+\}\$]+)"/g, 'href="Javascript:void(0)"'), f = f.replace(/{{html image.src}}/g, d.image.src ? d.image.src : "");
                    var g = t("<div>" + f + "</div>").tmpl(d);
                    a.empty().append(g)
                }
                var a = this.serializeArticle(this.$item, !0);
                parseInt(a.parent, 10) > -1 && (a = this.serializeArticle(this.$item.parent().parent(), !0));
                var r = this._$preview;
                if (this._$previewIframe !== !1 && (r = this._$previewIframe.find("body")), e(r, a, !1), this._$previewIframe !== !1) {
                    var n = this,
                        o = function () {
                            var t = r.children().eq(0).outerHeight();
                            n._$preview.find("iframe").eq(0).height(t)
                        };
                    setTimeout(o, 50), r.find("img").bind("load", o)
                }
                var l = t(".arlima-list-previewpage-width", p.getFocusedList().jQuery).val();
                l && c._$preview.children().eq(0).css("width", l + "px"), p.triggerEvent("previewUpdate", this.$item)
            },
            serializeArticle: function (e, i) {
                void 0 === i && (i = !1);
                var a = e.data("article");
                if (a === void 0) return {};
                if (a.title_fontsize || (a.title_fontsize = 24), a.children = [], i && (a.text = a.text.replace(RegExp('(<span)(.*class=".*teaser-entryword.*")>(.*)(</span>)', "g"), '<span class="teaser-entryword">$3</span>')), e.has("ul")) {
                    var r = this;
                    t("ul li", e).each(function () {
                        a.children.push(r.serializeArticle(t(this), i))
                    })
                }
                return a
            },
            edit: function (i, a) {
                p.setFocusedList(a);
                var r = this,
                    s = this.isShowingPreview();
                this.clear(), this.currentlyEditedList = a, this.$item = i;
                var n = a.jQuery.parent();
                n.find(".edited").removeClass("edited"), n.find(".active").removeClass("active"), a.jQuery.addClass("active"), i.addClass("edited");
                var o = this._$form.parent();
                o.is(":visible") || (this._isSlidingForm = !0, o.slideDown("100", function () {
                    r._isSlidingForm = !1, "function" == typeof r._formSlidingCallback && (r._formSlidingCallback(), r._formSlidingCallback = null)
                }));
                var l = this.$item.data("article");
                if (l.title_fontsize || (l.title_fontsize = 24), this.PostConnector.setup(l), t.tinyMCEContent(l.text), t("#arlima-edit-article-title-fontsize-slider").slider("value", l.title_fontsize), t.each(l, function (e, i) {
                    t("[name='" + e + "']", r._$form).not(":radio").val(i)
                }), !e.is_admin) {
                    var d = t("[name='options-admin_lock']", this._$form);
                    d.on("click", function (t) {
                        return t.preventDefault(), !1
                    }), d.parent().addClass("not_allowed").attr("title", e.lang.admin_only), d.parent().find("label").on("click", function (t) {
                        return t.preventDefault(), !1
                    })
                }
                var h = this.articleTemplate(l);
                this.toggleAvailableFormats(h), this.toggleEditorFeatures(h);
                var m = this.currentlyEditedList.defaultTemplate(),
                    u = t("#arlima-edit-article-options-template option");
                u.show(), u.each(function () {
                    return this.value == m ? (t(this).hide(), !1) : !0
                });
                var v = t("[name='options-hiderelated']", this._$form);
                l.options ? (t.each(l.options, function (e, i) {
                    t("[name='options-" + e + "']", r._$form).val(i)
                }), l.options.streamer && t("[name='options-streamer']", this._$form).prop("checked", !0).parent().addClass("checked"), l.options.sticky && t("[name='options-sticky']", this._$form).prop("checked", !0).parent().addClass("checked"), l.options.admin_lock && t("[name='options-admin_lock']", this._$form).prop("checked", !0).parent().addClass("checked"), l.options.streamer_color ? t("#arlima-edit-article-options-streamer-text div", this._$form).css("background", "#" + l.options.streamer_color) : t("#arlima-edit-article-options-streamer-text div", this._$form).css("background", "#000"), v.prop("checked", !1), v.length > 0 && l.options.hiderelated && v.prop("checked", !0)) : v.length > 0 && "checked" == v.attr("data-default") && v.prop("checked", !0), this.updateArticleImage(l.image_options, !1), this.updateArticle(!1), this.currentlyEditedList.isImported ? (this.toggleEditorBlocker(!0), l.image_options.url && c._$imgContainer.find("img").one("load", function () {
                    setTimeout(function () {
                        r.toggleEditorBlocker(!0)
                    }, 100)
                })) : (this.toggleEditorBlocker(!1), this.updateArticleStreamerPreview(), !e.is_admin && l.options && l.options.admin_lock && this.toggleEditorBlocker(!0, e.lang.admin_lock), s && this.togglePreview(!0))
            },
            toggleAvailableFormats: function (e) {
                "" == e && (e = this.currentlyEditedList.defaultTemplate());
                var i = t("#arlima-edit-article-options-format option");
                i.removeAttr("disabled"), i.each(function () {
                    var a = t(this),
                        r = a.attr("data-arlima-template");
                    "" != a.val() && r && -1 == r.indexOf("[" + e + "]") && (a.attr("disabled", "disabled"), a.is(":selected") && (i.get(0).selected = !0))
                })
            },
            updateArticleStreamerPreview: function () {
                var i = t("#arlima-edit-article-options-streamer-type").val();
                t("#arlima-edit-article-options-streamer").is(":checked") ? ("" != t("[name='options-streamer_image']").val() ? t("#arlima-edit-article-options-streamer-image-link").html('<img src="' + t("[name='options-streamer_image']").val() + '" width="170" style="vertical-align:middle;" />') : t("#arlima-edit-article-options-streamer-image-link").html(e.lang.chooseImage), t("#arlima-edit-article-options-streamer-content").show(), t(".arlima-edit-article-options-streamer-choice").not("#arlima-edit-article-options-streamer-" + i).hide(), 0 == i.indexOf("text-") ? t("#arlima-edit-article-options-streamer-text").show().find("div:last").hide() : (t("#arlima-edit-article-options-streamer-" + i).show(), t("#arlima-edit-article-options-streamer-text").find("div:last").show())) : t("#arlima-edit-article-options-streamer-content").hide()
            },
            toggleEditorFeatures: function (e) {
                "" == e && (e = this.currentlyEditedList.defaultTemplate());
                var a = i.templates[e];
                if (void 0 !== a) {
                    var r = t(".arlima-streamer");
                    if (a.indexOf("${streamer.") > -1) r.show();
                    else {
                        var s = r.find("input");
                        s.eq(0).is(":checked") && s[0].click(), r.hide()
                    }
                    a.indexOf("article.html_text") > -1 ? t("#wp-tinyMCE-wrap").show() : t("#wp-tinyMCE-wrap").hide(), a.indexOf("${article.url}") > -1 || a.indexOf("{html article.html_title}") > -1 ? t("#arlima-article-wp-connection").show() : t("#arlima-article-wp-connection").hide(), a.indexOf("{html article.html_title}") > -1 ? (t("#arlima-edit-article-title-fontsize-slider").show(), t("#arlima-edit-article-title-fontsize").show()) : (t("#arlima-edit-article-title-fontsize-slider").hide(), t("#arlima-edit-article-title-fontsize").hide())
                }
            },
            removeImageVersions: function () {
                var e = parseInt(t("#arlima-article-image-attach_id").val(), 10);
                !isNaN(e) && e && d.removeImageVersions(e)
            },
            updateArticleImage: function (e, i) {
                var a = t("#arlima-article-image-size"),
                    r = t("#arlima-article-image-alignment input"),
                    s = t("#arlima-article-image-attach_id"),
                    n = t("#arlima-article-image-updated"),
                    o = t("#arlima-article-image-connected_to_post_thumbnail");
                e && (e.html && this._$imgContainer.html(unescape(e.html)).removeClass("empty"), e.alignment && r.filter("[value=" + e.alignment + "]").prop("checked", !0), e.size && a.val(e.size), e.attach_id && s.val(e.attach_id), e.updated && n.val(e.updated), void 0 !== e.connected && o.val(e.connected)), "full" == a.val() ? (r.filter("[value=aligncenter]").prop("checked", !0), r.parent().hide()) : (r.parent().show(), "aligncenter" == r.filter(":checked").val() && r.filter("[value=alignleft]").prop("checked", !0));
                var l = t("#arlima-article-image-disconnect");
                1 == o.val() || "true" == o.val() ? l.show() : l.hide();
                var d = this._$imgContainer.find("img"),
                    c = {};
                d.length > 0 && (d.removeAttr("width"), d.removeAttr("height"), c = this.createArlimaArticleImageObject(t(d).parent().html(), r.filter(":checked").val(), a.val(), s.val(), n.val(), o.val()));
                var p = t("#arlima-article-image-options");
                p.data("image_options", c), c.html ? (p.show(), t("#arlima-article-image-links .hide-if-no-image").show(), c.attach_id || t("#arlima-article-image-scissors-popup").parent("li").hide()) : (p.hide(), t("#arlima-article-image").addClass("empty"), t("#arlima-article-image-links .hide-if-no-image").hide()), (i === void 0 || i === !0) && this.updateArticle()
            },
            createArlimaArticleImageObject: function (e, i, a, r, s, n) {
                var o = t(e);
                return {
                    html: escape(e),
                    url: o.attr("src"),
                    alignment: i,
                    size: a,
                    attach_id: r,
                    updated: s,
                    connected: n
                }
            },
            removeArticleImage: function (e) {
                t("#arlima-article-image").html("").addClass("empty"), t("#arlima-article-image-options").data("image_options", {}).hide().find(".hide-if-no-image").hide(), (e === void 0 || e === !0) && this.updateArticle()
            },
            isEditingList: function (e) {
                return t.isNumeric(e) || (e = e.id), this.currentlyEditedList && this.currentlyEditedList.id == e
            },
            togglePreview: function (t) {
                this.currentlyEditedList && !this.currentlyEditedList.isImported && (void 0 === t && (t = !this.isShowingPreview()), t ? (this.updatePreview(), this._$preview.show()) : this._$preview.hide())
            },
            isShowingPreview: function () {
                return this._$preview.is(":visible")
            },
            toggleEditorBlocker: function (e, i) {
                if (e === void 0 && (e = !this._$blocker.is(":visible")), e) {
                    var a = this,
                        r = a._$form.parent().parent(),
                        s = this._getFormHeader(),
                        n = function () {
                            var e = s.offset(),
                                n = s.outerWidth();
                            if (a._$blocker.css({
                                height: r.outerHeight() + s.outerHeight() + "px",
                                width: n + "px",
                                top: e.top + "px",
                                left: e.left + "px"
                            }), a._$blocker.show(), i) {
                                var o = a._$blocker.find(".block-msg");
                                0 == o.length && (o = t("<div></div>"), o.addClass("block-msg").appendTo(a._$blocker)), o.text(i)
                            }
                        };
                    this._isSlidingForm ? a._formSlidingCallback = n : n()
                } else this._$blocker.hide(), this._$blocker.find(".block-msg").remove()
            },
            hideForm: function () {
                this._$form.parent().parent().find(".handlediv").trigger("click")
            },
            clear: function () {
                this.currentlyEditedList && this.togglePreview(!1), this.$item = !1, this.currentlyEditedList = !1, this.toggleEditorBlocker(!1), t.tinyMCEContent(""), t(":input", this._$form).not(":button, :submit, :radio, :checkbox").val(""), t(":input", this._$form).prop("checked", !1).prop("selected", !1), t(".arlima-button").removeClass("checked"), t("#arlima-article-image").html(""), t("#arlima-article-image-options").removeData("image_options").hide(), t("#arlima-article-connected-post-change").show(), t("#arlima-article-post_id").hide(), t("#arlima-edit-article-options-streamer-content").hide()
            },
            isEditingArticle: function () {
                return this.$item !== !1
            },
            data: function (t) {
                return this.isEditingArticle() ? this.$item.data("article")[t] : (s("Trying to get article data but no article is being edited", "warn"), !1)
            }
        };
    c.PostConnector = {
        _$openButton: !1,
        _$urlInput: !1,
        _$postIdInput: !1,
        _$targetInput: !1,
        _$info: !1,
        _$tinyMCEMediaButton: !1,
        _$futureNotice: !1,
        $fancyBox: !1,
        init: function (e) {
            this._$info = t("#arlima-article-connected-post", e), this._$tinyMCEMediaButton = t("#tinyMCE-add_media", e), this._$openButton = t("#arlima-article-connected-post-open", e), this._$urlInput = e.find('input[name="options-overriding_url"]'), this._$targetInput = e.find('input[name="options-target"]'), this._$postIdInput = e.find('input[name="post_id"]'), this._$futureNotice = t("#future-notice", e), this.$fancyBox = t("#post-connect-fancybox")
        },
        setup: function (t) {
            var e = this;
            if (0 == t.post_id && (t.post_id = null), t.post_id) this._$info.html(""), this._$tinyMCEMediaButton.attr("href", "media-upload.php?post_id=" + t.post_id + "&type=image&TB_iframe=1&send=true"), d.getPost(t.post_id, function (t) {
                e._setConnectionLabel("(post #" + t.ID + ") " + t.post_title, t.post_title)
            });
            else {
                this._$tinyMCEMediaButton.attr("href", "media-upload.php?type=image&TB_iframe=1&send=true");
                var i = t.options.overriding_url || "";
                this._setConnectionLabel(i, i)
            }
            this._toggleOpenLink(t), this._$openButton.unbind("click"), this._$openButton.bind("click", function () {
                if (t.post_id) a.open("post.php?post=" + t.post_id + "&action=edit");
                else {
                    var e = t.options.overriding_url || !1;
                    e && a.open(e)
                }
                return !1
            })
        },
        _toggleOpenLink: function (e) {
            e.post_id || e.options.overriding_url ? t("#arlima-article-connected-post-open").show() : t("#arlima-article-connected-post-open").hide()
        },
        toggleFutureNotice: function (t) {
            t ? this._$futureNotice.show() : this._$futureNotice.hide()
        },
        getConnectionLabel: function () {
            return this._$info.find("em").attr("title")
        },
        _setConnectionLabel: function (t, e) {
            this._$info.html('<em style="color:#666" class="tooltip" title="' + t + '">' + o(e) + "</em>")
        },
        connect: function (e, i) {
            var a = c.$item.data("article");
            if (t.isNumeric(e)) {
                if (a.post_id != e) {
                    this._$urlInput.val(""), this._$targetInput.val(""), this._$postIdInput.val(e);
                    var r = this;
                    d.getPost(e, function (e) {
                        e && e.url ? (t("#arlima-edit-article-url").val(e.url), a.publish_date = e.publish_date, r._setConnectionLabel("(post #" + e.ID + ") " + e.post_title, e.post_title)) : (a.publish_date = 3, alert("This post has been removed")), c.updateArticle(!0, !1)
                    })
                }
            } else(a.url != e || a.options.target != i) && (this._setConnectionLabel(e, e), this._$targetInput.val(i), this._$postIdInput.val(""), this._$urlInput.val(e), a.publish_date = 3, c.updateArticle(!0, !1));
            this._toggleOpenLink(a)
        }
    };
    var p = {
        _focusedList: !1,
        _lists: {},
        _$element: null,
        previewWindow: !1,
        _events: {},
        init: function (e) {
            this._$element = t(e)
        },
        addEventListener: function (t, e) {
            this.removeEventListener(t, e), void 0 === this._events[t] && (this._events[t] = []), this._events[t].push(e)
        },
        removeEventListener: function (t, e) {
            if (void 0 !== this._events[t])
                for (var i = 0; this._events[t].length > i; i++)
                    if (this._events[t][i] == e) return this._events[t].splice(i, 1), !0;
            return !1
        },
        triggerEvent: function (e, i, a) {
            var r = this._events[e];
            if (t.isArray(r))
                for (var s = 0; r.length > s; s++) r[s](i, a)
        },
        loadCustomTemplates: function () {
            d.loadCustomTemplateData(function (e) {
                if (e) {
                    var i = t("#arlima-templates");
                    i.html(e.html), t(".dragger", i).each(function (i, a) {
                        r.prepareArticleForListTransactions(t(a), e.articles[i])
                    }).draggable({
                        helper: "clone",
                        sender: "postlist",
                        handle: ".handle",
                        connectToSortable: ".arlima-list",
                        revert: "invalid"
                    })
                }
            })
        },
        iterateLists: function (t) {
            for (var e in this._lists) this._lists.hasOwnProperty(e) && t(this._lists[e])
        },
        getFocusedList: function () {
            return this._focusedList
        },
        addList: function (t, e, i) {
            if (this.hasList(t)) this._lists[t].jQuery.effect("shake", {
                times: 4,
                distance: 10
            }, 500);
            else {
                var a = this;
                d.loadListData(t, "", function (s) {
                    if (s && s.exists) {
                        var n = r.create(t, s, a._$element, e);
                        a._lists[t] = n, n.jQuery.find(".arlima-list").hide().slideDown("fast", function () {
                            n.jQuery.trigger("init-list-container")
                        })
                    }
                    "function" == typeof i && i()
                })
            }
        },
        getUnsavedLists: function () {
            var t = [];
            return this.iterateLists(function (e) {
                e.isUnsaved && t.push(e)
            }), t
        },
        reloadList: function (i, a) {
            c.isEditingList(i) && (c.clear(), c.hideForm()), i.toggleUnsavedState(!1), i.toggleAjaxLoader(!0), i.jQuery.find(".arlima-list").fadeOut("fast", function () {
                d.loadListData(i.id, a, function (a) {
                    i.toggleAjaxLoader(!1), a && (a.exists ? (i.fill(a.articles, !1, !0), i.displayVersionInfo(a.version, a.versioninfo, a.versions), i.jQuery.find(".arlima-list").fadeIn("fast"), i.isImported || (t("html").trigger("click"), i.toggleUnsavedState(a.versions[0] != a.version.id))) : (alert(e.lang.listRemoved), p.removeList(i.id)), p.triggerEvent("listLoaded", i))
                })
            })
        },
        hasList: function (t) {
            return this._lists[t] !== void 0
        },
        removeList: function (t) {
            var e = "object" == typeof t ? t.id : "" + t;
            if (this.hasList(e)) {
                c.isEditingList(e) && (c.clear(), c.hideForm());
                var i = this;
                this._lists[e].jQuery.slideUp("fast", function () {
                    this._focusedList && this._focusedList.id == e && (c.clear(), this._focusedList = !1), delete i._lists[e]
                })
            } else s("Trying to remove list that does not exist " + e, "warn")
        },
        loadSetup: function (e) {
            t("#setup-loader").show();
            var i = function () {
                t("#setup-loader").hide(), "function" == typeof e && e()
            };
            d.loadListSetup(function (e) {
                if (e) {
                    var a = e.length;
                    0 == a ? i() : t.each(e, function (t, e) {
                        var r = {
                            top: parseInt(e.top),
                            left: parseInt(e.left),
                            height: parseInt(e.height),
                            width: parseInt(e.width)
                        };
                        p.addList(e.alid, r, function () {
                            a--, 0 == a && i()
                        })
                    })
                } else i()
            })
        },
        saveSetup: function () {
            var e = t("#save-setup-loader");
            e.show();
            var i = [];
            this.iterateLists(function (t) {
                var e = t.jQuery.position();
                i.push({
                    alid: t.id,
                    top: e.top,
                    left: e.left,
                    width: t.jQuery.width(),
                    height: t.jQuery.height()
                })
            }), d.saveListSetup(i, function () {
                e.hide()
            })
        },
        saveFocusedList: function () {
            if (this._focusedList) {
                if (this.getFocusedList().isUnsaved) {
                    var i = this.getFocusedList();
                    i.toggleAjaxLoader(!0);
                    var a = t(".arlima-version-id", i.jQuery).val();
                    d.getLaterVersion(i.id, a, function (a) {
                        if (a) {
                            var r = !0;
                            if (a.version && (r = confirm(e.lang.laterVersion + " \r\n " + a.versioninfo + "\r\n" + e.lang.overWrite)), t(".streamer-extra", i.jQuery).length > 1 && (r = confirm(e.lang.severalExtras + "\r\n" + e.lang.overWrite)), r) {
                                var s = [];
                                i.jQuery.find(".arlima-list").children().each(function (e, i) {
                                    s.push(c.serializeArticle(t(i)))
                                }), d.saveList(i.id, s, function (t) {
                                    i.toggleUnsavedState(!1), i.toggleAjaxLoader(!1), i.displayVersionInfo(t.version, t.versioninfo, t.versions)
                                })
                            } else i.toggleAjaxLoader(!1)
                        }
                    })
                }
            } else alert(e.lang.noList)
        },
        searchWordpressPosts: function (e) {
            t("#arlima-get-posts-loader").show();
            var i = {
                offset: e
            };
            return t("#arlima-post-search input, #arlima-post-search select").each(function () {
                this.name && (this.type ? "checkbox" == this.type && t(this).is(":checked") ? i[this.name] = this.value : "checkbox" != this.type && (i[this.name] = this.value) : i[this.name] = t(this).val())
            }), d.queryPosts(i, function (e) {
                if (t("#arlima-get-posts-loader").hide(), e) {
                    var i = t("#arlima-posts");
                    i.html(e.html), t(".dragger", i).each(function (i, s) {
                        var n = t(s),
                            o = e.posts[i],
                            l = o.content;
                        delete o.content, r.prepareArticleForListTransactions(n, o);
                        var d = {
                            position: {
                                my: "right top",
                                at: "center left",
                                viewport: t(a)
                            },
                            style: {
                                classes: "ui-tooltip-shadow ui-tooltip-light ui-tooltip-480"
                            }
                        };
                        d.content = '<h2 style="margin:0;">' + o.title + "</h2>" + l, t("a", n.parents("tr")).qtip(d)
                    }), t(".dragger", i).draggable({
                        helper: "clone",
                        sender: "postlist",
                        handle: ".handle",
                        connectToSortable: ".arlima-list",
                        revert: "invalid"
                    })
                }
            }), !1
        },
        getList: function (e) {
            var i;
            return i = t.isNumeric(e) ? this._lists["" + e] : this._lists[e.closest(".arlima-list-container").attr("data-list-id")], void 0 == i ? !1 : i
        },
        setFocusedList: function (e) {
            var i = t.isNumeric(e) ? e : "" + e.id;
            this._focusedList = this._lists[i]
        },
        previewFocusedList: function () {
            this._focusedList ? this.previewList(this._focusedList) : alert(e.lang.noList)
        },
        previewList: function (i) {
            var r = t(".arlima-list-previewpage", i.jQuery).val();
            if (r)
                if (i.isUnsaved) {
                    i.toggleAjaxLoader(!0);
                    var s = this;
                    s.previewWindow && s.previewWindow.close(), s.previewWindow = a.open(null, "arlimaPreviewWindow", "toolbar=1,scrollbars=1,width=10,height=10");
                    var n = function () {
                        if (i.toggleAjaxLoader(!1), s.previewWindow) {
                            var n = "/" == r || r == e.baseurl + "/" ? e.baseurl + "/" : r,
                                o = r.indexOf("?") > -1 ? "&" : "?";
                            n += "/" == r ? o + e.preview_query_arg : o + e.preview_query_arg, n += "=" + i.id, s.previewWindow.document.location = n;
                            var l = t(a);
                            s.previewWindow.resizeTo(l.width(), l.height());
                            var d = 0,
                                c = setInterval(function () {
                                    d++, d > 4 || !s.previewWindow ? clearInterval(c) : s.previewWindow.document && s.previewWindow.jQuery && (clearInterval(c), s.previewWindow.jQuery(s.previewWindow.document).ready(function () {
                                        s._addPreviewWindowListeners(s.previewWindow.jQuery(s.previewWindow.document), i)
                                    }))
                                }, 500);
                            s.previewWindow.focus()
                        } else alert("Your browser has blocked preview popup")
                    };
                    if (i.isUnsaved) {
                        var o = {};
                        t(">li", i.jQuery.find(".arlima-list")).each(function (e, i) {
                            o[e] = c.serializeArticle(t(i))
                        }), d.savePreview(i.id, o, function (t) {
                            t && n()
                        })
                    } else n()
                } else a.open(r);
                else alert(e.lang.missingPreviewPage)
        },
        _addPreviewWindowListeners: function (i, r) {
            i.ready(function () {
                var a = t("<div></div>");
                a.css({
                    position: "fixed",
                    top: "30px",
                    left: "0",
                    width: "100%",
                    zIndex: "9999"
                }).appendTo(i.find("body"));
                var s = -1 == navigator.userAgent.indexOf("Mac") ? "ctrl" : "cmd";
                t("<div></div>").text(s + " + s " + e.lang.savePreview + ' "' + r.getDisplayName() + '"').css({
                    background: "#222",
                    backgroundColor: "rgba(0,0,0, .85)",
                    fontSize: "13px",
                    color: "#FFF",
                    margin: "16px",
                    padding: "10px",
                    webkitborderRadius: "12px",
                    mozBorderRadius: "13px",
                    borderRadius: "12px",
                    fontWeight: "bold",
                    webkitBoxShadow: "0 0 7px #333",
                    mozBoxShadow: "0 0 7px #333",
                    boxShadow: "0 0 7px #333"
                }).appendTo(a)
            });
            var s = this;
            i.keydown(function (t) {
                var e = t.keyCode ? t.keyCode : t.which;
                return 83 == e && l(t) ? (s.saveFocusedList(), s.previewWindow.close(), a.focus(), t.preventDefault(), !1) : void 0
            })
        },
        dump: function () {
            var t = 0;
            this.iterateLists(function () {
                t++
            });
            var e = "# Having " + t + " lists on page\n",
                i = this.getUnsavedLists();
            if (0 == i.length) e += "# Having 0 unsaved lists\n";
            else {
                e += "# Having " + i.length + " unsaved lists:\n";
                for (var a = 0; i.length > a; a++) {
                    var r = i[a];
                    e += "   - " + r.getDisplayName() + "\n"
                }
            }
            this.getFocusedList() ? (e += '# Has focus on list "' + this.getFocusedList().getDisplayName() + '", the list has ' + (this.getFocusedList().isUnsaved ? "changes" : "no changes") + "\n", c.isEditingArticle() && (e += '# Article "' + c.$item.find(".arlima-listitem-title").text() + '" is being edited')) : e += "# Has no focused list\n# Has no article in the editor", s(e, "log");
            try {
                s(c.$item.data("article"), "log")
            } catch (n) {}
        }
    };
    r.prototype.toggleAjaxLoader = function (e) {
        var i = t(".ajax-loader", this.jQuery);
        e ? i.show() : i.hide()
    }, r.prototype.defaultTemplate = function () {
        return t(".arlima-list-previewtemplate", this.jQuery).val()
    }, r.prototype.rePositionStickyArticles = function (e) {
        void 0 === e && (e = "insertBefore");
        var i = this;
        this.jQuery.find(".sticky").each(function () {
            var a = t(this),
                r = a.prevAll().length,
                s = a.data("article").options.sticky_pos;
            if (s != r) {
                var n = i.jQuery.find(".listitem:not(ul ul > *)");
                a[e](s > n.length ? n.eq(n.length - 1) : n.eq(s)), a.prevAll().length != s && (s++, a[e](s > n.length ? n.eq(n.length - 1) : n.eq(s)))
            }
        })
    }, r.prototype.fill = function (i, a, s) {
        void 0 === s && (s = !0);
        var n = this,
            o = a ? a : this.jQuery.find(".arlima-list");
        o.html(""), t.each(i, function (i, a) {
            var s = t("<li />");
            if (s.addClass("listitem").html('<div><span class="arlima-listitem-title"></span><img class="arlima-listitem-remove" alt="remove" src="' + e.imageurl + 'close-icon.png" /></div>'), r.bindArticleItemEvents(s), r.prepareArticleForListTransactions(s, a), a.children.length > 0) {
                var l = t("<ul />");
                s.append(l), n.fill(a.children, l, !1)
            }
            o.append(s)
        }), s && this.applyListBehavior()
    }, r.bindArticleItemEvents = function (e) {
        e.find(".arlima-listitem-remove").click(function (e) {
            var i = t(this).parent().parent();
            return p.getList(i).removeListItem(i, l(e)), e.stopPropagation(), !1
        }), e.find("div").click(function (e) {
            var i = t(this).parent(),
                a = p.getList(i);
            return c.edit(i, a), e.stopPropagation(), !1
        })
    }, r.prototype.getDisplayName = function () {
        return t.trim(this.jQuery.find(".arlima-list-header").text())
    }, r.prototype.removeListItem = function (i, a) {
        var r = i.data("article");
        if (r.options && r.options.admin_lock && !e.is_admin) return alert(e.lang.admin_lock), void 0;
        if (a || confirm(e.lang.wantToRemove + t("span", i).text() + e.lang.fromList)) {
            this.toggleUnsavedState(!0), p.setFocusedList(this.id);
            var s = this;
            i.fadeOut("fast", function () {
                var e = i.hasClass("edited");
                t(this).remove(), s.rePositionStickyArticles("insertAfter"), c.isEditingList(p.getFocusedList().id) && (e ? (c.clear(), c.hideForm()) : c.updatePreview())
            })
        }
    }, r.prototype.displayVersionInfo = function (e, i, r) {
        if (this.isImported) this.jQuery.find(".arlima-list-version-info").text(i);
        else {
            var s = this.jQuery.find(".arlima-list-version-info");
            s.html("v " + e.id).attr("title", i).qtip({
                position: {
                    my: "right top",
                    at: "center left",
                    viewport: jQuery(a)
                },
                style: h
            });
            var n = this.jQuery.find(".arlima-list-version-ddl");
            n.html(""), t.each(r, function (i, a) {
                var r = t("<option></option>", {
                    value: a,
                    selected: a == e.id
                }).text("v " + a + " ");
                n.append(r)
            }), this.jQuery.find(".arlima-version-id").val(e.id)
        }
    }, r.prototype.toggleUnsavedState = function (t) {
        t = t === !0, t != this.isUnsaved && (this.isUnsaved = t, this.isUnsaved ? (this.jQuery.addClass("unsaved"), this.jQuery.find(".arlima-save-list").show()) : (this.jQuery.removeClass("unsaved"), this.jQuery.find(".arlima-save-list").hide()))
    }, r.prepareArticleForListTransactions = function (e, i) {
        e.hasClass("dragger") || t(".arlima-listitem-title", e).html(r.getArticleTitleHTML(i)), e.data("article", i), r.applyItemPresentation(e, i)
    }, r.applyItemPresentation = function (t, i) {
        if (i.publish_date ? n(i.publish_date) ? (t.addClass("future"), t.attr("title", new Date(1e3 * i.publish_date))) : (t.removeAttr("title"), t.removeClass("future")) : (t.removeAttr("title"), t.removeClass("future")), i.options && i.options.sticky) {
            t.addClass("sticky");
            var a = t.attr("title");
            void 0 === a && (a = ""), t.attr("title", a + " " + e.lang.sticky + " (" + i.options.sticky_interval + ")")
        } else t.removeClass("sticky"), t.hasClass("future") || t.removeAttr("title");
        return !1
    }, r.getArticleTitleHTML = function (t) {
        var e = "";
        if (t.title ? e = t.title.replace(/__/g, "") : t.text && (e += "[" + t.text.replace(/(<.*?>)/gi, "").replace(/__/g, "").substring(0, 30) + "...]"), t.options && t.options.pre_title && (e = t.options.pre_title + " " + e), t.options && t.options.streamer) {
            var i;
            switch (t.options.streamer_type) {
            case "extra":
                i = "black";
                break;
            case "image":
                i = "blue";
                break;
            default:
                i = "#" + t.options.streamer_color
            }
            "#" == i && (i = "black"), e = '<span class="arlima-streamer-indicator" style="background:' + i + '"></span> ' + e
        }
        return t.options && t.options.sticky && (e = '<span class="sticky-icon">' + e + "</span>"), e
    }, r.create = function (i, a, s, n) {
        var o = t("<div></div>");
        if (o.addClass("arlima-list-container" + (a.is_imported ? " imported" : "")).attr("id", "arlima-list-container-" + i).attr("data-list-id", i).html(a.html).appendTo(s), !n) {
            n = {};
            var d = s.find("div:last");
            if (d.length > 0) {
                var c = d.position(),
                    h = 0,
                    m = 0;
                c.left + d.width() + 300 <= o.width() && (m = c.left + d.width(), h = c.top), n = {
                    top: h + "px",
                    left: m + "px"
                }
            }
        }
        o.css(n), o.bind("init-list-container", function () {
            var i = p._lists[t(this).attr("data-list-id")];
            i.jQuery.resizable({
                containment: "parent"
            }), i.jQuery.draggable({
                containment: "parent",
                snap: !0,
                handle: ".arlima-list-header"
            }), i.applyListBehavior(), i.jQuery.disableSelection(), t(".arlima-refresh-list", this).click(function (t) {
                var a = !0;
                return !l(t) && i.isUnsaved && (a = confirm(e.lang.hasUnsavedChanges)), a && p.reloadList(i), !1
            }), t(".arlima-list-container-remove", this).click(function (t) {
                var a = !0;
                return !l(t) && i.isUnsaved && (a = confirm(e.lang.hasUnsavedChanges)), a && p.removeList(i.id), !1
            }), i.isImported || (t(".arlima-save-list", this).click(function () {
                return i.isUnsaved ? (p.setFocusedList(i.id), p.saveFocusedList(), !1) : !1
            }), t(".arlima-preview-list", this).click(function () {
                return p.previewList(i), !1
            }), t(".arlima-list-version-ddl", this).change(function (a) {
                var r = !0;
                !l(a) && i.isUnsaved && (r = confirm(e.lang.hasUnsavedChanges)), r && p.reloadList(i, t(this).val())
            }), t(".arlima-list-version", this).click(function (e) {
                return t(".arlima-list-version-info", this).hide(), t(".arlima-list-version-select", this).show(), e.stopPropagation(), !1
            }))
        });
        var u = new r(i, o, a.is_imported, a.title_element);
        return u.fill(a.articles, !1, !1), u.displayVersionInfo(a.version, a.versioninfo, a.versions), u
    }, r.listsInolvedInTransaction = [], r.copyFromList = !1, r.prototype.applyListBehavior = function () {
        var i = function (e, i) {
            e.data(t.extend({}, i.data()));
            var a = e.find("ul li");
            if (a.length > 0)
                for (var r = i.find("ul li"), s = a.length - 1; s >= 0; s--) a.eq(s).data(t.extend({}, r.eq(s).data()))
        };
        if (this.isImported) this.jQuery.find("li").draggable({
            sender: "importedlist",
            helper: "clone",
            handle: ".handle",
            connectToSortable: ".arlima-list",
            revert: "invalid",
            zIndex: 40,
            start: function (e, i) {
                var a = t(e.currentTarget);
                i.helper.width(a.width())
            }
        });
        else {
            var a = this,
                s = this.jQuery.find("ul:first");
            s.hasClass("ui-sortable") || s.nestedSortable({
                items: "li",
                listType: "ul",
                maxLevels: 2,
                opacity: .6,
                tabSize: 30,
                tolerance: "pointer",
                connectWith: [".arlima-list:not(.imported)"],
                distance: 15,
                placeholder: "arlima-listitem-placeholder",
                forcePlaceholderSize: !0,
                toleranceElement: "> div",
                start: function (e) {
                    r.copyFromList = !1, r.listsInolvedInTransaction = [], l(e) && (r.copyFromList = parseInt(t(this).parent().parent().attr("data-list-id")), -1 == t.inArray(r.copyFromList, r.listsInolvedInTransaction) && r.listsInolvedInTransaction.push(r.copyFromList))
                },
                helper: function (e, a) {
                    if (l(e)) {
                        var r = t(a.clone(!0).insertAfter(a));
                        i(r, a), r.hasClass("edited") && r.removeClass("edited"), r.effect("highlight", 500)
                    }
                    return a.clone()
                },
                receive: function (e, s) {
                    var n = t(s.item),
                        o = n.hasClass("dragger"),
                        l = n.hasClass("ui-draggable");
                    if (o || l) {
                        var d = o ? "dragger" : "ui-draggable",
                            h = t(this).find("." + d + ":first");
                        i(h, n);
                        var m = h.data("article");
                        h.removeClass("dragger"), h.removeClass("ui-draggable"), o && (t(".arlima-listitem-title", h).html(r.getArticleTitleHTML(m)), h.data("article", m));
                        var u = p.getList(n);
                        (!u || u.isImported) && r.bindArticleItemEvents(h), u && u.isImported && p.triggerEvent("articleImported", n);
                        var v = [];
                        u && u.isImported && !t.isEmptyObject(m.children) && t.each(m.children, function (t, e) {
                            e.image_options && e.image_options.url && v.push([e.image_options.url, h.find(".listitem").eq(t)])
                        }), u && u.isImported && m.image_options.url && !m.image_options.attach_id ? r.uploadExternalImage(m.image_options.url, h, function () {
                            v.length > 0 && r.uploadExternalImage(v)
                        }) : v.length > 0 && r.uploadExternalImage(v), r.copyFromList && !l || n[0] != c.$item[0] || c.edit(h, a)
                    }
                },
                update: function (e, i) {
                    var a = t(i.item),
                        s = a.parent().parent(),
                        n = a.data("article");
                    n.parent = s && s.hasClass("listitem") ? s.prevAll().length : -1, a.data("article", t.extend({}, n));
                    var o = parseInt(t(this).parent().parent().attr("data-list-id")); - 1 == t.inArray(o, r.listsInolvedInTransaction) && r.listsInolvedInTransaction.push(o), a.effect("highlight", 500), r.applyItemPresentation(a, n)
                },
                stop: function (i, a) {
                    var s = t(a.item),
                        n = s.data("article");
                    if (!e.is_admin && n.options && n.options.admin_lock && n.options.sticky) return t(this).nestedSortable("cancel"), void 0;
                    if (r.copyFromList && 1 == r.listsInolvedInTransaction.length) {
                        s.data("article", t.extend({}, s.data("article")));
                        var o = r.listsInolvedInTransaction[0];
                        p.getList(o).toggleUnsavedState(!0), p.setFocusedList(o)
                    } else r.copyFromList ? t.each(r.listsInolvedInTransaction, function (t, e) {
                        e != r.copyFromList && (p.getList(e).toggleUnsavedState(!0), p.setFocusedList(e))
                    }) : t.each(r.listsInolvedInTransaction, function (t, e) {
                        p.getList(e).toggleUnsavedState(!0), p.setFocusedList(e)
                    });
                    s.hasClass("sticky") ? (n.options.sticky_pos = s.prevAll().length, c.$item[0] == s[0] && t("#arlima-option-sticky-pos").val(n.options.sticky_pos), s.data("article", n)) : t.each(r.listsInolvedInTransaction, function (t, e) {
                        var i = p.getList(e);
                        i.isImported || i.rePositionStickyArticles()
                    }), p.triggerEvent("articleDropped", s), r.listsInolvedInTransaction.length = 0
                }
            })
        }
    }, r.uploadExternalImage = function (e, i, a) {
        c._$imgContainer.addClass("ajax-loader-icon");
        var r = function (t, e) {
            if (e[0] == c.$item[0]) c.updateArticleImage({
                html: t.html,
                size: "full",
                attach_id: t.attach_id
            });
            else {
                var i = e.data("article");
                i.image_options = c.createArlimaArticleImageObject(t.html, "aligncenter", "full", t.attach_id, 0, ""), e.data("article", i), p.getList(e).toggleUnsavedState(!0)
            }
        };
        if (t.isArray(e)) {
            var n = function () {
                if (0 == e.length) c._$imgContainer.removeClass("ajax-loader-icon"), "function" == typeof a && a();
                else {
                    var t = e.splice(0, 1)[0];
                    d.plupload(t[0], "", function (e) {
                        e ? r(e, t[1]) : s("Unable to upload external image", "error"), n()
                    })
                }
            };
            n()
        } else d.plupload(e, "", function (t) {
            c._$imgContainer.removeClass("ajax-loader-icon"), t ? r(t, i) : s("Unable to upload external image", "error"), "function" == typeof a && a()
        })
    };
    var h = {
        name: "dark",
        tip: !0,
        padding: "1px 3px",
        fontSize: 11,
        background: "#111",
        border: {
            width: 2,
            radius: 5,
            color: "#111"
        }
    };
    return {
        Backend: d,
        ArticleEditor: c,
        Manager: p,
        List: r,
        qtipStyle: h
    }
}(jQuery, ArlimaJS, ArlimaTemplateLoader, window);