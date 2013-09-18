/**
 * This file contains everything that happens when the page for
 * the list manager gets loaded
 *
 * @todo Fix all insufficient use of jQuery
 */

function arlimaTinyMCEChanged() {
    Arlima.ArticleEditor.updateArticle()
}
jQuery(function (t) {
    Arlima.Manager.init("#arlima-container-area"), Arlima.ArticleEditor.init(), ArlimaUploader.init(), Arlima.Manager.loadCustomTemplates(), ArlimaJS.is_admin && t.isNumeric(ArlimaJS.is_admin) && (ArlimaJS.is_admin = parseInt(ArlimaJS.is_admin)), Arlima.Manager.loadSetup(function () {
        "undefined" != typeof loadArlimListOnLoad && Arlima.Manager.addList(loadArlimListOnLoad)
    }), setTimeout(function () {
        t("#tinyMCE_ifr").css("height", "120px")
    }, 800), t("[title].tooltip").qtip({
        position: {
            my: "left top",
            at: "center right"
        },
        style: Arlima.qtipStyle
    }), t("[title].tooltip-left").qtip({
        position: {
            corner: {
                tooltip: "bottomLeft",
                target: "topLeft"
            }
        },
        style: Arlima.qtipStyle
    }), t(".fancybox").fancybox({
        speedIn: 300,
        speedOut: 300,
        titlePosition: "over"
    });
    var e = t("#arlima-article-image-options");
    t("#arlima-article-image-scissors-popup").fancybox({
        autoResize: 1,
        fitToView: 1,
        margin: new Array(40,0,0,0),
        speedIn: 300,
        speedOut: 300,
        titlePosition: "over",
        afterClose: function () {
        t("#arlima-article-image-container").removeClass("arlima-fancybox media-item-info"),
            t("#arlima-article-image-container").addClass("media-item-info"), 
            t("#arlima-article-image-container").removeAttr("style"), 
            t("#arlima-article-image img").removeClass("thumbnail"), 
            t("#arlima-article-image-scissors").html("").hide(), 
            Arlima.ArticleEditor.updateArticleImage({
                updated: Math.round((new Date).getTime() / 1e3)
            }), Arlima.ArticleEditor.removeImageVersions()                
        },
        beforeClose: function () {
   	
        },
        beforeLoad: function () {
            var i = e.data("image_options");
            Arlima.Backend.loadScissorsHTML(i.attach_id, function (e) {
                e && t("#arlima-article-image-scissors").html(e).show()
            }), t("#arlima-article-image-container").addClass("arlima-fancybox media-item-info"), t("#arlima-article-image img").addClass("thumbnail").removeAttr("width").removeAttr("height")
        }
    });
    var i = Arlima.ArticleEditor.PostConnector.$fancyBox,
        a = i.find(".button"),
        r = !1;
    t("#arlima-article-connected-post-change").fancybox({
        autoResize: 1,
        fitToView: 1,
        speedIn: 300,
        speedOut: 300,
        titlePosition: !1,
        beforeClose: function () {},
        afterClose: function () {
            if (r !== !1) {
                var t = void 0,
                    e = "";
                "external" === r ? (t = i.find("select").val(), e = i.find("input.url").val()) : e = i.find("input.post-connection").val(), Arlima.ArticleEditor.PostConnector.connect(e, t), r = !1
            }
        },
        beforeLoad: function () {
            var t = Arlima.ArticleEditor.PostConnector,
                e = Arlima.ArticleEditor.$item.data("article");
            if (r = !1, i.find(".connection").text(t.getConnectionLabel()), i.find(".invalid-info").remove(), i.find(".connection-containers").hide(), a.filter(".open").css("opacity", 1), i.find("input,select").val(""), i.find("option").removeAttr("selected"), !e.post_id) {
                i.find(".url").val(e.options.overriding_url || "");
                var s = e.options.target;
                s && i.find('option[value="' + s + '"]').attr("selected", "selected")
            }
        }
    }), a.filter(".open").click(function () {
        return a.filter(".open").css("opacity", 1), this.style.opacity = "0.6", i.find(".connection-containers").hide(), i.find("." + this.href.split("#")[1]).show(), !1
    }), i.find("input,select").bind("change", function () {
        r = i.find(".external-url").is(":visible") ? "external" : "post-id";
        var e = t(this);
        if (e.hasClass("url")) {
            var a = e.val();
            0 === a.indexOf("#") || "" == a || 0 === a.toLowerCase().indexOf("javascript: ") || a.match(/(^|\s)((https?:\/\/)?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/gi) ? e.parent().find(".invalid-info").remove() : e.after('<em style="color:darkred" class="invalid-info"><br />' + ArlimaJS.lang.invalidURL + "</em>")
        }
    }), i.find(".do-search").click(function () {
        var e = t.trim(i.find('input[type="search"]').val());
        return Arlima.Backend.queryPosts({
            search: e
        }, function (e) {
            var a = i.find(".search-result");
            if (0 == e.posts.length) a.html("<p>" + ArlimaJS.lang.nothingFound + "</p>");
            else {
                var s = "";
                t.each(e.posts, function (t, e) {
                    s += '<p><a href="#" data-post="' + e.post_id + '">' + e.title + "</a></p>"
                }), a.html(s), a.find("a").click(function () {
                    return r = "post-id", i.find(".post-connection").val(t(this).attr("data-post")), t("#fancybox-close").click(), !1
                })
            }
        }), !1
    }), t(".sticky-interval-fancybox").fancybox({
        autoResize: 1,
        fitToView: 1,
        speedIn: 300,
        speedOut: 300,
        titlePosition: !1,
        beforeClose: function () {},
        afterClose: function () {
            var e = Arlima.ArticleEditor.$item.data("article"),
                i = e.options.sticky_interval,
                a = t("#sticky-interval-fancybox"),
                r = function (t) {
                    var e = "*",
                        i = a.find("." + t);
                    return i.filter(":checked").length != i.length && (e = "", i.filter(":checked").each(function () {
                        e += "," + this.value
                    }), e = "" == e ? "*" : e.substr(1)), e
                }, s = r("day") + ":" + r("hour");
            s != i && (e.options.sticky_interval = s, Arlima.ArticleEditor.$item.data("article", e), t("#arlima-interval").val(e.options.sticky_interval), Arlima.ArticleEditor.updateArticle(!0, !1))
        },
        beforeLoad: function () {
            var e = Arlima.ArticleEditor.$item.data("article"),
                i = t("#sticky-interval-fancybox").find("input");
            i.removeAttr("checked"), t.each(e.options.sticky_interval.split(":"), function (e, a) {
                if ("*" == t.trim(a)) {
                    var r = 0 == e ? ".day" : ".hour";
                    i.filter(r).attr("checked", "checked")
                } else t.each(a.split(","), function (t, e) {
                    i.filter('[value="' + e + '"]').attr("checked", "checked")
                })
            })
        }
    });
    for (var s = t("#sticky-hour-container").children().eq(0), n = 1; 25 > n; n++) {
        var o = 10 > n ? "0" + n : n,
            l = 0 === n % 8 ? "<br />" : "";
        t('<label><input type="checkbox" class="hour" value="' + o + '" /> ' + o + "</label>" + l).insertBefore(s)
    }
    var c = !1,
        d = t("#arlima-edit-article-title-fontsize"),
        p = t("#arlima-edit-article-title-fontsize-slider").slider({
            value: 18,
            min: 8,
            max: 100,
            slide: function (t, e) {
                c = !0, d.val(e.value), Arlima.ArticleEditor.updateArticle()
            }
        }).mousedown(function () {
            c = !0
        });
    t("#arlima-edit-article-options-streamer-color").colourPicker({
        ico: "",
        title: !1
    }), setInterval(function () {
        Arlima.Manager.iterateLists(function (t) {
            t.isImported && !Arlima.ArticleEditor.isEditingList(t.id) && Arlima.Manager.reloadList(t)
        })
    }, 9e4), t("#arlima-edit-article-options-template").change(function () {
        Arlima.ArticleEditor.toggleAvailableFormats(this.value), Arlima.ArticleEditor.toggleEditorFeatures(this.value), Arlima.ArticleEditor.updatePreview(), Arlima.Manager.triggerEvent("templateChange")
    }), t("#arlima-search-lists").arlimaListSearch("#arlima-lists .arlima-list-link"), t(".arlima-list-link").on("click", function () {
        Arlima.Manager.addList(t(this).attr("data-alid"))
    }), t("#arlima-refresh-all-lists").click(function (t) {
        var e = !0;
        return !t.metaKey && Arlima.Manager.getUnsavedLists().length > 0 && (e = confirm(ArlimaJS.lang.unsaved)), e && Arlima.Manager.iterateLists(function (t) {
            Arlima.Manager.reloadList(t)
        }), !1
    }), t("#arlima-article-image").click(function (e) {
        e.preventDefault();
        var i = Arlima.ArticleEditor.data("post_id");
        t.isNumeric(i) ? (t("#arlima-article-attachments").html(""), Arlima.Backend.getPostAttachments(i, function (e) {
            var i = t("#arlima-article-attachments");
            t.each(e, function (e, a) {
                t("<div></div>").addClass("arlima-article-attachment").html(a.thumb).on("click", function () {
                    var e = Arlima.ArticleEditor.createArlimaArticleImageObject(a.large, "center", "full", a.attach_id);
                    e.connected = 1, Arlima.ArticleEditor.updateArticleImage(e), t("#fancybox-close").trigger("click")
                }).appendTo(i)
            })
        }), t.fancybox({
            minHeight: 200,
            href: "#arlima-article-attachments"
        })) : alert(ArlimaJS.lang.noImages)
    }), t("#arlima-toggle-preview").click(function () {
        return Arlima.ArticleEditor.togglePreview(), !1
    }), t("#arlima-preview-active-list").click(function () {
        return Arlima.Manager.previewFocusedList(), !1
    }), t("#arlima-save-active-list").click(function () {
        return Arlima.Manager.saveFocusedList(), !1
    }), t("#arlima-add-list-btn").click(function () {
        var e = t("#arlima-add-list-select").val();
        return e && Arlima.Manager.addList(e), !1
    }), t(".time-checkbox-toggler").on("click", function () {
        var e = t(this).parent().parent().find("input[type=checkbox]");
        return 0 == e.filter("*:checked").length ? e.attr("checked", "checked") : e.removeAttr("checked"), !1
    }), t("html").click(function () {
        t(".arlima-list-version-select").hide(), t(".arlima-list-version-info").show(), c = !1
    }), window.onbeforeunload = function () {
        return Arlima.Manager.previewWindow && Arlima.Manager.previewWindow.close(), Arlima.Manager.getUnsavedLists().length > 0 ? ArlimaJS.lang.unsaved : void 0
    }, t("#arlima-save-setup-btn").click(function () {
        Arlima.Manager.saveSetup()
    }), t("#arlima-edit-article-form").change(function (e) {
        var i = t(e.target),
            a = i.attr("name");
        if ("image_align" != a && "post_id" != a && "arlima-article-image-size" != i.attr("id")) {
            var r = -1 == t.inArray(a, ["title", "options-pre_title", "options-streamer_content", "options-hiderelated", "url"]);
            Arlima.ArticleEditor.updateArticle(!0, r)
        }
    }).find("input").bind("keyup", function () {
        t.inArray(this.name, ["title", "options-pre_title", "options-streamer_content", "post_id", "url"]) > -1 && Arlima.ArticleEditor.updateArticle(this, -1 == t.inArray(this.name, ["post_id", "url"]))
    }), e.find("input").click(function () {
        Arlima.ArticleEditor.updateArticleImage({
            updated: Math.round((new Date).getTime() / 1e3)
        })
    }), e.find("select").change(function () {
        Arlima.ArticleEditor.updateArticleImage({
            updated: Math.round((new Date).getTime() / 1e3)
        })
    }), t("#arlima-article-image-remove").click(function () {
        t(".hide-if-no-image").hide(), Arlima.ArticleEditor.removeArticleImage()
    }), t("#arlima-edit-article-options-streamer-image-list img").click(function () {
        t("[name='options-streamer_image']").val(t(this).attr("alt")), Arlima.ArticleEditor.updateArticle(), t.fancybox.close()
    }), t('.arlima-button input[type="checkbox"]').on("change", function () {
        var e = t(this);
        e.is(":checked") ? e.parent().addClass("checked") : e.parent().removeClass("checked")
    }), t("#arlima-option-sticky").on("change", function () {
        var e = t(this).is(":checked"),
            i = Arlima.ArticleEditor.$item.data("article");
        e ? (i.options.sticky_pos = Arlima.ArticleEditor.$item.prevAll().length, t("#arlima-option-sticky-pos").val(i.options.sticky_pos)) : i.options && i.options.sticky_pos && (t("#arlima-option-sticky-pos").val(""), i.options.sticky_pos = ""), Arlima.ArticleEditor.$item.data("article", i)
    }), t(window).bind("resize", function () {
        var t = Arlima.Manager.getFocusedList();
        t && t.isImported && Arlima.ArticleEditor.isEditingArticle() && Arlima.ArticleEditor.toggleEditorBlocker(!0)
    }), t(".handlediv").click(function () {
        t(this).parent().find(".inside").slideToggle(200)
    }), t("#arlima-post-search").submit(function () {
        return Arlima.Manager.searchWordpressPosts(0), !1
    });
    var h = t(document);
    h.on("click", ".arlima-get-posts-paging", function () {
        return Arlima.Manager.searchWordpressPosts(t(this).attr("alt")), !1
    });
    var m = setInterval(function () {
        if (void 0 !== tinyMCE && (clearInterval(m), tinyMCE.editors && tinyMCE.editors.length > 0)) {
            t(tinyMCE.editors[0].getDoc()).contents().find("body").focus(function () {
                Arlima.Manager.setFocusedList(Arlima.ArticleEditor.currentlyEditedList)
            });
            var e = 1;
            tinyMCE.editors[0].onKeyDown.add(function (t, i) {
                var a = i.keyCode ? i.keyCode : i.which;
                switch (a) {
                case 80:
                    if (i.ctrlKey || i.metaKey) return Arlima.ArticleEditor.togglePreview(), i.preventDefault(), !1;
                    break;
                case 32:
                    0 === e % 3 ? (arlimaTinyMCEChanged(), e = 1) : e++;
                    break;
                case 76:
                    if (i.ctrlKey || i.metaKey) return Arlima.Manager.previewFocusedList(), i.preventDefault(), !1
                }
            })
        }
    }, 500);
    t("#arlima-article-image-disconnect").click(function () {
        var e = t("#arlima-article-image-attach_id").val();
        if (!t.isNumeric(e)) throw Error("Trying to disconnect image that is not connected");
        return Arlima.Backend.duplicateImage(e, function (t) {
            if (t) {
                var e = {
                    attach_id: t.attach_id,
                    html: t.html,
                    connected: 0,
                    updated: Math.round((new Date).getTime() / 1e3)
                };
                Arlima.ArticleEditor.updateArticleImage(e, !0)
            }
        }), !1
    });
    var u = function (e) {
        var i = t(e.target),
            a = i.attr("id");
        if (a && 0 == a.indexOf("scissorsCrop")) {
            var r = t("#arlima-article-image-attach_id").val(),
                s = function (e, i, a) {
                    t("<button></button>").html(e).addClass("button").appendTo("#scissorsCropPane-" + r).bind("click", function () {
                        return t("#scissorsLockBox-" + r).prop("checked", !0), scissorsAspectChange(r), t("#scissorsLockX-" + r).val(i), t("#scissorsLockY-" + r).val(a), scissorsManualAspectChange(r), !1
                    })
                };
            s("Widescreen", 16, 9), s("Cinema", 21, 9), s("Square", 666, 666), i.find('input[type="checkbox"]').each(function () {
                this.id && 0 == this.id.indexOf("scissorsLockBox") && t(this).prop("checked", !1)
            }), i.find("div").each(function () {
                this.id && 0 == this.id.indexOf("scissorsReir") && t("#" + this.id).hide()
            })
        } else a && 0 === a.indexOf("scissorsWatermark") && i.find('input[type="checkbox"]').each(function () {
            if (this.id && 0 == this.id.indexOf("scissors_watermark_target")) {
                var e = this.id.split("_");
                void 0 !== e[3] && (e = e[3].split("-"), t(this).prop("checked", !0), scissorsWatermarkStateChanged(e[e.length - 1], e[0]))
            }
        })
    };
    document.addEventListener("DOMNodeInserted", u), h.bind("keydown", function (e) {
        var i = e.keyCode ? e.keyCode : e.which;
        if ((e.ctrlKey || e.metaKey) && t.inArray(i, [80, 83, 76]) > -1) {
            switch (i) {
            case 80:
                Arlima.ArticleEditor.togglePreview();
                break;
            case 83:
                Arlima.Manager.saveFocusedList();
                break;
            case 76:
                Arlima.Manager.previewFocusedList()
            }
            return !1
        }
        if (t.inArray(i, [39, 37]) > -1 && c && Arlima.ArticleEditor.isEditingArticle()) {
            var a = parseInt(d.val(), 10);
            return a += 37 == i ? -1 : 1, p.slider("value", a), d.val(a), Arlima.ArticleEditor.updateArticle(), !1
        }
    })
});