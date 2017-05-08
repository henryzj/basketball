<?php

/**
 * 阿里大鱼
 *
 * @link http://www.alidayu.com
 *
 * @author JiangJian <silverd@sohu.com>
 */

Yaf_Loader::import(SYS_PATH . 'Third/TaobaoSDK/TopSdk.php');

class Com_Sms_AliDaYu
{
    /**
     * 发送纯文本短信
     * 向指定手机号码发送模板短信，模板内可设置部分变量。
     * 使用前需要在阿里大鱼管理中心添加短信签名与短信模板。
     *
     * @link http://open.taobao.com/doc2/apiDetail?apiId=25450
     *
     * @param string $mobile   多个手机号用半角逗号分隔（最多200个）
     * @param string $signName 前缀签名（不含【】括号）
     * @param string $tplId    短信模版ID @link http://www.alidayu.com/admin/service/tpl
     * @param array  $tplVars  短信模版变量 ['code' => 验证码, 'product' => 应用名称]
     * @param string $extend   公共回传参数，在“消息返回”中会透传回该参数；
     *                         例：用户可以传入自己下级的会员ID，在消息返回时，该会员ID会包含在内
     *                         用户可以根据该会员ID识别是哪位会员使用了你的应用
     * @return array
     */
    public static function sendText(array $params)
    {
        $mobile   = $params['mobile'];
        $signName = $params['signName'];
        $tplId    = $params['tplId'];
        $tplVars  = $params['tplVars'];
        $extend   = isset($params['extend']) ? $params['extend'] : null;
        $c = new TopClient;
        $c->format = 'json';
        $c->appkey = ALIDAYU_APP_KEY;
        $c->secretKey = ALIDAYU_APP_SECRET;

        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend($extend);
        $req->setSmsType('normal');
        $req->setSmsFreeSignName($signName);
        $req->setSmsParam(json_encode(array_map('strval', $tplVars), JSON_UNESCAPED_UNICODE));
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode($tplId);

        $resp = $c->execute($req);

        // 如果返回了错误码
        if (isset($resp->code)) {
            return [
                'is_ok' => 0,
                'return_msg' => $resp,
            ];
        }

        return [
            'is_ok' => 1,
            'return_msg' => $resp,
        ];
    }

    /**
     * 发送语音验证码（文本转语音通知）
     * 向指定手机号码发起单向呼叫，将文本模板内容转化为语音播放给被叫方。
     * 使用前需要在阿里大鱼管理中心添加去电显示号码与文本转语音模板。
     *
     * @link http://open.taobao.com/doc2/apiDetail?apiId=25444
     *
     * @param string $mobile        支持国内手机号与固话号码,格式如下057188773344,13911112222,4001112222,95500
     * @param string $calledShowNum 被叫号显，传入的显示号码必须是阿里大鱼“管理中心-号码管理”中申请或购买的号码
     * @param string $tplId         短信模版ID @link http://www.alidayu.com/admin/service/tts
     * @param array  $tplVars       短信模版变量 ['code' => 验证码, 'product' => 应用名称]
     * @param string $extend        共回传参数，在“消息返回”中会透传回该参数；
     *                              例：用户可以传入自己下级的会员ID，在消息返回时，该会员ID会包含在内
     *                              用户可以根据该会员ID识别是哪位会员使用了你的应用
     * @return array
     */
    public static function sendVoice(array $params)
    {
        $mobile        = $params['mobile'];
        $calledShowNum = $params['calledShowNum'];
        $tplId         = $params['tplId'];
        $tplVars       = $params['tplVars'];
        $extend        = isset($params['extend']) ? $params['extend'] : null;
        $c = new TopClient;
        $c->format = 'json';
        $c->appkey = ALIDAYU_APP_KEY;
        $c->secretKey = ALIDAYU_APP_SECRET;

        $req = new AlibabaAliqinFcTtsNumSinglecallRequest;
        $req->setExtend($extend);
        $req->setTtsParam(json_encode(array_map('strval', $tplVars), JSON_UNESCAPED_UNICODE));
        $req->setCalledNum($mobile);
        $req->setCalledShowNum($calledShowNum);
        $req->setTtsCode($tplId);

        $resp = $c->execute($req);

        // 如果返回了错误码
        if (isset($resp->code)) {
            return [
                'is_ok' => 0,
                'return_msg' => $resp,
            ];
        }

        return [
            'is_ok' => 1,
            'return_msg' => $resp,
        ];
    }
}