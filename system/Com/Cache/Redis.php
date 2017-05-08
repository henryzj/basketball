<?php

/**
 * Redis 封装类
 *
 * @author JiangJian <silverd@sohu.com>
 * @uses https://github.com/phpredis/phpredis
 */

class Com_Cache_Redis
{
    /**
     * 连接实例
     *
     * @var new Redis()
     */
    private $_redis;

    /**
     * 缺省配置
     *
     * @var array
     */
    private $_config = array(
        'host'       => '127.0.0.1',
        'port'       => '6379',
        'database'   => 0,
        'timeout'    => 0,
        'persistent' => true,
        'options'    => array(),
    );

    /**
     * 加载分组配置
     *
     * @param string $group
     * @throws Core_Exception_Fatal
     */
    public function __construct($group = 'default')
    {
        $config = Core_Config::loadEnv('redis');

        if (! isset($config[$group])) {
            throw new Core_Exception_Fatal('没有找到 ' . $group . ' 分组的 Redis 配置信息，请检查 redis.conf.php');
        }

        $this->_config = $config[$group] + $this->_config;
    }

    /**
     * 释放连接
     */
    public function __destruct()
    {
        if ($this->_redis && is_object($this->_redis)) {

            if (method_exists($this->_redis, 'close')) {
                $this->_redis->close();
            } elseif (method_exists($this->_redis, 'quit')) {
                $this->_redis->quit();
            }

            $this->_redis = null;
        }
    }

    /**
     * 建立连接
     *
     * @return bool
     */
    private function _connect()
    {
        if ($this->_redis === null || ! is_object($this->_redis)) {

            $this->_redis = new Redis();

            $func = $this->_config['persistent'] ? 'pconnect' : 'connect';
            $this->_redis->$func($this->_config['host'], $this->_config['port'], $this->_config['timeout']);

            // 附加参数
            if ($this->_config['options']) {
                foreach ($this->_config['options'] as $key => $value) {
                    $this->_redis->setOption($key, $value);
                }
            }

            // 需要密码认证
            if (isset($this->_config['auth']) && $this->_config['auth']) {
                if (! $this->_redis->auth($this->_config['auth'])) {
                    throw new Core_Exception_Fatal($this->_redis->getLastError());
                }
            }
        }

        // 选择数据库
        $database = isset($this->_config['database']) ? $this->_config['database'] : 0;
        $this->_redis->select($database);
    }

    public function set($key, $value, $ttl = 0)
    {
        $this->_connect();

        if ($ttl) {
            return $this->_redis->setEx($key, $ttl, $value);
        }

        return $this->_redis->set($key, $value);
    }

    public function add($key, $value, $ttl = 0)
    {
        $this->_connect();

        if ($result = $this->_redis->setNx($key, $value)) {
            if ($ttl) {
                return $this->_redis->expire($key, $ttl);
            }
        }

        return $result;
    }

    public function replace($key, $value, $ttl = 0)
    {
        $this->_connect();

        if (! $this->_redis->exists($key)) {
            return false;
        }

        return $this->set($key, $value, $ttl);
    }

    /**
     * 调用魔术方法
     *
     * @param string $method
     * @param mixed $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $this->_connect();

        return call_user_func_array(array($this->_redis, $method), $args);
    }

    /**
     * 自动裁剪列表
     * 例如：动态列表
     *
     * @param string $listName
     * @param int $maxLength
     * @param string $contentKeyPrefix 相关联的内容key前缀
     * @return void
     */
    public function autoTrimList($listName, $maxLength, $contentKeyPrefix = null)
    {
        $this->_connect();

        // 获取需要删除的 msgIds
        if (! $msgIdsToCut = $this->_redis->lRange($listName, $maxLength, -1)) {
            return false;
        }

        // 同时删除相关联的内容数据
        if ($contentKeyPrefix) {
            foreach ($msgIdsToCut as $msgId) {
                $this->_redis->del($contentKeyPrefix . $msgId);
            }
        }

        // 裁剪列表
        return $this->_redis->lTrim($listName, 0, $maxLength - 1);
    }
}