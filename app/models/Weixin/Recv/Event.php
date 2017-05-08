<?php

/**
 * 微信公众号接收消息
 * 接收事件推送
 *
 * @link http://mp.weixin.qq.com/wiki/2/5baf56ce4947d35003b86a9805634b1e.html
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Weixin_Recv_Event extends Model_Weixin_Recv_Abstract
{
    // 分发调度
    public function process()
    {
        $methodName = '_on' . ucfirst($this->_data['Event']);

        if (! method_exists($this, $methodName)) {
            throws403('Invalid Weixin Event');
        }

        return $this->$methodName();
    }

    // 关注公众号
    protected function _onSubscribe()
    {
        // 解析场景ID
        $sceneId = $this->__getSceneId();

        // 记录关注状态
        Model_Account_Third::setWxMpSubscribed($this->_data['FromUserName'], 1, $sceneId);

        // 如果用户还未关注公众号，则用户可以关注公众号
        // 关注后微信会将带场景值关注事件推送给开发者
        $result = $this->__handleScanQrcode($sceneId);

        // 回复默认欢迎语 （本段必须放在最后，因为会exit输出一些内容）
        if (false === $result) {
            $this->__defaultWelcome();
        }
    }

    // 取消关注公众号
    protected function _onUnsubscribe()
    {
        // 记录取关状态
        Model_Account_Third::setWxMpSubscribed($this->_data['FromUserName'], 0);

        return true;
    }

    // 扫码（带参数的公众号二维码）
    protected function _onScan()
    {
        // 解析场景ID
        $sceneId = $this->__getSceneId();

        // 处理扫码逻辑
        $this->__handleScanQrcode($sceneId);

        return true;
    }

    // 轮询上报地理位置（每5秒上报一次）
    protected function _onLocation()
    {
        return true;
    }

    // 点击自定义菜单
    protected function _onClick()
    {
        switch ($this->_data['EventKey']) {

            // 我要留言
            case 'leave_msg' :

                $this->replyText('您可回复本消息留下您的问题或建议');
                break;
        }
    }

    // 解析场景ID
    private function __getSceneId()
    {
        // 有 Ticket 参数表示二维码扫描
        if (! isset($this->_data['EventKey']) || ! isset($this->_data['Ticket'])) {
            return false;
        }

        $sceneId = str_replace('qrscene_', '', $this->_data['EventKey']);

        return $sceneId;
    }

    // 扫码的后续处理
    private function __handleScanQrcode($sceneId)
    {
        // 等于1表示是默认不带参数的官方公众号二维码
        // 大于1表示是推荐人UID
        // 这里要是必须是带参数的二维码
        if ($sceneId <= 1) {
            return false;
        }

        // 根据微信openid查出我方uid
        $fromUid = Model_Account_Third::getUidByWxOpenId($this->_data['FromUserName']);

        try {
            // 扫码登陆的临时二维码
            if ($sceneId >= 2000000000) {
                // 建立临时二维码场景ID和UID的绑定关系
                $this->__bindScanLoginUid($sceneId, $fromUid);
            }
            else {
                return false;
            }
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

    // 建立临时二维码场景ID和UID的绑定关系
    private function __bindScanLoginUid($sceneId, $uid)
    {
        if ($uid < 1) {
            return false;
        }

        $result = Model_Account_ScanLogin::bindSceneIdToUid($sceneId, $uid);

        $this->replyText('授权成功，请在网页版上继续操作');

        return true;
    }

    // 默认的欢迎语
    private function __defaultWelcome()
    {
        $articles = [
            [
                'Title'       => '欢迎关注Stay！',
                'Description' => "轻享五星之夜 - 您的专属出行顾问",
                'PicUrl'      => 'http://cdn.69night.cn/img/welcome_69.jpg',
                'Url'         => sUrl('/hotel/index'),
            ]
        ];

        $this->replyNews($articles);
    }
}