<?php

/**
 * 用户相关
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_User_Base extends Model_User_Trait
{
    /**
     * 每天首次登陆游戏执行本函数逻辑
     * 更新今天第一次登陆时间、更新累计登陆的次数
     */
    public function updateLastLogin()
    {
        $timeDiff  = strtotime('today') - strtotime($this->_user['last_login_at']);

        // 2次访问时间的跨度不足一天
        if ($timeDiff < 1) {
            return false;
        }

        $updated = $this->_user->update([
            // 是否已读每日首次登录的提示
            'read_today_tips' => 0,
            // 更新上次访问时间
            'last_login_at'   => $GLOBALS['_DATE'],
            // 更新上次访问IP
            'last_login_ip'   => Helper_Client::getUserIp(),
        ], [
            // 防并发
            'last_login_at'   => ['<', TODAY],
        ]);

        // 影响行数防并发
        if (! $updated) {
            return false;
        }
        // 记录今天首次登陆时间
        $this->_user->dailyStats->update(['first_login_at' => $GLOBALS['_DATE']]);
    }

    // 年龄范围
    public static $ageRange = ['min' => 15, 'max' => 50];

    // 更新个人资料
    public function updateProfile(array $postData)
    {
        $setArr = [];

        // 空字段填充
        initDataFields($postData, [
            'nickname', 'age', 'industry_1', 'industry_2', 'province', 'city', 'signature'
        ]);

        // 昵称
        if ($nickname = Helper_String::cut2($postData['nickname'], 20)) {
            // 昵称不能重复
            if ($findUid = Dao('Core_UserIndex')->getUidByName($nickname)) {
                if ($findUid != $this->_uid) {
                    throws('昵称已被使用，请更换');
                }
            }
            else {
                $setArr['nickname'] = $nickname;
            }
        }

        // 年龄（选填）
        $age = intval($postData['age']);
        if ($age && $age >= self::$ageRange['min'] && $age <= self::$ageRange['max']) {
            $setArr['age'] = $age;
        }

        // 行业（选填）
        $industry1 = intval($postData['industry_1']);
        $industry2 = intval($postData['industry_2']);
        if ($industry1 && $industry2) {
            if ($industry1 = Dao('Core_V2StaticConfigIndustry')->name($industry1)) {
                $setArr['industry_1'] = $industry1;
            }
            if ($industry2 = Dao('Core_V2StaticConfigIndustry')->name($industry2)) {
                $setArr['industry_2'] = $industry2;
            }
        }

        // 常住城市（选填）
        $province = intval($postData['province']);
        $city = intval($postData['city']);
        if ($province && $city) {
            if ($province = Dao('Core_V2StaticConfigArea')->name($province)) {
                $setArr['province'] = $province;
            }
            if ($city = Dao('Core_V2StaticConfigArea')->name($city)) {
                $setArr['city'] = $city;
            }
            // TODO
            $setArr['country'] = '中国';
        }

        // 签名档（选填）
        $setArr['signature'] = $postData['signature'];

        if (! $setArr) {
            return false;
        }

        return $this->_user->update($setArr);
    }
}