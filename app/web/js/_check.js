// 是否为空
String.prototype.testNull = function () {
    if (this.replace(/(^\s*)|(\s*$)/g, '').length <= 0) {
        return true;
    } else { //不为空
        return false;
    }
}

// 邮箱验证
String.prototype.isEmail = function () {
    var reg = /^\w+([-.]?\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
    return reg.test(this);
}

// 手机
String.prototype.isMobile = function() {
    var reg = /^(((1[3|4|5|7|8|9]{1}[0-9]{1}))[0-9]{8})$/;
    return reg.test(this);
}

// 固定电话
String.prototype.isPhone = function () {
    var reg = /^((0\d{2,3})-)?(\d{7,8})(-(\d{3,}))?$/;
    return reg.test(this);
}

// 邮政编码
String.prototype.isZip = function () {
    var reg = /^\d{6}$/;
    return reg.test(this);
}

// 密码验证
String.prototype.isPassword = function () {
    return (this.length > 16 || this.length < 6) ? false : true;
}

String.prototype.isMoney = function () {
    var reg = new RegExp(/^\d+(\.\d+)?$/);
    return reg.test(this);
}

String.prototype.isQQ = function () {
    var reg = /^\d{4,}$/;
    return reg.test(this);
}

// 验证身份证
String.prototype.isIdentityCard = function ()
{
    var formatMsg = "";

    if (this == "") {
        formatMsg = "输入的身份证不能为空";
        return formatMsg;
    }

    var num = this.toUpperCase();

    // 身份证号码为15位或者18位，15位时全为数字，18位前17位为数字，最后一位是校验位，可能为数字或字符X。
    if (! (/(^\d{15}$)|(^\d{17}([0-9]|X)$)/.test(num))) {
        formatMsg = "输入的身份证格式不正确";
        return formatMsg;
    }

    // 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
    // 下面分别分析出生日期和校验位
    var len, re;
    len = num.length;

    if (len == 15) {
        re = new RegExp(/^(\d{6})(\d{2})(\d{2})(\d{2})(\d{3})$/);
        var arrSplit = num.match(re);

        // 检查生日日期是否正确
        var dtmBirth = new Date('19' + arrSplit[2] + '/' + arrSplit[3] + '/' + arrSplit[4]);
        var bGoodDay;
        bGoodDay = (dtmBirth.getYear() == Number(arrSplit[2])) && ((dtmBirth.getMonth() + 1) == Number(arrSplit[3])) && (dtmBirth.getDate() == Number(arrSplit[4]));
        if (! bGoodDay) {
            formatMsg = "输入的身份证号里出生日期不对";
            return formatMsg;
        } else {
            // 将15位身份证转成18位
            // 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            var arrInt = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            var arrCh = new Array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            var nTemp = 0,
            i;
            num = num.substr(0, 6) + '19' + num.substr(6, num.length - 6);
            for (i = 0; i < 17; i++) {
                nTemp += num.substr(i, 1) * arrInt[i];
            }
            num += arrCh[nTemp % 11];
        }
    }
    else {
        re = new RegExp(/^(\d{6})(\d{4})(\d{2})(\d{2})(\d{3})([0-9]|X)$/);
        var arrSplit = num.match(re);

        // 检查生日日期是否正确
        var dtmBirth = new Date(arrSplit[2] + "/" + arrSplit[3] + "/" + arrSplit[4]);
        var bGoodDay;
        bGoodDay = (dtmBirth.getFullYear() == Number(arrSplit[2])) && ((dtmBirth.getMonth() + 1) == Number(arrSplit[3])) && (dtmBirth.getDate() == Number(arrSplit[4]));
        if (! bGoodDay) {
            formatMsg = "输入的身份证号里出生日期不对";
            return formatMsg;
        } else {
            // 检验18位身份证的校验码是否正确。
            // 校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            var valnum;
            var arrInt = new Array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            var arrCh = new Array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            var nTemp = 0,
            i;
            for (i = 0; i < 17; i++) {
                nTemp += num.substr(i, 1) * arrInt[i];
            }
            valnum = arrCh[nTemp % 11];
            if (valnum != num.substr(17, 1)) {
                formatMsg = "18位身份证的校验码不正确";
                return formatMsg;
            }
        }
    }
}