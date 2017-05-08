var __UA__    = navigator.userAgent.toLowerCase();
var isWeixin  = __UA__.indexOf('micromessenger') != -1;
var isAndroid = __UA__.indexOf('android') != -1;
var isIOS     = __UA__.indexOf('iphone') != -1 || __UA__.indexOf('ipad') != -1;

var bodyContext = 'body';

if ('ontouchstart' in window) {
    // mobile version
    var __clickEvent = "touchend";
} else {
    // desktop version
    var __clickEvent = "click";
}

var SILVER = {

    // 全站弹层“层级”累加器
    popWindowZindex: 10000,

    // 最近一个遮罩
    lastLoading: null,

    // 遮罩移除定时器
    // 延迟移除，防止点击穿透
    remMaskSt: null,

    // 初始化
    handlePriorAjaxResponse: null,
    iTipsInAjaxResponse: null,

    // 显示Loading条
    loading: function ()
    {
        return SILVER.lastLoading = SILVER.showMask();
    },

    // 移除Loading条
    unloading: function (isRemMaskDirectly, maskObj)
    {
        if (maskObj == undefined) {
            maskObj = SILVER.lastLoading;
        }

        if (maskObj) {
            // 仅移除遮罩DOM
            if (isRemMaskDirectly) {
                SILVER.remMaskDom(maskObj);
            }
            // 完美移除遮罩（同时延迟更新body的pointer-events可点击状态）
            else {
                SILVER.hideMask(maskObj);
            }
        }
    },

    toastTips: null,

    toast: function (text, style, halted)
    {
        text = text || '数据加载中 ...';

        if (style == 'wx') {
            var html =  '<div id="loadingToast" class="weui_loading_toast">' +
                '   <div class="weui_mask_transparent"></div>' +
                '   <div class="weui_toast">' +
                '       <div class="weui_loading">' +
                '           <div class="weui_loading_leaf weui_loading_leaf_0"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_1"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_2"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_3"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_4"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_5"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_6"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_7"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_8"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_9"></div>' +
                '           <div class="weui_loading_leaf weui_loading_leaf_10"></div>' +
                 '          <div class="weui_loading_leaf weui_loading_leaf_11"></div>' +
                 '      </div>' +
                '       <p class="weui_toast_content">' + text +'</p>' +
                '   </div>' +
                '</div>';
        }
        else {
            var html = '<div id="toast_tips" style="position:fixed; bottom:20%; left:50%; transform:translate(-50%,0%); -ms-transform:translate(-50%,0%); -moz-transform:translate(-50%,0%); -webkit-transform:translate(-50%,0%); -o-transform:translate(-50%,0%); background-color: rgba(40, 40, 40, 0.75); padding:10px 20px; border-radius: 5px; color:#FFF; z-index:99999; overflow: initial; width: auto; height: auto;">' + text + '</div>';
        }

        SILVER.toastTips = $(html).prependTo('body');

        // 自动消失
        if (! halted) {
            setTimeout(function () {
                SILVER.untoast();
            }, 3000);
        }
    },

    untoast: function ()
    {
        SILVER.toastTips.remove();
    },

    // 显示遮罩
    showMask: function (opacity, zIndex)
    {
        if (opacity == undefined) {
            opacity = 0.6;
        }

        // 缺省层级
        if (zIndex == -1 || zIndex == undefined) {
            zIndex = SILVER.popWindowZindex++;
        }

        // 清除上一个“遮罩移除”定时器
        if (SILVER.remMaskSt) {
            clearTimeout(SILVER.remMaskSt);
        }

        // 立即禁用body所有事件
        $(bodyContext).css({
            "pointer-events": "none",
            "overflow" : "hidden"
        }).on("touchmove", function (e) {
            e.preventDefault();
        });

        // PC菜单栏保持可以点击
        if ($('#SILVER-menubar').length > 0) {
            $('#SILVER-menubar').css({"pointer-events": "auto"});
        }

        var maskHtml = '<div class="SILVER_mask" style="position:fixed; top:0; right:0; bottom:0; left:0; background:#000;"></div>';

        return $(maskHtml)
            .appendTo(bodyContext).css({
                "pointer-events" : "auto",
                "opacity"        : opacity,
                "z-index"        : zIndex
            });
    },

    // 仅移除遮罩DOM
    remMaskDom: function (maskObj)
    {
        // 移除指定
        if (typeof maskObj == 'object') {
            maskObj.remove();
        }
        // 移除所有
        else {
            $('.SILVER_mask').remove();
        }

        $(bodyContext).css({"overflow" : "auto"}).off("touchmove");
    },

    // 完美移除遮罩（同时延迟更新body的pointer-events可点击状态）
    hideMask: function (maskObj, timeOut)
    {
        // 移除遮罩DOM
        SILVER.remMaskDom(maskObj);

        // 延迟若干毫秒将body事件恢复正常（防止点击穿透跨页面传播）
        SILVER.delayRestoreBodyEvent(timeOut);
    },

    // 延迟若干毫秒将body事件恢复正常（防止点击穿透跨页面传播）
    delayRestoreBodyEvent: function (timeOut)
    {
        // 延迟移除
        if (timeOut == undefined) {
            timeOut = 600;
        }

        // 先立即禁用body所有事件
        $(bodyContext).css({"pointer-events": "none", "overflow" : "auto"}).off("touchmove");

        // 延迟恢复
        SILVER.remMaskSt = setTimeout(function () {
            $(bodyContext).css({"pointer-events": "auto"});
        }, timeOut);
    },

    // JS跳转
    redirect: function (url)
    {
        if (url == '#') {
            return false;
        }

        // 跳转前遮罩
        SILVER.loading();

        location.href = url;
        return false;
    },

    // 刷新本页
    reload: function ()
    {
        // 跳转前遮罩
        SILVER.loading();

        location.reload();
        return false;
    },

    // 统一的异常处理器
    ajaxError: function (XMLHttpRequest, textStatus)
    {
        // 如果超时则重新刷新页面，通知客户端超时
        if (textStatus == 'timeout') {
            SILVER.reload();
        } else {
            alert(XMLHttpRequest.responseText || '服务器异常，请稍候再试');
        }
    },

    // 页面是否已滚动到顶部
    isScrolledToTop: true
};

$(window).scroll(function () {
    SILVER.isScrolledToTop = false;
});

function getBodyScrollTop() {
    // 页面已滚动到顶部
    if (SILVER.isScrolledToTop) {
        return 0;
    }
    return $('body').scrollTop();
}

// 生成SELECT
function insertSelect(selectObj, data, initText)
{
    selectObj.empty();
    if (initText != undefined) {
        selectObj[0].options[0] = new Option(initText, '');
    }
    j = 1;
    for (i in data) {
        selectObj[0].options[j++] = new Option(data[i], i);
    }
}

function G(selectorId)
{
    if (selectorId.substr(0, 1) == '#') {
        selectorId = selectorId.substr(1);
    }

    return $(document.getElementById(selectorId));
}

// 让光标聚焦在文本框最后一位
$.fn.focusEnd = function ()
{
    var _val = $(this).val();
    $(this).focus();
    $(this).val(_val);
}

$.fn.appendToBody = function (zIndex)
{
    $(this).appendTo(bodyContext).css({
        "z-index"  : zIndex || SILVER.popWindowZindex++,
        "pointer-events" : 'auto'
    });

    return this;
}

$.fn.incr = function (step, max)
{
    step = step || 1;

    var value = Number($(this).html()) + Number(step);

    if (max != undefined) {
        value = Math.min(value, max);
    }

    $(this).html(value);

    return this;
};

$.fn.incrAttr = function (attrField, step, max)
{
    step = step || 1;

    var value = parseInt($(this).attr(attrField)) + parseInt(step);

    if (max != undefined) {
        value = Math.min(value, max);
    }

    $(this).attr(attrField, value);

    return this;
};

$.fn.incrVal = function (step, min)
{
    $(this).incrAttr('value', step, min);
};

$.fn.decr = function (step, min)
{
    step = step || 1;

    var value = Number($(this).html()) - Number(step);

    if (min != undefined) {
        value = Math.max(value, min);
    }

    $(this).html(value);

    return this;
};

$.fn.decrAttr = function (attrField, step, min)
{
    step = step || 1;

    var value = parseInt($(this).attr(attrField)) - parseInt(step);

    if (min != undefined) {
        value = Math.max(value, min);
    }

    $(this).attr(attrField, value);

    return this;
};

$.fn.decrVal = function (step, min)
{
    $(this).decrAttr('value', step, min);
};

$.fn.loading = function (loadingTxt)
{
    var loadingTxt = loadingTxt || '加载中';

    $(this).disabled();

    // 如果是按钮
    if ($(this).is('button') ||
       ($(this).is('input') && $(this).attr('type') == 'button') ||
       ($(this).is('a') && $(this).hasClass('button'))
    ) {
        $(this).each(function () {
            $(this).next('.puppet').val(loadingTxt).html(loadingTxt);
        });
    }

    return this;
};

$.fn.unloading = function ()
{
    $(this).enabled();

    return this;
};

$.fn.enabled = function ()
{
    $(this).each(function () {
        $(this).prop('disabled', false).show();
        $(this).next('.puppet').remove();
    });

    return this;
};

$.fn.disabled = function (btnClass)
{
    btnClass = btnClass || 'button';

    $(this).each(function () {

        if ($(this).prop('disabled')) {
            return false;
        }

        // 克隆一个傀儡元素
        var puppet = $(this).clone().removeAttr('onclick').removeAttr('touchend').off();
        puppet.removeAttr('id').removeAttr('href').removeAttr('xhref').removeClass('xhref');

        if ($(this).hasClass(btnClass)) {
            puppet.addClass(btnClass + '_no');
        }

        // 阻止冒泡委托
        puppet.addClass('puppet').on('click', function (event) {
            event.stopPropagation();
        }).on('touchend', function (event) {
            event.stopPropagation();
        });

        $(this).prop('disabled', true).hide().after(puppet);
    });

    return this;
};

$.fn.disabledForver = function (btnClass)
{
    btnClass = btnClass || 'button';

    $(this).each(function () {

        $(this).removeAttr('onclick').removeAttr('ontouchend').off();
        $(this).removeAttr('href').removeAttr('xhref').removeClass('xhref');
        $(this).prop('disabled', true);

        if ($(this).hasClass(btnClass)) {
            $(this).addClass(btnClass + '_no');
        }

        // 阻止冒泡委托
        $(this).on('click', function (event) {
            event.stopPropagation();
        }).on('touchend', function (event) {
            event.stopPropagation();
        });
    });

    return this;
};

$.fn.attrs = function (field)
{
    var result = [];

    $(this).each(function (i, element) {
        var data = $(element).attr(field);
        if (undefined != data) {
            result.push(data);
        }
    });

    return result;
};

$.fn.xhover = function (down, up) {
    $(this).each(function () {
        if ('ontouchstart' in window) {
            $(this).on('touchstart', down);
            $(this).on('touchend', up);
        }
        else {
            $(this).on('mousedown', down);
            $(this).on('mouseup', up);
        }
    });
};

// 将当前元素滚动到可视区域
$.fn.scrollIntoView = function (speed) {
    if (! speed) {
        $('body').scrollTop($(this).offset().top);
    }
    else {
        $('body').animate({"scrollTop": $(this).offset().top}, speed);
    }
};

$.fn.loadx = function (ajaxUrl, callback, options) {
    var that = this;
    $._getx(ajaxUrl, null, function (resp) {
        $(that).html(resp);
        callback && callback();
    }, options);
};

$.fn.loadPageContent = function (ajaxUrl, callback) {
    ajaxUrl += (ajaxUrl.indexOf("?") == -1 ? "?" : "&") + "__load_content=1";
    $(this).loadx(ajaxUrl, callback, {"loadingMask": true});
};

$.fn.shuffle = function() {
    var allElems = this.get(),
        getRandom = function(max) {
            return Math.floor(Math.random() * max);
        },
        shuffled = $.map(allElems, function(){
            var random = getRandom(allElems.length),
                randEl = $(allElems[random]).clone(true)[0];
            allElems.splice(random, 1);
            return randEl;
       });
    this.each(function(i){
        $(this).replaceWith($(shuffled[i]));
    });
    return $(shuffled);
};

// 扩展$静态方法
$.extend($, {

    // getJSON的封装
    _getJSONX: function (ajaxUrl, data, callback, btnObj, options)
    {
        $.__JSONX('GET', ajaxUrl, data, callback, btnObj, options);
    },

    _postJSONX: function (ajaxUrl, data, callback, btnObj, options)
    {
        $.__JSONX('POST', ajaxUrl, data, callback, btnObj, options);
    },

    __JSONX: function (method, ajaxUrl, data, callback, btnObj, options)
    {
        if ($.isFunction(data)) {
            options  = btnObj;
            btnObj   = callback;
            callback = data;
            data     = null;
        }

        data    = data || null;
        btnObj  = btnObj || null;
        options = options || {};

        options.loadingMask && SILVER.loading();
        btnObj && $(btnObj).loading(options.btnLoadingTxt);

        // 三个位置的回调函数
        var callbackBeforeError = null;
        var callbackAfterError  = null;
        var callbackAfterTips   = null;

        // 有多个callback
        if ($.isArray(callback)) {
            callbackBeforeError = callback[0];
            callbackAfterError  = callback[1] || null;
            callbackAfterTips   = callback[2] || null;
        }
        else {
            callbackBeforeError = null;
            callbackAfterError  = callback;
            callbackAfterTips   = null;
        }

        var __CALLBACK = function (resp) {

            btnObj && $(btnObj).unloading();
            options.loadingMask && SILVER.unloading();

            if ($.isFunction(callbackBeforeError)) {
                callbackBeforeError(resp);
            }

            // 优先处理一遍 _getJSON 的响应结果
            // 提前拦截例异常：例如金块不足、行动力不足等引导弹窗
            if ($.isFunction(SILVER.handlePriorAjaxResponse)) {
                if (! SILVER.handlePriorAjaxResponse(resp, options)) {
                    return false;
                }
            }

            // 执行回调
            if ($.isFunction(callbackAfterError)) {
                callbackAfterError(resp);
            }
            // 默认最后弹出“操作成功”提示框
            else {
                $.mbox.alert(resp.msg, {"icon" : "normal"});
            }

            // 处理AJAX响应中的iTips
            if ($.isFunction(SILVER.iTipsInAjaxResponse)) {
                SILVER.iTipsInAjaxResponse(resp);
            }

            // 执行回调
            if ($.isFunction(callbackAfterTips)) {
                callbackAfterTips(resp);
            }
        };

        if ('POST' == method) {
            $._postJSON(ajaxUrl, data, __CALLBACK);
        }
        else if ('GET' == method) {
            $._getJSON(ajaxUrl, data, __CALLBACK);
        }
    },

    _getJSON: function (ajaxUrl, data, callback)
    {
        if ($.isFunction(data)) {
            callback = data;
            data = null;
        }

        $.ajax({
            "url"      : ajaxUrl,
            "type"     : 'get',
            "data"     : data,
            "dataType" : 'json',
            "cache"    : false,
            "timeout"  : 10000,   // 超时
            "error"    : SILVER.ajaxError,
            "success"  : function (resp) {
                // 执行回调
                if ($.isFunction(callback)) {
                    callback(resp);
                }
            }
        });
    },

    _postJSON: function (ajaxUrl, data, callback)
    {
        if ($.isFunction(data)) {
            callback = data;
            data = null;
        }

        $.ajax({
            "url"      : ajaxUrl,
            "type"     : 'post',
            "data"     : data,
            "dataType" : 'json',
            "cache"    : false,
            "timeout"  : 10000,   // 超时
            "error"    : SILVER.ajaxError,
            "success"  : function (resp) {
                // 执行回调
                if ($.isFunction(callback)) {
                    callback(resp);
                }
            }
        });
    },

    _getx: function (ajaxUrl, data, callback, options)
    {
        $.__X('GET', ajaxUrl, data, callback, options);
    },

    _postx: function (ajaxUrl, data, callback, options)
    {
        $.__X('POST', ajaxUrl, data, callback, options);
    },

    __X: function (method, ajaxUrl, data, callback, options)
    {
        if ($.isFunction(data)) {
            options  = callback;
            callback = data;
            data     = null;
        }

        data    = data || null;
        options = options || {};

        options.loadingMask && SILVER.loading();

        // 两个位置的回调函数
        var callbackBeforeError = null;
        var callbackAfterError = null;

        // 有多个callback
        if ($.isArray(callback)) {
            callbackBeforeError = callback[0];
            callbackAfterError  = callback[1] || null;
        }
        else {
            callbackBeforeError = null;
            callbackAfterError  = callback;
        }

        var __CALLBACK = function (resp) {

            options.loadingMask && SILVER.unloading();

            if ($.isFunction(callbackBeforeError)) {
                callbackBeforeError(resp);
            }

            // 如果响应内容是JSON格式，说明在PHP抛了异常
            // 提前拦截例异常：例如金块不足、行动力不足等引导弹窗
            if (typeof resp == 'object' && resp.status != undefined) {
                // 优先处理一遍 _getJSON 的响应结果
                if ($.isFunction(SILVER.handlePriorAjaxResponse)) {
                    if (! SILVER.handlePriorAjaxResponse(resp, options)) {
                        return false;
                    }
                }
            }

            // 执行回调
            if ($.isFunction(callbackAfterError)) {
                callbackAfterError(resp);
            }
        };

        if ('POST' == method) {
            $._post(ajaxUrl, data, __CALLBACK);
        }
        else if ('GET' == method) {
            $._get(ajaxUrl, data, __CALLBACK);
        }
    },

    _get: function (ajaxUrl, data, callback)
    {
        if ($.isFunction(data)) {
            callback = data;
            data = null;
        }

        $.ajax({
            "url"      : ajaxUrl,
            "type"     : 'get',
            "data"     : data,
            "cache"    : false,
            "timeout"  : 10000,   // 超时
            "error"    : SILVER.ajaxError,
            "success"  : function (resp) {
                // 执行回调
                if ($.isFunction(callback)) {
                    callback(resp);
                }
            }
        });
    },

    _post: function (ajaxUrl, data, callback)
    {
        if ($.isFunction(data)) {
            callback = data;
            data = null;
        }

        $.ajax({
            "url"      : ajaxUrl,
            "type"     : 'post',
            "data"     : data,
            "cache"    : false,
            "timeout"  : 10000,   // 超时
            "error"    : SILVER.ajaxError,
            "success"  : function (resp) {
                // 执行回调
                if ($.isFunction(callback)) {
                    callback(resp);
                }
            }
        });
    }
});