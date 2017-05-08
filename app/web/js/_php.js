function msleep(ms)
{
    var endTime = new Date().getTime() + ms;
    while (new Date().getTime() < endTime);
}

function getcookie(name)
{
    var cookie_start = document.cookie.indexOf(name);
    var cookie_end = document.cookie.indexOf(";", cookie_start);
    return cookie_start == -1 ? '': unescape(document.cookie.substring(cookie_start + name.length + 1, (cookie_end > cookie_start ? cookie_end: document.cookie.length)));
}

function setcookie(cookieName, cookieValue, seconds, path, domain, secure)
{
    if (seconds) {
        var expires = new Date();
        expires.setTime(expires.getTime() + seconds * 1000);
    }

    document.cookie = escape(cookieName) + '=' + escape(cookieValue) + (seconds ? '; expires=' + expires.toGMTString() : '') + (path ? '; path=' + path: '/') + (domain ? '; domain=' + domain: '') + (secure ? '; secure': '');
}

function str_replace(search, replace, subject, count)
{
    var i = 0,
        j = 0,
        temp = '',
        repl = '',
        sl = 0,
        fl = 0,
        f = [].concat(search),
        r = [].concat(replace),
        s = subject,
        ra = Object.prototype.toString.call(r) === '[object Array]',
        sa = Object.prototype.toString.call(s) === '[object Array]';
    s = [].concat(s);
    if (count) {
        this.window[count] = 0;
    }

    for (i = 0, sl = s.length; i < sl; i++) {
        if (s[i] === '') {
            continue;
        }
        for (j = 0, fl = f.length; j < fl; j++) {
            temp = s[i] + '';
            repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
            s[i] = (temp).split(f[j]).join(repl);
            if (count && s[i] !== temp) {
                this.window[count] += (temp.length - s[i].length) / f[j].length;
            }
        }
    }
    return sa ? s : s[0];
}

function str_repeat(input, multiplier)
{
    var y = '';
    if (multiplier) {
        while (true) {
            if (multiplier & 1) {
                y += input;
            }
            multiplier >>= 1;
            if (multiplier) {
                input += input;
            } else {
                break;
            }
        }
    }
    return y;
}

function rawurlencode(str)
{
    str = (str + '').toString();
    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28'). replace(/\)/g, '%29').replace(/\*/g, '%2A');
}

function rawurldecode(str)
{
    return decodeURIComponent((str + '').replace(/%(?![\da-f]{2})/gi, function () {
        return '%25';
    }));
}

// Êý×ÖÇ§·Ö·û
function renderMoney(v) {
    if (isNaN(v)) {
        return v;
    }
    v = (Math.round((v - 0) * 100)) / 100;
    v = (v == Math.floor(v)) ? v + ".00" : ((v * 10 == Math.floor(v * 10)) ? v + "0" : v);
    v = String(v);
    var ps = v.split('.');
    var whole = ps[0];
    var sub = ps[1] ? '.' + ps[1] : '.00';
    var r = /(\d+)(\d{3})/;
    while (r.test(whole)) {
        whole = whole.replace(r, '$1' + ',' + '$2');
    }
    v = whole + sub;
    return v;
}