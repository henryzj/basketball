<?php

/**
 * 发送邮件
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Mailer extends Core_Model_Abstract
{
    // 使用哪种方式发送邮件
    protected static $_adapter = 'PHPMailer';    // SendCloud/PHPMailer

    // 设置我的适配器
    public static function setAdapter($adapter)
    {
        self::$_adapter = $adapter;
    }

    // 队列发送
    public static function send($to, $subject, $html, $attachmentPath = null)
    {
        // 多个收件人
        if (is_array($to)) {
            $to = implode(';', $to);
        }

        $params = [
            'from'     => PHP_MAILER_NOREPLY_USER,
            'fromname' => 'noreply',
            'to'       => $to,
            'subject'  => $subject,
            'html'     => $html,
            'adapter'  => self::$_adapter,
        ];

        // 有附件
        if ($attachmentPath) {
            $params['files'] = '@' . $attachmentPath;
        }

        return S('Model_Queue_Email')->push(json_encode($params, JSON_UNESCAPED_UNICODE));
    }

    // 直接发送
    public static function sendDirect($to, $subject, $html, $attachmentPath = null)
    {
        // 多个收件人
        if (is_array($to)) {
            $to = implode(';', $to);
        }

        $params = [
            'from'     => PHP_MAILER_NOREPLY_USER,
            'fromname' => 'noreply',
            'to'       => $to,
            'subject'  => $subject,
            'html'     => $html,
        ];

        // 有附件
        if ($attachmentPath) {
            $params['files'] = '@' . $attachmentPath;
        }

        $mailerClass = 'Com_Mailer_' . ucfirst(self::$_adapter);

        // 正式发送
        $result = $mailerClass::send($params);

        return [
            'is_ok'      => $result['is_ok'],
            'return_msg' => $result['return_msg'],
        ];
    }
}