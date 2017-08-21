<?php
namespace mutex;
class Mutex{
	public static $dir='/var/lock/';
	public static function lock(string $name, int $timeout=0, int $pid=0, bool $recursive=false): bool{
		if(!$pid) $pid = posix_getpid();
		$ok = 0;
		$dir = static::dirName($name);
		try{
			$ok = @mkdir($dir);
		} catch (\Exception $ex) {
			;
		}
		if(!$ok && $timeout>0 && $pid && !$recursive && static::killZombie($name, $timeout))
			$ok = static::lock($name, $timeout, $pid, true);

		if($ok){
			file_put_contents("$dir/lock", "$pid:".time(), 2);
			register_shutdown_function(function() use ($name){Mutex::unlock($name);});
			return true;
		}else
			return false;
	}
	protected static function killZombie(string $name, int $timeout=0): bool{
		if($timeout<=0) return false;
		$dir = static::dirName($name);
		$meta = file_get_contents("$dir/lock");
		if($meta) {
			list($pid, $time) = explode(':', $meta);
			if($pid > 0 && $time + $timeout < time()) {
				#echo "killing zombie. fun\n";
				if(!posix_kill($pid, SIGKILL))
					@posix_kill($pid);
				static::unlock($name);
				return true;
			}
		}
		return false;
	}
	protected static function dirName(string $name): string{
		return static::$dir.md5($name);
	}
	public static function unlock(string $name): bool{
		$dir = static::dirName($name);
		unlink("$dir/lock");
		return rmdir($dir);
	}
}
