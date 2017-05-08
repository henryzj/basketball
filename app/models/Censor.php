<?php

/**
 * 敏感词过滤
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Model_Censor extends Core_Model_Abstract
{
    private $_censor;

    public function __construct()
    {
        // 获取敏感词库（一维数组）
        $keywords = Dao('Core_V2StaticConfigCensor')->getNames() ?: [];

        $this->_censor = new Com_Censor($keywords);
    }

    // 将敏感词过滤成星号
    public function filter($word)
    {
        return $this->_censor->filter($word);
    }

    // 检测是否包含敏感词
    public function verify($word)
    {
        $result = $this->_censor->filter($word);

        return strpos($result, '*') === false ? true : false;
    }

    // 检测并查找出文中所包含的敏感词
    public function check($word)
    {
        return $this->_censor->check($word);
    }
}