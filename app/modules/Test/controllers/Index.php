<?php

class Controller_Index extends Core_Controller_Test
{
    public $yafAutoRender = false;

    // jiangjian
    // 测试：ohSGIs3qdMqa_I3Tdls3GiNv2XMs
    // 生产：oQfhXtwjnfSaOD98Bt31LlHlqbSM
    public function sendTplMsgAction()
    {
        $openId = $this->getx('to');

        if (! $openId) {
            exit('empty recv_openid');
        }


        $msgData = '{"touser":"' . $openId . '","template_id":"RECV_JOB_APPLICATION","url":"http:\/\/163.com","topcolor":"#0099FF","data":{"first":{"value":"' . date('Y-m-d H:i:s') . '\n","color":"#000000"},"keyword1":{"value":"1AAA\n","color":"#000000"},"keyword2":{"value":"2BBB\n","color":"#000000"},"keyword3":{"value":"3CCC\n","color":"#000000"},"keyword4":{"value":"4DDD\n","color":"#000000"},"remark":{"value":"啊啊啊  是是是是是是 \n","color":"#0099FF"}}}';

        vd(Model_Weixin_TplMsg_Api::send($msgData));
    }

    public function sendTplMsg2Action()
    {
        $openId = $this->getx('to');

        if (! $openId) {
            exit('empty recv_openid');
        }

        $params = [
            'recv_openid' => $openId,
            'title'       => 'YYYYYY',
            'tpl_id'      => 'RECV_JOB_APPLICATION',
            'url'         => 'http://163.com',
            'keywords'    => [
                '1AAA',
                '2BBB',
                '3CCC',
                '4DDD',
            ],
            'remark'      => '啊啊啊 是是是是是是 ',
        ];

        $r = Model_Weixin_TplMsg_Base::push($params);
        var_dump($r);
        exit;
    }

    public function sendTplMsgCreditAction()
    {
        $openId = $this->getx('to');

        if (! $openId) {
            exit('empty recv_openid');
        }

        $params = [
            'recv_openid' => $openId,
            'title'       => 'YYYYYY',
            'tpl_id'      => 'CREDIT_BALANCE_CHANGED',
            'url'         => 'http://163.com',
            'keywords'    => [
                '变动原因',
                '推荐有简历大咖',
                '增加',
                '12312',
                '12345',
            ],
            'remark'      => '啊啊啊 是是是是是是 ',
        ];

        $r = Model_Weixin_TplMsg_Base::push($params, 'credit');
        var_dump($r);
        exit;
    }

    public function sendRedPackAction()
    {
        $openId = $this->getx('to');

        if (! $openId) {
            exit('empty recv_openid');
        }

        // 生产环境
        $r = Model_Weixin_RedPack_Base::sendDirect([
            'action'      => 50901,
            'recv_openid' => $openId,
            'title'       => 'BigCard申请大咖',
            'wishing'     => '您已成功申请BigCard职位，祝您前程似锦～',
            'money'       => 100,
            'memos'       => 'ZZZZZ职位IDXXXXX: 1',
        ]);
        var_dump($r);
        exit;
    }

    public function sendRedPack2Action()
    {
        $openId = $this->getx('to');

        if (! $openId) {
            exit('empty recv_openid');
        }

        // 生产环境
        $r = Model_Weixin_RedPack_Base::push([
            'action'      => 50100,
            'recv_openid' => $openId,
            'title'       => 'BigCard申请大咖',
            'wishing'     => '您已成功申请BigCard职位，祝您前程似锦～',
            'money'       => 100,
            'memos'       => '2222222222222222职位ID: 1',
        ]);
        var_dump($r);
        exit;
    }

    public function sendMchPayAction()
    {
        $openId = $this->getx('to');

        if (! $openId) {
            exit('empty recv_openid');
        }

        // 生产环境
        $r = Model_Weixin_MchPay_Base::sendDirect([
            'action'      => 60901,
            'recv_openid' => $openId,
            'money'       => 1,
            'desc'        => '这里是转账描述（给用户看的）',
            'memos'       => '这里是转账备注（给管理员看的）',
        ]);
        var_dump($r);
        exit;
    }

    public function sendMchPay2Action()
    {
        $openId = $this->getx('to');

        if (! $openId) {
            exit('empty recv_openid');
        }

        // 生产环境
        $r = Model_Weixin_MchPay_Base::push([
            'action'      => 60901,
            'recv_openid' => $openId,
            'money'       => 8,
            'desc'        => '这里是转账描述（给用户看的）',
            'memos'       => '这里是转账备注（给管理员看的）',
        ]);
        var_dump($r);
        exit;
    }

    public function sendEmailAction()
    {
        $email = $this->getx('to');

        if (! $email) {
            exit('empty recv_email');
        }

        $subject = '亲爱的BigCard用户您好';
        $html    = '亲爱的用户：<br />感谢您验证接受接收简历邮箱，请点击以下链接完成验证。<br />' .
                   '<a href="http://mp4-beta.local.morecruit.cn/auth/validateEmail?uid=711&email=79095956@qq.com&vcode=1a013cac74690a5e280e5cacb0a6a2b66fdb920c" target="_blank"></a><br />' .
                   '如果以上链接无法点击，请将它复制到你的浏览器地址栏中进入访问，该链接24小时内有效。';

        $result = Model_Mailer::sendDirect(explode(';', $email), $subject, $html);

        vd($result);
    }

    public function sendEmail2Action()
    {
        $email = $this->getx('to');

        if (! $email) {
            exit('empty recv_email');
        }

        $r = Model_Mailer::send(explode(';', $email), 'TEST EMAIL TITLE', 'TEST EMAIL CONTENT');

        var_dump($r);
        exit;
    }

    public function addRedisLogAction()
    {
        $r1 = Com_Logger_Redis::custom('redPack', [
            'action'      => 50000,
            'recv_openid' => '1234',
            'money'       => '11111',
            'created_at'  => $GLOBALS['_DATE'],
        ]);

        $r2 = Com_Logger_Redis::info('hello', 'world');

        $r3 = Com_Logger_Redis::custom('test', 'jiang');
        $r4 = Com_Logger_Redis::custom('sss', 'xxx');

        vd($r1, 0);
        vd($r2, 0);
        vd($r3, 0);
        vd($r4, 0);
    }

    public function chatgroundAction()
    {
        $r = Model_Ground::calcRedPackList(794);
        $r = Model_Ground::dispatchRedPacks($r);
        vd($r);
    }

    public function syncRedPackDataAction()
    {
        set_time_limit(0);
        $readPackType = [
            '0' => 50100,
            '1' => 50101,
            '2' => 50102,
            '3' => 50103,
            '4' => 50104,
        ];

        $count = Dao('Core_W3_FollowRedEnvelope')->fetchCount();
        $pageSize = 1000;
        $pageCount = ceil($count / $pageSize);

        $j = 0;
        for($i = 1; $i <= $pageCount; $i++) {
            $start = ($i - 1) * $pageSize;
            $res = Dao('Core_W3_FollowRedEnvelope')->limit($start, $pageSize)->fetchAll();

            foreach ($res as $value) {

                if ($value['from_type'] == 5) {
                    continue;
                }

                // 接收人openId
                $data[] = array(
                    'uid'        => $value['follow_id'],
                    'type'       => 1,
                    'action'     => $readPackType[$value['from_type']],
                    'money'      => $value['amount'],
                    'memos'      => $value['from_id']? '职位ID: ' . $value['from_id'] : '',
                    'created_at' => date('Y-m-d, H:i:s', $value['create_time']),
                );

                $j++;
            }
        }

        Dao('Massive_LogUserMoney')->batchInsert($data);

        exit('总计导入了' . $j . '条数据');
    }

    public function addVisitAction()
    {
        $r[] = Model_VisitHistory::addVisit(711, 'Job', 1);
        $r[] = Model_VisitHistory::addVisit(711, 'Job', 6);
        $r[] = Model_VisitHistory::addVisit(711, 'Job', 7);
        // $r[] = Model_VisitHistory::addVisit(712, 'Job', 1);
        // $r[] = Model_VisitHistory::addVisit(713, 'Job', 1);
        // $r[] = Model_VisitHistory::addVisit(714, 'Job', 1);

        print_r($r);
        exit;
    }

    public function listVisitAction()
    {
        $c = Model_VisitHistory::getVistorCount('Job', 1);
        $l = Model_VisitHistory::getVistorList('Job', 1);

        print_r($c);

        echo '----' . PHP_EOL;

        print_r($l);
        exit;
    }

    public function footprintAction()
    {
        $c = Model_VisitHistory::getMyFootPrint(711, 'Job');
        print_r($c);
        exit;
    }

    public function leifengAction()
    {
        $job = new Model_Job(7270);

        $f = $job->sendLeiFengRedPack(108872);
        vd($f);
    }

    public function sendEmail3Action()
    {
        $title   = '职位发布审核';
        $content = '<a href="' . sUrl('/gm/job/index') . '">点击查看详情</a>';
        $result = Model_Mailer::send($GLOBALS['JOB_ADMINS'], $title, $content);

        exit('OK');
    }

    public function sendEmail4Action()
    {
        $userEmail = '79095956@qq.com';
        // $subject = '来自BigCard的邀约 - 聊个offer吗亲？';

        // $html = '<p>亲爱的大咖，有BigCard用户对你发出了诚挚的邀约。</p>' .
        //         '<p>Re公公对你说：khkjh; adsfasd<p>' .
        //         '<p>TA的公司：Morecruit<p>' .
        //         '<p>TA的职位：总管<p>' .
        //         '<p>扫描二维码查看更多TA的信息，关注BigCard推荐平台公众号，就可以随手推荐身边活儿好的大咖了。<p>' .
        //         '<p><a href="/auth/dealFreeTransFromEmail/?trans_id=XXXXXXXXXXXXXXXX">点击这里立即处理本次邀约。</a></p>' .
        //         '<p><img src="" width="200" /></p>';
        //
        // $subject = '来自BigCard的简历 - 你成功搭讪了一位大咖！';
        // $html    = '亲爱的BigCard用户，你成功搭讪了一位技能全满的大咖：<strong>张三</strong><br /><br />' .
        //            '<a href="XXXXXXXXXX">戳这里下载TA的简历</a><br /><br />' .
        //            '祝你们早日擦出各类火花。';
        //

        $subject = '很高兴遇见你^_^来自BigCard - 最受欢迎的大咖推荐平台';
        $html = '<p>亲爱的大咖，我们在BigCard上发现了你的身影。有一位BigCard用户把你推荐给了求贤若渴的优质公司，并且有更多的用户已经为你的技能点赞！</p>' .
                '<p>推荐你的BigCard用户：江风雨后</p>' .
                '<p>TA的公司：more</p>' .
                '<p>TA的身份：总管</p>' .
                '<p>扫描二维码关注BigCard推荐平台微信公众号，发现更多机会，搭讪更多大咖，还能随手推荐身边靠谱的职场伙伴。</p>' .
                '<p><img src="XXXXXXXXXXXX" width="200" /></p>';

        Model_Mailer::setAdapter('SendCloud');
        $result = Model_Mailer::send($userEmail, $subject, $html);
        exit('OK');
    }

    public function sendSmsAction()
    {
        $r = S('Model_Queue_Sms')->push(['created_at' => date('Y-m-d H:i:s')]);
        vd($r);
    }

    public function membsAction()
    {
        // $result = Model_Membership::factory(8)->fetchUserData('306200020293', '753951');
        // $result = Model_Membership::factory(7)->fetchUserData('H18916722882', 'Sebxu69ht'); // seb
        $result = Model_Membership::factoryByCode('Accor')->loginQA();
        // $result = Model_Membership::factory(7)->parseStatsTest();

        var_dump($result);
        exit;
    }

    public function lbsAction()
    {
        $r = Com_BaiduLBS_GeoSearch::getNearby('125943', ['116.442267', '39.93872'], [
            'radius' => 100000, // 100公里
            'sortby' => 'distance:1',
        ]);

        pr($r);
    }

    public function jPushAction()
    {
        $pusher = new Com_Push_JPush(JPUSH_APP_KEY, JPUSH_APP_SECRET);

        $r = $pusher->pushMsgToSingleDevice(['1a0018970aa483745b8', '0a19bc96649', '001363c5951'], 'Stay生活家 - 留住不一样的风景');

        vd($r);
    }

    public function testMcAction()
    {
        // F('Redis')->set('aaa', 1111);
        // vd(F('Redis')->get('aaa'));

        F('Memcache')->set('testa', 111);
        vd(F('Memcache')->get('testa'), 0);
        vd(F('Memcache')->increment('testa'), 0);
        vd(F('Memcache')->get('testa'), 0);

        vd(F('Memcache')->setByTag('testb', 222, 'testTag'), 0);
        vd(F('Memcache')->setByTag('testc', 333, 'testTag'), 0);
        vd(F('Memcache')->get('testTag'), 0);
        vd(F('Memcache')->deleteByTag('testTag'),0);
        vd(F('Memcache')->get('testb'),0);
        vd(F('Memcache')->get('testc'),0);
        vd(F('Memcache')->get('testTag'), 0);
    }

    public function aliDaYuSendSmsAction()
    {
        $r = Com_Sms_AliDaYu::sendText('13524925240', '注册验证', 'SMS_5070595', [
            'code' => mt_rand(100000, 999999),
            'product' => 'StayApp',
        ]);

        vd($r);
    }

    public function aliDaYuSendVoiceAction()
    {

    }

    public function faceDetectAction()
    {
        $imgUrl = 'https://oxfordportal.blob.core.windows.net/face/demo/detection%205.jpg';

        $r = Com_Oxford_FaceApi::detect($imgUrl);

        vd($r);
    }

    public function findSimilarsAction()
    {
        $base64Pic1 = base64_encode(file_get_contents(WEB_PATH . '1.jpg'));
        $base64Pic2 = base64_encode(file_get_contents(WEB_PATH . '2.jpg'));

        $r = Com_Recog_Recognition::compareByPic($base64Pic1, $base64Pic2);

        vd($r);
    }

    // 聚合数据发送短息
    public function juheSendSmsAction()
    {
        // 云片短信
        // $data1 = [
        //     'mobile'        => '15021910632',
        //     'adapter'       => 'YunPian',
        //     'signature'     => ''
        //     'calledShowNum' => ''
        //     'tplId'         => 0,
        //     'tplVars'       => [
        //         'code' => '123456',
        //         'vcode' => '123456',
        //         'product' => 'StayApp',
        //     ],
        // ];

        // // 云片语音
        // $data2 = [
        //     'mobile'    => '15021910632',
        //     'adapter'   => 'YunPian',
        //     'vcode'     => '123452',
        //     'send_type' => 'voice'
        // ];

        // // 聚合短信
        // $data3 = [
        //     'mobile'  => '15021910632',
        //     'adapter' => 'Juhe',
        //     'tplId'   => '10326',
        //     'tplVars' => [
        //         'code'    => '123453'
        //         'vcode'   => ''
        //     ],
        // ];

        // // 聚合语音
        // $data4 = [
        //     'mobile' => '15021910632',
        //     'adapter' => 'Juhe',
        //     'vcode' => '123454',
        //     'send_type' => 'voice'
        // ];

        // // 阿里大鱼短信
        // $data5 = [
        //     'mobile' => '15021910632',
        //     'adapter' => 'AliDaYu',
        //     'signature' => '注册验证',
        //     'tplId' => 'SMS_5070595',
        //     'tplVars' => [
        //         'code' => '123455',
        //         'product' => 'StayApp',
        //     ],
        // ];

        // // 阿里大鱼语音
        // $data6 = [
        //     'mobile' => '15021910632',
        //     'adapter' => 'AliDaYu',
        //     'calledShowNum' => '',
        //     'tplId' => 'SMS_5070595',
        //     'tplVars' => [
        //         'code' => '123456',
        //         'product' => 'StayApp',
        //     ],
        //     'send_type' => 'voice',
        // ];

        $tplVars = [
            'code' => '123456',
            'vcode' => '123456',
            'date' => '2013-03-26',
            'product' => 'StayApp',
        ];

        // 如果需要设置
        // Model_Sms::setAdapter('YunPian');
        $r = Model_Sms::send('15021910632', $tplVars, 'REGISTER', 'YunPian', 'text');

        // $r = S('Model_Queue_Sms')->push($data1);

        // $r = Com_Sms_YunPian::sendText($data1);
        // $r = Com_Sms_Juhe::sendText($data3);
        // $r = Com_Sms_AliDaYu::sendText($data5);

        vd($r);
    }

    // 更新百度酒店的标签。
    public function bizAction()
    {
        $postData = [
            'hotel_id' => 10040024,
            'name'     => 'test333大酒店',
            'address' => 'Victoria Road, Camps Bay Cape Town, Western Cape, 8040, South Africa',
            'longitude' => 18.365443,
            'latitude' => -33.977275,
            'name_en' => '12 Apostles Hotel & Spa',
            'keywords' => 'test1,test2',
        ];

        S('Model_Queue_Biz')->push(json_encode([
            'callback' => 'Com_BaiduLBS_GeoStorage::updatePoi',
            'params' => [
                'v2_static_hotel_info',
                [
                    'title'     => $postData['name'],
                    'address'   => $postData['address'],
                    'longitude' => $postData['longitude'],
                    'latitude'  => $postData['latitude'],
                    'custom_cols' => [
                        'name_en'  => $postData['name_en'],
                        'keywords' => $postData['keywords'],
                    ],
                ],
                $postData['hotel_id'],
            ]
        ], JSON_UNESCAPED_UNICODE));

        exit('OK');
    }
}