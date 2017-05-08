// 优先处理一遍AJAX的响应结果
// 提前拦截并处理异常：例如金块不足、行动力不足等引导弹窗
SILVER.handlePriorAjaxResponse = function (resp, options)
{
    options = options || {};

    if (resp.status == '403') {

        // 调试模式下会输出具体信息
        if (resp.extra && resp.extra.isDebug) {
            $.mbox.alert(resp.msg, {
                "icon": 'sorry',
                "onok": function () {
                    SILVER.redirect('/');
                }
            }, options.errCloseAll);
        }
        // TODO 是否直接隐藏信息
        // 非调试模式直接跳首页
        else {
            SILVER.redirect('/?403=' + rawurlencode(resp.msg));
        }

        return false;
    }

    // 异常、错误提示
    else if (resp.status == 'error') {

        switch (resp.msg) {

            // 账户余额不足
            case 'NoEnoughMoney':

                $.mbox.alert('对不起，你的账户余额不足，请前往充值', {
                    "icon": 'sorry',
                    "onok": function () {
                        SILVER.redirect('/wallet/deposit');
                    }
                }, options.errCloseAll);

                break;

            // 积分余额不足
            case 'NoEnoughCredit':

                var message = '对不起，你的积分余额不足，无法购买<br /><br /><a href="/wallet/creditRules">如何获得积分？</a>';

                $.mbox.alert(message, {"icon": 'sorry'}, options.errCloseAll);

                break;

            // 请先关注公众号
            case 'NeedSubscribeMP':

                var message = '对不起，请先关注「69旅行」公众号。<br />如果已关注，请取关后重新关注后再试。<br /><br />' +
                              '<img style="width: 70%" src="' + IMG_DIR + '/qrcode.jpg" />';

                $.mbox.alert(message, {"icon": 'sorry'}, options.errCloseAll);

                break;

            case 'SystemBusy':

                $.mbox.alert('对不起，网络繁忙，请刷新页面后重试', {
                    "icon": 'sorry',
                    "onok": function () {
                        SILVER.reload();
                    }
                }, options.errCloseAll);

                break;

            default:

                // 跳转URL
                if (resp.msg.toString().indexOf('AjaxJump::') != -1) {
                    SILVER.redirect(resp.msg.replace('AjaxJump::', ''));
                }

                // 点“确定”后刷新本页
                else if (options.errReload) {
                    $.mbox.alert(resp.msg, {
                        "onok" : function () {
                            SILVER.reload();
                        }
                    }, options.errCloseAll);
                }

                // 指定的错误回调处理
                else if (options.errCallback) {
                    options.errCallback(resp, options);
                }

                // 其他错误信息
                else {
                    $.mbox.alert(resp.msg, null, options.errCloseAll);
                }
        }

        return false;
    }

    return true;
}

// 处理AJAX响应中的iTips
SILVER.iTipsInAjaxResponse = function (resp, autoPopUp)
{
    if (autoPopUp == undefined) {
        autoPopUp = true;
    }

    // 处理 iTips 实时提示框
    // 例如经验值、等级提升等提示
    if (resp.extra && resp.extra.iTips) {
        var iTipsInstant = iTips.filter(resp.extra.iTips, 'autoPopUp', autoPopUp);
        if (iTipsInstant.length > 0) {
            // 立即弹出实时提示框
            iTips.popUp(iTipsInstant);
        }
    }
}

// 返回上页
SILVER.goback = function ()
{
    if (document.referrer) {
        top.location.href = document.referrer;
    }
    else if (typeof wx != 'undefined') {
        wx.closeWindow();
    }
}

// AJAX 弹出一整个新页面
function popUpAjaxNewPage(xhref, dontChangeUrl)
{
    // 显示Loading条
    SILVER.loading();

    var callbacks = [

        function (resp) {
            // 移除Loading条
            SILVER.unloading(true);
        },

        function (resp) {

            // 创建新窗口DOM
            var $dom = $('<div class="popWindow"></div>').appendTo(bodyContext);

            // 点击“关闭”按钮事件
            $dom.on(__clickEvent, '.close, .close-win', function () {
                closeAjaxNewPage($dom);
            });

            $dom.css({
                "overflow" : 'auto',
                "width" : '100%',
                "height" : '100%',
                "pointer-events" : "auto",
                "background-color" : '#EEEEEE',
                "z-index"  : SILVER.popWindowZindex++,
                "position" : 'fixed',
                "top" : 0,
                "left" : 0,
            });
            $dom.html(resp);

            // 自动滚动到顶部
            $dom.scrollIntoView(0);

            // 修改浏览历史
            document.title = $(resp).filter("title").text();
            dontChangeUrl || window.history.pushState(null, null, xhref);
        }
    ];

    $._getx(xhref, null, callbacks);
}

// 关闭一个AJAX页面
function closeAjaxNewPage(popWin)
{
    if (typeof popWin == 'object') {
        popWin.remove();
    } else if (typeof popWin == 'string') {
        $('#' + popWin).remove();
    } else {
        $('.popWindow').remove();
    }

    // 延迟若干毫秒将body事件恢复正常（防止点击穿透跨页面传播）
    SILVER.delayRestoreBodyEvent();
}

// 红色聚焦闪烁
$.fn.blingbling = function (text)
{
    $(this).val('').attr('placeholder', text).addClass('animation').addClass('animation::-webkit-input-placeholder').focus();

    return this;
};

function showLoadingBar()
{
    hideLoadingBar();
    $('<div id="loading_bar"><div class="loading"><div class="loading_img"></div></div><div class="mask"></div></div>').prependTo('body');
}

function hideLoadingBar()
{
    $('#loading_bar').remove();
}

function gotoPayment(productType, productInfo, totalFee, returnUrl)
{
    SILVER.redirect('/wallet/payment/?uid=' + __USER.uid + '&product_type=' + productType + '&product_info=' + productInfo + '&total_fee=' + totalFee + '&return_url=' + rawurlencode(returnUrl));
}

function startWeixinPay(uid, productType, productInfo, totalFee, onSuccess, onCancel)
{
    var params = {
        "uid"          : uid,
        "product_type" : productType,
        "product_info" : productInfo,
        "total_fee"    : totalFee
    };

    showLoadingBar();

    // 微信JS支付（微信浏览器内）
    if (isWeixin) {

        $.getJSON('/payment/create/channel_id/99/', params, function (wxParams) {

            hideLoadingBar();

            if (! wxParams.package) {
                if (wxParams.msg) {
                    $.mbox.alert(wxParams.msg, {
                        "onok" : function () {
                            if ($.isFunction(onCancel)) {
                                onCancel();
                            }
                        }
                    });
                }
                else {
                    alert(JSON.stringify(wxParams));
                    if ($.isFunction(onCancel)) {
                        onCancel();
                    }
                }
                return false;
            }

            wx.chooseWXPay({
                "timestamp": wxParams.timeStamp,
                "nonceStr" : wxParams.nonceStr,
                "package"  : wxParams.package,
                "signType" : wxParams.signType,
                "paySign"  : wxParams.paySign,
                success: function (res) {
                    if ($.isFunction(onSuccess)) {
                        onSuccess();
                    }
                    else {
                        $.mbox.alert('微信支付成功');
                    }
                },
                fail: function (res) {
                    alert(JSON.stringify(res));
                },
                cancel: function (res) {
                    if ($.isFunction(onCancel)) {
                        onCancel();
                    }
                }
            });
        });
    }

    // 微信WAP支付（微信浏览器外）
    else {

        $.getJSON('/payment/create/channel_id/96/', params, function (wxParams) {

            hideLoadingBar();

            if (! wxParams.deepLink) {
                if (wxParams.msg) {
                    $.mbox.alert(wxParams.msg, {
                        "onok" : function () {
                            if ($.isFunction(onCancel)) {
                                onCancel();
                            }
                        }
                    });
                }
                else {
                    alert(JSON.stringify(wxParams));
                    if ($.isFunction(onCancel)) {
                        onCancel();
                    }
                }
                return false;
            }

            $.mbox.open('请在微信内完成支付。', {
                "cancelBtnTxt" : '遇到问题',
                "oncancel" : function () {
                    if ($.isFunction(onCancel)) {
                        onCancel();
                    }
                },
                "onok" : function () {
                    if ($.isFunction(onSuccess)) {
                        onSuccess();
                    }
                    else {
                        $.mbox.alert('微信支付成功');
                    }
                }
            });

            window.location.href = wxParams.deepLink;

        });
    }
}

// 初次使用引导提示
function showGuidingLayer(actionType)
{
    var mask = SILVER.showMask(0.8);

    switch (actionType) {
        case 'guide_entry_group_tips':
            var html = '';
            break;
    }

    var guideLayer = $('<div class="guide_layer"></div>');

    guideLayer.html(html).appendTo(bodyContext).css({
        "z-index"  : SILVER.popWindowZindex++,
        "pointer-events" : 'auto'
    });

    // 关闭引导层
    var hideGuideLayer = function () {
        guideLayer.remove();
        SILVER.hideMask(mask);
    }

    // 点击“不再提醒”按钮
    guideLayer.on(__clickEvent, function () {
        // 关闭引导层
        hideGuideLayer();
        // 通知服务器执行忽略
        $._getJSONX('/user/ignoreGuide', {'action_type' : actionType}, function () {});
    });
}

// JS 触底分页条
function initScrollPagination(options)
{
    options.loadMoreText = options.loadMoreText || '正在加载更多 ...';
    options.noMoreText   = options.noMoreText   || '没有更多的数据了~';

    var html = '<div id="loadmore_bar" style="display:none;">' +
               '    <div class="loading_more">' + options.loadMoreText + '</div><div class="h30"></div>' +
               '</div>' +
               '<div id="nomore_bar" style="display:none;">' +
               '    <div class="nomore">' + options.noMoreText + '</div><div class="h30"></div>' +
               '</div>';

    // LoadingBar显示区域
    $('#loadingBarCont').html(html);

    // 当前页码
    var curPage = 1;

    $(options.listCont).scrollPagination({
        'contentPage': options.loadUrl,
        'contentData': {
            "page" : function () {
                return ++curPage;
            }
        },
        'scrollTarget': $(window),
        'heightOffset': 10,
        'beforeLoad': function () {
            $('#loadmore_bar').fadeIn();
        },
        'afterLoad': function (elementsLoaded) {
            $('#loadmore_bar').fadeOut();
            $(elementsLoaded).fadeInWithDelay();
        },
        "whenEof": function (elementsLoaded) {
            $(options.listCont).stopScrollPagination();
            $('#nomore_bar').show();
        }
    });
}

// 加载评论区
function loadComment(commentTargetType, commentTargetId, cmtContainer)
{
    cmtContainer = cmtContainer || $('#comment_content');
    if (cmtContainer.length > 0) {
        cmtContainer.load('/comment/index/?target_type=' + commentTargetType + '&target_id=' + commentTargetId);
    }
}

// 发送手机验证码
function sendMobileVcode(btnObj, mobile, scene, options)
{
    $._getJSONX('/auth/sendVcode/', {"mobile" : mobile, "scene" : scene}, function (resp) {

        var cdSecs = resp.msg;

        $(btnObj).html('<span id="cd_secs">' + cdSecs + '</span>秒后重发').addClass('btn_disabled').prop('disabled', true);

        var __st = setInterval(function () {
            if (cdSecs > 1) {
                cdSecs--;
                $("#cd_secs").text(cdSecs);
            } else {
                $(btnObj).html('获取验证码').removeClass('btn_disabled').prop('disabled', false);
                clearInterval(__st);
            }
        }, 1000);

    }, btnObj, options);
}

// 验证身份证号并获取出生日期
function getBirthdatByIdNo(iIdNo)
{
    var tmpStr = "";
    var strReturn = "";

    if ((iIdNo.length != 15) && (iIdNo.length != 18)) {
        strReturn = "";
        return strReturn;
    }

    if (iIdNo.length == 15) {
        tmpStr = iIdNo.substring(6, 12);
        tmpStr = "19" + tmpStr;
        tmpStr = tmpStr.substring(0, 4) + "-" + tmpStr.substring(4, 6) + "-" + tmpStr.substring(6);

        return tmpStr;
    } else {
        tmpStr = iIdNo.substring(6, 14);
        tmpStr = tmpStr.substring(0, 4) + "-" + tmpStr.substring(4, 6) + "-" + tmpStr.substring(6);

        return tmpStr;
    }
}

// 校验生日与性别与身份证相符
function checkBirSexByIdNo(iIdNo, bir, sex)
{
    var strReturn = false;
    var birStr = "";
    var sexStr = "";

    if (iIdNo.length == 15) {
        birStr = iIdNo.substring(6, 12);
        birStr = "19" + birStr;
        birStr = birStr.substring(0, 4) + "-" + birStr.substring(4, 6) + "-" + birStr.substring(6);
        sexStr = iIdNo.substring(13, 14);
    }
    else if (iIdNo.length == 18) {
        birStr = iIdNo.substring(6, 14);
        birStr = birStr.substring(0, 4) + "-" + birStr.substring(4, 6) + "-" + birStr.substring(6);
        sexStr = iIdNo.substring(16, 17);
    }

    strReturn = bir == birStr;

    if (strReturn && sex) {
        if (sexStr % 2 == 0) {
            // 女
            strReturn = sex == "f";
        } else {
            // 男
            strReturn = sex == "m";
        }
    }

    return strReturn;
}