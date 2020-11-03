<?php
declare(strict_types = 1);

namespace App\Component;

use App\Helper\ArrayHelper;
use Hyperf\Redis\RedisProxy;

class ClientManager
{

    public const HASH_UID_TO_FD_PREFIX = 'hash.socket_user_fd';

    public const HASH_FD_TO_UID_PREFIX = 'hash.socket_fd_user';

    public const ZSET_IP_TO_UID = 'zset.ip_to_uid_bind';

    /**
     *存储fd,ip,uid
     *
     * @param RedisProxy  $redis
     * @param string      $uid
     * @param int         $fd
     */
    public static function put(RedisProxy $redis, string $uid, int $fd)
    {
        //bind key to fd
        $redis->hSet(self::HASH_UID_TO_FD_PREFIX, $uid, $fd);
        $redis->hSet(self::HASH_FD_TO_UID_PREFIX, $fd, $uid);
    }

    /**
     * 删除对应关系
     *
     * @param RedisProxy  $redis
     * @param string      $uid
     * @param null|int    $fd
     */
    public static function del(RedisProxy $redis, string $uid, int $fd = null)
    {
        //del key to fd
        $redis->hDel(self::HASH_UID_TO_FD_PREFIX, $uid);
        $redis->hDel(self::HASH_FD_TO_UID_PREFIX, $fd);
    }

    public static function disconnect(RedisProxy $redis, int $fd)
    {
        $uid = $redis->hGet(self::HASH_FD_TO_UID_PREFIX, $fd);
        if (empty($uid)) {
            return;
        }
        self::del($redis, $uid, $fd);
    }

    /**
     * @param \Redis $redis
     * @param string $uid
     *
     * @return null|int
     */
    public static function fd(\Redis $redis, string $uid)
    {
        return (int)$redis->hGet(self::HASH_UID_TO_FD_PREFIX, $uid) ?? null;
    }

    /**
     * @param RedisProxy $redis
     * @param array      $uids
     *
     * @return array
     */
    public static function fds(RedisProxy $redis, array $uids = [])
    {
        if (empty($uids)) {
            return [];
        }
        return ArrayHelper::multiArrayValues($redis->hMGet(self::HASH_UID_TO_FD_PREFIX, $uids) ?? []);
    }

    /**
     * @param RedisProxy $redis
     * @param int        $fd
     *
     * @return null|string
     */
    public static function key(RedisProxy $redis, int $fd)
    {
        return $redis->hGet(self::HASH_FD_TO_UID_PREFIX, $fd) ?? null;
    }

    /**
     * @param RedisProxy  $redis
     * @param null|string $ip
     *
     * @return array|void
     */
    public static function getIpUid(RedisProxy $redis, string $ip = null)
    {
        if (empty($ip)) {
            return;
        }
        return $redis->sMembers(sprintf('%s.%s', self::ZSET_IP_TO_UID, $ip));
    }

    /**
     * @param \Hyperf\Redis\RedisProxy $redis
     * @param int                      $uid
     *
     * @return false|string
     */
    public static function isOnline(RedisProxy $redis, int $uid)
    {
        return $redis->hGet(self::HASH_UID_TO_FD_PREFIX, $uid) ?? false;
    }

}

