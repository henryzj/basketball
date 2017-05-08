<?php

class Controller_Onetime extends Core_Controller_Test
{
    // 批量修改品牌logo图片文件名
    public function changeDirNameAction()
    {
        $dirName = 'D:\blocLogo';

        if (! is_dir($dirName)) {
            throws('不是有效目录');
        }

        $dirRe = opendir($dirName);

        while ($fn = readdir($dirRe)) {

            if ($fn != '.' && $fn != '..') {

                $num = strripos($fn, '.');
                $brandName = iconv('GBK', 'UTF-8', str_replace('-', ' ', substr($fn, 0, $num)));
                $brandName = str_replace('@3x', '', $brandName);
                $ext = substr($fn, $num);

                if ($brandId = Dao('Core_V2StaticHotelBloc')->field('id')->where(['name' => $brandName])->fetchOne()) {
                    $newName = $dirName . "/" . $brandId . $ext;
                    if (! rename($dirName . "/" . $fn, $newName)) {
                        echo $fn . "重命名未成功 <br/>";
                    };
                }
            }
        }

        exit('DONE');
    }

    // 原创聚合页保存
    public function saveHomepageAction()
    {
        $fileName = __DIR__ . '/news2.json';
        $result = json_decode(file_get_contents($fileName), true);
        foreach ($result['item'] as $item) {
            foreach ($item['content']['news_item'] as $info) {
                $data[] = [
                    'cate_id'     => 1,
                    'title'       => $info['title'],
                    'description' => isset($info['digest']) ? $info['digest'] : '',
                    'cover_url'   => isset($info['thumb_url']) ? $info['thumb_url'] : '',
                    'link_url'    => isset($info['url']) ? $info['url'] : '',
                    'created_at'  => date('Y-m-d H:i:s', $item['update_time']),
                    'updated_at'  => date('Y-m-d H:i:s', $item['update_time']),
                ];
            }
        }

        Dao('Core_V2Homepage')->batchInsert($data);

        exit('DONE');
    }

    // 批量修改v2_user_message.target_info 中的 headimgurl
    public function updateMessageHeadimgurlAction()
    {
        $where = ['type' => ['IN', [1, 2]]];

        $messageList = Dao('Core_V2UserMessage')->where($where)->fetchAll();

        foreach ($messageList as &$messageInfo) {

            $targetInfo = json_decode($messageInfo['target_info'], true);

            // 获取该用户信息
            $userInfo = Model_User::getNameCard($targetInfo['uid']);

            if ($userInfo) {

                $targetInfo['user_info']['headimgurl'] = $userInfo['headimgurl'] ?: '';

                $newTargetInfo = json_encode($targetInfo, true);

                Dao('Core_V2UserMessage')->updateByPk(['target_info' => $newTargetInfo], $messageInfo['id']);
            }
        }

        exit('成功');
    }

    // 更新用户的关注状态
    public function updateWxMpSubscribedAction()
    {
        if (! $openIds = Dao('Ucenter_WxUnion')->field('openid')->where(['source' => 'Weixin_MP', 'app_id' => WX_MP_APP_ID])->fetchCol()) {
            exit('no datas');
        }

        $setArr = [];
        $errorData = [];
        foreach ($openIds as $openId) {
            try {
                if (! $wxUserInfo = Model_Weixin_User::getFansUserInfo($openId)) {
                    continue;
                }

                $setArr[] = [
                    'openid'     => $openId,
                    'app_id'     => WX_MP_APP_ID,
                    'status'     => $wxUserInfo['subscribe'] ? 1 : 0,
                    'scene_id'   => 0,
                    'updated_at' => $GLOBALS['_DATE'],
                ];
            } catch (Exception $e) {
                $errorData[] = 'error_openId:' . $openId . ', error_msg:' . $e->getMessage() . PHP_EOL;
            }
        }

        if ($setArr) {
            Dao('Ucenter_WxMpFollow')->batchInsert($setArr, false, true);
        }

        pr('DONE:' . count($setArr), 0);
        pr($errorData);
    }
}