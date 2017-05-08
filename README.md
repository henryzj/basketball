## basketball

基于 Yaf (2.2.9 Stable) 开发 <http://pecl.php.net/package/yaf/>
框架源码：<https://github.com/silverd/voyage-yaf/>

#### 框架说明

- 严格遵循KISS原则和PSR-0/1/2标准
- 完善的懒人机制，无文档、快速上手、专注开发
- 数据库主从读写分离
- DAO 透明缓存层
- NoSQL 缓存组件的封装
- 队列组件 Memcache/RedisQ
- 支持多语言国际化的封装 Po 文件
- 可集成 FirePHP/XPhrof 进行调试

#### 框架需求

- Memcached [点击获取](http://www.memcached.org/files/memcached-1.4.22.tar.gz)
- Redis [点击获取](http://download.redis.io/releases/redis-2.8.19.tar.gz)
- PHP 5.5+
    - PECL-Redis 扩展 (./configure --enable-redis-igbinary) [点击获取](https://github.com/phpredis/phpredis)
    - PECL-Memcached 扩展 (需要 [libmemcached-1.0.18](https://launchpad.net/libmemcached/1.0/1.0.18/+download/libmemcached-1.0.18.tar.gz)) [点击获取](http://pecl.php.net/get/memcached-2.2.0.tgz)
    - PECL-IgBinary 扩展 [点击获取](https://pecl.php.net/get/igbinary-1.2.1.tgz)

***

#### 编码规范

严格遵守 PSR-0/1/2 代码规范 <http://www.php-fig.org>

- PSR-0 Autoloading Standard
- PSR-1 Basic Coding Standard
- PSR-2 Code Style Guide