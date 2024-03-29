<?php
declare (strict_types=1);

namespace biwankaifa\redis;

use BadFunctionCallException;
use Redis as OriginalRedis;


class Redis
{
    /**
     * 类单例数组
     *
     * @var array
     */
    private static $instance = [];
    /**
     * redis连接句柄
     *
     * @var object
     */
    private $redis;
    /**
     * hash的key
     *
     * @var int
     */
    private $hash;

    /**
     * 私有化构造函数,防止类外实例化
     *
     * @param int $db_number
     */
    private function __construct($db_number)
    {
        if (!extension_loaded('redis')) {
            throw new BadFunctionCallException('not support: redis');      //判断是否有扩展
        }

        $db_number = (int)$db_number;
        $options=config('redis');
        $this->hash = $db_number;
        $this->redis = new OriginalRedis();
        $func = $options['persistent'] ? 'pconnect' : 'connect';     //长链接
        $this->redis->$func($options['host'], $options['port'], $options['timeout']);
        if ($options['password'] != "") {
            $this->redis->auth($options['password']);
        }
        $this->redis->select($db_number);
    }

    private function __clone()
    {
    }

    /**
     * 获取类单例
     *
     * @param ?int $db_number
     * @return OriginalRedis
     */
    public static function db($db_number=null)
    {
        if(is_null($db_number)){
            $db_number=config('redis.db');
        }
        if (!isset(self::$instance[(int)$db_number])) {
            self::$instance[(int)$db_number] = new self($db_number);
        }
        return self::$instance[(int)$db_number]->redis;
    }

    /**
     * 关闭单例时做清理工作
     */
    public function __destruct()
    {
        $key = $this->hash;
        self::$instance[$key]->redis->close();
        self::$instance[$key] = null;
        unset(self::$instance[$key]);
    }
}
