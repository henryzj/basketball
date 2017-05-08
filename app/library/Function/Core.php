<?php

function addWeixinLog($data, $type = 0)
{
    return Dao('Massive_LogWeixin')->insert([
        'data'       => $data,
        'type'       => $type,
        'created_at' => $GLOBALS['_DATE'],
    ]);
}

function __getSalt($extraKey = null)
{
    return md5('BigYafSalt:' . $extraKey);
}

function __encrypt($content, $extraKey = null)
{
    return Helper_Cryption_Rijndael::encrypt($content, __getSalt($extraKey));
}

function __decrypt($content, $extraKey = null)
{
    return Helper_Cryption_Rijndael::decrypt($content, __getSalt($extraKey));
}

// 生成深链接
function deepLinks($type, $val = '')
{
    if ($type == 0) {
        return '';
    }
    elseif ($type == 1) {
        return 'stayapp://hotel_exp/' . __encrypt($val);
    }
    elseif ($type == 2) {
        return 'stayapp://article/' . __encrypt($val);
    }
    elseif ($type == 3) {
        return 'stayapp://user/' . __encrypt($val);
    }
    elseif ($type == 4) {
        return 'stayapp://hotel/' . __encrypt($val);
    }
    elseif ($type == 5) {
        return 'stayapp://membership/' . __encrypt($val);
    }
    elseif ($type == 6) {
        return 'stayapp://topic/' . __encrypt($val);
    }
    else {
        return $val;
    }
}