<?php /** @noinspection PhpUndefinedConstantInspection */
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUndefinedNamespaceInspection */

/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace mgboot\swoole;

use Closure;
use mgboot\Cast;
use mgboot\util\StringUtils;
use Throwable;

final class Swoole
{
    /**
     * @var mixed
     */
    private static $server = null;

    /**
     * @var array
     */
    private static $tcpClientSettings = [
        'connect_timeout' => 2.0,
        'write_timeout' => 5.0,
        'read_timeout' => 300.0,
        'open_eof_check' => true,
        'package_eof' => '@^@end',
        'package_max_length' => 8 * 1024 * 1024
    ];

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @param mixed $server
     */
    public static function setServer($server): void
    {
        self::$server = $server;
    }

    /**
     * @return mixed
     */
    public static function getServer()
    {
        return self::$server;
    }

    /**
     * @param mixed $arg0
     * @return bool
     */
    public static function isSwooleHttpRequest($arg0): bool
    {
        if (!is_object($arg0)) {
            return false;
        }

        return StringUtils::endsWith(get_class($arg0), "Swoole\\Http\\Request");
    }

    /**
     * @param mixed $arg0
     * @return bool
     */
    public static function isSwooleHttpResponse($arg0): bool
    {
        if (!is_object($arg0)) {
            return false;
        }

        return StringUtils::endsWith(get_class($arg0), "Swoole\\Http\\Response");
    }

    public static function getWorkerId(): int
    {
        $server = self::$server;

        if (!is_object($server) || !property_exists($server, 'worker_id')) {
            return -1;
        }

        $workerId = Cast::toInt($server->worker_id);
        return $workerId >= 0 ? $workerId : -1;
    }

    public static function inTaskWorker(): bool
    {
        $workerId = self::getWorkerId();

        if ($workerId < 0) {
            return false;
        }

        $server = self::$server;

        if (!is_object($server) || !property_exists($server, 'taskworker')) {
            return false;
        }

        return Cast::toBoolean($server->taskworker);
    }

    public static function getCoroutineId(): int
    {
        try {
            $cid = \Swoole\Coroutine::getCid();
            return is_int($cid) && $cid >= 0 ? $cid : -1;
        } catch (Throwable $ex) {
            return -1;
        }
    }

    public static function inCoroutineMode(bool $notTaskWorker = false): bool
    {
        if (self::getCoroutineId() < 0) {
            return false;
        }

        return !$notTaskWorker || !self::inTaskWorker();
    }

    public static function newTcpClient(string $host, int $port, ?array $settings = null): \Swoole\Coroutine\Client
    {
        if (empty($settings)) {
            $settings = self::$tcpClientSettings;
        } else {
            $settings = array_merge(self::$tcpClientSettings, $settings);
        }

        $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $client->set($settings);
        $client->connect($host, $port);
        return $client;
    }

    /**
     * @param mixed $client
     * @param string $contents
     */
    public static function tcpClientSend($client, string $contents): void
    {
        if ($client instanceof \Swoole\Coroutine\Client) {
            $client->send($contents);
        }
    }

    /**
     * @param mixed $client
     * @param float|null $timeout
     * @return mixed
     */
    public static function tcpClientRecv($client, ?float $timeout = null)
    {
        if ($client instanceof \Swoole\Coroutine\Client) {
            return $client->recv($timeout);
        }

        return null;
    }

    /**
     * @param mixed $client
     * @return bool
     */
    public static function tcpClientIsConnected($client): bool
    {
        if ($client instanceof \Swoole\Coroutine\Client) {
            $client->isConnected();
        }

        return false;
    }

    /**
     * @param mixed $client
     */
    public static function tcpClientClose($client): void
    {
        if ($client instanceof \Swoole\Coroutine\Client) {
            $client->close();
        }
    }

    /**
     * @return \Swoole\Coroutine\WaitGroup
     */
    public static function newWaitGroup(): \Swoole\Coroutine\WaitGroup
    {
        return new \Swoole\Coroutine\WaitGroup();
    }

    /**
     * @param int|null $value
     * @return \Swoole\Atomic
     */
    public static function newAtomic(?int $value = null): \Swoole\Atomic
    {
        return is_int($value) && $value > 0 ? new \Swoole\Atomic($value) : new \Swoole\Atomic();
    }

    /**
     * @param mixed $atomic
     * @return mixed
     */
    public static function atomicGet($atomic)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->get();
        }

        return null;
    }

    /**
     * @param mixed $atomic
     * @param int $value
     * @return mixed
     */
    public static function atomicSet($atomic, int $value)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->set($value);
        }

        return null;
    }

    /**
     * @param mixed $atomic
     * @param int $value
     * @return mixed
     */
    public static function atomicAdd($atomic, int $value)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->add($value);
        }

        return null;
    }

    /**
     * @param mixed $atomic
     * @param int $value
     * @return mixed
     */
    public static function atomicSub($atomic, int $value)
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->sub($value);
        }

        return null;
    }

    /**
     * @param mixed $atomic
     * @param int $cmpValue
     * @param int $setValue
     * @return bool
     */
    public static function atomicCompareAndSet($atomic, int $cmpValue, int $setValue): bool
    {
        if ($atomic instanceof \Swoole\Atomic) {
            return $atomic->cmpset($cmpValue, $setValue);
        }

        return false;
    }

    /**
     * @param Closure $fn
     */
    public static function defer(Closure $fn): void
    {
        \Swoole\Coroutine::defer($fn);
    }

    /**
     * @param callable $call
     * @param mixed ...$args
     */
    public static function runInCoroutine(callable $call, ...$args): void
    {
        \Swoole\Coroutine\run($call, ...$args);
    }

    /**
     * @param int $ms
     * @param callable $call
     * @param mixed ...$args
     * @return int
     */
    public static function timerTick(int $ms, callable $call, ...$args): int
    {
        $id = \Swoole\Timer::tick($ms, $call, ...$args);
        return Cast::toInt($id);
    }

    public static function timerClear(int $timerId): void
    {
        if ($timerId < 0) {
            return;
        }

        \Swoole\Timer::clear($timerId);
    }

    public static function newChannel(?int $size = null): \Swoole\Coroutine\Channel
    {
        return new \Swoole\Coroutine\Channel($size);
    }

    /**
     * @param mixed $ch
     * @return bool
     */
    public static function chanIsEmpty($ch): bool
    {
        if ($ch instanceof \Swoole\Coroutine\Channel) {
            return $ch->isEmpty();
        }

        return true;
    }

    /**
     * @param mixed $ch
     * @param mixed $data
     * @param float|null $timeout
     */
    public static function chanPush($ch, $data, ?float $timeout = null): void
    {
        if ($ch instanceof \Swoole\Coroutine\Channel) {
            $ch->push($data, $timeout);
        }
    }

    /**
     * @param mixed $ch
     * @param float|null $timeout
     * @return mixed
     */
    public static function chanPop($ch, ?float $timeout = null)
    {
        if ($ch instanceof \Swoole\Coroutine\Channel) {
            return $ch->pop($timeout);
        }

        return null;
    }

    public static function sleep(float $seconds): void
    {
        \Swoole\Coroutine::sleep($seconds);
    }

    public static function buildGlobalVarKey(?int $workerId = null): string
    {
        if (!is_int($workerId) || $workerId < 0) {
            $workerId = self::getWorkerId();
        }

        return $workerId >= 0 ? "worker$workerId" : 'noworker';
    }
}
