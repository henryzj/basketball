<?php

/**
 * 用户模型基类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: User.php 12178 2014-09-26 02:10:30Z jiangjian $
 */

class Model_User extends Core_Model_Abstract
{
    protected $_uid;

    // 全局索引表冗余字段 core.user_index
    public static $userIndexFields = [
        'nickname',
        'sex',
        'province',
        'city',
        'country',
        'language',
        'headimgurl',
        'age',
        'industry_1',
        'industry_2',
    ];

    // 名片可能影响的字段
    public static $nameCardRelatedFields = [
        'nickname',
        'sex',
        'province',
        'city',
        'country',
        'headimgurl',
        'vip_lv',
        'age',
        'industry_1',
        'industry_2',
        'signature',
    ];

    public function __construct($uid, $extendInit = false)
    {
        $this->_prop = self::getUser($uid, true);
        $this->_uid  = $this->_prop['id'] = $this->_prop['uid'];

        // 默认昵称
        if (! $this->_prop['nickname'] && ! $this->_prop['account']['mobile']) {
            $this->_prop['nickname'] = '匿名';
        }

        // 默认头像
        if (! $this->_prop['headimgurl']) {
            $this->_prop['headimgurl'] = IMG_DIR . '/no-avatar.png';
        }

        // 更多玩家数据的初始化
        // 某些场合为了节约性能，不需要这些，例如战斗玩家列表
        if ($extendInit) {
            // TODO
        }
    }

    public function __get($var)
    {
        // 我的扩展行为
        static $_traits = [
            'base'         => 1,  // 基本操作
            'settings'     => 1,  // 我的设置
            'stats'        => 1,  // 我的统计数据
            'dailyStats'   => 1,  // 我的统计数据（每天）
            'hourlyStats'  => 1,  // 我的统计数据（每小时）
            'guide'        => 1,  // 我的新手引导
            'wallet'       => 1,  // 我的钱包
            'credit'       => 1,  // 我的积分
        ];

        if (isset($_traits[$var])) {
            $class = 'Model_User_' . ucfirst($var);
            return $this->{$var} = new $class($this);
        }

        // 我的扩展数据
        static $_extends = array(
            'nameCard' => 1,    // 我的名片卡
        );

        if (isset($_extends[$var])) {
            $method = '_loadUser' . ucfirst($var);
            return $this->{$var} = $this->$method();
        }

        return parent::__get($var);
    }

    public function DaoDs($class)
    {
        return Dao('Dist_' . $class)->loadDs($this->_uid);
    }

    public static function getUser($uid, $halted = false)
    {
        $user = [];

        // 基本信息
        if ($uid < 1) {
            if ($halted) {
                throws403('Invalid Uid:' . $uid);
            }
        }

        if (! $user = Dao('Dist_User')->loadDs($uid)->get($uid)) {
            if ($halted) {
                throws('用户信息读取失败。' . 'Uid:' . $uid);
            }
        }

        // 合并一些数据
        $userIndex = Dao('Core_UserIndex')->get($uid);
        unset($userIndex['id'], $userIndex['uid']);
        $user = array_merge($user, $userIndex);

        // 再合并一些数据
        $accountIndex = Dao('Ucenter_AccountIndex')->get($uid);
        $user['account'] = [
            'mobile'   => $accountIndex['mobile'],
            'password' => $accountIndex['password'],
            'platform' => $accountIndex['platform'],
        ];

        return $user;
    }

    /**
     * 更新用户（封装）
     *
     * @param array $setArr
     * @param array $extraWhere 格外的WHERE条件
     * @return void
     */
    public function update($setArr, array $extraWhere = [])
    {
        if (! $setArr) {
            return false;
        }

        // 更新用户索引表
        $this->_updateUserIndex($setArr);

        // 更新用户基本信息表
        $this->_updateUserBase($setArr, $extraWhere);

        // 更新名片信息
        $this->_updateNameCard($setArr);

        // 当前 $this->_prop 数组数据更新
        $this->_prop = self::getUser($this->_uid);

        return true;
    }

    /**
     * 更新用户索引表
     *
     * @param array $setArr
     * @return void
     */
    protected function _updateUserIndex(array $setArr)
    {
        $updateArr = [];

        foreach (self::$userIndexFields as $field) {
            if (isset($setArr[$field])) {
                $updateArr[$field] = $setArr[$field];
            }
        }

        if (! $updateArr) {
            return false;
        }

        Dao('Core_UserIndex')->updateByPk($updateArr, $this->_uid);

        // 更新“用户中心”的用户信息表
        Model_Account_Base::updateAccountInfo($this->_uid, $updateArr);

        return true;
    }

    /**
     * 更新用户基本信息表
     *
     * @param array $setArr
     * @param array $extraWhere 格外的WHERE条件
     * @return bool
     */
    protected function _updateUserBase(array $setArr, array $extraWhere = [])
    {
        // 过滤一些字段
        foreach (self::$userIndexFields as $field) {
            if (isset($setArr[$field])) {
                unset($setArr[$field]);
            }
        }

        return $this->DaoDs('User')->updateByPk($setArr, $this->_uid, $extraWhere);
    }

    /**
     * 更新名片缓存
     *
     * @param array $setArr
     * @return bool
     */
    protected function _updateNameCard(array $setArr)
    {
        // 如果改动了名片字段，则更新缓存
        foreach (self::$nameCardRelatedFields as $field) {
            if (isset($setArr[$field]) && $setArr[$field] != $this->_prop[$field]) {
                return self::deleteNameCard($this->_uid);
            }
        }
    }

    /**
     * 获取指定用户的名片缓存
     *
     * @param int $uid
     * @return array
     */
    public static function getNameCard($uid)
    {
        $cacheObj = F('Memcache')->default;
        $cacheKey = 'User:NameCard:' . $uid;

        if (! $nameCard = $cacheObj->get($cacheKey)) {

            try {

                $user = new self($uid);

                $nameCard = [
                    'uid'        => $user['uid'],
                    'nickname'   => $user['nickname'],
                    'sex'        => $user['sex'],
                    'province'   => $user['province'],
                    'city'       => $user['city'],
                    'country'    => $user['country'],
                    'headimgurl' => $user['headimgurl'],
                    'vip_lv'     => $user['vip_lv'],
                    'age'        => $user['age'],
                    'industry_1' => $user['industry_1'],
                    'industry_2' => $user['industry_2'],
                    'signature'  => $user['signature'],
                ];

                // 获取卡包会籍图标
                $nameCard['membs_lvs'] = Model_Membership::getTopLvIcons($uid, 3);

                $cacheObj->set($cacheKey, $nameCard);
            }

            catch (Exception $e) {
                // do nothing
            }
        }

        return $nameCard ?: [];
    }

    public static function getNameCardLite($uid)
    {
        if (! $userInfo = self::getNameCard($uid)) {
            return [];
        }

        return siftDataFields($userInfo, ['uid', 'nickname', 'headimgurl', 'membs_lvs']);
    }

    /**
     * 删除指定用户的名片缓存
     *
     * @param int $uid
     * @return bool
     */
    public static function deleteNameCard($uid)
    {
        $cacheKey = 'User:NameCard:' . $uid;

        return F('Memcache')->delete($cacheKey);
    }

    // 是否VIP
    public function isVip($vipLv = null)
    {
        if ($vipLv) {
            if ($this->_prop['vip_lv'] != $vipLv) {
                return false;
            }
        }
        else {
            if (! $this->_prop['vip_lv']) {
                return false;
            }
        }

        // 已过期
        if ($this->isVipExpired()) {
            return false;
        }

        return true;
    }

    // VIP是否已过期
    public function isVipExpired()
    {
        return $this->_prop['vip_lv'] && $GLOBALS['_TIME'] > $this->_prop['vip_expires_at'] ? true : false;
    }

    /**
     * 是否管理员
     * 内部账号不参与排行榜等统计
     *
     * @return bool
     */
    public function isGM()
    {
        return $this->_prop['is_gm'] ? true : false;
    }

    // 我是否关注了微信公众号
    public function isSubscribed()
    {
        $wxAppId = WX_MP_APP_ID;

        if (! $wxMpOpenId = $this->getWxOpenId('MP', $wxAppId)) {
            return false;
        }

        return Dao('Ucenter_WxMpFollow')->getField([$wxMpOpenId, $wxAppId], 'status') ? 1 : 0;
    }

    // 获取我的微信openid
    // 默认是我在官方公众号下的openid
    public function getWxOpenId($wxFrom = 'MP', $wxAppId = WX_MP_APP_ID)
    {
        return Model_Account_Third::getWxOpenId($this->_uid, $wxFrom, $wxAppId);
    }

    // 主动拉取、更新我的用户信息
    // 微信公众号粉丝专用：去微信平台拉取一遍用户昵称和头像
    public function refreshWxFollowInfo($halted = false)
    {
        try {
            return Model_Account_Third::refreshWxFollowInfo($this->_uid);
        }
        catch (Exception $e) {
            if ($halted) {
                throw $e;
            }
        }

        return false;
    }

    public function loadStats()
    {
        return $this->stats;
    }

    public function loadSettings()
    {
        return $this->settings;
    }

    protected function _loadUserNameCard()
    {
        return self::getNameCard($this->_uid);
    }

    // 获取所有GM名单
    public static function getGmList()
    {
        return Dao('Core_UserIndex')->where(['is_gm' => 1])->fetchAll();
    }

    // 我是否QQ会员
    public function isQQVip()
    {
        $platform = Model_Account_Third::factory('QQ');

        if (! $accessToken = $platform->getAccessToken($this->_uid)) {
            return 0;
        }

        if (! $qqOpenId = Dao('Ucenter_AccountThirdPfBind')->getThirdUid($this->_uid, 'QQ')) {
            return 0;
        }

        $qqUserInfo = $platform->getVipRichInfo($accessToken, $qqOpenId);

        return isset($qqUserInfo['is_qq_vip']) ? $qqUserInfo['is_qq_vip'] : 0;
    }

    // 我是否QQ超级会员
    public function isQQSuperVip()
    {
        // TODO
        return $this->isQQVip();
    }
}