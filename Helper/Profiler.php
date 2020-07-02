<?php
/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Helper;

/**
 * Class Product
 * @package Unbxd\ProductFeed\Helper
 */
class Profiler
{
    /**
     * #@+ Profiler constants
     */
    const DEFAULT_TIMER_ID = 'feed_execute';
    const ACCEPTABLE_CPU_LOAD = 70;

    /**
     * #@+ Timer statistics data keys
     */
    const ID = 'id';
    const START = 'start';
    const TIME = 'execution_time';
    const COUNT = 'process_count';
    const AVG = 'avg';
    const REAL_MEMORY_USAGE = 'real_memory_usage';
    const REAL_MEMORY_USAGE_START = 'real_memory_usage_start';
    const EMALLOC = 'emalloc';
    const EMALLOC_START = 'emalloc_start';
    const CPU_LOAD_START = 'cpu_load_start';
    const CPU_LOAD = 'cpu_load';
    /**#@-*/

    /**#@-*/
    protected $timers = [];

    /**
     * Starts timer
     *
     * @param string $timerId
     * @param int $time
     * @param int $realMemory Real size of memory allocated from system
     * @param int $emallocMemory Memory used by emalloc()
     * @param int $cpuLoad
     * @return void
     */
    public function start($timerId, $time, $realMemory, $emallocMemory, $cpuLoad)
    {
        if (empty($this->timers[$timerId])) {
            $this->timers[$timerId] = [
                self::START => false,
                self::TIME => 0,
                self::COUNT => 0,
                self::REAL_MEMORY_USAGE => 0,
                self::EMALLOC => 0,
                self::CPU_LOAD => 0,
            ];
        }

        $this->timers[$timerId][self::REAL_MEMORY_USAGE_START] = $realMemory;
        $this->timers[$timerId][self::EMALLOC_START] = $emallocMemory;
        $this->timers[$timerId][self::CPU_LOAD_START] = $cpuLoad;
        $this->timers[$timerId][self::START] = $time;
        $this->timers[$timerId][self::COUNT]++;
    }

    /**
     * Stops timer
     *
     * @param string $timerId
     * @param int $time
     * @param int $realMemory Real size of memory allocated from system
     * @param int $emallocMemory Memory used by emalloc()
     * @param int $cpuLoad
     * @return void
     * @throws \InvalidArgumentException if timer doesn't exist
     */
    public function stop($timerId, $time, $realMemory, $emallocMemory, $cpuLoad)
    {
        if (empty($this->timers[$timerId])) {
            throw new \InvalidArgumentException(sprintf('Timer "%s" doesn\'t exist.', $timerId));
        }

        $this->timers[$timerId][self::TIME] += round($time - $this->timers[$timerId]['start'], 2);
        $this->timers[$timerId][self::START] = false;
        $this->timers[$timerId][self::REAL_MEMORY_USAGE] += $realMemory;
        $this->timers[$timerId][self::REAL_MEMORY_USAGE] -= $this->timers[$timerId][self::REAL_MEMORY_USAGE_START];
        $this->timers[$timerId][self::EMALLOC] += $emallocMemory;
        $this->timers[$timerId][self::EMALLOC] -= $this->timers[$timerId][self::EMALLOC_START];
        $this->timers[$timerId][self::CPU_LOAD] += $cpuLoad;
        $this->timers[$timerId][self::CPU_LOAD] -= $this->timers[$timerId][self::CPU_LOAD_START];
        $cpuLoadResult = $this->timers[$timerId][self::CPU_LOAD] - $this->timers[$timerId][self::CPU_LOAD_START];
        if ($cpuLoadResult == 0) {
            $cpuLoadResult = $this->timers[$timerId][self::CPU_LOAD_START];
        }
        $this->timers[$timerId][self::CPU_LOAD] = abs($cpuLoadResult);
    }

    /**
     * Get timer statistics data by timer id
     *
     * @param string $timerId
     * @return array
     * @throws \InvalidArgumentException if timer doesn't exist
     */
    public function get($timerId)
    {
        if (empty($this->timers[$timerId])) {
            throw new \InvalidArgumentException(sprintf('Timer "%s" doesn\'t exist.', $timerId));
        }
        return $this->timers[$timerId];
    }

    /**
     * Clear collected statistics for specified timer or for all timers if timer id is omitted
     *
     * @param string|null $timerId
     * @return void
     */
    public function clear($timerId = null)
    {
        if ($timerId) {
            unset($this->timers[$timerId]);
        } else {
            $this->timers = [];
        }
    }

    /**
     * Starts profiling
     *
     * @param $timerId
     * @return $this
     */
    public function startProfiling($timerId = self::DEFAULT_TIMER_ID)
    {
        $this->clear();
        $this->start(
            $timerId,
            microtime(true),
            memory_get_usage(true),
            memory_get_usage(),
            $this->getCurrentCpuLoad()
        );

        return $this;
    }

    /**
     * Stops profiling
     *
     * @param $timerId
     * @return $this
     */
    public function stopProfiling($timerId = self::DEFAULT_TIMER_ID)
    {
        $this->stop(
            $timerId,
            microtime(true),
            memory_get_usage(true),
            memory_get_usage(),
            $this->getCurrentCpuLoad()
        );

        return $this;
    }

    /**
     * Retrieves statistics
     *
     * @param $timerId
     * @return false|array
     */
    public function getProfilingStat($timerId = self::DEFAULT_TIMER_ID)
    {
        $stat = $this->get($timerId);
        unset($stat[self::START]);

        return $stat;
    }

    /**
     * Retrieves statistics as string
     *
     * @param $timerId
     * @return false|string
     */
    public function getProfilingStatAsString($timerId = self::DEFAULT_TIMER_ID)
    {
        $profilingStat = $this->getProfilingStat();
        if (empty($profilingStat)) {
            return '';
        }

        $result = [];
        foreach ($profilingStat as $key => $value) {
            $result[] = sprintf('%s %s', ucwords(str_replace('_', ' ', $key)), $value);
        }

        $result = trim(implode(' | ', $result));

        return $result;
    }

    /**
     * Get current load average
     *
     * @return int
     */
    public function getCurrentCpuLoad()
    {
        if ($this->isWin()) {
            return false;
        }
        try {
        $cores = $this->getCpuCoresNumber();
        $currentAvg = $this->getCurrentCpuLoadAvg();
        $fullLoad = $cores + $cores/2;
        return round(min(100, $currentAvg * 100 / $fullLoad), 3);
        }catch (\Exception $e){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getCurrentCpuLoadAvg()
    {
        if (!function_exists('sys_getloadavg')) {
            return 99999;
        }

        try {
            $load = sys_getloadavg();
            return $load[0];
        } catch (\Exception $e) {
            // catch and log exception
        }

        return 99999;
    }

    /**
     * @return float|int|mixed
     */
    public function getAcceptableLoadAverage()
    {
        if ($this->isWin()) {
            return 1.5;
        }

        $cores = $this->getCpuCoresNumber();
        $fullLoad = $cores + $cores/2;

        return $fullLoad * (self::ACCEPTABLE_CPU_LOAD / 100);
    }

    /**
     * @return bool
     */
    public function isWin()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }

        return false;
    }

    /**
     * Get CPU cores count (for UNIX only)
     *
     * @return int|mixed
     */
    protected function getCpuCoresNumber()
    {
        $result = [];
        $status = [];
        try {
            exec('grep -c ^processor /proc/cpuinfo 2>&1', $result, $status);
            if ($status != 0) {
                new \Exception(print_r($result, true));
            }
            return $result[0];
        } catch (\Exception $e) {
            // catch and log exception
        }

        return 1;
    }
}