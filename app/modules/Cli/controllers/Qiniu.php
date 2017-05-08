<?php

/**
 * 七牛相关脚本
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Controller_Qiniu extends Core_Controller_Cli
{
    // 备份指定空间的资源到本地
    public function backupAction()
    {
        $bucket = $this->getx('bucket');

        if (! isset($GLOBALS['_QINIU_BUCKETS'][$bucket])) {
            exit('invalid bucket');
        }

        $dnHost = $GLOBALS['_QINIU_BUCKETS'][$bucket];

        $bkSaveDir = WEB_PATH . 'qiniu_backup/' . $bucket;
        @mkdir($bkSaveDir, 0777, true);

        $ok = 0;
        $pageSize = 20;

        // 获取下次的标志
        $cacheKey = 'stay_qiniu_bucket:' . $bucket . ':marker';

        while (1) {
            $marker = F('Redis')->default->get($cacheKey) ?: null;
            $result = Com_Qiniu::listFiles($bucket, $marker, $pageSize);

            if (! $result['items']) {
                break;
            }

            // 说明还有数据没有转存完成
            if (! $result['marker']) {
                break;
            }

            F('Redis')->default->set($cacheKey, $result['marker']);
            // 将这些数据转存到阿里云
            // TODO

            // 暂时将数据存到本地
            foreach ($result['items'] as $item) {
                if ($item['key']) {
                    $fileStream = file_get_contents($dnHost . '/' . $item['key']);
                    file_put_contents($bkSaveDir . '/' . $item['key'], $fileStream);
                    $ok++;
                }
            }
        }

        $logs = '成功备份：' . $ok;

        $this->log(__METHOD__, $logs);

        exit($logs);
    }

    /**
     * 将微信头像存储到七牛
     *
     * @author zhengjiang
     */
    public function updateAvatarUrlAction()
    {
        $pageSize = 100;
        $page = 1;
        $ok = 0;

        while (1) {

            $start = ($page - 1) * $pageSize;
            $list = Dao('Core_UserIndex')->field('uid, headimgurl')->limit($start, $pageSize)->fetchPairs();

            if (! $list) {
                break;
            }

            foreach ($list as $uid => $headimgurl) {
                // 将第三方头像地址转存到七牛（防止第三方平台图片过期）
                try {
                    if (false !== strpos($headimgurl, 'http://wx.qlogo.cn/')) {
                        $imgKey = Com_Qiniu::fetchSaveRemote($headimgurl);
                        $newHeadimgurl = QINIU_DEF_DNHOST . '/'. $imgKey;

                        Dao('Core_UserIndex')->updateByPk(['headimgurl' => $newHeadimgurl], $uid);
                        Dao('Ucenter_AccountInfo')->updateByPk(['headimgurl' => $newHeadimgurl], $uid);
                        $ok++;
                    }

                } catch (Exception $e) {
                    continue;
                }
            }

            $page++;
        }

        exit('Done:' . $ok);
    }

    /**
     * 将指定表指定字段的外链图片转存到七牛
     * 特别注明：%2f是“/”经过urlencode的编码。例如要加的前缀是“homepage/” => “homepage%2f”
     * @author zhengjiang
     *
     * @sample: /usr/bin/php /home/wwwroot/69night/balloon/cli.php request_uri=/cli/qiniu/updateImgUrl/db/stay_core/tbl/v2_static_hotel_brand/fields/logo_url,cover_url/bucket/silverd/prefix/homepage%2f
     */
    public function updateImgUrlAction()
    {
        $dbName = $this->getx('db');
        $table  = $this->getx('tbl');
        $fields = xexplode($this->getx('fields'));
        $bucket = $this->getx('bucket');
        $prefix = urldecode($this->getx('prefix'));

        if (! $dbName) {
            throws('plz input dbName');
        }

        if (! $table) {
            throws('plz input tableName');
        }

        if (! $fields) {
            throws('plz input fields');
        }

        if (! $bucket && ! isset($GLOBALS['_QINIU_BUCKETS'][$bucket])) {
            throws('plz input bucket');
        }
        else {
            $dnHost = $GLOBALS['_QINIU_BUCKETS'][$bucket];
        }

        $fieldArr = [];
        foreach ($fields as $field) {
            $fieldArr[] = "`" . $field . "`";
        }

        $fieldSql = implode(',', $fieldArr);

        $db = Com_DB::get($dbName);

        $i = 0;
        $ok = 0;
        $page = 1;
        $pageSize = 100;

        while (1) {

            $start = ($page - 1) * $pageSize;

            $sql = "SELECT " . $fieldSql . ", `id` FROM `" . $table . "` LIMIT " . $start . "," . $pageSize;

            $list = $db->fetchAll($sql);

            if (! $list) {
                break;
            }

            foreach ($list as $info) {

                $setSql = $comma = '';

                try {

                    foreach ($fields as $field) {

                        if (! empty($info[$field]) && false === strpos($info[$field], $dnHost)) {

                            $fileName = null;

                            // 自定义文件名前缀
                            if ($prefix) {
                                $fileName = $prefix . md5(uniqid(($i++) . mt_rand(10000, 99999)));
                            }

                            $imgKey = Com_Qiniu::fetchSaveRemote($info[$field], $bucket, $fileName);
                            $imgUrl = $dnHost . '/'. $imgKey;
                            $setSql .= $comma . "`" . $field . "` = '{$imgUrl}' ";
                            $comma = ', ';
                        }
                    }

                    if ($setSql) {
                        $updateSql = "UPDATE " . $table . " SET " . $setSql . "WHERE `id` = " . $info['id'];
                        if ($db->query($updateSql)) {
                            $ok++;
                        }
                    }

                } catch (Exception $e) {
                    echo 'ID=' . $info['id'] . PHP_EOL;
                    print($e) . PHP_EOL;
                    continue;
                }
            }

            $page++;
        }

        exit('DONE:' . $ok);
    }
}