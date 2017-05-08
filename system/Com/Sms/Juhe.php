<?php

/**
 * 聚合数据-即时通讯
 *
 * @link https://www.juhe.cn/
 *
 * @author zhengjaing
 */

class Com_Sms_Juhe
{
    const TEXT_API_URL  = 'http://v.juhe.cn/sms/send';
    const VOICE_API_URL = 'http://op.juhe.cn/yuntongxun/voice';

    /**
     * 发送纯文本短信
     *
     * @link https://www.juhe.cn/docs/api/id/54
     *
     * @param string $mobile 接收短信的手机号码
     * @param int $tplId 短信模板ID，请参考个人中心短信模板设置
     * @param array $tplVars 变量值键值对
     * @return array
     */
    public static function sendText(array $params)
    {
        $mobile  = $params['mobile'];
        $tplId   = $params['tplId'];
        $tplVars = $params['tplVars'];

        $tplValue = '';

        if ($tplVars) {
            $vars = [];
            foreach ($tplVars as $key => $var) {
                $vars[] = '#' . $key . '#=' . $var;
            }
            $tplValue = implode('&', $vars);
        }

        $postData = [
            'key'       => JUHE_TEXT_APP_KEY,
            'mobile'    => $mobile,
            'tpl_id'    => $tplId,
            'tpl_value' => $tplValue,
            'dtype'     => 'json',
        ];

        $result = Com_Http::request(self::TEXT_API_URL, $postData, 'CURL-GET');
        $result = json_decode($result, true);

        // 是否成功
        $isOk = ! $result && isset($result['error_code']) ? 0 : 1;

        return [
            'is_ok' => $isOk,
            'return_msg' => $result,
        ];
    }

    /**
     * 发送语音验证码
     *
     * @link https://www.juhe.cn/docs/api/id/61
     *
     * @param string $mobile 接收手机号码
     * @param string $vcode  验证码内容，字母、数字 4-8位
     * @return array
     */
    public static function sendVoice(array $params)
    {
        $mobile = $params['mobile'];
        $vcode  = $params['tplVars']['code'];

        $postData = [
            'key'       => JUHE_VOICE_APP_KEY,
            'to'        => $mobile,
            'valicode'  => $vcode,
            'playtimes' => 3,
            'dtype'     => 'json',
        ];

        $result = Com_Http::request(self::VOICE_API_URL, $postData, 'CURL-POST');
        $result = json_decode($result, true);

        // 是否成功
        $isOk = ! $result && isset($result['error_code']) ? 0 : 1;

        return [
            'is_ok' => $isOk,
            'return_msg' => $result,
        ];
    }
}