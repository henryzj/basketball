// 统一封装微信 JsApi
var wxJsApiManager = function () {

    function initConfig(wxSignPackage) {
        wx.config({
            debug: false,
            appId: wxSignPackage.appId,
            timestamp: wxSignPackage.timestamp,
            nonceStr: wxSignPackage.nonceStr,
            signature: wxSignPackage.signature,
            jsApiList: [
                'checkJsApi',
                'chooseWXPay',
                'onMenuShareTimeline',
                'onMenuShareAppMessage',
                'onMenuShareQQ',
                'onMenuShareWeibo',
                'hideMenuItems',
                'showMenuItems',
                'hideAllNonBaseMenuItem',
                'showAllNonBaseMenuItem',
                'getNetworkType',
                'openLocation',
                'getLocation',
                'hideOptionMenu',
                'showOptionMenu',
                'closeWindow',
                'scanQRCode',
                'chooseImage',
                'previewImage',
                'uploadImage',
                'downloadImage'
            ]
        });
        wx.error(function (res) {
            alert(JSON.stringify(res));
        });
    }

    function toFriend(option) {
        wx.onMenuShareAppMessage({
            title: option.title,
            link: option.link,
            imgUrl: option.logo,
            desc: option.desc,
            success: function () {
                option.success();
            },
            cancel: function () {
                option.cancel();
            }
        });
    }

    function toTimeline(option) {
        wx.onMenuShareTimeline({
            title: option.title,
            link: option.link,
            imgUrl: option.logo,
            success: function () {
                option.success();
            },
            cancel: function () {
                option.cancel();
            }
        });
    }

    function toFriendOnly(option) {
        wx.ready(function () {
            wx.showMenuItems({
                menuList: ["menuItem:share:appMessage", "menuItem:share:timeline", "menuItem:share:qq", "menuItem:share:weiboApp"]
            });
            wx.hideMenuItems({
                menuList: ["menuItem:share:timeline", "menuItem:share:qq", "menuItem:share:weiboApp"]
            });
            toFriend(option);
        });
    }

    function toFriendAndTimelineOnly(option) {
        wx.ready(function () {
            wx.showMenuItems({
                menuList: ["menuItem:share:appMessage", "menuItem:share:timeline", "menuItem:share:qq", "menuItem:share:weiboApp"]
            });
            wx.hideMenuItems({
                menuList: ["menuItem:share:qq", "menuItem:share:weiboApp"]
            });
            toFriend(option);
            toTimeline(option);
        });
    }

    function forbidShare() {
        wx.ready(function () {
            wx.hideMenuItems({
                menuList: ["menuItem:share:appMessage", "menuItem:share:timeline", "menuItem:share:qq", "menuItem:share:weiboApp"]
            });
        });
    }

    return {
        initConfig: initConfig,
        toFriendOnly: toFriendOnly,
        toFriendAndTimelineOnly: toFriendAndTimelineOnly,
        forbidShare: forbidShare,
    }

}();