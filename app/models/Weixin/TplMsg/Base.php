<?php

/**
 * 微信模板消息-应用类
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_TplMsg_Base extends Core_Model_Abstract
{
    // 将一个任务塞进队列
    public static function push(array $params, $tplStyle = 'default')
    {
        // 根据UID查询我在公众号下的openid
        if (! isset($params['recv_openid']) && isset($params['recv_uid'])) {
            $params['recv_openid'] = Model_Account_Third::getWxOpenId($params['recv_uid']);
        }

        if (! $params['recv_openid']) {
            throws('模板消息收件人不能为空');
        }

        if (! $params['tpl_id'] || ! $params['url'] || ! $params['title']) {
            throws('必填项不能为空');
        }

        $msgData = json_encode(Model_Weixin_TplMsg_Api::buildMsgData($params, $tplStyle), JSON_UNESCAPED_UNICODE);

        return S('Model_Queue_TplMsg')->push($msgData);
    }

    // 不经过队列直接调用微信API发消息
    public static function sendDirect(array $params)
    {
        return Model_Weixin_TplMsg_Api::sendByParams($params);
    }
}