<?php

class Model_Weixin_TplMsg_Biz
{
    // 给所有GM发通知
    public static function notifyGms($title, $remark, $url = '/')
    {
        if (! $gmList = Model_User::getGmList()) {
            return null;
        }

        foreach ($gmList as $gmIndex) {
            Model_Weixin_TplMsg_Base::push([
                'recv_uid' => $gmIndex['uid'],
                'title'    => $title,
                'tpl_id'   => 'COMMON_SOMETHING_TODO',
                'url'      => sUrl($url),
                'keywords' => [
                    '系统消息',
                    '--',
                    $GLOBALS['_DATE'],
                ],
                'remark'   => $remark,
            ]);
        }
    }

    // 钱包余额变更通知
    public static function walletMoneyChanged($uid, $amount, $curMoney)
    {
        return Model_Weixin_TplMsg_Base::push([
            'recv_uid' => $uid,
            'title'    => '尊敬的用户，您的账户余额发生了变动',
            'tpl_id'   => 'WALLET_BALANCE_CHANGED',
            'url'      => sUrl('/wallet'),
            'keywords' => [
                ($amount > 0 ? '增加' : '减少') . '￥' . (abs($amount) / 100),
                $GLOBALS['_DATE'],
                '￥' . ($curMoney / 100),
            ],
            'remark'   => '点击查看您的账户明细，如有疑问请询问客服。',
        ]);
    }

    // 积分余额变更通知
    public static function creditChanged($uid, $amount, $reason, $curCredits)
    {
        return Model_Weixin_TplMsg_Base::push([
            'recv_uid' => $uid,
            'title'    => '你的积分变更如下',
            'tpl_id'   => 'CREDIT_BALANCE_CHANGED',
            'url'      => sUrl('/wallet'),
            'keywords' => [
                '变动原因',
                $reason,
                ($amount > 0 ? '增加' : '减少'),
                abs($amount),
                $curCredits,
            ],
            'remark'   => '可以在「我的钱包」中查看积分收支明细',
        ], 'credit');
    }
}