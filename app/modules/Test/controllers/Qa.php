<?php

/**
 * QA专用模拟登陆任何用户
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Qa extends Core_Controller_Web
{
    public function indexAction()
    {
        // 按 uid
        if ($uid = $this->getInt('uid')) {
            $uid = $uid;
        }
        // 按微信unionid
        elseif ($unoinid = $this->getx('unionid')) {
            $uid = Dao('Ucenter_AccountThirdPfBind')->getField(['Weixin', $unoinid], 'uid');
        }
        // 按微信openid
        elseif ($openId = $this->getx('openid')) {
            $uid = Model_Account_Third::getUidByWxOpenId($openId);
        }
        // 按手机号
        elseif ($mobile = $this->getx('mobile')) {
            $uid = Dao('Ucenter_AccountIndex')->getUidByMobile($mobile);
        }
        // 按ticket
        elseif ($ticket = $this->getx('ticket')) {
            $uid = Dao('Ucenter_AccountTicket')->getUidByTicket($ticket);
        }

        $secretKey = Model_Account_EmuLogin::SECRET_KEY;
        $this->redirect('/auth/emulogin/?uid=' . $uid . '&login_type=2&sign=' . md5($uid . '|2|' . $secretKey) . '&output=' . $this->getBool('output'));
    }
}