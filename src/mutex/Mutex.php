<?php

namespace mutex;

/**
 * Simple MutEx lock implementation based on directory creation
 * @package mutex
 */
class Mutex
{
    public static $dir = '/var/lock/';
    public static $autoUnlock = true;

    /**
     * Try to lock, execute something ONLY if lock returned true
     * @param string $name
     * @param int $timeout
     * @param int $pid
     * @param bool $recursive
     * @return bool
     */
    public static function lock($name, $timeout = 0, $pid = 0, $recursive = false)
    {
        if (!$pid) $pid = posix_getpid();
        $ok = 0;
        $dir = static::dirName($name);
        try {
            $ok = @mkdir($dir);
        } catch (\Exception $ex) {
            ;
        }
        if (!$ok && $timeout > 0 && $pid && !$recursive && static::killZombie($name, $timeout))
            $ok = static::lock($name, $timeout, $pid, true);

        if ($ok) {
            file_put_contents("$dir/lock", "$pid:" . time(), 2);
            if (static::$autoUnlock)
                register_shutdown_function(function () use ($name) {
                    Mutex::unlock($name);
                });
            return true;
        } else
            return false;
    }

    /**
     * @param string $name
     * @param int $timeout
     * @return bool
     */
    protected static function killZombie($name, $timeout = 0)
    {
        if ($timeout <= 0) return false;
        $dir = static::dirName($name);
        $meta = file_get_contents("$dir/lock");
        if ($meta) {
            list($pid, $time) = explode(':', $meta);
            if ($pid > 0 && $time + $timeout < time()) {
                #echo "killing zombie. fun\n";
                if (!posix_kill($pid, SIGKILL))
                    @posix_kill($pid);
                static::unlock($name);
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @return string
     */
    protected static function dirName($name)
    {
        return static::$dir . md5($name);
    }

    /**
     * Unlock
     * @param string $name
     * @return bool
     */
    public static function unlock($name)
    {
        $dir = static::dirName($name);
        unlink("$dir/lock");
        return rmdir($dir);
    }
}
