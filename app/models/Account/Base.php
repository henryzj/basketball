<?php

/**
 * 账号系统-方法集合（登陆、注册行为）
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Account_Base extends Core_Model_Abstract
{
    const
        CLIENT_TICKET_NAME = '__vuser',
        UID_HASH_SALT      = 'ze0e99f77ce43915957e77504180badz';

    public static function getUidHash($uid)
    {
        return sha1($uid . '|' . self::UID_HASH_SALT);
    }

    // 注册一个全新的官方账号
    public static function register($mobile, $password, $vcode = null, $platform = '')
    {
        if (! $mobile || ! Com_Validate::mobile($mobile)) {
            throws('手机号格式不正确');
        }

        // 密码是否由系统生成
        $autoRndPwd = false;

        // 缺省生成随机密码
        if (null === $password) {
            $password = self::getRndPwd();
            $autoRndPwd = true;
        }

        // 检查密码长度
        $pwdLength = Helper_String::strlen($password);
        if ($pwdLength < 6 || $pwdLength > 40) {
            throws('密码长度不合法');
        }

        // 比对手机验证码
        if (null !== $vcode) {
            Model_MobileVcode::checkVcode($mobile, 'REGISTER', $vcode);
        }

        // 检测重复
        if (Dao('Ucenter_AccountIndex')->getUidByMobile($mobile)) {
            throws('该手机号已注册，请更换');
        }

        $setArr = [
            'mobile'   => $mobile,
            'password' => $password,
            'platform' => $platform,    // 空表示官方渠道
        ];

        // 创建“用户中心”的一个新用户
        $uid = self::createUcenterUser($setArr);

        if ($uid < 1) {
            throws('网络繁忙，请稍后重试');
        }

        // 创建“当前应用”的一个新用户
        self::createAppUser($uid, [
            'mobile'   => $mobile,
            'platform' => $platform,   // 空表示官方渠道
        ]);

        // 如果密码是由系统生成的，则还需要把密码告知用户
        if ($autoRndPwd) {
            Model_Sms::send($mobile, ['name' => $mobile, 'password' => $password], 'SEND_PASSWORD');
        }

        return $uid;
    }

    // 登录
    public static function login($mobile, $password)
    {
        if (! $mobile || ! Com_Validate::mobile($mobile)) {
            throws('手机号格式不正确');
        }

        if (! $password) {
            throws('密码不能为空');
        }

        // 查看用户信息
        $accountIndex = Dao('Ucenter_AccountIndex')->getUserByMobile($mobile);

        if (! $accountIndex)  {
            throws('该手机号不存在');
        }

        if (self::buildPassword($password, $accountIndex['salt']) != $accountIndex['password']) {
            throws('密码输入错误');
        }

        $uid = $accountIndex['id'];

        return $uid;
    }

    // 动态登录（短信验证码登录）
    public static function dynamicLogin($mobile, $vcode)
    {
        if (! $mobile || ! Com_Validate::mobile($mobile)) {
            throws('手机号格式不正确');
        }

        // 查看用户信息
        $accountIndex = Dao('Ucenter_AccountIndex')->getUserByMobile($mobile);

        if (! $accountIndex)  {
            throws('该手机号不存在');
        }

        // 比对手机验证码
        Model_MobileVcode::checkVcode($mobile, 'LOGIN', $vcode);

        $uid = $accountIndex['id'];

        return $uid;
    }

    /**
     * 注销
     *
     * @return void
     */
    public static function logout($uid)
    {
        F('Cookie')->del(self::CLIENT_TICKET_NAME);

        return self::clsTicket($uid);
    }

    /**
     * 构造密码（带盐）
     *
     * @return void
     */
    public static function buildPassword($password, $salt)
    {
        return sha1($password . $salt);
    }

    /**
     * 验证密码是否正确
     *
     * @return bool
     */
    public static function verifyPassword($uid, $password)
    {
        $accountIndex = Dao('Ucenter_AccountIndex')->get($uid);

        if (! $accountIndex || self::buildPassword($password, $accountIndex['salt']) != $accountIndex['password']) {
            return false;
        }

        return true;
    }

    /**
     * 刷新登录凭证
     *
     * @param int $uid
     * @return string $__vuser
     */
    public static function refreshTicket($uid, $isNeedSetCookie = true, $remember = false)
    {
        if (! $ticket = Dao('Ucenter_AccountTicket')->getField($uid, 'ticket')) {

            $ticket = self::buildTicket($uid);

            if (! Dao('Ucenter_AccountTicket')->replaceByPk(['uid' => $uid, 'ticket' => $ticket], $uid)) {
                throws('刷新登录凭证失败，请稍后再试');
            }
        }

        $uidHash = self::getUidHash($uid);
        $__vuser = $uidHash . '-' . $ticket;

        // 保存到 Cookie
        if ($isNeedSetCookie) {
            // 如果勾选了“自动登录”，则永久记录
            $cookieExpiresAt = $remember ? $GLOBALS['_TIME'] + 31536000 : null;
            // 执行设置 Cookie
            self::setUserCookie($__vuser, $cookieExpiresAt);
        }

        return $__vuser;
    }

    /**
     * 保存用户凭证
     *
     * @return void
     */
    public static function setUserCookie($__vuser, $expiresAt = null)
    {
        F('Cookie')->set(self::CLIENT_TICKET_NAME, $__vuser, $expiresAt);
    }

    /**
     * 读取用户凭证
     *
     * @return string
     */
    public static function getUserCookie()
    {
        return F('Cookie')->get(self::CLIENT_TICKET_NAME);
    }

    /**
     * 销毁登录凭证
     *
     * @param int $uid
     * @return bool
     */
    public static function clsTicket($uid)
    {
        return Dao('Ucenter_AccountTicket')->deleteByPk($uid);
    }

    /**
     * 生成唯一的随机 ticket
     *
     * @param int $uid
     * @return string
     */
    public static function buildTicket($uid)
    {
        return sha1(uniqid($uid) . mt_rand(1, 10000));
    }

    /**
     * 获取新用户使用的分库
     *
     * @param int $uid
     * @return int
     */
    public static function getDbSuffixForNewUser($uid)
    {
        $suffixes = explode(',', DB_SUFFIX_NEW_USER);

        if (count($suffixes) < 2) {
            return current($suffixes);
        }

        // 奇偶分库
        return $suffixes[(($uid + 1 ) % 2)];
    }

    /**
     * 创建“当前应用”的一个新用户
     *
     * @param int $uid 平台uid
     * @param array $data
     * @return bool
     */
    public static function createAppUser($uid, array $data = [])
    {
        if ($uid < 1) {
            return false;
        }

        // 空字段填充
        initDataFields($data, [
            'nickname', 'sex', 'province', 'city', 'country', 'language', 'headimgurl',
            'mobile', 'platform',
        ]);

        // 用户索引表
        Dao('Core_UserIndex')->insert([
            'uid'        => $uid,
            'db_suffix'  => self::getDbSuffixForNewUser($uid),   // 用户库后缀
            'nickname'   => $data['nickname'],
            'sex'        => $data['sex'],
            'province'   => $data['province'],
            'city'       => $data['city'],
            'country'    => $data['country'],
            'language'   => $data['language'],
            'headimgurl' => $data['headimgurl'],
        ]);

        // 插入用户基本信息
        Dao('Dist_User')->loadDs($uid)->insert([
            'uid'         => $uid,
            'created_at'  => $GLOBALS['_DATE'],
            'money'       => 0, // 账户余额
            'credits'     => 0, // 积分
        ]);

        // 用户统计信息
        Dao('Dist_UserStats')->loadDs($uid)->insert(['uid' => $uid]);

        // 用户设置信息
        Dao('Dist_UserSettings')->loadDs($uid)->insert(['uid' => $uid]);

        return true;
    }

    // 是否已经初始化过
    public static function hasCreatedAppUser($uid)
    {
        return Dao('Core_UserIndex')->get($uid) ? true : false;
    }

    public static function initAppUser($uid)
    {
        if (self::hasCreatedAppUser($uid)) {
            return true;
        }

        if (! $accountInfo = Dao('Ucenter_AccountInfo')->get($uid)) {
            return false;
        }

        $accountIndex = Dao('Ucenter_AccountIndex')->get($uid);
        $accountInfo['mobile']   = $accountIndex['mobile'];
        $accountInfo['platform'] = $accountIndex['platform'];

        return self::createAppUser($uid, $accountInfo);
    }

    /**
     * 创建“用户中心”的一个新用户
     *
     * @param array $data
     * @param bool $isNeedRefreshTicket
     * @return int $uid
     */
    public static function createUcenterUser(array $data, $isNeedRefreshTicket = false)
    {
        $mobile   = isset($data['mobile'])    ? $data['mobile']   : '';
        $password = isset($data['password'])  ? $data['password'] : '';
        $platform = isset($data['platform'])  ? $data['platform'] : '';

        // 密码盐值
        $salt = uniqid();

        // 插“用户中心”索引表
        $setArr = [
            'mobile'   => $mobile,
            'password' => $password ? self::buildPassword($password, $salt) : '',
            'salt'     => $salt,
            'platform' => $platform,
        ];

        $uid = Dao('Ucenter_AccountIndex')->insert($setArr);

        // 刷新用户凭证（但不设置cookie）
        if ($isNeedRefreshTicket) {
            self::refreshTicket($uid, false);
        }

        return $uid;
    }

    /**
     * 更新“用户中心”的用户信息表
     *
     * @param array $data
     * @return bool
     */
    public static function updateAccountInfo($uid, array $data)
    {
        $setArr = [];

        foreach (['nickname', 'sex', 'province', 'city', 'country', 'language', 'headimgurl'] as $field) {
            if (isset($data[$field])) {
                $setArr[$field] = $data[$field];
            }
        }

        if (! $setArr) {
            return false;
        }

        if (Dao('Ucenter_AccountInfo')->get($uid)) {
            return Dao('Ucenter_AccountInfo')->updateByPk($setArr, $uid);
        }
        else {
            $setArr['uid'] = $uid;
            return Dao('Ucenter_AccountInfo')->insert($setArr);
        }
    }

    // 是否需要引导前往完善昵称、头像
    public static function isNeedPerfect($uid)
    {
        $accountInfo = Dao('Ucenter_AccountInfo')->get($uid);

        return $accountInfo && $accountInfo['nickname'] ? 0 : 1;
    }

    /**
     * 获取当前已登录用户信息
     *
     * @param string $__vuser
     * @return int
     */
    public static function getUidByTicket($__vuser = null)
    {
        $tmpData = explode('-', $__vuser);
        $uidHash = isset($tmpData[0]) ? $tmpData[0] : '';
        $ticket  = isset($tmpData[1]) ? $tmpData[1] : '';

        if (! $uidHash || ! $ticket) {
            return false;
        }

        $uid = Dao('Ucenter_AccountTicket')->getUidByTicket($ticket);

        if ($uid < 1) {
            return false;
        }

        if ($uidHash != self::getUidHash($uid)) {
            return false;
        }

        return $uid;
    }

    /**
     * 重置密码
     *
     * @return bool
     */
    public static function resetPassword($mobile, $password, $vcode)
    {
        if (! $mobile || ! Com_Validate::mobile($mobile)) {
            throws('手机号格式不正确');
        }

        if (! $password) {
            throws('密码不能为空');
        }

        // 检查密码长度
        $pwdLength = Helper_String::strlen($password);
        if ($pwdLength < 6 || $pwdLength > 40) {
            throws('密码长度不合法');
        }

        // 查看用户信息
        $uid = Dao('Ucenter_AccountIndex')->getUidByMobile($mobile);

        if ($uid < 1) {
            throws('该手机号不存在');
        }

        // 比对手机验证码
        Model_MobileVcode::checkVcode($mobile, 'RESET_PWD', $vcode);

        // 执行更新
        self::changePassword($uid, $password);

        // 退出登录
        self::logout($uid);

        return true;
    }

    /**
     * 修改登录密码
     *
     * @param int $uid
     * @param string $password
     * @return bool
     */
    public static function changePassword($uid, $password)
    {
        $accountIndex = Dao('Ucenter_AccountIndex')->get($uid);

        return Dao('Ucenter_AccountIndex')->updateByPk([
            'password' => self::buildPassword($password, $accountIndex['salt'])
        ], $uid);
    }

    // 是否绑定了指定的第三方渠道
    public static function hasBindedThird($uid, $platform)
    {
        if (! $thirdUid = Dao('Ucenter_AccountThirdPfBind')->getThirdUid($uid, $platform)) {
            return 0;
        }

        return 1;
    }

    // 是否已绑定了注册手机号
    public static function hasBindedMobile($uid)
    {
        $accountIndex = Dao('Ucenter_AccountIndex')->get($uid);

        // 已填写了手机号和密码
        if (Com_Validate::mobile($accountIndex['mobile']) && $accountIndex['password']) {
            return true;
        }

        return false;
    }

    // 生成随机密码
    public static function getRndPwd($length = 6)
    {
        $sourceStr = '23456789abcdefghjkmnpqrstuvwxyz';
        return Helper_String::random($length, false, $sourceStr);
    }

    // 执行：根据一个第三方账号注册一个全新的官方账号，并同时建立关联
    public static function bindingNewAccount(Model_Account_Third_Abstract $platform, array $thirdUser, $mobile, $password, $vcode)
    {
        // 查找该第三方账号对应的我方账号
        $uid = $platform->getUidByThird($thirdUser);

        if ($uid) {
            throws('该第三方账号已绑定过其他官方账号了');
        }

        // 注册一个官方账号
        $uid = self::register($mobile, $password, $vcode, $platform->getPlatform());

        // 将第三方账号和当前官方账号绑定
        $platform->bindUidByThird($uid, $thirdUser, true);

        return $uid;
    }

    // 执行：把一个第三方账号跟一个已存在的官方账号进行绑定关联
    public static function bindingExistAccount(Model_Account_Third_Abstract $platform, array $thirdUser, $mobile, $password)
    {
        // 验证当前账号的手机号和密码
        $uid = self::login($mobile, $password);

        // 将第三方账号和当前官方账号绑定
        $platform->bindUidByThird($uid, $thirdUser, false);

        return $uid;
    }

    // 交互简化版的第三方账号绑定流程（用于移动版）
    // 合并了注册新用户和绑定现有用户的流程
    // 亮点：登陆时，不用密码，用短信验证码即可保证该手机号的宿主合法性
    public static function bindingAccountLite(Model_Account_Third_Abstract $platform, array $thirdUser, $mobile, $password, $vcode)
    {
        // 比对手机验证码
        Model_MobileVcode::checkVcode($mobile, 'REGISTER', $vcode);

        // 该手机号是否注册过
        $uid = Dao('Ucenter_AccountIndex')->getUidByMobile($mobile);

        // 如果未注册官方账号
        if ($uid < 1) {
            // 则自动帮他注册，密码缺省随机通过手机短信发送给用户
            $uid = self::register($mobile, $password, null, $platform->getPlatform());
            // 将第三方账号和当前官方账号绑定
            $platform->bindUidByThird($uid, $thirdUser, true);
        }
        else {
            // 将第三方账号和当前官方账号绑定
            $platform->bindUidByThird($uid, $thirdUser, false);
        }

        return $uid;
    }

    // 更换手机号
    public static function changeMobile($uid, $newMobile, $vcode)
    {
        if (! $newMobile || ! Com_Validate::mobile($newMobile)) {
            throws('手机号格式不正确');
        }

        $oppUid = Dao('Ucenter_AccountIndex')->getUidByMobile($newMobile);

        // 没有变更
        if ($oppUid == $uid) {
            return true;
        }

        if ($oppUid) {
            throws('该手机号已被他人使用');
        }

        // 比对手机验证码
        Model_MobileVcode::checkVcode($newMobile, 'CHANGE_INFO', $vcode);

        return Dao('Ucenter_AccountIndex')->updateByPk(['mobile' => $newMobile], $uid);
    }
}