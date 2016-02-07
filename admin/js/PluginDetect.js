/*
PluginDetect v0.7.7 www.pinlady.net/PluginDetect | www.pinlady.net/PluginDetect/license
[ getVersion isMinVersion onDetectionDone onWindowLoaded ]
[ Java(OTF) ]
*/
var PluginDetect = {
    version: "0.7.7",
    rDate: "04/11/2012",
    name: "PluginDetect",
    handler: function (c, b, a) {
        return function () {
            c(b, a)
        }
    },
    isDefined: function (b) {
        return typeof b != "undefined"
    },
    isArray: function (b) {
        return (/array/i).test(Object.prototype.toString.call(b))
    },
    isFunc: function (b) {
        return typeof b == "function"
    },
    isString: function (b) {
        return typeof b == "string"
    },
    isNum: function (b) {
        return typeof b == "number"
    },
    isStrNum: function (b) {
        return (typeof b == "string" && (/\d/).test(b))
    },
    getNumRegx: /[\d][\d\.\_,-]*/,
    splitNumRegx: /[\.\_,-]/g,
    getNum: function (b, c) {
        var d = this,
            a = d.isStrNum(b) ? (d.isDefined(c) ? new RegExp(c) : d.getNumRegx).exec(b) : null;
        return a ? a[0] : null
    },
    compareNums: function (h, f, d) {
        var e = this,
            c, b, a, g = parseInt;
        if (e.isStrNum(h) && e.isStrNum(f)) {
            if (e.isDefined(d) && d.compareNums) {
                return d.compareNums(h, f)
            }
            c = h.split(e.splitNumRegx);
            b = f.split(e.splitNumRegx);
            for (a = 0; a < Math.min(c.length, b.length); a++) {
                if (g(c[a], 10) > g(b[a], 10)) {
                    return 1
                }
                if (g(c[a], 10) < g(b[a], 10)) {
                    return -1
                }
            }
        }
        return 0
    },
    formatNum: function (b, c) {
        var d = this,
            a, e;
        if (!d.isStrNum(b)) {
            return null
        }
        if (!d.isNum(c)) {
            c = 4
        }
        c--;
        e = b.replace(/\s/g, "").split(d.splitNumRegx).concat(["0", "0", "0", "0"]);
        for (a = 0; a < 4; a++) {
            if (/^(0+)(.+)$/.test(e[a])) {
                e[a] = RegExp.$2
            }
            if (a > c || !(/\d/).test(e[a])) {
                e[a] = "0"
            }
        }
        return e.slice(0, 4).join(",")
    },
    $$hasMimeType: function (a) {
        return function (c) {
            if (!a.isIE && c) {
                var f, e, b, d = a.isArray(c) ? c : (a.isString(c) ? [c] : []);
                for (b = 0; b < d.length; b++) {
                    if (a.isString(d[b]) && /[^\s]/.test(d[b])) {
                        f = navigator.mimeTypes[d[b]];
                        e = f ? f.enabledPlugin : 0;
                        if (e && (e.name || e.description)) {
                            return f
                        }
                    }
                }
            }
            return null
        }
    },
    findNavPlugin: function (l, e, c) {
        var j = this,
            h = new RegExp(l, "i"),
            d = (!j.isDefined(e) || e) ? /\d/ : 0,
            k = c ? new RegExp(c, "i") : 0,
            a = navigator.plugins,
            g = "",
            f, b, m;
        for (f = 0; f < a.length; f++) {
            m = a[f].description || g;
            b = a[f].name || g;
            if ((h.test(m) && (!d || d.test(RegExp.leftContext + RegExp.rightContext))) || (h.test(b) && (!d || d.test(RegExp.leftContext + RegExp.rightContext)))) {
                if (!k || !(k.test(m) || k.test(b))) {
                    return a[f]
                }
            }
        }
        return null
    },
    getMimeEnabledPlugin: function (k, m, c) {
        var e = this,
            f, b = new RegExp(m, "i"),
            h = "",
            g = c ? new RegExp(c, "i") : 0,
            a, l, d, j = e.isString(k) ? [k] : k;
        for (d = 0; d < j.length; d++) {
            if ((f = e.hasMimeType(j[d])) && (f = f.enabledPlugin)) {
                l = f.description || h;
                a = f.name || h;
                if (b.test(l) || b.test(a)) {
                    if (!g || !(g.test(l) || g.test(a))) {
                        return f
                    }
                }
            }
        }
        return 0
    },
    AXO: window.ActiveXObject,
    getAXO: function (a) {
        var f = null,
            d, b = this,
            c = {};
        try {
            f = new b.AXO(a)
        } catch (d) {}
        return f
    },
    convertFuncs: function (f) {
        var a, g, d, b = /^[\$][\$]/,
            c = this;
        for (a in f) {
            if (b.test(a)) {
                try {
                    g = a.slice(2);
                    if (g.length > 0 && !f[g]) {
                        f[g] = f[a](f);
                        delete f[a]
                    }
                } catch (d) {}
            }
        }
    },
    initObj: function (e, b, d) {
        var a, c;
        if (e) {
            if (e[b[0]] == 1 || d) {
                for (a = 0; a < b.length; a = a + 2) {
                    e[b[a]] = b[a + 1]
                }
            }
            for (a in e) {
                c = e[a];
                if (c && c[b[0]] == 1) {
                    this.initObj(c, b)
                }
            }
        }
    },
    initScript: function () {
        var c = this,
            a = navigator,
            e = "/",
            f, i = a.userAgent || "",
            g = a.vendor || "",
            b = a.platform || "",
            h = a.product || "";
        c.initObj(c, ["$", c]);
        for (f in c.Plugins) {
            if (c.Plugins[f]) {
                c.initObj(c.Plugins[f], ["$", c, "$$", c.Plugins[f]], 1)
            }
        };
        c.OS = 100;
        if (b) {
            var d = ["Win", 1, "Mac", 2, "Linux", 3, "FreeBSD", 4, "iPhone", 21.1, "iPod", 21.2, "iPad", 21.3, "Win.*CE", 22.1, "Win.*Mobile", 22.2, "Pocket\\s*PC", 22.3, "", 100];
            for (f = d.length - 2; f >= 0; f = f - 2) {
                if (d[f] && new RegExp(d[f], "i").test(b)) {
                    c.OS = d[f + 1];
                    break
                }
            }
        }
        c.convertFuncs(c);
        c.head = (document.getElementsByTagName("head")[0] || document.getElementsByTagName("body")[0] || document.body || null);
        c.isIE = (new Function("return " + e + "*@cc_on!@*" + e + "false"))();
        c.verIE = c.isIE && (/MSIE\s*(\d+\.?\d*)/i).test(i) ? parseFloat(RegExp.$1, 10) : null;
        c.ActiveXEnabled = false;
        if (c.isIE) {
            var f, j = ["Msxml2.XMLHTTP", "Msxml2.DOMDocument", "Microsoft.XMLDOM", "ShockwaveFlash.ShockwaveFlash", "TDCCtl.TDCCtl", "Shell.UIHelper", "Scripting.Dictionary", "wmplayer.ocx"];
            for (f = 0; f < j.length; f++) {
                if (c.getAXO(j[f])) {
                    c.ActiveXEnabled = true;
                    break
                }
            }
        }
        c.isGecko = (/Gecko/i).test(h) && (/Gecko\s*\/\s*\d/i).test(i);
        c.verGecko = c.isGecko ? c.formatNum((/rv\s*\:\s*([\.\,\d]+)/i).test(i) ? RegExp.$1 : "0.9") : null;
        c.isChrome = (/Chrome\s*\/\s*(\d[\d\.]*)/i).test(i);
        c.verChrome = c.isChrome ? c.formatNum(RegExp.$1) : null;
        c.isSafari = ((/Apple/i).test(g) || (!g && !c.isChrome)) && (/Safari\s*\/\s*(\d[\d\.]*)/i).test(i);
        c.verSafari = c.isSafari && (/Version\s*\/\s*(\d[\d\.]*)/i).test(i) ? c.formatNum(RegExp.$1) : null;
        c.isOpera = (/Opera\s*[\/]?\s*(\d+\.?\d*)/i).test(i);
        c.verOpera = c.isOpera && ((/Version\s*\/\s*(\d+\.?\d*)/i).test(i) || 1) ? parseFloat(RegExp.$1, 10) : null;
        c.addWinEvent("load", c.handler(c.runWLfuncs, c))
    },
    init: function (d) {
        var c = this,
            b, d, a = {
                status: -3,
                plugin: 0
            };
        if (!c.isString(d)) {
            return a
        }
        if (d.length == 1) {
            c.getVersionDelimiter = d;
            return a
        }
        d = d.toLowerCase().replace(/\s/g, "");
        b = c.Plugins[d];
        if (!b || !b.getVersion) {
            return a
        }
        a.plugin = b;
        if (!c.isDefined(b.installed)) {
            b.installed = null;
            b.version = null;
            b.version0 = null;
            b.getVersionDone = null;
            b.pluginName = d
        }
        c.garbage = false;
        if (c.isIE && !c.ActiveXEnabled && d !== "java") {
            a.status = -2;
            return a
        }
        a.status = 1;
        return a
    },
    fPush: function (b, a) {
        var c = this;
        if (c.isArray(a) && (c.isFunc(b) || (c.isArray(b) && b.length > 0 && c.isFunc(b[0])))) {
            a.push(b)
        }
    },
    callArray: function (b) {
        var c = this,
            a;
        if (c.isArray(b)) {
            for (a = 0; a < b.length; a++) {
                if (b[a] === null) {
                    return
                }
                c.call(b[a]);
                b[a] = null
            }
        }
    },
    call: function (c) {
        var b = this,
            a = b.isArray(c) ? c.length : -1;
        if (a > 0 && b.isFunc(c[0])) {
            c[0](b, a > 1 ? c[1] : 0, a > 2 ? c[2] : 0, a > 3 ? c[3] : 0)
        } else {
            if (b.isFunc(c)) {
                c(b)
            }
        }
    },
    $$isMinVersion: function (a) {
        return function (h, g, d, c) {
            var e = a.init(h),
                f, b = -1,
                j = {};
            if (e.status < 0) {
                return e.status
            }
            f = e.plugin;
            g = a.formatNum(a.isNum(g) ? g.toString() : (a.isStrNum(g) ? a.getNum(g) : "0"));
            if (f.getVersionDone != 1) {
                f.getVersion(g, d, c);
                if (f.getVersionDone === null) {
                    f.getVersionDone = 1
                }
            }
            a.cleanup();
            if (f.installed !== null) {
                b = f.installed <= 0.5 ? f.installed : (f.installed == 0.7 ? 1 : (f.version === null ? 0 : (a.compareNums(f.version, g, f) >= 0 ? 1 : -0.1)))
            };
            return b
        }
    },
    getVersionDelimiter: ",",
    $$getVersion: function (a) {
        return function (g, d, c) {
            var e = a.init(g),
                f, b, h = {};
            if (e.status < 0) {
                return null
            };
            f = e.plugin;
            if (f.getVersionDone != 1) {
                f.getVersion(null, d, c);
                if (f.getVersionDone === null) {
                    f.getVersionDone = 1
                }
            }
            a.cleanup();
            b = (f.version || f.version0);
            b = b ? b.replace(a.splitNumRegx, a.getVersionDelimiter) : b;
            return b
        }
    },
    cleanup: function () {
        var a = this;
        if (a.garbage && a.isDefined(window.CollectGarbage)) {
            window.CollectGarbage()
        }
    },
    addWinEvent: function (d, c) {
        var e = this,
            a = window,
            b;
        if (e.isFunc(c)) {
            if (a.addEventListener) {
                a.addEventListener(d, c, false)
            } else {
                if (a.attachEvent) {
                    a.attachEvent("on" + d, c)
                } else {
                    b = a["on" + d];
                    a["on" + d] = e.winHandler(c, b)
                }
            }
        }
    },
    winHandler: function (d, c) {
        return function () {
            d();
            if (typeof c == "function") {
                c()
            }
        }
    },
    WLfuncs0: [],
    WLfuncs: [],
    runWLfuncs: function (a) {
        var b = {};
        a.winLoaded = true;
        a.callArray(a.WLfuncs0);
        a.callArray(a.WLfuncs);
        if (a.onDoneEmptyDiv) {
            a.onDoneEmptyDiv()
        }
    },
    winLoaded: false,
    $$onWindowLoaded: function (a) {
        return function (b) {
            if (a.winLoaded) {
                a.call(b)
            } else {
                a.fPush(b, a.WLfuncs)
            }
        }
    },
    $$onDetectionDone: function (a) {
        return function (h, g, c, b) {
            var d = a.init(h),
                k, e, j = {};
            if (d.status == -3) {
                return -1
            }
            e = d.plugin;
            if (!a.isArray(e.funcs)) {
                e.funcs = []
            }
            if (e.getVersionDone != 1) {
                k = a.isMinVersion ? a.isMinVersion(h, "0", c, b) : a.getVersion(h, c, b)
            }
            if (e.installed != -0.5 && e.installed != 0.5) {
                a.call(g);
                return 1
            }
            if (e.NOTF) {
                a.fPush(g, e.funcs);
                return 0
            }
            return 1
        }
    },
    div: null,
    divID: "plugindetect",
    divWidth: 50,
    pluginSize: 1,
    emptyDiv: function () {
        var d = this,
            b, h, c, a, f, g;
        if (d.div && d.div.childNodes) {
            for (b = d.div.childNodes.length - 1; b >= 0; b--) {
                c = d.div.childNodes[b];
                if (c && c.childNodes) {
                    for (h = c.childNodes.length - 1; h >= 0; h--) {
                        g = c.childNodes[h];
                        try {
                            c.removeChild(g)
                        } catch (f) {}
                    }
                }
                if (c) {
                    try {
                        d.div.removeChild(c)
                    } catch (f) {}
                }
            }
        }
        if (!d.div) {
            a = document.getElementById(d.divID);
            if (a) {
                d.div = a
            }
        }
        if (d.div && d.div.parentNode) {
            try {
                d.div.parentNode.removeChild(d.div)
            } catch (f) {}
            d.div = null
        }
    },
    DONEfuncs: [],
    onDoneEmptyDiv: function () {
        var c = this,
            a, b;
        if (!c.winLoaded) {
            return
        }
        if (c.WLfuncs && c.WLfuncs.length && c.WLfuncs[c.WLfuncs.length - 1] !== null) {
            return
        }
        for (a in c) {
            b = c[a];
            if (b && b.funcs) {
                if (b.OTF == 3) {
                    return
                }
                if (b.funcs.length && b.funcs[b.funcs.length - 1] !== null) {
                    return
                }
            }
        }
        for (a = 0; a < c.DONEfuncs.length; a++) {
            c.callArray(c.DONEfuncs)
        }
        c.emptyDiv()
    },
    getWidth: function (c) {
        if (c) {
            var a = c.scrollWidth || c.offsetWidth,
                b = this;
            if (b.isNum(a)) {
                return a
            }
        }
        return -1
    },
    getTagStatus: function (m, g, a, b) {
        var c = this,
            f, k = m.span,
            l = c.getWidth(k),
            h = a.span,
            j = c.getWidth(h),
            d = g.span,
            i = c.getWidth(d);
        if (!k || !h || !d || !c.getDOMobj(m)) {
            return -2
        }
        if (j < i || l < 0 || j < 0 || i < 0 || i <= c.pluginSize || c.pluginSize < 1) {
            return 0
        }
        if (l >= i) {
            return -1
        }
        try {
            if (l == c.pluginSize && (!c.isIE || c.getDOMobj(m).readyState == 4)) {
                if (!m.winLoaded && c.winLoaded) {
                    return 1
                }
                if (m.winLoaded && c.isNum(b)) {
                    if (!c.isNum(m.count)) {
                        m.count = b
                    }
                    if (b - m.count >= 10) {
                        return 1
                    }
                }
            }
        } catch (f) {}
        return 0
    },
    getDOMobj: function (g, a) {
        var f, d = this,
            c = g ? g.span : 0,
            b = c && c.firstChild ? 1 : 0;
        try {
            if (b && a) {
                d.div.focus()
            }
        } catch (f) {}
        return b ? c.firstChild : null
    },
    setStyle: function (b, g) {
        var f = b.style,
            a, d, c = this;
        if (f && g) {
            for (a = 0; a < g.length; a = a + 2) {
                try {
                    f[g[a]] = g[a + 1]
                } catch (d) {}
            }
        }
    },
    insertDivInBody: function (a, i) {
        var h, f = this,
            b = "pd33993399",
            d = null,
            j = i ? window.top.document : window.document,
            c = "<",
            g = (j.getElementsByTagName("body")[0] || j.body);
        if (!g) {
            try {
                j.write(c + 'div id="' + b + '">o' + c + "/div>");
                d = j.getElementById(b)
            } catch (h) {}
        }
        g = (j.getElementsByTagName("body")[0] || j.body);
        if (g) {
            if (g.firstChild && f.isDefined(g.insertBefore)) {
                g.insertBefore(a, g.firstChild)
            } else {
                g.appendChild(a)
            } if (d) {
                g.removeChild(d)
            }
        } else {}
    },
    insertHTML: function (g, b, h, a, l) {
        var m, n = document,
            k = this,
            q, p = n.createElement("span"),
            o, j, f = "<";
        var c = ["outlineStyle", "none", "borderStyle", "none", "padding", "0px", "margin", "0px", "visibility", "visible"];
        var i = "outline-style:none;border-style:none;padding:0px;margin:0px;visibility:visible;";
        if (!k.isDefined(a)) {
            a = ""
        }
        if (k.isString(g) && (/[^\s]/).test(g)) {
            g = g.toLowerCase().replace(/\s/g, "");
            q = f + g + ' width="' + k.pluginSize + '" height="' + k.pluginSize + '" ';
            q += 'style="' + i + 'display:inline;" ';
            for (o = 0; o < b.length; o = o + 2) {
                if (/[^\s]/.test(b[o + 1])) {
                    q += b[o] + '="' + b[o + 1] + '" '
                }
            }
            q += ">";
            for (o = 0; o < h.length; o = o + 2) {
                if (/[^\s]/.test(h[o + 1])) {
                    q += f + 'param name="' + h[o] + '" value="' + h[o + 1] + '" />'
                }
            }
            q += a + f + "/" + g + ">"
        } else {
            q = a
        } if (!k.div) {
            j = n.getElementById(k.divID);
            if (j) {
                k.div = j
            } else {
                k.div = n.createElement("div");
                k.div.id = k.divID
            }
            k.setStyle(k.div, c.concat(["width", k.divWidth + "px", "height", (k.pluginSize + 3) + "px", "fontSize", (k.pluginSize + 3) + "px", "lineHeight", (k.pluginSize + 3) + "px", "verticalAlign", "baseline", "display", "block"]));
            if (!j) {
                k.setStyle(k.div, ["position", "absolute", "right", "0px", "top", "0px"]);
                k.insertDivInBody(k.div)
            }
        }
        if (k.div && k.div.parentNode) {
            k.setStyle(p, c.concat(["fontSize", (k.pluginSize + 3) + "px", "lineHeight", (k.pluginSize + 3) + "px", "verticalAlign", "baseline", "display", "inline"]));
            try {
                p.innerHTML = q
            } catch (m) {};
            try {
                k.div.appendChild(p)
            } catch (m) {};
            return {
                span: p,
                winLoaded: k.winLoaded,
                tagName: g,
                outerHTML: q
            }
        }
        return {
            span: null,
            winLoaded: k.winLoaded,
            tagName: "",
            outerHTML: q
        }
    },
    file: {
        $: 1,
        any: "fileStorageAny999",
        valid: "fileStorageValid999",
        save: function (d, f, c) {
            var b = this,
                e = b.$,
                a;
            if (d && e.isDefined(c)) {
                if (!d[b.any]) {
                    d[b.any] = []
                }
                if (!d[b.valid]) {
                    d[b.valid] = []
                }
                d[b.any].push(c);
                a = b.split(f, c);
                if (a) {
                    d[b.valid].push(a)
                }
            }
        },
        getValidLength: function (a) {
            return a && a[this.valid] ? a[this.valid].length : 0
        },
        getAnyLength: function (a) {
            return a && a[this.any] ? a[this.any].length : 0
        },
        getValid: function (c, a) {
            var b = this;
            return c && c[b.valid] ? b.get(c[b.valid], a) : null
        },
        getAny: function (c, a) {
            var b = this;
            return c && c[b.any] ? b.get(c[b.any], a) : null
        },
        get: function (d, a) {
            var c = d.length - 1,
                b = this.$.isNum(a) ? a : c;
            return (b < 0 || b > c) ? null : d[b]
        },
        split: function (g, c) {
            var b = this,
                e = b.$,
                f = null,
                a, d;
            g = g ? g.replace(".", "\\.") : "";
            d = new RegExp("^(.*[^\\/])(" + g + "\\s*)$");
            if (e.isString(c) && d.test(c)) {
                a = (RegExp.$1).split("/");
                f = {
                    name: a[a.length - 1],
                    ext: RegExp.$2,
                    full: c
                };
                a[a.length - 1] = "";
                f.path = a.join("/")
            }
            return f
        },
        z: 0
    },
    Plugins: {
        java: {
            mimeType: ["application/x-java-applet", "application/x-java-vm", "application/x-java-bean"],
            classID: "clsid:8AD9C840-044E-11D1-B3E9-00805F499D93",
            navigator: {
                a: window.navigator.javaEnabled(),
                javaEnabled: function () {
                    return this.a
                },
                mimeObj: 0,
                pluginObj: 0
            },
            OTF: null,
            minIEver: 7,
            debug: 0,
            debugEnable: function () {
                var a = this,
                    b = a.$;
                a.debug = 1
            },
            isDisabled: {
                $: 1,
                DTK: function () {
                    var a = this,
                        c = a.$,
                        b = a.$$;
                    if ((c.isGecko && c.compareNums(c.verGecko, c.formatNum("1.6")) <= 0) || (c.isSafari && c.OS == 1 && (!c.verSafari || c.compareNums(c.verSafari, "5,1,0,0") < 0)) || c.isChrome || (c.isIE && !c.ActiveXEnabled)) {
                        return 1
                    }
                    return 0
                },
                AXO: function () {
                    var a = this,
                        c = a.$,
                        b = a.$$;
                    return (!c.isIE || !c.ActiveXEnabled || (!b.debug && b.DTK.query().status !== 0))
                },
                navMime: function () {
                    var b = this,
                        d = b.$,
                        c = b.$$,
                        a = c.navigator;
                    if (d.isIE || !a.mimeObj || !a.pluginObj) {
                        return 1
                    }
                    return 0
                },
                navPlugin: function () {
                    var b = this,
                        d = b.$,
                        c = b.$$,
                        a = c.navigator;
                    if (d.isIE || !a.mimeObj || !a.pluginObj) {
                        return 1
                    }
                    return 0
                },
                windowDotJava: function () {
                    var a = this,
                        c = a.$,
                        b = a.$$;
                    if (!window.java) {
                        return 1
                    }
                    if (c.OS == 2 && c.verOpera && c.verOpera < 9.2 && c.verOpera >= 9) {
                        return 1
                    }
                    if (c.verGecko && c.compareNums(c.verGecko, "1,9,0,0") < 0 && c.compareNums(c.verGecko, "1,8,0,0") >= 0) {
                        return 1
                    }
                    return 0
                },
                allApplets: function () {
                    var b = this,
                        d = b.$,
                        c = b.$$,
                        a = c.navigator;
                    if (d.OS >= 20) {
                        return 0
                    }
                    if (d.verOpera && d.verOpera < 11 && !a.javaEnabled() && !c.lang.System.getProperty()[0]) {
                        return 1
                    }
                    if ((d.verGecko && d.compareNums(d.verGecko, d.formatNum("2")) < 0) && !a.mimeObj && !c.lang.System.getProperty()[0]) {
                        return 1
                    }
                    return 0
                },
                AppletTag: function () {
                    var b = this,
                        d = b.$,
                        c = b.$$,
                        a = c.navigator;
                    return d.isIE ? !a.javaEnabled() : 0
                },
                ObjectTag: function () {
                    var a = this,
                        c = a.$,
                        b = a.$$;
                    return c.isIE ? !c.ActiveXEnabled : 0
                },
                z: 0
            },
            getVerifyTagsDefault: function () {
                var a = this,
                    c = a.$,
                    b = [1, 0, 1];
                if (c.OS >= 20) {
                    return b
                }
                if ((c.isIE && (c.verIE < 9 || !c.ActiveXEnabled)) || (c.verGecko && c.compareNums(c.verGecko, c.formatNum("2")) < 0) || (c.isSafari && (!c.verSafari || c.compareNums(c.verSafari, c.formatNum("4")) < 0)) || (c.verOpera && c.verOpera < 10)) {
                    b = [1, 1, 1]
                }
                return b
            },
            getVersion: function (j, g, i) {
                var b = this,
                    d = b.$,
                    e, a = b.applet,
                    h = b.verify,
                    k = b.navigator,
                    f = null,
                    l = null,
                    c = null;
                if (b.getVersionDone === null) {
                    b.OTF = 0;
                    k.mimeObj = d.hasMimeType(b.mimeType);
                    if (k.mimeObj) {
                        k.pluginObj = k.mimeObj.enabledPlugin
                    }
                    if (h) {
                        h.begin()
                    }
                }
                a.setVerifyTagsArray(i);
                d.file.save(b, ".jar", g);
                if (b.getVersionDone === 0) {
                    if (a.should_Insert_Query_Any()) {
                        e = a.insert_Query_Any();
                        b.setPluginStatus(e[0], e[1], f)
                    }
                    return
                }
                if ((!f || b.debug) && b.DTK.query().version) {
                    f = b.DTK.version
                }
                if ((!f || b.debug) && b.navMime.query().version) {
                    f = b.navMime.version
                }
                if ((!f || b.debug) && b.navPlugin.query().version) {
                    f = b.navPlugin.version
                }
                if ((!f || b.debug) && b.AXO.query().version) {
                    f = b.AXO.version
                }
                if (b.nonAppletDetectionOk(f)) {
                    c = f
                }
                if (!c || b.debug || a.VerifyTagsHas(2.2) || a.VerifyTagsHas(2.5)) {
                    e = b.lang.System.getProperty();
                    if (e[0]) {
                        f = e[0];
                        c = e[0];
                        l = e[1]
                    }
                }
                b.setPluginStatus(c, l, f);
                if (a.should_Insert_Query_Any()) {
                    e = a.insert_Query_Any();
                    if (e[0]) {
                        c = e[0];
                        l = e[1]
                    }
                }
                b.setPluginStatus(c, l, f)
            },
            nonAppletDetectionOk: function (b) {
                var d = this,
                    e = d.$,
                    a = d.navigator,
                    c = 1;
                if (!b || (!a.javaEnabled() && !d.lang.System.getPropertyHas(b)) || (!e.isIE && !a.mimeObj && !d.lang.System.getPropertyHas(b)) || (e.isIE && !e.ActiveXEnabled)) {
                    c = 0
                } else {
                    if (e.OS >= 20) {} else {
                        if (d.info && d.info.getPlugin2Status() < 0 && d.info.BrowserRequiresPlugin2()) {
                            c = 0
                        }
                    }
                }
                return c
            },
            setPluginStatus: function (d, f, a) {
                var c = this,
                    e = c.$,
                    b;
                a = a || c.version0;
                if (c.OTF > 0) {
                    d = d || c.lang.System.getProperty()[0]
                }
                if (c.OTF < 3) {
                    b = d ? 1 : (a ? -0.2 : -1);
                    if (c.installed === null || b > c.installed) {
                        c.installed = b
                    }
                }
                if (c.OTF == 2 && c.NOTF && !c.applet.getResult()[0] && !c.lang.System.getProperty()[0]) {
                    c.installed = a ? -0.2 : -1
                };
                if (a) {
                    c.version0 = e.formatNum(e.getNum(a))
                }
                if (d) {
                    c.version = e.formatNum(e.getNum(d))
                }
                if (f && e.isString(f)) {
                    c.vendor = f
                }
                if (!c.vendor) {
                    c.vendor = ""
                }
                if (c.verify && c.verify.isEnabled()) {
                    c.getVersionDone = 0
                } else {
                    if (c.getVersionDone != 1) {
                        if (c.OTF < 2) {
                            c.getVersionDone = 0
                        } else {
                            c.getVersionDone = c.applet.can_Insert_Query_Any() ? 0 : 1
                        }
                    }
                }
            },
            DTK: {
                $: 1,
                hasRun: 0,
                status: null,
                VERSIONS: [],
                version: "",
                HTML: null,
                Plugin2Status: null,
                classID: "clsid:CAFEEFAC-DEC7-0000-0000-ABCDEFFEDCBA",
                mimeType: ["application/java-deployment-toolkit", "application/npruntime-scriptable-plugin;DeploymentToolkit"],
                disabled: function () {
                    return this.$$.isDisabled.DTK()
                },
                query: function () {
                    var k = this,
                        g = k.$,
                        d = k.$$,
                        j, l, h, m = {}, f = {}, a, c = null,
                        i = null,
                        b = (k.hasRun || k.disabled());
                    k.hasRun = 1;
                    if (b) {
                        return k
                    }
                    k.status = 0;
                    if (g.isIE && g.verIE >= 6) {
                        k.HTML = g.insertHTML("object", [], []);
                        c = g.getDOMobj(k.HTML)
                    } else {
                        if (!g.isIE && (h = g.hasMimeType(k.mimeType)) && h.type) {
                            k.HTML = g.insertHTML("object", ["type", h.type], []);
                            c = g.getDOMobj(k.HTML)
                        }
                    } if (c) {
                        if (g.isIE && g.verIE >= 6) {
                            try {
                                c.classid = k.classID
                            } catch (j) {}
                        };
                        try {
                            a = c.jvms;
                            if (a) {
                                i = a.getLength();
                                if (g.isNum(i)) {
                                    k.status = i > 0 ? 1 : -1;
                                    for (l = 0; l < i; l++) {
                                        h = g.getNum(a.get(i - 1 - l).version);
                                        if (h) {
                                            k.VERSIONS.push(h);
                                            f["a" + g.formatNum(h)] = 1
                                        }
                                    }
                                }
                            }
                        } catch (j) {}
                    }
                    h = 0;
                    for (l in f) {
                        h++
                    }
                    if (h && h !== k.VERSIONS.length) {
                        k.VERSIONS = []
                    }
                    if (k.VERSIONS.length) {
                        k.version = g.formatNum(k.VERSIONS[0])
                    };
                    return k
                }
            },
            AXO: {
                $: 1,
                hasRun: 0,
                VERSIONS: [],
                version: "",
                disabled: function () {
                    return this.$$.isDisabled.AXO()
                },
                JavaVersions: [
                    [1, 9, 2, 30],
                    [1, 8, 2, 30],
                    [1, 7, 2, 30],
                    [1, 6, 1, 40],
                    [1, 5, 1, 30],
                    [1, 4, 2, 30],
                    [1, 3, 1, 30]
                ],
                query: function () {
                    var a = this,
                        e = a.$,
                        b = a.$$,
                        c = (a.hasRun || a.disabled());
                    a.hasRun = 1;
                    if (c) {
                        return a
                    }
                    var i = [],
                        k = [1, 5, 0, 14],
                        j = [1, 6, 0, 2],
                        h = [1, 3, 1, 0],
                        g = [1, 4, 2, 0],
                        f = [1, 5, 0, 7],
                        d = b.getInfo ? true : false,
                        l = {};
                    if (e.verIE >= b.minIEver) {
                        i = a.search(j, j, d);
                        if (i.length > 0 && d) {
                            i = a.search(k, k, d)
                        }
                    } else {
                        if (d) {
                            i = a.search(f, f, true)
                        }
                        if (i.length == 0) {
                            i = a.search(h, g, false)
                        }
                    } if (i.length) {
                        a.version = i[0];
                        a.VERSIONS = [].concat(i)
                    };
                    return a
                },
                search: function (a, j, p) {
                    var h, d, f = this,
                        e = f.$,
                        k = f.$$,
                        n, c, l, q, b, o, r, i = [];
                    if (e.compareNums(a.join(","), j.join(",")) > 0) {
                        j = a
                    }
                    j = e.formatNum(j.join(","));
                    var m, s = "1,4,2,0",
                        g = "JavaPlugin." + a[0] + "" + a[1] + "" + a[2] + "" + (a[3] > 0 ? ("_" + (a[3] < 10 ? "0" : "") + a[3]) : "");
                    for (h = 0; h < f.JavaVersions.length; h++) {
                        d = f.JavaVersions[h];
                        n = "JavaPlugin." + d[0] + "" + d[1];
                        b = d[0] + "." + d[1] + ".";
                        for (l = d[2]; l >= 0; l--) {
                            r = "JavaWebStart.isInstalled." + b + l + ".0";
                            if (e.compareNums(d[0] + "," + d[1] + "," + l + ",0", j) >= 0 && !e.getAXO(r)) {
                                continue
                            }
                            m = e.compareNums(d[0] + "," + d[1] + "," + l + ",0", s) < 0 ? true : false;
                            for (q = d[3]; q >= 0; q--) {
                                c = l + "_" + (q < 10 ? "0" + q : q);
                                o = n + c;
                                if (e.getAXO(o) && (m || e.getAXO(r))) {
                                    i.push(b + c);
                                    if (!p) {
                                        return i
                                    }
                                }
                                if (o == g) {
                                    return i
                                }
                            }
                            if (e.getAXO(n + l) && (m || e.getAXO(r))) {
                                i.push(b + l);
                                if (!p) {
                                    return i
                                }
                            }
                            if (n + l == g) {
                                return i
                            }
                        }
                    }
                    return i
                }
            },
            navMime: {
                $: 1,
                hasRun: 0,
                mimetype: "",
                version: "",
                length: 0,
                mimeObj: 0,
                pluginObj: 0,
                disabled: function () {
                    return this.$$.isDisabled.navMime()
                },
                query: function () {
                    var i = this,
                        f = i.$,
                        a = i.$$,
                        b = (i.hasRun || i.disabled());
                    i.hasRun = 1;
                    if (b) {
                        return i
                    };
                    var n = /^\s*application\/x-java-applet;jpi-version\s*=\s*(\d.*)$/i,
                        g, l, j, d = "",
                        h = "a",
                        o, m, k = {}, c = f.formatNum("0");
                    for (l = 0; l < navigator.mimeTypes.length; l++) {
                        o = navigator.mimeTypes[l];
                        m = o ? o.enabledPlugin : 0;
                        g = o && n.test(o.type || d) ? f.formatNum(f.getNum(RegExp.$1)) : 0;
                        if (g && m && (m.description || m.name)) {
                            if (!k[h + g]) {
                                i.length++
                            }
                            k[h + g] = o.type;
                            if (f.compareNums(g, c) > 0) {
                                c = g
                            }
                        }
                    }
                    g = k[h + c];
                    if (g) {
                        o = f.hasMimeType(g);
                        i.mimeObj = o;
                        i.pluginObj = o ? o.enabledPlugin : 0;
                        i.mimetype = g;
                        i.version = c
                    };
                    return i
                }
            },
            navPlugin: {
                $: 1,
                hasRun: 0,
                version: "",
                disabled: function () {
                    return this.$$.isDisabled.navPlugin()
                },
                query: function () {
                    var l = this,
                        d = l.$,
                        c = l.$$,
                        g = c.navigator,
                        i, k, j, f, a, h, e = 0,
                        b = (l.hasRun || l.disabled());
                    l.hasRun = 1;
                    if (b) {
                        return l
                    };
                    a = g.pluginObj.name || "";
                    h = g.pluginObj.description || "";
                    if (!e || c.debug) {
                        f = /Java.*TM.*Platform[^\d]*(\d+)(?:[\.,_](\d*))?(?:\s*[Update]+\s*(\d*))?/i;
                        if ((f.test(a) || f.test(h)) && parseInt(RegExp.$1, 10) >= 5) {
                            e = "1," + RegExp.$1 + "," + (RegExp.$2 ? RegExp.$2 : "0") + "," + (RegExp.$3 ? RegExp.$3 : "0")
                        }
                    }
                    if (!e || c.debug) {
                        f = /Java[^\d]*Plug-in/i;
                        k = f.test(h) ? d.formatNum(d.getNum(h)) : 0;
                        j = f.test(a) ? d.formatNum(d.getNum(a)) : 0;
                        if (k && (d.compareNums(k, d.formatNum("1,3") < 0) || d.compareNums(k, d.formatNum("2") >= 0))) {
                            k = 0
                        }
                        if (j && (d.compareNums(j, d.formatNum("1,3") < 0) || d.compareNums(j, d.formatNum("2") >= 0))) {
                            j = 0
                        }
                        e = k && j ? (d.compareNums(k, j) > 0 ? k : j) : (k || j || e)
                    }
                    if (!e && d.isSafari && d.OS == 2) {
                        i = d.findNavPlugin("Java.*\\d.*Plug-in.*Cocoa", 0);
                        if (i) {
                            k = d.getNum(i.description);
                            if (k) {
                                e = k
                            }
                        }
                    }
                    if (e) {
                        l.version = d.formatNum(e)
                    };
                    return l
                }
            },
            lang: {
                $: 1,
                System: {
                    $: 1,
                    hasRun: 0,
                    result: [null, null],
                    disabled: function () {
                        return this.$$.isDisabled.windowDotJava()
                    },
                    getPropertyHas: function (a) {
                        var b = this,
                            d = b.$,
                            c = b.getProperty()[0];
                        return (a && c && d.compareNums(d.formatNum(a), d.formatNum(c)) === 0) ? 1 : 0
                    },
                    getProperty: function () {
                        var f = this,
                            g = f.$,
                            d = f.$$,
                            i, h = {}, b = (f.hasRun || f.disabled());
                        f.hasRun = 1;
                        if (!b) {
                            var a = "java_qqq990";
                            g[a] = null;
                            try {
                                var c = document.createElement("script");
                                c.type = "text/javascript";
                                c.appendChild(document.createTextNode("(function(){var e;try{if (window.java && window.java.lang && window.java.lang.System){" + g.name + "." + a + '=[window.java.lang.System.getProperty("java.version")+" ",window.java.lang.System.getProperty("java.vendor")+" "]}}catch(e){}})();'));
                                if (g.head.firstChild) {
                                    g.head.insertBefore(c, g.head.firstChild)
                                } else {
                                    g.head.appendChild(c)
                                }
                                g.head.removeChild(c)
                            } catch (i) {}
                            if (g[a] && g.isArray(g[a])) {
                                f.result = [].concat(g[a])
                            }
                        }
                        return f.result
                    }
                }
            },
            applet: {
                $: 1,
                results: [
                    [null, null],
                    [null, null],
                    [null, null]
                ],
                getResult: function () {
                    var c = this.results,
                        a, b = [];
                    for (a = 0; a < c.length; a++) {
                        b = c[a];
                        if (b[0]) {
                            break
                        }
                    }
                    return [].concat(b)
                },
                HTML: [0, 0, 0],
                active: [0, 0, 0],
                DummyObjTagHTML: 0,
                DummySpanTagHTML: 0,
                allowed: [1, 1, 1],
                VerifyTagsHas: function (c) {
                    var d = this,
                        b;
                    for (b = 0; b < d.allowed.length; b++) {
                        if (d.allowed[b] === c) {
                            return 1
                        }
                    }
                    return 0
                },
                saveAsVerifyTagsArray: function (c) {
                    var b = this,
                        d = b.$,
                        a;
                    if (d.isArray(c)) {
                        for (a = 0; a < b.allowed.length; a++) {
                            if (d.isNum(c[a])) {
                                if (c[a] < 0) {
                                    c[a] = 0
                                }
                                if (c[a] > 3) {
                                    c[a] = 3
                                }
                                b.allowed[a] = c[a]
                            }
                        }
                    }
                },
                setVerifyTagsArray: function (d) {
                    var b = this,
                        c = b.$,
                        a = b.$$;
                    if (a.getVersionDone === null) {
                        b.saveAsVerifyTagsArray(a.getVerifyTagsDefault())
                    }
                    if (a.debug || (a.verify && a.verify.isEnabled())) {
                        b.saveAsVerifyTagsArray([3, 3, 3])
                    } else {
                        if (d) {
                            b.saveAsVerifyTagsArray(d)
                        }
                    }
                },
                allDisabled: function () {
                    return this.$$.isDisabled.allApplets()
                },
                isDisabled: function (d) {
                    var b = this,
                        c = b.$,
                        a = b.$$;
                    if (d == 2 && !c.isIE) {
                        return 1
                    }
                    if (d === 0 || d == 2) {
                        return a.isDisabled.ObjectTag()
                    }
                    if (d == 1) {
                        return a.isDisabled.AppletTag()
                    }
                },
                can_Insert_Query: function (b) {
                    var a = this;
                    if (a.HTML[b]) {
                        return 0
                    }
                    return !a.isDisabled(b)
                },
                can_Insert_Query_Any: function () {
                    var b = this,
                        a;
                    for (a = 0; a < b.results.length; a++) {
                        if (b.can_Insert_Query(a)) {
                            return 1
                        }
                    }
                    return 0
                },
                should_Insert_Query: function (d) {
                    var b = this,
                        e = b.allowed,
                        c = b.$,
                        a = b.$$;
                    if (!b.can_Insert_Query(d)) {
                        return 0
                    }
                    if (e[d] == 3) {
                        return 1
                    }
                    if (e[d] == 2.8 && !b.getResult()[0]) {
                        return 1
                    }
                    if (e[d] == 2.5 && !a.lang.System.getProperty()[0]) {
                        return 1
                    }
                    if (e[d] == 2.2 && !a.lang.System.getProperty()[0] && !b.getResult()[0]) {
                        return 1
                    }
                    if (!a.nonAppletDetectionOk(a.version0)) {
                        if (e[d] == 2) {
                            return 1
                        }
                        if (e[d] == 1 && !b.getResult()[0]) {
                            return 1
                        }
                    }
                    return 0
                },
                should_Insert_Query_Any: function () {
                    var b = this,
                        a;
                    for (a = 0; a < b.allowed.length; a++) {
                        if (b.should_Insert_Query(a)) {
                            return 1
                        }
                    }
                    return 0
                },
                query: function (f) {
                    var h, a = this,
                        g = a.$,
                        d = a.$$,
                        i = null,
                        j = null,
                        b = a.results,
                        c;
                    if ((b[f][0] && b[f][1]) || (d.debug && d.OTF < 3)) {
                        return
                    }
                    c = g.getDOMobj(a.HTML[f], true);
                    if (c) {
                        try {
                            i = c.getVersion() + " ";
                            j = c.getVendor() + " ";
                            c.statusbar(g.winLoaded ? " " : " ")
                        } catch (h) {}
                        if (i && g.isStrNum(i)) {
                            b[f] = [i, j]
                        } else {};
                        try {
                            if (g.isIE && i && c.readyState != 4) {
                                g.garbage = true;
                                c.parentNode.removeChild(c)
                            }
                        } catch (h) {}
                    }
                },
                insert_Query_Any: function () {
                    var d = this,
                        i = d.$,
                        e = d.$$,
                        l = d.results,
                        p = d.HTML,
                        a = "&nbsp;&nbsp;&nbsp;&nbsp;",
                        g = "A.class",
                        m = i.file.getValid(e);
                    if (!m) {
                        return d.getResult()
                    }
                    if (e.OTF < 1) {
                        e.OTF = 1
                    }
                    if (d.allDisabled()) {
                        return d.getResult()
                    }
                    if (e.OTF < 1.5) {
                        e.OTF = 1.5
                    }
                    var j = m.name + m.ext,
                        h = m.path;
                    var f = ["archive", j, "code", g],
                        c = ["mayscript", "true"],
                        o = ["scriptable", "true"].concat(c),
                        n = e.navigator,
                        b = !i.isIE && n.mimeObj && n.mimeObj.type ? n.mimeObj.type : e.mimeType[0];
                    if (d.should_Insert_Query(0)) {
                        if (e.OTF < 2) {
                            e.OTF = 2
                        };
                        p[0] = i.isIE ? i.insertHTML("object", ["type", b], ["codebase", h].concat(f).concat(o), a, e) : i.insertHTML("object", ["type", b], ["codebase", h].concat(f).concat(o), a, e);
                        l[0] = [0, 0];
                        d.query(0)
                    }
                    if (d.should_Insert_Query(1)) {
                        if (e.OTF < 2) {
                            e.OTF = 2
                        };
                        p[1] = i.isIE ? i.insertHTML("applet", ["alt", a].concat(c).concat(f), ["codebase", h].concat(c), a, e) : i.insertHTML("applet", ["codebase", h, "alt", a].concat(c).concat(f), [].concat(c), a, e);
                        l[1] = [0, 0];
                        d.query(1)
                    }
                    if (d.should_Insert_Query(2)) {
                        if (e.OTF < 2) {
                            e.OTF = 2
                        };
                        p[2] = i.isIE ? i.insertHTML("object", ["classid", e.classID], ["codebase", h].concat(f).concat(o), a, e) : i.insertHTML();
                        l[2] = [0, 0];
                        d.query(2)
                    }
                    if (!d.DummyObjTagHTML && !e.isDisabled.ObjectTag()) {
                        d.DummyObjTagHTML = i.insertHTML("object", [], [], a)
                    }
                    if (!d.DummySpanTagHTML) {
                        d.DummySpanTagHTML = i.insertHTML("", [], [], a)
                    };
                    return d.getResult()
                }
            },
            zz: 0
        },
        zz: 0
    }
};
PluginDetect.initScript();