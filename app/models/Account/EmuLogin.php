<?php

/**
 * 模拟登陆前台（免用户名、免密码）
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_EmuLogin extends Core_Model_Abstract
{
    const SECRET_KEY = 'ANCKasdfLD^(@@LsdfX77XOIUAQ';

    /**
     * 验证加密串
     *
     * @param int $uid
     * @param int $loginType
     * @param string $sign
     * @return bool
     */
    public static function verifySign($uid, $loginType, $sign)
    {
        $hash = md5($uid . '|' . $loginType . '|' . self::SECRET_KEY);

        return strtoupper($hash) == strtoupper($sign) ? true : false;
    }

    /**
     * 设置 Cookie
     *
     * @param int $uid
     * @param string $ticket
     * @return bool
     */
    public static function setCookie($uid, $ticket)
    {
        $__vuser = Model_Account_Base::getUidHash($uid) . '-' . $ticket;

        F('Cookie')->set(Model_Account_Base::CLIENT_TICKET_NAME, $__vuser);

        return $__vuser;
    }

    /**
     * 执行模拟登陆
     *
     * @param int $uid
     * @param int $loginType 1:无痕登录 2:真实登录-会更新玩家最后登录时间
     * @param string $sign
     * @return string $__vuser
     */
    public static function loginWrap($uid, $loginType, $sign)
    {
        if ($uid < 1 || ! Dao('Ucenter_AccountIndex')->get($uid)) {
            throws403('Invalid EmuLogin Uid');
        }

        // 验证签名
        if (! self::verifySign($uid, $loginType, $sign)) {
            throws403('Invalid EmuLogin Sign');
        }

        $ticket = Dao('Ucenter_AccountTicket')->getField($uid, 'ticket');

        // 非正常用户
        if (! $ticket) {
            // 有些用户从未登录过，所以从未有登录令牌（例如批量导入的用户）
            // 则我们这里帮他刷一下令牌，同时设置 Cookie
            $__vuser = Model_Account_Base::refreshTicket($uid, true);
        }
        // 正常用户
        else {
            // 设置 Cookie
            $__vuser = self::setCookie($uid, $ticket);
        }

        // 无痕登录标记
        if ($loginType == 1) {
            F('Cookie')->set('isQaLogin', true);
        }
        else {
            F('Cookie')->set('isQaLogin', null);
        }

        return $__vuser;
    }
}