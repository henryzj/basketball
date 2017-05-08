<?php

/**
 * 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Abstract.php 11672 2014-07-09 09:29:49Z jiangjian $
 */

abstract class Core_Controller_Abstract extends Yaf_Controller_Abstract
{
    /**
     * 用于生成 formHash 以及URL加密的用户唯一值
     * 可以是 uid/user_code
     *
     * @var string
     */
    protected $_uniqUserKey = null;

    public function init()
    {
        Yaf_Registry::set('controller', $this);

        // 兼容 RAW-JSON
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
            if ($params = file_get_contents('php://input')) {
                $params = json_decode($params, true);
                $params && $this->_request->setParam($params);
            }
        }

        // 支持 GET/POST 方式传递 session_id
        if ($sessionId = $this->getx(session_name())) {
            session_id($sessionId);
        }
    }

    public function get($key)
    {
        return $this->_request->get($key);
    }

    public function getx($key)
    {
        $value = $this->_request->get($key);
        return Helper_String::deepFilterDatasInput($value);
    }

    public function getz($key)
    {
        return addslashes(strip_tags(trim($this->get($key))));
    }

    public function getInt($key)
    {
        return intval($this->_request->get($key));
    }

    public function getInts($key)
    {
        $value = $this->get($key);
        return is_array($value) ? array_filter(array_map('intval', $value)) : intval($value);
    }

    public function getBool($key)
    {
        return $this->_request->get($key) ? true : false;
    }

    public function isGet()
    {
        return $this->_request->isGet();
    }

    public function isPost()
    {
        return $this->_request->isPost();
    }

    public function isSubmit()
    {
        return $this->isPost() || $this->getQuery('submit');
    }

    public function isAjax()
    {
        return $this->_request->isXmlHttpRequest() || $this->getBool('is_ajax');
    }

    public function isPjax()
    {
        return isset($_SERVER['HTTP_X_PJAX']) ? $_SERVER['HTTP_X_PJAX'] : false;
    }

    public function getQuery($key = null, $default = null)
    {
        if (null === $key && null === $default) {
            return $this->_request->getQuery();
        }
        else {
            return $this->_request->getQuery($key, $default);
        }
    }

    public function getPost($key = null, $default = null)
    {
        if (null === $key && null === $default) {
            return $this->_request->getPost();
        }
        else {
            return $this->_request->getPost($key, $default);
        }
    }

    public function getQueryx($key = null, $default = null)
    {
        return Helper_String::deepFilterDatas($this->getQuery($key, $default), array('strip_tags', 'trim'));
    }

    public function getPostx($key = null, $default = null)
    {
        return Helper_String::deepFilterDatas($this->getPost($key, $default), array('strip_tags', 'trim'));
    }

    public function getParam($key = null, $default = null)
    {
        if (null === $key && null === $default) {
            return $this->_request->getParams();
        }
        else {
            return $this->_request->getParam($key, $default);
        }
    }

    public function getParams()
    {
        return $this->_request->getParams();
    }

    public function getParamsx()
    {
        return Helper_String::deepFilterDatas($this->getParams(), array('strip_tags', 'trim'));
    }

    // $_GET + $_POST
    public function getQueryPostx($key = null, $default = null)
    {
        return array_merge($this->getQueryx($key, $default), $this->getPostx($key, $default));
    }

    public function getBaseUri()
    {
        return '/' . lcfirst($this->_request->getControllerName()) . '/' . $this->_request->getActionName();
    }

    public function isBaseUri($uris)
    {
        if (! is_array($uris)) {
            $uris = array($uris => 1);
        }

        $baseUri = strtolower(str_replace('-', '', $this->getBaseUri()));

        return isset($uris[$baseUri]) ? true : false;
    }

    public function isActions($actions)
    {
        if (! is_array($actions)) {
            $actions = array($actions);
        }

        $actions = array_map('strtolower', $actions);

        return in_array($this->_request->getActionName(), $actions) ? true : false;
    }

    // 设置当前语言环境
    public function setLocale($lang = null)
    {
        if (null === $lang) {
            $lang = $this->getLocale();
        }

        // 设置环境变量
        putenv('LANG=' . $lang);
        putenv('LC_ALL=' . $lang);

        // 设置场景信息
        setlocale(LC_ALL, $lang);

        // 设置要绑定的语言包的目录
        bindtextdomain(CUR_TEXT_DOMAIN, LOCALE_PATH);
        bind_textdomain_codeset(CUR_TEXT_DOMAIN, 'UTF-8');

        // 设置默认的包
        textdomain(CUR_TEXT_DOMAIN);

        // 当前语言版本
        define('CUR_LANG', $lang);

        // CSS存放目录（全局）
        define('PUBLIC_CSS_DIR', CDN_PATH . 'public/css');

        // CSS存放目录（当前语言版本）
        define('LOCALE_CSS_DIR', CDN_PATH . CUR_LANG . '/css');

        // 图片存放目录（全局）
        define('PUBLIC_IMG_DIR', CDN_PATH . 'public/img');

        // 图片存放目录（当前语言版本）
        define('LOCALE_IMG_DIR', CDN_PATH . CUR_LANG . '/img');

        // JS存放目录（全局）
        define('PUBLIC_JS_DIR',  CDN_PATH . 'public/js');

        // JS存放目录（当前语言版本）
        define('LOCALE_JS_DIR',  CDN_PATH . CUR_LANG . '/js');
    }

    // 获取当前语言
    public function getLocale()
    {
        // 优先从URL参数中读取
        if ($curLang = $this->getQueryx('lang')) {
            F('Cookie')->set('__lang', $curLang);
        }
        // 如果没有则从cookie中读取
        else {
            $curLang = F('Cookie')->get('__lang');
        }

        $supportLangs = explode(',', SUPPORT_LANGS);

        if (! $curLang || ! in_array($curLang, $supportLangs)) {
            $curLang = $supportLangs[0];
        }

        return $curLang;
    }

    public function json($output)
    {
        header('Content-type: text/json');
        header('Content-type: application/json; charset=UTF-8');
        exit(json_encode($output, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 用于 AJAX 响应输出 JSON
     *
     * @param string $msg
     * @param string $resultType success|error|warnings|tips
     * @param array $extra
     * @param bool $obClean 是否先清除之前的缓冲区
     */
    public function jsonx($msg, $resultType = 'success', array $extra = array(), $obClean = false)
    {
        // 清除之前的缓冲区，防止多余输出
        $obClean && ob_clean();

        if ($globalExtra = Yaf_Registry::get('jsonRespExtra')) {
            $extra = array_merge($globalExtra, $extra);
        }

        $output = array('msg' => $msg, 'status' => $resultType);

        if ($extra) {
            $output['extra'] = $extra;
        }

        $this->json($output);
    }

    public function setJsonExtra(array $extra)
    {
        return Yaf_Registry::set('jsonRespExtra', $extra);
    }

    /**
     * 获取当前用户盐
     *
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function getSalt($extraKey = null)
    {
        // 今天凌晨5点以后~第二天凌晨5点前
        // 日期标示符为当天
        if (date('G') >= 5) {
            $dateString = date('md');
        }
        // 今天凌晨5点前
        // 日期标示符仍用昨天的
        else {
            $dateString = date('md', strtotime('-1 day'));
        }

        return md5('VoyageYAF:' . $dateString . ':' . $this->_uniqUserKey . ':' . $extraKey);
    }

    /**
     * 加密一个字符串
     *
     * @param string $content 待加密内容
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function encrypt($content, $extraKey = null)
    {
        return Helper_Cryption_Rijndael::encrypt($content, $this->getSalt($extraKey));
    }

    /**
     * 加密一维数组的值
     *
     * @param array $array 待加密的数组
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encrypts(array $array, $extraKey = null)
    {
        if ($array) {
            foreach ($array as &$value) {
                $value = $this->encrypt($value, $extraKey);
            }
        }

        return $array;
    }

    /**
     * 解密一个字符串
     *
     * @param string $content 待解密内容
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function decrypt($content, $extraKey = null)
    {
        return Helper_Cryption_Rijndael::decrypt($content, $this->getSalt($extraKey));
    }

    /**
     * 加密指定一行的某些字段
     *
     * @param array/object $row
     * @param string/array $idFields 待加密字段名，可设多个
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encryptId($row, $idFields = 'id', $extraKey = null, $overwrite = true)
    {
        if ($row) {
            foreach ((array) $idFields as $key => $field) {
                if (is_array($field)) {
                    if (isset($row[$key]) && is_array($row[$key])) {
                        $row[$key] = $this->encryptId($row[$key], $field, $extraKey, $overwrite);
                    }
                }
                else {
                    if (isset($row[$field])) {
                        $result = $this->encrypt($row[$field], $extraKey);
                        if ($overwrite) {
                            // 把加密后的数据覆盖原字段值
                            $row[$field] = $result;
                        } else {
                            // 不覆盖原字段值，增加前置双下划线以区分
                            $row['__' . $field] = $result;
                        }
                    }
                }
            }
        }

        return $row;
    }

    /**
     * 批量加密指定列表的某些字段
     *
     * @param array $list
     * @param string/array $idFields 待加密字段名，可设多个
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encryptIds($list, $idFields = 'id', $extraKey = null, $overwrite = true)
    {
        if ($list) {
            foreach ($list as &$row) {
                $row = $this->encryptId($row, $idFields, $extraKey, $overwrite);
            }
        }

        return $list;
    }

    /**
     * 批量加密指定列表的KEY下标
     *
     * @param array $list
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encryptKeys($list, $extraKey = null)
    {
        $newList = array();

        if ($list) {
            foreach ($list as $key => $value) {
                $__key = $this->encrypt($key, $extraKey);
                $newList[$__key] = $value;
            }
        }

        return $newList;
    }

    /**
     * 实时加密URL中的指定参数（们）
     * URL格式为: http://...../?id=[encrypt:原始ID]
     *
     * @param string $url
     * @return string
     */
    public function encryptUrl($url)
    {
        if (strpos($url, '[encrypt:') === false) {
            return $url;
        }

        return preg_replace_callback('/\[encrypt\:(.+?)\]/', array($this, '_encryptUrlCallback'), $url);
    }

    protected function _encryptUrlCallback($matches)
    {
        return $matches ? rawurlencode($this->encrypt($matches[1])) : '';
    }

    public function getDx($key, $extraKey = null)
    {
        if (! $value = $this->getx($key)) {
            return null;
        }

        $value = $this->decrypt($value, $extraKey);

        // header('X-Rijndael-' . $key . ':' . $value);

        return $value;
    }

    public function getDxs($key)
    {
        if (! $value = $this->get($key)) {
            return null;
        }

        $values = is_array($value) ? array_filter(array_map(array($this, 'decrypt'), $value)) : $this->decrypt($value);

        // header('X-Rijndael-' . $key . ':' . implode(',', $values));

        return $values;
    }
}