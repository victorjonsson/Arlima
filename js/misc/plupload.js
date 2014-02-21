/**
 * plupload.js / plupload.html5.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under GPL License.
 *
 * License: http://www.plupload.com/license
 * Contributing: http://www.plupload.com/contributing
 */
(function () {
    var count = 0, runtimes = [], i18n = {}, mimes = {}, xmlEncodeChars = {"<": "lt", ">": "gt", "&": "amp", '"': "quot", "'": "#39"}, xmlEncodeRegExp = /[<>&\"\']/g, undef, delay = window.setTimeout, eventhash = {}, uid;

    function preventDefault() {
        this.returnValue = false
    }

    function stopPropagation() {
        this.cancelBubble = true
    }

    (function (mime_data) {
        var items = mime_data.split(/,/), i, y, ext;
        for (i = 0; i < items.length; i += 2) {
            ext = items[i + 1].split(/ /);
            for (y = 0; y < ext.length; y++) {
                mimes[ext[y]] = items[i]
            }
        }
    })("application/msword,doc dot," + "application/pdf,pdf," + "application/pgp-signature,pgp," + "application/postscript,ps ai eps," + "application/rtf,rtf," + "application/vnd.ms-excel,xls xlb," + "application/vnd.ms-powerpoint,ppt pps pot," + "application/zip,zip," + "application/x-shockwave-flash,swf swfl," + "application/vnd.openxmlformats-officedocument.wordprocessingml.document,docx," + "application/vnd.openxmlformats-officedocument.wordprocessingml.template,dotx," + "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,xlsx," + "application/vnd.openxmlformats-officedocument.presentationml.presentation,pptx," + "application/vnd.openxmlformats-officedocument.presentationml.template,potx," + "application/vnd.openxmlformats-officedocument.presentationml.slideshow,ppsx," + "application/x-javascript,js," + "application/json,json," + "audio/mpeg,mpga mpega mp2 mp3," + "audio/x-wav,wav," + "audio/mp4,m4a," + "image/bmp,bmp," + "image/gif,gif," + "image/jpeg,jpeg jpg jpe," + "image/photoshop,psd," + "image/png,png," + "image/svg+xml,svg svgz," + "image/tiff,tiff tif," + "text/plain,asc txt text diff log," + "text/html,htm html xhtml," + "text/css,css," + "text/csv,csv," + "text/rtf,rtf," + "video/mpeg,mpeg mpg mpe," + "video/quicktime,qt mov," + "video/mp4,mp4," + "video/x-m4v,m4v," + "video/x-flv,flv," + "video/x-ms-wmv,wmv," + "video/avi,avi," + "video/webm,webm," + "video/vnd.rn-realvideo,rv," + "application/vnd.oasis.opendocument.formula-template,otf," + "application/octet-stream,exe");
    var plupload = {VERSION: "@@version@@", STOPPED: 1, STARTED: 2, QUEUED: 1, UPLOADING: 2, FAILED: 4, DONE: 5, GENERIC_ERROR: -100, HTTP_ERROR: -200, IO_ERROR: -300, SECURITY_ERROR: -400, INIT_ERROR: -500, FILE_SIZE_ERROR: -600, FILE_EXTENSION_ERROR: -601, IMAGE_FORMAT_ERROR: -700, IMAGE_MEMORY_ERROR: -701, IMAGE_DIMENSIONS_ERROR: -702, mimeTypes: mimes, ua: function () {
        var nav = navigator, userAgent = nav.userAgent, vendor = nav.vendor, webkit, opera, safari;
        webkit = /WebKit/.test(userAgent);
        safari = webkit && vendor.indexOf("Apple") !== -1;
        opera = window.opera && window.opera.buildNumber;
        return{windows: navigator.platform.indexOf("Win") !== -1, ie: !webkit && !opera && /MSIE/gi.test(userAgent) && /Explorer/gi.test(nav.appName), webkit: webkit, gecko: !webkit && /Gecko/.test(userAgent), safari: safari, opera: !!opera}
    }(), typeOf: function (o) {
        return{}.toString.call(o).match(/\s([a-z|A-Z]+)/)[1].toLowerCase()
    }, extend: function (target) {
        plupload.each(arguments, function (arg, i) {
            if (i > 0) {
                plupload.each(arg, function (value, key) {
                    target[key] = value
                })
            }
        });
        return target
    }, cleanName: function (name) {
        var i, lookup;
        lookup = [/[\300-\306]/g, "A", /[\340-\346]/g, "a", /\307/g, "C", /\347/g, "c", /[\310-\313]/g, "E", /[\350-\353]/g, "e", /[\314-\317]/g, "I", /[\354-\357]/g, "i", /\321/g, "N", /\361/g, "n", /[\322-\330]/g, "O", /[\362-\370]/g, "o", /[\331-\334]/g, "U", /[\371-\374]/g, "u"];
        for (i = 0; i < lookup.length; i += 2) {
            name = name.replace(lookup[i], lookup[i + 1])
        }
        name = name.replace(/\s+/g, "_");
        name = name.replace(/[^a-z0-9_\-\.]+/gi, "");
        return name
    }, addRuntime: function (name, runtime) {
        runtime.name = name;
        runtimes[name] = runtime;
        runtimes.push(runtime);
        return runtime
    }, guid: function () {
        var guid = (new Date).getTime().toString(32), i;
        for (i = 0; i < 5; i++) {
            guid += Math.floor(Math.random() * 65535).toString(32)
        }
        return(plupload.guidPrefix || "p") + guid + (count++).toString(32)
    }, buildUrl: function (url, items) {
        var query = "";
        plupload.each(items, function (value, name) {
            query += (query ? "&" : "") + encodeURIComponent(name) + "=" + encodeURIComponent(value)
        });
        if (query) {
            url += (url.indexOf("?") > 0 ? "&" : "?") + query
        }
        return url
    }, each: function (obj, callback) {
        var length, key, i;
        if (obj) {
            length = obj.length;
            if (length === undef) {
                for (key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        if (callback(obj[key], key) === false) {
                            return
                        }
                    }
                }
            } else {
                for (i = 0; i < length; i++) {
                    if (callback(obj[i], i) === false) {
                        return
                    }
                }
            }
        }
    }, formatSize: function (size) {
        if (size === undef || /\D/.test(size)) {
            return plupload.translate("N/A")
        }
        if (size > 1073741824) {
            return Math.round(size / 1073741824, 1) + " GB"
        }
        if (size > 1048576) {
            return Math.round(size / 1048576, 1) + " MB"
        }
        if (size > 1024) {
            return Math.round(size / 1024, 1) + " KB"
        }
        return size + " b"
    }, getPos: function (node, root) {
        var x = 0, y = 0, parent, doc = document, nodeRect, rootRect;
        node = node;
        root = root || doc.body;
        function getIEPos(node) {
            var bodyElm, rect, x = 0, y = 0;
            if (node) {
                rect = node.getBoundingClientRect();
                bodyElm = doc.compatMode === "CSS1Compat" ? doc.documentElement : doc.body;
                x = rect.left + bodyElm.scrollLeft;
                y = rect.top + bodyElm.scrollTop
            }
            return{x: x, y: y}
        }

        if (node && node.getBoundingClientRect && navigator.userAgent.indexOf("MSIE") > 0 && doc.documentMode < 8) {
            nodeRect = getIEPos(node);
            rootRect = getIEPos(root);
            return{x: nodeRect.x - rootRect.x, y: nodeRect.y - rootRect.y}
        }
        parent = node;
        while (parent && parent != root && parent.nodeType) {
            x += parent.offsetLeft || 0;
            y += parent.offsetTop || 0;
            parent = parent.offsetParent
        }
        parent = node.parentNode;
        while (parent && parent != root && parent.nodeType) {
            x -= parent.scrollLeft || 0;
            y -= parent.scrollTop || 0;
            parent = parent.parentNode
        }
        return{x: x, y: y}
    }, getSize: function (node) {
        return{w: node.offsetWidth || node.clientWidth, h: node.offsetHeight || node.clientHeight}
    }, parseSize: function (size) {
        var mul;
        if (typeof size == "string") {
            size = /^([0-9]+)([mgk]?)$/.exec(size.toLowerCase().replace(/[^0-9mkg]/g, ""));
            mul = size[2];
            size = +size[1];
            if (mul == "g") {
                size *= 1073741824
            }
            if (mul == "m") {
                size *= 1048576
            }
            if (mul == "k") {
                size *= 1024
            }
        }
        return size
    }, xmlEncode: function (str) {
        return str ? ("" + str).replace(xmlEncodeRegExp, function (chr) {
            return xmlEncodeChars[chr] ? "&" + xmlEncodeChars[chr] + ";" : chr
        }) : str
    }, toArray: function (obj) {
        var i, arr = [];
        for (i = 0; i < obj.length; i++) {
            arr[i] = obj[i]
        }
        return arr
    }, inArray: function (needle, array) {
        if (array) {
            if (Array.prototype.indexOf) {
                return Array.prototype.indexOf.call(array, needle)
            }
            for (var i = 0, length = array.length; i < length; i++) {
                if (array[i] === needle) {
                    return i
                }
            }
        }
        return-1
    }, addI18n: function (pack) {
        return plupload.extend(i18n, pack)
    }, translate: function (str) {
        return i18n[str] || str
    }, isEmptyObj: function (obj) {
        if (obj === undef)return true;
        for (var prop in obj) {
            return false
        }
        return true
    }, hasClass: function (obj, name) {
        var regExp;
        if (obj.className == "") {
            return false
        }
        regExp = new RegExp("(^|\\s+)" + name + "(\\s+|$)");
        return regExp.test(obj.className)
    }, addClass: function (obj, name) {
        if (!plupload.hasClass(obj, name)) {
            obj.className = obj.className == "" ? name : obj.className.replace(/\s+$/, "") + " " + name
        }
    }, removeClass: function (obj, name) {
        var regExp = new RegExp("(^|\\s+)" + name + "(\\s+|$)");
        obj.className = obj.className.replace(regExp, function ($0, $1, $2) {
            return $1 === " " && $2 === " " ? " " : ""
        })
    }, getStyle: function (obj, name) {
        if (obj.currentStyle) {
            return obj.currentStyle[name]
        } else if (window.getComputedStyle) {
            return window.getComputedStyle(obj, null)[name]
        }
    }, addEvent: function (obj, name, callback) {
        var func, events, types, key;
        key = arguments[3];
        name = name.toLowerCase();
        if (uid === undef) {
            uid = "Plupload_" + plupload.guid()
        }
        if (obj.addEventListener) {
            func = callback;
            obj.addEventListener(name, func, false)
        } else if (obj.attachEvent) {
            func = function () {
                var evt = window.event;
                if (!evt.target) {
                    evt.target = evt.srcElement
                }
                evt.preventDefault = preventDefault;
                evt.stopPropagation = stopPropagation;
                callback(evt)
            };
            obj.attachEvent("on" + name, func)
        }
        if (obj[uid] === undef) {
            obj[uid] = plupload.guid()
        }
        if (!eventhash.hasOwnProperty(obj[uid])) {
            eventhash[obj[uid]] = {}
        }
        events = eventhash[obj[uid]];
        if (!events.hasOwnProperty(name)) {
            events[name] = []
        }
        events[name].push({func: func, orig: callback, key: key})
    }, removeEvent: function (obj, name) {
        var type, callback, key;
        if (typeof arguments[2] == "function") {
            callback = arguments[2]
        } else {
            key = arguments[2]
        }
        name = name.toLowerCase();
        if (obj[uid] && eventhash[obj[uid]] && eventhash[obj[uid]][name]) {
            type = eventhash[obj[uid]][name]
        } else {
            return
        }
        for (var i = type.length - 1; i >= 0; i--) {
            if (type[i].key === key || type[i].orig === callback) {
                if (obj.removeEventListener) {
                    obj.removeEventListener(name, type[i].func, false)
                } else if (obj.detachEvent) {
                    obj.detachEvent("on" + name, type[i].func)
                }
                type[i].orig = null;
                type[i].func = null;
                type.splice(i, 1);
                if (callback !== undef) {
                    break
                }
            }
        }
        if (!type.length) {
            delete eventhash[obj[uid]][name]
        }
        if (plupload.isEmptyObj(eventhash[obj[uid]])) {
            delete eventhash[obj[uid]];
            try {
                delete obj[uid]
            } catch (e) {
                obj[uid] = undef
            }
        }
    }, removeAllEvents: function (obj) {
        var key = arguments[1];
        if (obj[uid] === undef || !obj[uid]) {
            return
        }
        plupload.each(eventhash[obj[uid]], function (events, name) {
            plupload.removeEvent(obj, name, key)
        })
    }};
    plupload.Uploader = function (settings) {
        var events = {}, total, files = [], startTime, disabled = false;
        total = new plupload.QueueProgress;
        settings = plupload.extend({chunk_size: 0, multipart: true, multi_selection: true, file_data_name: "file", filters: []}, settings);
        function uploadNext() {
            var file, count = 0, i;
            if (this.state == plupload.STARTED) {
                for (i = 0; i < files.length; i++) {
                    if (!file && files[i].status == plupload.QUEUED) {
                        file = files[i];
                        file.status = plupload.UPLOADING;
                        if (this.trigger("BeforeUpload", file)) {
                            this.trigger("UploadFile", file)
                        }
                    } else {
                        count++
                    }
                }
                if (count == files.length) {
                    this.stop();
                    this.trigger("UploadComplete", files)
                }
            }
        }

        function calc() {
            var i, file;
            total.reset();
            for (i = 0; i < files.length; i++) {
                file = files[i];
                if (file.size !== undef) {
                    total.size += file.size;
                    total.loaded += file.loaded
                } else {
                    total.size = undef
                }
                if (file.status == plupload.DONE) {
                    total.uploaded++
                } else if (file.status == plupload.FAILED) {
                    total.failed++
                } else {
                    total.queued++
                }
            }
            if (total.size === undef) {
                total.percent = files.length > 0 ? Math.ceil(total.uploaded / files.length * 100) : 0
            } else {
                total.bytesPerSec = Math.ceil(total.loaded / ((+new Date - startTime || 1) / 1e3));
                total.percent = total.size > 0 ? Math.ceil(total.loaded / total.size * 100) : 0
            }
        }

        plupload.extend(this, {state: plupload.STOPPED, runtime: "", features: {}, files: files, settings: settings, total: total, id: plupload.guid(), init: function () {
            var self = this, i, runtimeList, a, runTimeIndex = 0, items;
            if (typeof settings.preinit == "function") {
                settings.preinit(self)
            } else {
                plupload.each(settings.preinit, function (func, name) {
                    self.bind(name, func)
                })
            }
            settings.page_url = settings.page_url || document.location.pathname.replace(/\/[^\/]+$/g, "/");
            if (!/^(\w+:\/\/|\/)/.test(settings.url)) {
                settings.url = settings.page_url + settings.url
            }
            settings.chunk_size = plupload.parseSize(settings.chunk_size);
            settings.max_file_size = plupload.parseSize(settings.max_file_size);
            self.bind("FilesAdded", function (up, selected_files) {
                var i, file, count = 0, extensionsRegExp, filters = settings.filters;
                if (filters && filters.length) {
                    extensionsRegExp = [];
                    plupload.each(filters, function (filter) {
                        plupload.each(filter.extensions.split(/,/), function (ext) {
                            if (/^\s*\*\s*$/.test(ext)) {
                                extensionsRegExp.push("\\.*")
                            } else {
                                extensionsRegExp.push("\\." + ext.replace(new RegExp("[" + "/^$.*+?|()[]{}\\".replace(/./g, "\\$&") + "]", "g"), "\\$&"))
                            }
                        })
                    });
                    extensionsRegExp = new RegExp(extensionsRegExp.join("|") + "$", "i")
                }
                for (i = 0; i < selected_files.length; i++) {
                    file = selected_files[i];
                    file.loaded = 0;
                    file.percent = 0;
                    file.status = plupload.QUEUED;
                    if (extensionsRegExp && !extensionsRegExp.test(file.name)) {
                        up.trigger("Error", {code: plupload.FILE_EXTENSION_ERROR, message: plupload.translate("File extension error."), file: file});
                        continue
                    }
                    if (file.size !== undef && file.size > settings.max_file_size) {
                        up.trigger("Error", {code: plupload.FILE_SIZE_ERROR, message: plupload.translate("File size error."), file: file});
                        continue
                    }
                    files.push(file);
                    count++
                }
                if (count) {
                    delay(function () {
                        self.trigger("QueueChanged");
                        self.refresh()
                    }, 1)
                } else {
                    return false
                }
            });
            if (settings.unique_names) {
                self.bind("UploadFile", function (up, file) {
                    var matches = file.name.match(/\.([^.]+)$/), ext = "tmp";
                    if (matches) {
                        ext = matches[1]
                    }
                    file.target_name = file.id + "." + ext
                })
            }
            self.bind("UploadProgress", function (up, file) {
                file.percent = file.size > 0 ? Math.ceil(file.loaded / file.size * 100) : 100;
                calc()
            });
            self.bind("StateChanged", function (up) {
                if (up.state == plupload.STARTED) {
                    startTime = +new Date
                } else if (up.state == plupload.STOPPED) {
                    for (i = up.files.length - 1; i >= 0; i--) {
                        if (up.files[i].status == plupload.UPLOADING) {
                            up.files[i].status = plupload.QUEUED;
                            calc()
                        }
                    }
                }
            });
            self.bind("QueueChanged", calc);
            self.bind("Error", function (up, err) {
                if (err.file) {
                    err.file.status = plupload.FAILED;
                    calc();
                    if (up.state == plupload.STARTED) {
                        delay(function () {
                            uploadNext.call(self)
                        }, 1)
                    }
                }
            });
            self.bind("FileUploaded", function (up, file) {
                file.status = plupload.DONE;
                file.loaded = file.size;
                up.trigger("UploadProgress", file);
                delay(function () {
                    uploadNext.call(self)
                }, 1)
            });
            if (settings.runtimes) {
                runtimeList = [];
                items = settings.runtimes.split(/\s?,\s?/);
                for (i = 0; i < items.length; i++) {
                    if (runtimes[items[i]]) {
                        runtimeList.push(runtimes[items[i]])
                    }
                }
            } else {
                runtimeList = runtimes
            }
            function callNextInit() {
                var runtime = runtimeList[runTimeIndex++], features, requiredFeatures, i;
                if (runtime) {
                    features = runtime.getFeatures();
                    requiredFeatures = self.settings.required_features;
                    if (requiredFeatures) {
                        requiredFeatures = requiredFeatures.split(",");
                        for (i = 0; i < requiredFeatures.length; i++) {
                            if (!features[requiredFeatures[i]]) {
                                callNextInit();
                                return
                            }
                        }
                    }
                    runtime.init(self, function (res) {
                        if (res && res.success) {
                            self.features = features;
                            self.runtime = runtime.name;
                            self.trigger("Init", {runtime: runtime.name});
                            self.trigger("PostInit");
                            self.refresh()
                        } else {
                            callNextInit()
                        }
                    })
                } else {
                    self.trigger("Error", {code: plupload.INIT_ERROR, message: plupload.translate("Init error.")})
                }
            }

            callNextInit();
            if (typeof settings.init == "function") {
                settings.init(self)
            } else {
                plupload.each(settings.init, function (func, name) {
                    self.bind(name, func)
                })
            }
        }, refresh: function () {
            this.trigger("Refresh")
        }, start: function () {
            if (files.length && this.state != plupload.STARTED) {
                this.state = plupload.STARTED;
                this.trigger("StateChanged");
                uploadNext.call(this)
            }
        }, stop: function () {
            if (this.state != plupload.STOPPED) {
                this.state = plupload.STOPPED;
                this.trigger("CancelUpload");
                this.trigger("StateChanged")
            }
        }, disableBrowse: function () {
            disabled = arguments[0] !== undef ? arguments[0] : true;
            this.trigger("DisableBrowse", disabled)
        }, getFile: function (id) {
            var i;
            for (i = files.length - 1; i >= 0; i--) {
                if (files[i].id === id) {
                    return files[i]
                }
            }
        }, removeFile: function (file) {
            var i;
            for (i = files.length - 1; i >= 0; i--) {
                if (files[i].id === file.id) {
                    return this.splice(i, 1)[0]
                }
            }
        }, splice: function (start, length) {
            var removed;
            removed = files.splice(start === undef ? 0 : start, length === undef ? files.length : length);
            this.trigger("FilesRemoved", removed);
            this.trigger("QueueChanged");
            return removed
        }, trigger: function (name) {
            var list = events[name.toLowerCase()], i, args;
            if (list) {
                args = Array.prototype.slice.call(arguments);
                args[0] = this;
                for (i = 0; i < list.length; i++) {
                    if (list[i].func.apply(list[i].scope, args) === false) {
                        return false
                    }
                }
            }
            return true
        }, hasEventListener: function (name) {
            return!!events[name.toLowerCase()]
        }, bind: function (name, func, scope) {
            var list;
            name = name.toLowerCase();
            list = events[name] || [];
            list.push({func: func, scope: scope || this});
            events[name] = list
        }, unbind: function (name) {
            name = name.toLowerCase();
            var list = events[name], i, func = arguments[1];
            if (list) {
                if (func !== undef) {
                    for (i = list.length - 1; i >= 0; i--) {
                        if (list[i].func === func) {
                            list.splice(i, 1);
                            break
                        }
                    }
                } else {
                    list = []
                }
                if (!list.length) {
                    delete events[name]
                }
            }
        }, unbindAll: function () {
            var self = this;
            plupload.each(events, function (list, name) {
                self.unbind(name)
            })
        }, destroy: function () {
            this.stop();
            this.trigger("Destroy");
            this.unbindAll()
        }})
    };
    plupload.File = function (id, name, size, relativePath) {
        var self = this;
        self.id = id;
        self.name = name;
        self.size = size;
        self.relativePath = relativePath || name;
        self.loaded = 0;
        self.percent = 0;
        self.status = 0
    };
    plupload.Runtime = function () {
        this.getFeatures = function () {
        };
        this.init = function (uploader, callback) {
        }
    };
    plupload.QueueProgress = function () {
        var self = this;
        self.size = 0;
        self.loaded = 0;
        self.uploaded = 0;
        self.failed = 0;
        self.queued = 0;
        self.percent = 0;
        self.bytesPerSec = 0;
        self.reset = function () {
            self.size = self.loaded = self.uploaded = self.failed = self.queued = self.percent = self.bytesPerSec = 0
        }
    };
    plupload.runtimes = {};
    window.plupload = plupload
})();
(function (window, document, plupload, undef) {
    var html5files = {}, fakeSafariDragDrop;

    function readFileAsDataURL(file, callback) {
        var reader;
        if ("FileReader"in window) {
            reader = new FileReader;
            reader.readAsDataURL(file);
            reader.onload = function () {
                callback(reader.result)
            }
        } else {
            return callback(file.getAsDataURL())
        }
    }

    function readFileAsBinary(file, callback) {
        var reader;
        if ("FileReader"in window) {
            reader = new FileReader;
            reader.readAsBinaryString(file);
            reader.onload = function () {
                callback(reader.result)
            }
        } else {
            return callback(file.getAsBinary())
        }
    }

    function scaleImage(file, resize, mime, callback) {
        var canvas, context, img, scale, up = this;
        readFileAsDataURL(html5files[file.id], function (data) {
            canvas = document.createElement("canvas");
            canvas.style.display = "none";
            document.body.appendChild(canvas);
            context = canvas.getContext("2d");
            img = new Image;
            img.onerror = img.onabort = function () {
                callback({success: false})
            };
            img.onload = function () {
                var width, height, percentage, jpegHeaders, exifParser;
                if (!resize["width"]) {
                    resize["width"] = img.width
                }
                if (!resize["height"]) {
                    resize["height"] = img.height
                }
                scale = Math.min(resize.width / img.width, resize.height / img.height);
                if (scale < 1 || scale === 1 && mime === "image/jpeg") {
                    width = Math.round(img.width * scale);
                    height = Math.round(img.height * scale);
                    canvas.width = width;
                    canvas.height = height;
                    context.drawImage(img, 0, 0, width, height);
                    if (mime === "image/jpeg") {
                        jpegHeaders = new JPEG_Headers(atob(data.substring(data.indexOf("base64,") + 7)));
                        if (jpegHeaders["headers"] && jpegHeaders["headers"].length) {
                            exifParser = new ExifParser;
                            if (exifParser.init(jpegHeaders.get("exif")[0])) {
                                exifParser.setExif("PixelXDimension", width);
                                exifParser.setExif("PixelYDimension", height);
                                jpegHeaders.set("exif", exifParser.getBinary());
                                if (up.hasEventListener("ExifData")) {
                                    up.trigger("ExifData", file, exifParser.EXIF())
                                }
                                if (up.hasEventListener("GpsData")) {
                                    up.trigger("GpsData", file, exifParser.GPS())
                                }
                            }
                        }
                        if (resize["quality"]) {
                            try {
                                data = canvas.toDataURL(mime, resize["quality"] / 100)
                            } catch (e) {
                                data = canvas.toDataURL(mime)
                            }
                        }
                    } else {
                        data = canvas.toDataURL(mime)
                    }
                    data = data.substring(data.indexOf("base64,") + 7);
                    data = atob(data);
                    if (jpegHeaders && jpegHeaders["headers"] && jpegHeaders["headers"].length) {
                        data = jpegHeaders.restore(data);
                        jpegHeaders.purge()
                    }
                    canvas.parentNode.removeChild(canvas);
                    callback({success: true, data: data})
                } else {
                    callback({success: false})
                }
            };
            img.src = data
        })
    }

    plupload.runtimes.Html5 = plupload.addRuntime("html5", {getFeatures: function () {
        var xhr, hasXhrSupport, hasProgress, canSendBinary, dataAccessSupport, sliceSupport;
        hasXhrSupport = hasProgress = dataAccessSupport = sliceSupport = false;
        if (window.XMLHttpRequest) {
            xhr = new XMLHttpRequest;
            hasProgress = !!xhr.upload;
            hasXhrSupport = !!(xhr.sendAsBinary || xhr.upload)
        }
        if (hasXhrSupport) {
            canSendBinary = !!(xhr.sendAsBinary || window.Uint8Array && window.ArrayBuffer);
            dataAccessSupport = !!(File && (File.prototype.getAsDataURL || window.FileReader) && canSendBinary);
            sliceSupport = !!(File && (File.prototype.mozSlice || File.prototype.webkitSlice || File.prototype.slice))
        }
        fakeSafariDragDrop = plupload.ua.safari && plupload.ua.windows;
        return{html5: hasXhrSupport, dragdrop: function () {
            var div = document.createElement("div");
            return"draggable"in div || "ondragstart"in div && "ondrop"in div
        }(), jpgresize: dataAccessSupport, pngresize: dataAccessSupport, multipart: dataAccessSupport || !!window.FileReader || !!window.FormData, canSendBinary: canSendBinary, cantSendBlobInFormData: !!(plupload.ua.gecko && window.FormData && window.FileReader && !FileReader.prototype.readAsArrayBuffer), progress: hasProgress, chunks: sliceSupport, multi_selection: !(plupload.ua.safari && plupload.ua.windows), triggerDialog: plupload.ua.gecko && window.FormData || plupload.ua.webkit}
    }, init: function (uploader, callback) {
        var features, xhr;

        function hasFiles(dataTransfer) {
            if (!dataTransfer || typeof dataTransfer.files === "undefined") {
                return false
            }
            var types = plupload.toArray(dataTransfer.types || []);
            return types.indexOf("public.file-url") !== -1 || types.indexOf("application/x-moz-file") !== -1 || types.indexOf("Files") !== -1 || types.length === 0
        }

        function walkFileSystem(directory, callback, error) {
            if (!callback.pending) {
                callback.pending = 0
            }
            if (!callback.files) {
                callback.files = []
            }
            callback.pending++;
            var reader = directory.createReader(), relativePath = directory.fullPath.replace(/^\//, "").replace(/(.+?)\/?$/, "$1/");
            reader.readEntries(function (entries) {
                callback.pending--;
                plupload.each(entries, function (entry) {
                    if (entry.isFile) {
                        callback.pending++;
                        entry.file(function (File) {
                            File.relativePath = relativePath + File.name;
                            callback.files.push(File);
                            if (--callback.pending === 0) {
                                callback(callback.files)
                            }
                        }, error)
                    } else {
                        walkFileSystem(entry, callback, error)
                    }
                });
                if (callback.pending === 0) {
                    callback(callback.files)
                }
            }, error)
        }

        function addSelectedFiles(native_files) {
            var file, i, files = [], id, fileNames = {};
            for (i = 0; i < native_files.length; i++) {
                file = native_files[i];
                if (fileNames[file.name]) {
                    continue
                }
                fileNames[file.name] = true;
                id = plupload.guid();
                html5files[id] = file;
                var fileObj = new plupload.File(id, file.fileName || file.name, file.fileSize || file.size, file.relativePath);
                fileObj.native = html5files[id];
                files.push(fileObj);
            }
            if (files.length) {
                uploader.trigger("FilesAdded", files)
            }
        }

        features = this.getFeatures();
        if (!features.html5) {
            callback({success: false});
            return
        }
        uploader.bind("Init", function (up) {
            var inputContainer, browseButton, mimes = [], i, y, filters = up.settings.filters, ext, type, container = document.body, inputFile;
            inputContainer = document.createElement("div");
            inputContainer.id = up.id + "_html5_container";
            plupload.extend(inputContainer.style, {position: "absolute", background: uploader.settings.shim_bgcolor || "transparent", width: "100px", height: "100px", overflow: "hidden", zIndex: 99999, opacity: uploader.settings.shim_bgcolor ? "" : 0});
            inputContainer.className = "plupload html5";
            if (uploader.settings.container) {
                container = document.getElementById(uploader.settings.container);
                if (plupload.getStyle(container, "position") === "static") {
                    container.style.position = "relative"
                }
            }
            container.appendChild(inputContainer);
            no_type_restriction:for (i = 0; i < filters.length; i++) {
                ext = filters[i].extensions.split(/,/);
                for (y = 0; y < ext.length; y++) {
                    if (ext[y] === "*") {
                        mimes = [];
                        break no_type_restriction
                    }
                    type = plupload.mimeTypes[ext[y]];
                    if (type && plupload.inArray(type, mimes) === -1) {
                        mimes.push(type)
                    }
                }
            }
            inputContainer.innerHTML = '<input id="' + uploader.id + '_html5" ' + ' style="font-size:999px"' + ' type="file" accept="' + mimes.join(",") + '" ' + (uploader.settings.multi_selection && uploader.features.multi_selection ? 'multiple="multiple"' : "") + " />";
            inputContainer.scrollTop = 100;
            inputFile = document.getElementById(uploader.id + "_html5");
            if (up.features.triggerDialog) {
                plupload.extend(inputFile.style, {position: "absolute", width: "100%", height: "100%"})
            } else {
                plupload.extend(inputFile.style, {cssFloat: "right", styleFloat: "right"})
            }
            inputFile.onchange = function () {
                addSelectedFiles(this.files);
                this.value = ""
            };
            browseButton = document.getElementById(up.settings.browse_button);
            if (browseButton) {
                var hoverClass = up.settings.browse_button_hover, activeClass = up.settings.browse_button_active, topElement = up.features.triggerDialog ? browseButton : inputContainer;
                if (hoverClass) {
                    plupload.addEvent(topElement, "mouseover", function () {
                        plupload.addClass(browseButton, hoverClass)
                    }, up.id);
                    plupload.addEvent(topElement, "mouseout", function () {
                        plupload.removeClass(browseButton, hoverClass)
                    }, up.id)
                }
                if (activeClass) {
                    plupload.addEvent(topElement, "mousedown", function () {
                        plupload.addClass(browseButton, activeClass)
                    }, up.id);
                    plupload.addEvent(document.body, "mouseup", function () {
                        plupload.removeClass(browseButton, activeClass)
                    }, up.id)
                }
                if (up.features.triggerDialog) {
                    plupload.addEvent(browseButton, "click", function (e) {
                        var input = document.getElementById(up.id + "_html5");
                        if (input && !input.disabled) {
                            input.click()
                        }
                        e.preventDefault()
                    }, up.id)
                }
            }
        });
        uploader.bind("PostInit", function () {
            var dropElm = document.getElementById(uploader.settings.drop_element);
            if (dropElm) {
                if (fakeSafariDragDrop) {
                    plupload.addEvent(dropElm, "dragenter", function (e) {
                        var dropInputElm, dropPos, dropSize;
                        dropInputElm = document.getElementById(uploader.id + "_drop");
                        if (!dropInputElm && hasFiles(e.dataTransfer)) {
                            dropInputElm = document.createElement("input");
                            dropInputElm.setAttribute("type", "file");
                            dropInputElm.setAttribute("id", uploader.id + "_drop");
                            dropInputElm.setAttribute("multiple", "multiple");
                            plupload.addEvent(dropInputElm, "change", function () {
                                addSelectedFiles(this.files);
                                plupload.removeEvent(dropInputElm, "change", uploader.id);
                                dropInputElm.parentNode.removeChild(dropInputElm)
                            }, uploader.id);
                            dropElm.appendChild(dropInputElm)
                        }
                        dropPos = plupload.getPos(dropElm, document.getElementById(uploader.settings.container));
                        dropSize = plupload.getSize(dropElm);
                        if (plupload.getStyle(dropElm, "position") === "static") {
                            plupload.extend(dropElm.style, {position: "relative"})
                        }
                        plupload.extend(dropInputElm.style, {position: "absolute", display: "block", top: 0, left: 0, width: dropSize.w + "px", height: dropSize.h + "px", opacity: 0})
                    }, uploader.id);
                    return
                }
                plupload.addEvent(dropElm, "dragover", function (e) {
                    if (hasFiles(e.dataTransfer)) {
                        e.preventDefault()
                    }
                }, uploader.id);
                plupload.addEvent(dropElm, "drop", function (e) {
                    var dataTransfer = e.dataTransfer;
                    if (!hasFiles(dataTransfer)) {
                        return
                    }
                    var items = dataTransfer.items || [], firstEntry;
                    if (items[0] && items[0].webkitGetAsEntry && (firstEntry = items[0].webkitGetAsEntry())) {
                        walkFileSystem(firstEntry.filesystem.root, function (files) {
                            addSelectedFiles(files)
                        }, function () {
                            addSelectedFiles(dataTransfer.files)
                        })
                    } else {
                        addSelectedFiles(dataTransfer.files)
                    }
                    e.preventDefault()
                }, uploader.id)
            }
        });
        uploader.bind("Refresh", function (up) {
            var browseButton, browsePos, browseSize, inputContainer, zIndex;
            browseButton = document.getElementById(uploader.settings.browse_button);
            if (browseButton) {
                browsePos = plupload.getPos(browseButton, document.getElementById(up.settings.container));
                browseSize = plupload.getSize(browseButton);
                inputContainer = document.getElementById(uploader.id + "_html5_container");
                plupload.extend(inputContainer.style, {top: browsePos.y + "px", left: browsePos.x + "px", width: browseSize.w + "px", height: browseSize.h + "px"});
                if (uploader.features.triggerDialog) {
                    if (plupload.getStyle(browseButton, "position") === "static") {
                        plupload.extend(browseButton.style, {position: "relative"})
                    }
                    zIndex = parseInt(plupload.getStyle(browseButton, "zIndex"), 10);
                    if (isNaN(zIndex)) {
                        zIndex = 0
                    }
                    plupload.extend(browseButton.style, {zIndex: zIndex});
                    plupload.extend(inputContainer.style, {zIndex: zIndex - 1})
                }
            }
        });
        uploader.bind("DisableBrowse", function (up, disabled) {
            var input = document.getElementById(up.id + "_html5");
            if (input) {
                input.disabled = disabled
            }
        });
        uploader.bind("CancelUpload", function () {
            if (xhr && xhr.abort) {
                xhr.abort()
            }
        });
        uploader.bind("UploadFile", function (up, file) {
            var settings = up.settings, nativeFile, resize;

            function w3cBlobSlice(blob, start, end) {
                var blobSlice;
                if (File.prototype.slice) {
                    try {
                        blob.slice();
                        return blob.slice(start, end)
                    } catch (e) {
                        return blob.slice(start, end - start)
                    }
                } else if (blobSlice = File.prototype.webkitSlice || File.prototype.mozSlice) {
                    return blobSlice.call(blob, start, end)
                } else {
                    return null
                }
            }

            function sendBinaryBlob(blob) {
                var chunk = 0, loaded = 0, fr = "FileReader"in window ? new FileReader : null;

                function uploadNextChunk() {
                    var chunkBlob, br, chunks, args, chunkSize, curChunkSize, mimeType, url = up.settings.url;

                    function prepareAndSend(bin) {
                        var multipartDeltaSize = 0, boundary = "----pluploadboundary" + plupload.guid(), formData, dashdash = "--", crlf = "\r\n", multipartBlob = "";
                        xhr = new XMLHttpRequest;
                        if (xhr.upload) {
                            xhr.upload.onprogress = function (e) {
                                file.loaded = Math.min(file.size, loaded + e.loaded - multipartDeltaSize);
                                up.trigger("UploadProgress", file)
                            }
                        }
                        xhr.onreadystatechange = function () {
                            var httpStatus, chunkArgs;
                            if (xhr.readyState == 4 && up.state !== plupload.STOPPED) {
                                try {
                                    httpStatus = xhr.status
                                } catch (ex) {
                                    httpStatus = 0
                                }
                                if (httpStatus >= 400) {
                                    up.trigger("Error", {code: plupload.HTTP_ERROR, message: plupload.translate("HTTP Error."), file: file, status: httpStatus})
                                } else {
                                    if (chunks) {
                                        chunkArgs = {chunk: chunk, chunks: chunks, response: xhr.responseText, status: httpStatus};
                                        up.trigger("ChunkUploaded", file, chunkArgs);
                                        loaded += curChunkSize;
                                        if (chunkArgs.cancelled) {
                                            file.status = plupload.FAILED;
                                            return
                                        }
                                        file.loaded = Math.min(file.size, (chunk + 1) * chunkSize)
                                    } else {
                                        file.loaded = file.size
                                    }
                                    up.trigger("UploadProgress", file);
                                    bin = chunkBlob = formData = multipartBlob = null;
                                    if (!chunks || ++chunk >= chunks) {
                                        file.status = plupload.DONE;
                                        up.trigger("FileUploaded", file, {response: xhr.responseText, status: httpStatus})
                                    } else {
                                        uploadNextChunk()
                                    }
                                }
                            }
                        };
                        if (up.settings.multipart && features.multipart) {
                            args.name = file.target_name || file.name;
                            xhr.open("post", url, true);
                            plupload.each(up.settings.headers, function (value, name) {
                                xhr.setRequestHeader(name, value)
                            });
                            if (typeof bin !== "string" && !!window.FormData) {
                                formData = new FormData;
                                plupload.each(plupload.extend(args, up.settings.multipart_params), function (value, name) {
                                    formData.append(name, value)
                                });
                                formData.append(up.settings.file_data_name, bin);
                                xhr.send(formData);
                                return
                            }
                            if (typeof bin === "string") {
                                xhr.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + boundary);
                                plupload.each(plupload.extend(args, up.settings.multipart_params), function (value, name) {
                                    multipartBlob += dashdash + boundary + crlf + 'Content-Disposition: form-data; name="' + name + '"' + crlf + crlf;
                                    multipartBlob += unescape(encodeURIComponent(value)) + crlf
                                });
                                mimeType = plupload.mimeTypes[file.name.replace(/^.+\.([^.]+)/, "$1").toLowerCase()] || "application/octet-stream";
                                multipartBlob += dashdash + boundary + crlf + 'Content-Disposition: form-data; name="' + up.settings.file_data_name + '"; filename="' + unescape(encodeURIComponent(file.name)) + '"' + crlf + "Content-Type: " + mimeType + crlf + crlf + bin + crlf + dashdash + boundary + dashdash + crlf;
                                multipartDeltaSize = multipartBlob.length - bin.length;
                                bin = multipartBlob;
                                if (xhr.sendAsBinary) {
                                    xhr.sendAsBinary(bin)
                                } else if (features.canSendBinary) {
                                    var ui8a = new Uint8Array(bin.length);
                                    for (var i = 0; i < bin.length; i++) {
                                        ui8a[i] = bin.charCodeAt(i) & 255
                                    }
                                    xhr.send(ui8a.buffer)
                                }
                                return
                            }
                        }
                        url = plupload.buildUrl(up.settings.url, plupload.extend(args, up.settings.multipart_params));
                        xhr.open("post", url, true);
                        xhr.setRequestHeader("Content-Type", "application/octet-stream");
                        plupload.each(up.settings.headers, function (value, name) {
                            xhr.setRequestHeader(name, value)
                        });
                        xhr.send(bin)
                    }

                    if (file.status == plupload.DONE || file.status == plupload.FAILED || up.state == plupload.STOPPED) {
                        return
                    }
                    args = {name: file.target_name || file.name};
                    if (settings.chunk_size && file.size > settings.chunk_size && (features.chunks || typeof blob == "string")) {
                        chunkSize = settings.chunk_size;
                        chunks = Math.ceil(file.size / chunkSize);
                        curChunkSize = Math.min(chunkSize, file.size - chunk * chunkSize);
                        if (typeof blob == "string") {
                            chunkBlob = blob.substring(chunk * chunkSize, chunk * chunkSize + curChunkSize)
                        } else {
                            chunkBlob = w3cBlobSlice(blob, chunk * chunkSize, chunk * chunkSize + curChunkSize)
                        }
                        args.chunk = chunk;
                        args.chunks = chunks
                    } else {
                        curChunkSize = file.size;
                        chunkBlob = blob
                    }
                    if (up.settings.multipart && features.multipart && typeof chunkBlob !== "string" && fr && features.cantSendBlobInFormData && features.chunks && up.settings.chunk_size) {
                        fr.onload = function () {
                            prepareAndSend(fr.result)
                        };
                        fr.readAsBinaryString(chunkBlob)
                    } else {
                        prepareAndSend(chunkBlob)
                    }
                }

                uploadNextChunk()
            }

            nativeFile = html5files[file.id];
            if (features.jpgresize && up.settings.resize && /\.(png|jpg|jpeg)$/i.test(file.name)) {
                scaleImage.call(up, file, up.settings.resize, /\.png$/i.test(file.name) ? "image/png" : "image/jpeg", function (res) {
                    if (res.success) {
                        file.size = res.data.length;
                        sendBinaryBlob(res.data)
                    } else if (features.chunks) {
                        sendBinaryBlob(nativeFile)
                    } else {
                        readFileAsBinary(nativeFile, sendBinaryBlob)
                    }
                })
            } else if (!features.chunks && features.jpgresize) {
                readFileAsBinary(nativeFile, sendBinaryBlob)
            } else {
                sendBinaryBlob(nativeFile)
            }
        });
        uploader.bind("Destroy", function (up) {
            var name, element, container = document.body, elements = {inputContainer: up.id + "_html5_container", inputFile: up.id + "_html5", browseButton: up.settings.browse_button, dropElm: up.settings.drop_element};
            for (name in elements) {
                element = document.getElementById(elements[name]);
                if (element) {
                    plupload.removeAllEvents(element, up.id)
                }
            }
            plupload.removeAllEvents(document.body, up.id);
            if (up.settings.container) {
                container = document.getElementById(up.settings.container)
            }
            container.removeChild(document.getElementById(elements.inputContainer))
        });
        callback({success: true})
    }});
    function BinaryReader() {
        var II = false, bin;

        function read(idx, size) {
            var mv = II ? 0 : -8 * (size - 1), sum = 0, i;
            for (i = 0; i < size; i++) {
                sum |= bin.charCodeAt(idx + i) << Math.abs(mv + i * 8)
            }
            return sum
        }

        function putstr(segment, idx, length) {
            var length = arguments.length === 3 ? length : bin.length - idx - 1;
            bin = bin.substr(0, idx) + segment + bin.substr(length + idx)
        }

        function write(idx, num, size) {
            var str = "", mv = II ? 0 : -8 * (size - 1), i;
            for (i = 0; i < size; i++) {
                str += String.fromCharCode(num >> Math.abs(mv + i * 8) & 255)
            }
            putstr(str, idx, size)
        }

        return{II: function (order) {
            if (order === undef) {
                return II
            } else {
                II = order
            }
        }, init: function (binData) {
            II = false;
            bin = binData
        }, SEGMENT: function (idx, length, segment) {
            switch (arguments.length) {
                case 1:
                    return bin.substr(idx, bin.length - idx - 1);
                case 2:
                    return bin.substr(idx, length);
                case 3:
                    putstr(segment, idx, length);
                    break;
                default:
                    return bin
            }
        }, BYTE: function (idx) {
            return read(idx, 1)
        }, SHORT: function (idx) {
            return read(idx, 2)
        }, LONG: function (idx, num) {
            if (num === undef) {
                return read(idx, 4)
            } else {
                write(idx, num, 4)
            }
        }, SLONG: function (idx) {
            var num = read(idx, 4);
            return num > 2147483647 ? num - 4294967296 : num
        }, STRING: function (idx, size) {
            var str = "";
            for (size += idx; idx < size; idx++) {
                str += String.fromCharCode(read(idx, 1))
            }
            return str
        }}
    }

    function JPEG_Headers(data) {
        var markers = {65505: {app: "EXIF", name: "APP1", signature: "Exif\x00"}, 65506: {app: "ICC", name: "APP2", signature: "ICC_PROFILE\x00"}, 65517: {app: "IPTC", name: "APP13", signature: "Photoshop 3.0\x00"}}, headers = [], read, idx, marker = undef, length = 0, limit;
        read = new BinaryReader;
        read.init(data);
        if (read.SHORT(0) !== 65496) {
            return
        }
        idx = 2;
        limit = Math.min(1048576, data.length);
        while (idx <= limit) {
            marker = read.SHORT(idx);
            if (marker >= 65488 && marker <= 65495) {
                idx += 2;
                continue
            }
            if (marker === 65498 || marker === 65497) {
                break
            }
            length = read.SHORT(idx + 2) + 2;
            if (markers[marker] && read.STRING(idx + 4, markers[marker].signature.length) === markers[marker].signature) {
                headers.push({hex: marker, app: markers[marker].app.toUpperCase(), name: markers[marker].name.toUpperCase(), start: idx, length: length, segment: read.SEGMENT(idx, length)})
            }
            idx += length
        }
        read.init(null);
        return{headers: headers, restore: function (data) {
            read.init(data);
            var jpegHeaders = new JPEG_Headers(data);
            if (!jpegHeaders["headers"]) {
                return false
            }
            for (var i = jpegHeaders["headers"].length; i > 0; i--) {
                var hdr = jpegHeaders["headers"][i - 1];
                read.SEGMENT(hdr.start, hdr.length, "")
            }
            jpegHeaders.purge();
            idx = read.SHORT(2) == 65504 ? 4 + read.SHORT(4) : 2;
            for (var i = 0, max = headers.length; i < max; i++) {
                read.SEGMENT(idx, 0, headers[i].segment);
                idx += headers[i].length
            }
            return read.SEGMENT()
        }, get: function (app) {
            var array = [];
            for (var i = 0, max = headers.length; i < max; i++) {
                if (headers[i].app === app.toUpperCase()) {
                    array.push(headers[i].segment)
                }
            }
            return array
        }, set: function (app, segment) {
            var array = [];
            if (typeof segment === "string") {
                array.push(segment)
            } else {
                array = segment
            }
            for (var i = ii = 0, max = headers.length; i < max; i++) {
                if (headers[i].app === app.toUpperCase()) {
                    headers[i].segment = array[ii];
                    headers[i].length = array[ii].length;
                    ii++
                }
                if (ii >= array.length)break
            }
        }, purge: function () {
            headers = [];
            read.init(null)
        }}
    }

    function ExifParser() {
        var data, tags, offsets = {}, tagDescs;
        data = new BinaryReader;
        tags = {tiff: {274: "Orientation", 34665: "ExifIFDPointer", 34853: "GPSInfoIFDPointer"}, exif: {36864: "ExifVersion", 40961: "ColorSpace", 40962: "PixelXDimension", 40963: "PixelYDimension", 36867: "DateTimeOriginal", 33434: "ExposureTime", 33437: "FNumber", 34855: "ISOSpeedRatings", 37377: "ShutterSpeedValue", 37378: "ApertureValue", 37383: "MeteringMode", 37384: "LightSource", 37385: "Flash", 41986: "ExposureMode", 41987: "WhiteBalance", 41990: "SceneCaptureType", 41988: "DigitalZoomRatio", 41992: "Contrast", 41993: "Saturation", 41994: "Sharpness"}, gps: {0: "GPSVersionID", 1: "GPSLatitudeRef", 2: "GPSLatitude", 3: "GPSLongitudeRef", 4: "GPSLongitude"}};
        tagDescs = {ColorSpace: {1: "sRGB", 0: "Uncalibrated"}, MeteringMode: {0: "Unknown", 1: "Average", 2: "CenterWeightedAverage", 3: "Spot", 4: "MultiSpot", 5: "Pattern", 6: "Partial", 255: "Other"}, LightSource: {1: "Daylight", 2: "Fliorescent", 3: "Tungsten", 4: "Flash", 9: "Fine weather", 10: "Cloudy weather", 11: "Shade", 12: "Daylight fluorescent (D 5700 - 7100K)", 13: "Day white fluorescent (N 4600 -5400K)", 14: "Cool white fluorescent (W 3900 - 4500K)", 15: "White fluorescent (WW 3200 - 3700K)", 17: "Standard light A", 18: "Standard light B", 19: "Standard light C", 20: "D55", 21: "D65", 22: "D75", 23: "D50", 24: "ISO studio tungsten", 255: "Other"}, Flash: {0: "Flash did not fire.", 1: "Flash fired.", 5: "Strobe return light not detected.", 7: "Strobe return light detected.", 9: "Flash fired, compulsory flash mode", 13: "Flash fired, compulsory flash mode, return light not detected", 15: "Flash fired, compulsory flash mode, return light detected", 16: "Flash did not fire, compulsory flash mode", 24: "Flash did not fire, auto mode", 25: "Flash fired, auto mode", 29: "Flash fired, auto mode, return light not detected", 31: "Flash fired, auto mode, return light detected", 32: "No flash function", 65: "Flash fired, red-eye reduction mode", 69: "Flash fired, red-eye reduction mode, return light not detected", 71: "Flash fired, red-eye reduction mode, return light detected", 73: "Flash fired, compulsory flash mode, red-eye reduction mode", 77: "Flash fired, compulsory flash mode, red-eye reduction mode, return light not detected", 79: "Flash fired, compulsory flash mode, red-eye reduction mode, return light detected", 89: "Flash fired, auto mode, red-eye reduction mode", 93: "Flash fired, auto mode, return light not detected, red-eye reduction mode", 95: "Flash fired, auto mode, return light detected, red-eye reduction mode"}, ExposureMode: {0: "Auto exposure", 1: "Manual exposure", 2: "Auto bracket"}, WhiteBalance: {0: "Auto white balance", 1: "Manual white balance"}, SceneCaptureType: {0: "Standard", 1: "Landscape", 2: "Portrait", 3: "Night scene"}, Contrast: {0: "Normal", 1: "Soft", 2: "Hard"}, Saturation: {0: "Normal", 1: "Low saturation", 2: "High saturation"}, Sharpness: {0: "Normal", 1: "Soft", 2: "Hard"}, GPSLatitudeRef: {N: "North latitude", S: "South latitude"}, GPSLongitudeRef: {E: "East longitude", W: "West longitude"}};
        function extractTags(IFD_offset, tags2extract) {
            var length = data.SHORT(IFD_offset), i, ii, tag, type, count, tagOffset, offset, value, values = [], hash = {};
            for (i = 0; i < length; i++) {
                offset = tagOffset = IFD_offset + 12 * i + 2;
                tag = tags2extract[data.SHORT(offset)];
                if (tag === undef) {
                    continue
                }
                type = data.SHORT(offset += 2);
                count = data.LONG(offset += 2);
                offset += 4;
                values = [];
                switch (type) {
                    case 1:
                    case 7:
                        if (count > 4) {
                            offset = data.LONG(offset) + offsets.tiffHeader
                        }
                        for (ii = 0; ii < count; ii++) {
                            values[ii] = data.BYTE(offset + ii)
                        }
                        break;
                    case 2:
                        if (count > 4) {
                            offset = data.LONG(offset) + offsets.tiffHeader
                        }
                        hash[tag] = data.STRING(offset, count - 1);
                        continue;
                    case 3:
                        if (count > 2) {
                            offset = data.LONG(offset) + offsets.tiffHeader
                        }
                        for (ii = 0; ii < count; ii++) {
                            values[ii] = data.SHORT(offset + ii * 2)
                        }
                        break;
                    case 4:
                        if (count > 1) {
                            offset = data.LONG(offset) + offsets.tiffHeader
                        }
                        for (ii = 0; ii < count; ii++) {
                            values[ii] = data.LONG(offset + ii * 4)
                        }
                        break;
                    case 5:
                        offset = data.LONG(offset) + offsets.tiffHeader;
                        for (ii = 0; ii < count; ii++) {
                            values[ii] = data.LONG(offset + ii * 4) / data.LONG(offset + ii * 4 + 4)
                        }
                        break;
                    case 9:
                        offset = data.LONG(offset) + offsets.tiffHeader;
                        for (ii = 0; ii < count; ii++) {
                            values[ii] = data.SLONG(offset + ii * 4)
                        }
                        break;
                    case 10:
                        offset = data.LONG(offset) + offsets.tiffHeader;
                        for (ii = 0; ii < count; ii++) {
                            values[ii] = data.SLONG(offset + ii * 4) / data.SLONG(offset + ii * 4 + 4)
                        }
                        break;
                    default:
                        continue
                }
                value = count == 1 ? values[0] : values;
                if (tagDescs.hasOwnProperty(tag) && typeof value != "object") {
                    hash[tag] = tagDescs[tag][value]
                } else {
                    hash[tag] = value
                }
            }
            return hash
        }

        function getIFDOffsets() {
            var Tiff = undef, idx = offsets.tiffHeader;
            data.II(data.SHORT(idx) == 18761);
            if (data.SHORT(idx += 2) !== 42) {
                return false
            }
            offsets["IFD0"] = offsets.tiffHeader + data.LONG(idx += 2);
            Tiff = extractTags(offsets["IFD0"], tags.tiff);
            offsets["exifIFD"] = "ExifIFDPointer"in Tiff ? offsets.tiffHeader + Tiff.ExifIFDPointer : undef;
            offsets["gpsIFD"] = "GPSInfoIFDPointer"in Tiff ? offsets.tiffHeader + Tiff.GPSInfoIFDPointer : undef;
            return true
        }

        function setTag(ifd, tag, value) {
            var offset, length, tagOffset, valueOffset = 0;
            if (typeof tag === "string") {
                var tmpTags = tags[ifd.toLowerCase()];
                for (hex in tmpTags) {
                    if (tmpTags[hex] === tag) {
                        tag = hex;
                        break
                    }
                }
            }
            offset = offsets[ifd.toLowerCase() + "IFD"];
            length = data.SHORT(offset);
            for (i = 0; i < length; i++) {
                tagOffset = offset + 12 * i + 2;
                if (data.SHORT(tagOffset) == tag) {
                    valueOffset = tagOffset + 8;
                    break
                }
            }
            if (!valueOffset)return false;
            data.LONG(valueOffset, value);
            return true
        }

        return{init: function (segment) {
            offsets = {tiffHeader: 10};
            if (segment === undef || !segment.length) {
                return false
            }
            data.init(segment);
            if (data.SHORT(0) === 65505 && data.STRING(4, 5).toUpperCase() === "EXIF\x00") {
                return getIFDOffsets()
            }
            return false
        }, EXIF: function () {
            var Exif;
            Exif = extractTags(offsets.exifIFD, tags.exif);
            if (Exif.ExifVersion && plupload.typeOf(Exif.ExifVersion) === "array") {
                for (var i = 0, exifVersion = ""; i < Exif.ExifVersion.length; i++) {
                    exifVersion += String.fromCharCode(Exif.ExifVersion[i])
                }
                Exif.ExifVersion = exifVersion
            }
            return Exif
        }, GPS: function () {
            var GPS;
            GPS = extractTags(offsets.gpsIFD, tags.gps);
            if (GPS.GPSVersionID) {
                GPS.GPSVersionID = GPS.GPSVersionID.join(".")
            }
            return GPS
        }, setExif: function (tag, value) {
            if (tag !== "PixelXDimension" && tag !== "PixelYDimension")return false;
            return setTag("exif", tag, value)
        }, getBinary: function () {
            return data.SEGMENT()
        }}
    }
})(window, document, plupload);