<?php

/**
 * 云片短信服务
 *
 * @link http://www.yunpian.com/api/sms.html
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Sms_YunPian
{
    const TEXT_API_URL  = 'http://yunpian.com/v1/sms/send.json';
    const VOICE_API_URL = 'http://voice.yunpian.com/v1/voice/send.json';

    /**
     * 发送纯文本短信
     *
     * @param string $mobile 多个手机号用半角逗号分隔（最多100个）
     * @param string $content 短信内容
     * @param array $tplVars 短信变量
     * @return array
     */
    public static function sendText(array $params)
    {
        $mobile = $params['mobile'];

        // 替换文本中的变量
        if ($params['tplVars']) {

            $searchs = $replaces = [];

            foreach ($params['tplVars'] as $key => $var) {
                $searchs[] = '#' . $key . '#';
                $replaces[] = $var;
            }

            $content = str_replace($searchs, $replaces, $params['content']);
        }

        $postData = [
            'apikey'       => YUNPIAN_API_KEY,
            'mobile'       => $mobile,
            'text'         => $content,
            'callback_url' => YUNPIAN_CB_URL,
        ];

        $result = Com_Http::request(self::TEXT_API_URL, $postData, 'CURL-POST');
        $result = json_decode($result, true);

        // 是否成功
        $isOk = $result && isset($result['code']) && $result['code'] == 0 ? 1 : 0;

        return [
            'is_ok' => $isOk,
            'return_msg' => $result,
        ];
    }

    /**
     * 发送语音验证码
     *
     * @param string $mobile 接收的手机号、固话（需加区号）或400电话
     * @param int $vcode 支持4~6位阿拉伯数字
     * @return array
     */
    public static function sendVoice(array $params)
    {
        $mobile = $params['mobile'];
        $vcode  = $params['tplVars']['code'];

        $postData = [
            'apikey'       => YUNPIAN_API_KEY,
            'mobile'       => $mobile,
            'code'         => $vcode,
            'callback_url' => YUNPIAN_CB_URL,
        ];

        $result = Com_Http::request(self::VOICE_API_URL, $postData, 'CURL-POST');
        $result = json_decode($result, true);

        // 是否成功
        $isOk = $result && isset($result['code']) && $result['code'] == 0 ? 1 : 0;

        return [
            'is_ok' => $isOk,
            'return_msg' => $result,
        ];
    }
}