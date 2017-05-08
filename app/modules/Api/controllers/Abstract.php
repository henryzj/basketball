<?php

abstract class Controller_Abstract extends Core_Controller_Api_Standard
{
    /**
     * 当前用户 uid
     *
     * @var int
     */
    protected $_uid;

    /**
     * 当前用户实例对象
     *
     * @var Model_User
     */
    protected $_user;

    /**
     * 是否检测登陆
     *
     * @var bool
     */
    protected $_checkAuth = 1;

    /**
     * 是否采用 2016-05-16 以后的新接口
     *
     * @var bool
     */
    protected $_isNewVer;

    /**
     * 构造函数
     */
    public function init()
    {
        parent::init();

        // 是否采用 2016-05-16 以后的新接口
        $this->_isNewVer = version_compare($this->_appVersion, '1.2.99') >= 0;

        // 当前登陆用户uid
        $__vuser = isset($_SERVER['HTTP_VUSER'])
            ? $_SERVER['HTTP_VUSER']
            : $this->getx(Model_Account_Base::CLIENT_TICKET_NAME);

        $this->_uid = Model_Account_Base::getUidByTicket($__vuser);

        // 未登录
        if (! $this->_uid) {
            $this->checkAccess($this->_checkAuth);
        }

        // 已登录
        else {

            // 初始化用户信息
            Model_Account_Base::initAppUser($this->_uid);

            // 创建用户实例
            $this->_user = new Model_User($this->_uid, true);

            // 更新用户的最后登录时间、IP等信息
            $this->_user->base->updateLastLogin();

            // 引导前往完善用户信息
            $this->_checkAuth && $this->checkPerfect();
        }
    }

    // 引导前往登录、注册
    public function checkAccess($checkAuth = null)
    {
        if ($this->_uid) {
            return true;
        }

        if (null === $checkAuth) {
            $checkAuth = $this->_checkAuth;
        }

        if ($checkAuth) {
            $this->output('令牌不正确或已过期，请重新登录', -1000);
        }
    }

    // 引导前往完善用户信息
    public function checkPerfect()
    {
        // 注意：全小写，不需要模块名
        $whiteList = [
            '/user/perfect' => 1,
            '/cloud/getqiniuuptoken' => 1,
        ];

        if (! $this->isBaseUri($whiteList)) {
            if (Model_Account_Base::isNeedPerfect($this->_uid)) {
                $this->output('请前往完善昵称、头像', -1010);
            }
        }
    }

    /**
     * 获取当前用户盐
     *
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function getSalt($extraKey = null)
    {
        return __getSalt($extraKey);
    }

    // 防刷机制，每两次请求必须间隔X毫秒
    public function assertReqInterval($ms = 1000, $namespace = null)
    {
        $antiRefresh = new Com_AntiRefresh();

        if (! $antiRefresh->intervalReqLimit($this->_uid, $this->_request, $ms, $namespace)) {
            throws('您的动作太快了，请休息一会儿');
        }
    }

    // 装饰体验列表
    protected function _decoExpList(array $expList)
    {
        foreach ($expList as $key => &$expInfo) {
            if (! $expInfo) {
                unset($expList[$key]);
            }
            $expInfo = $this->_decoExpInfo($expInfo);
        }

        return $expList;
    }

    // 装饰体验详情
    protected function _decoExpInfo(array $expInfo)
    {
        if (! $expInfo) {
            return $expInfo;
        }

        if (! isset($expInfo['is_liked'])) {
            $expInfo['is_liked'] = $this->_uid ? Model_Stay_Like::hasLiked($this->_uid, 1, $expInfo['id']) : 0;
        }

        if (! isset($expInfo['is_faved'])) {
            $expInfo['is_faved'] = $this->_uid ? Model_Stay_Fav::hasFaved($this->_uid, 1, $expInfo['id']) : 0;
        }

        if (! isset($expInfo['follow_relation'])) {
            $expInfo['follow_relation'] = $this->_uid ? Model_Stay_Follow::isFollowed($this->_uid, $expInfo['uid']) : 0;
        }

        if ($this->_uid) {
            $expInfo['invite_icons'] = Model_Stay_Exp::decoInviteIcons($this->_uid, $expInfo['id'], $expInfo['invite_icons']);
        }

        if (! isset($expInfo['is_self'])) {
            $expInfo['is_self'] = $this->_uid && $expInfo['uid'] == $this->_uid ? 1 : 0;
        }

        return $expInfo;
    }

    // 装饰文章详情
    protected function _decoArticleInfo(array $articleInfo)
    {
        if (! $articleInfo) {
            return $articleInfo;
        }

        if (! isset($articleInfo['is_liked'])) {
            $articleInfo['is_liked'] = $this->_uid ? Model_Stay_Like::hasLiked($this->_uid, 2, $articleInfo['id']) : 0;
        }

        if (! isset($articleInfo['is_faved'])) {
            $articleInfo['is_faved'] = $this->_uid ? Model_Stay_Fav::hasFaved($this->_uid, 2, $articleInfo['id']) : 0;
        }

        if (! isset($articleInfo['follow_relation'])) {
            $articleInfo['follow_relation'] = $this->_uid ? Model_Stay_Follow::isFollowed($this->_uid, $articleInfo['uid']) : 0;
        }

        if (! isset($articleInfo['is_self'])) {
            $articleInfo['is_self'] = $this->_uid && $articleInfo['uid'] == $this->_uid ? 1 : 0;
        }

        return $articleInfo;
    }

    // 装饰动态列表
    protected function _decoFeedList(array $feedList)
    {
        foreach ($feedList as &$feed) {
            // 体验
            if ($feed['target_type'] == 1) {
                $feed['target_info'] = $this->_decoExpInfo($feed['target_info']);
            }
            // 文章
            elseif ($feed['target_type'] == 2) {
                $feed['target_info'] = $this->_decoArticleInfo($feed['target_info']);
            }
            elseif ($feed['target_type'] == 602) {
                $feed['target_info'] = $this->_decoSpecialAd($feed['target_info']);
            }
            // 附近的人、明星用户
            elseif ($feed['target_type'] == 601 || $feed['target_type'] == 603) {
                $feed['target_info'] = $this->_decoUserListInAd($feed['target_info']);
            }
            // 拼接缩略图URL
            $feed = MyHelper_Thumb::decoThumbUrl($feed, [
                'FullWidthSquare'    => ['target_info' => ['cover_url']],
                'FullWidthRectangle' => ['target_info' => ['bg_img']],
                'AvatarSmall'        => ['target_info' => ['user_info' => ['headimgurl']]],
            ]);
        }

        return $feedList;
    }

    protected function _decoTopicInfo(array $topicInfo)
    {
        // 拼接缩略图URL
        $topicInfo = MyHelper_Thumb::decoThumbUrl($topicInfo, ['FullWidthRectangle' => ['cover_url']], false);
        $topicInfo = MyHelper_Thumb::decoThumbUrl($topicInfo, ['PhotoSmall' => ['cover_url']], false);

        // 高斯模糊背景大图
        $topicInfo['thumb_fullwidthrectangle_cover_url'] = Com_Qiniu::getBlurImgUrl($topicInfo['thumb_fullwidthrectangle_cover_url']);

        return $topicInfo;
    }

    // 装饰图文专题
    protected function _decoSpecialAd(array $info)
    {
        $info['able_like'] = 0;
        $info['like_target_info'] = [];

        // 当该图文链接的是文章
        if (strpos($info['url'], 'stayapp://article/') === 0) {

            // 截取文章ID,并获取文章信息
            $targetId = str_replace('stayapp://article/', '', $info['url']);
            $articleInfo = Model_Stay_Article::getInfo(__decrypt($targetId));

            $info['able_like'] = 1;
            $info['like_target_info'] = [
                'target_type'   => 2,
                'target_id'     => $targetId,
                'like_count'    => $articleInfo['like_count'],
                'comment_count' => $articleInfo['comment_count'],
            ];

            $info['like_target_info']['is_liked'] = $this->_uid ? Model_Stay_Like::hasLiked($this->_uid, $info['like_target_info']['target_type'], $this->decrypt($info['like_target_info']['target_id'])) : 0;
        }

        return $info;
    }

    // 装饰图文列表
    protected function _decoSpecialAds(array $list)
    {
        foreach ($list as &$info) {
            $info = self::_decoSpecialAd($info);
        }

        return $list;
    }

    // 装饰明星用户运营位
    protected function _decoUserListInAd(array $info)
    {
        if (! isset($info['user_list']) || ! $info['user_list']) {
            return $info;
        }

        foreach ($info['user_list'] as &$userInfo) {
            $userInfo['relation'] = $this->_uid ? Model_Stay_Follow::isFollowed($this->_uid, $userInfo['uid']) : 0;
            $userInfo['is_self'] = $this->_uid && $userInfo['uid'] == $this->_uid ? 1 : 0;
            $userInfo['uid'] = $this->encrypt($userInfo['uid']);
        }

        // 拼接缩略图URL
        $info['user_list'] = MyHelper_Thumb::decoThumbUrls($info['user_list'], ['AvatarBig' => 'headimgurl']);

        return $info;
    }

    protected function _encryptExp(array $info)
    {
        if (isset($info['liker_list'])) {
            $info['liker_list'] = $this->encryptIds($info['liker_list'], ['uid']);
        }

        return $this->encryptId($info, ['id', 'uid', 'user_info' => ['uid'], 'hotel_id', 'hotel_info' => ['id']]);
    }

    protected function _encryptExps(array $list)
    {
        return $this->encryptIds($list, ['id', 'uid', 'user_info' => ['uid'], 'hotel_id', 'hotel_info' => ['id']]);
    }

    protected function _encryptArticle(array $info)
    {
        if (isset($info['liker_list'])) {
            $info['liker_list'] = $this->encryptIds($info['liker_list'], ['uid']);
        }

        return $this->encryptId($info, ['id', 'uid', 'user_info' => ['uid']]);
    }

    protected function _encryptArticles(array $list)
    {
        return $this->encryptIds($list, ['id', 'uid', 'user_info' => ['uid']]);
    }

    protected function _encryptHotel(array $info)
    {
        return $this->encryptId($info, ['id', 'bloc_id', 'brand_id']);
    }

    protected function _encryptHotels(array $list)
    {
        return $this->encryptIds($list, ['id', 'bloc_id', 'brand_id']);
    }

    protected function _encryptTList(array $list)
    {
        return $this->encryptIds($list, ['uid', 'target_id']);
    }

    protected function _encryptFeedList(array $list)
    {
        return $this->encryptIds($list, ['id', 'target_id', 'target_info' => ['id', 'uid', 'user_info' => ['uid'], 'hotel_id', 'hotel_info' => ['id']]]);
    }
}