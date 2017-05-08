<?php

/**
 * Memcache 客户端封装
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Memcache.php 8039 2014-01-16 10:09:37Z jiangjian $
 */

class Com_Cache_Memcache
{
    /**
     * 连接实例
     *
     * @var object
     */
    private $_cache;

    /**
     * 是否使用 PECL::Memcached 扩展
     *
     * @var bool
     */
    private $_isMemcached = false;

    /**
     * 加载分组配置
     *
     * @param string $group
     * @throws Core_Exception_Fatal
     */
    public function __construct($group = 'default')
    {
        $config = Core_Config::loadEnv('memcache');

        if (! isset($config[$group])) {
            throw new Core_Exception_Fatal('没有找到 ' . $group . ' 分组的 Memcache 配置信息，请检查 memcache.conf.php');
        }

        if (! isset($config[$group]['servers'])) {
            throw new Core_Exception_Fatal('没有找到 ' . $group . ' 分组的 Memcache 服务器配置信息(servers)，请检查 memcache.conf.php');
        }

        // 使用 PECL::Memcached 扩展
        if (isset($config[$group]['class']) && strtolower($config[$group]['class']) == 'memcached' && extension_loaded('memcached')) {
            $this->_isMemcached = true;
        }

        $this->_config = $config[$group];
    }

    /**
     * 释放连接
     */
    public function __destruct()
    {
        if ($this->_cache && is_object($this->_cache)) {
            if (method_exists($this->_cache, 'close')) {
                $this->_cache->close();
            }
            $this->_cache = null;
        }
    }

    /**
     * 建立连接
     */
    protected function _connect()
    {
        if ($this->_cache !== null && is_object($this->_cache)) {
            return true;
        }

        // 使用 PECL::Memcached 扩展
        if ($this->_isMemcached) {

            $this->_cache = new Memcached();

            // 添加服务器
            $servers = [];
            foreach ($this->_config['servers'] as $server) {
                $server += array('host' => '127.0.0.1', 'port' => '11211', 'weight' => 1);
                $servers[] = array($server['host'], $server['port'], $server['weight']);
            }
            $this->_cache->addServers($servers);

            // 开启大值自动压缩（注意：开启后 append/prepend 方法失效）
            // $this->_cache->setOption(Memcached::OPT_COMPRESSION, true);

            // 可以用于为key创建“域”。这个值将会被作为每个key的前缀，它不能长于128个字符，
            // 并且将会缩短最大可允许的key的长度。这个前缀仅仅用于被存储的元素的key，而不会用于服务器key。
            $this->_cache->setOption(Memcached::OPT_PREFIX_KEY, DB_PREFIX);

            // 开启一致性哈希
            $this->_cache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);

            // 开启ketama算法兼容（注意：打开本算法时，sub_hash会使用KETAMA默认的MD5）
            $this->_cache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

            // 使用二进制协议（注意：使用SASL必须要开启本选项）
            if (isset($this->_config['sasl']) && $this->_config['sasl']) {
                $this->_cache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            }

            // 设置哈希算法，当不使用KETAMA算法时，这个设置才生效，有几种可选择，不过如果启用ketama，这个选项是没用的
            // $this->_cache->setOption(Memcached::OPT_HASH, Memcached::HASH_MD5);
            // $this->_cache->setOption(Memcached::OPT_HASH, Memcached::HASH_CRC);

            // 开启已连接socket的无延迟特性（在某些环境可能会带来速度上的提升）
            $this->_cache->setOption(Memcached::OPT_TCP_NODELAY, true);

            // 开启异步I/O。这将使得存储函数传输速度最大化。
            $this->_cache->setOption(Memcached::OPT_NO_BLOCK, true);

            // 需要密码连接
            if (isset($this->_config['user']) && $this->_config['user']) {
                if (! method_exists($this->_cache, 'setSaslAuthData')) {
                    throw new Core_Exception_Fatal('请重新编译 PECL::Memcached 扩展 --enable-memcached-sasl，并在 php.ini 中启用 memcached.use_sasl = 1');
                }
                $this->_cache->setSaslAuthData($this->_config['user'], $this->_config['pass']);
            }

        // 使用 PECL::Memcache 扩展
        } else {

            $this->_cache = new Memcache();

            foreach ($this->_config['servers'] as $server) {
                $server += array('host' => '127.0.0.1', 'port' => '11211', 'persistent' => true, 'weight' => 1);
                $this->_cache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight']);
            }

            // 开启大值自动压缩: 0.2表示20%压缩率
            // 使用该方法会忽略 set 时的 flag (MEMCACHE_COMPRESSED) 参数
            $this->_cache->setCompressThreshold(20000, 0.2);
        }
    }

    /**
     * 调用魔术方法（大多数会直接转移调用原生方法，少部分方法会重写）
     *
     * @param string $method
     * @param mixed $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $this->_connect();

        if (! $this->_isMemcached && in_array($method, ['get', 'delete'])) {
            $args[0] = DB_PREFIX . $args[0];
        }

        return call_user_func_array(array($this->_cache, $method), $args);
    }

    // PECL::Memcached cas get 专用
    // 因为 call_user_func_array 对引用参数的处理有问题
    public function getCas($key, $cacheCb, &$token)
    {
        $this->_connect();

        return $this->_cache->get($key, $cacheCb, $token);
    }

    public function set($key, $value, $ttl = 0)
    {
        return $this->_write('set', $key, $value, $ttl);
    }

    public function add($key, $value, $ttl = 0)
    {
        return $this->_write('add', $key, $value, $ttl);
    }

    public function replace($key, $value, $ttl = 0)
    {
        return $this->_write('replace', $key, $value, $ttl);
    }

    /**
     * 写入操作
     *
     * @param string $func set/add/replace
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return bool
     */
    private function _write($func, $key, $value, $ttl = 0)
    {
        $this->_connect();

        if ($this->_isMemcached) {
            return $this->_cache->$func($key, $value, $ttl);
        } else {
            return $this->_cache->$func(DB_PREFIX . $key, $value, MEMCACHE_COMPRESSED, $ttl);
        }
    }

    /**
     * 批量读
     *
     * @param array $keys
     * @param int $flag
     * @return array
     */
    public function getMulti(array $keys, $flag = 0)
    {
        $this->_connect();

        if ($this->_isMemcached) {
            return $this->_cache->getMulti($keys, $flag);
        }
        else {
            $return = [];
            foreach ($keys as $key) {
                $result = $this->get($key);
                if ($result !== false) {
                    $return[$key] = $result;
                }
            }
            return $return;
        }
    }

    /**
     * 批量写
     *
     * @param array $items
     * @param int $ttl
     * @return bool
     */
    public function setMulti(array $items, $ttl = 0)
    {
        $this->_connect();

        if ($this->_isMemcached) {
            return $this->_cache->setMulti($items, $ttl);
        }
        else {
            foreach ($items as $key => $value) {
                $this->set($key, $value, $ttl);
            }
            return true;
        }
    }

    /**
     * 按标签保存（方便统一管理）
     *
     * @param string $key
     * @param string $value
     * @param string $tag
     * @param int $ttl
     * @return bool
     */
    public function setByTag($key, $value, $tag, $ttl = 0)
    {
        $this->_connect();

        $this->set($key, $value, $ttl);
        $keys = $this->get($tag);

        if (! empty($keys) && is_array($keys)) {
            $keys[] = $key;
            $keys = array_unique($keys);
        } else {
            $keys = array($key);
        }

        return $this->set($tag, $keys, $ttl);
    }

    /**
     * 按标签删除（方便统一管理）
     *
     * @param string $tag
     * @param int $ttl 延时删除
     * @return bool
     */
    public function deleteByTag($tag, $ttl = 0)
    {
        $this->_connect();

        $keys = $this->get($tag);

        if (! empty($keys) && is_array($keys)) {
            foreach ($keys as $key) {
                $this->delete($key, $ttl);
            }
        }

        return $this->delete($tag, $ttl);
    }

    /**
     * 自增（注意：只能操作无符号数，不能为负数）
     *
     * @param string $key
     * @param int $step 步长
     * @param int $ttl 初次设置的过期时间
     * @return increment
     */
    public function increment($key, $step = 1, $ttl = 0)
    {
        $this->_connect();

        if ($this->_isMemcached) {
            $result = $this->_cache->increment($key, $step);
        } else {
            $result = $this->_cache->increment(DB_PREFIX . $key, $step);
        }

        if ($result === false) {
            if ($this->set($key, $step, $ttl)) {
                return $step;
            }
        }

        return $result;
    }
}