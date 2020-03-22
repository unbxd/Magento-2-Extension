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
namespace Unbxd\ProductFeed\Logger;

use Unbxd\ProductFeed\Logger\LoggerInterface;

/**
 * Class LoggerProxy
 * @package Unbxd\ProductFeed\Logger
 */
class LoggerProxy implements LoggerInterface
{
    /**
     * Configuration group name
     */
    const CONF_GROUP_NAME = 'unbxd_logger';

    /**
     * Logger alias param name
     */
    const PARAM_ALIAS = 'output';

    /**
     * Logger log all param name
     */
    const PARAM_LOG_ALL = 'log_everything';

    /**
     * Logger call stack param name
     */
    const PARAM_CALL_STACK = 'include_stacktrace';

    /**
     * File logger alias
     */
    const LOGGER_ALIAS_FILE = 'file';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var string
     */
    private $loggerAlias;

    /**
     * @var bool
     */
    private $logAll;

    /**
     * @var bool
     */
    private $logCallStack;

    /**
     * LoggerProxy constructor.
     * @param FileFactory $fileFactory
     * @param string $loggerAlias
     * @param bool $logAll
     * @param bool $logCallStack
     */
    public function __construct(
        FileFactory $fileFactory,
        $loggerAlias = OptionsListConstants::LOGGER_OUTPUT,
        $logAll = OptionsListConstants::LOGGER_LOG_EVERYTHING,
        $logCallStack = OptionsListConstants::LOGGER_INCLUDE_STACKTRACE
    ) {
        $this->fileFactory = $fileFactory;
        $this->loggerAlias = $loggerAlias;
        $this->logAll = $logAll;
        $this->logCallStack = $logCallStack;
    }

    /**
     * Get logger object. Initialize if needed.
     *
     * @param string $type
     * @return File|\Unbxd\ProductFeed\Logger\LoggerInterface
     */
    public function create($type = 'default')
    {
        switch ($this->loggerAlias) {
            case self::LOGGER_ALIAS_FILE:
                $this->logger = $this->fileFactory->create(
                    [
                        'logFileName' => $type,
                        'logAll' => $this->logAll,
                        'logCallStack' => $this->logCallStack,
                    ]
                );
                break;
            default:
                $this->logger = $this->fileFactory->create();
                break;
        }

        return $this->logger;
    }

    /**
     * Get logger object. Initialize if needed.
     *
     * @return LoggerInterface
     */
    private function getLogger()
    {
        if ($this->logger === null) {
            $this->logger = $this->create();
        }

        return $this->logger;
    }

    /**
     * Adds info log record
     *
     * @param string $string
     * @return void
     */
    public function info($string)
    {
        $this->getLogger()->log($string, self::INFO);
    }

    /**
     * Adds debug log record
     *
     * @param string $string
     * @return void
     */
    public function debug($string)
    {
        $this->getLogger()->log($string, self::DEBUG);
    }

    /**
     * Adds error log record
     *
     * @param string $string
     * @return void
     */
    public function error($string)
    {
        $this->getLogger()->log($string, self::ERROR);
    }

    /**
     * @param \Exception $exception
     * @return void
     */
    public function critical(\Exception $exception)
    {
        $this->getLogger()->critical($exception);
    }

    /**
     * @return void
     */
    public function startTimer()
    {
        $this->getLogger()->startTimer();
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->getLogger()->getTime();
    }

    /**
     * @param string $extraMessage
     * @param null $type
     * @return mixed|void
     */
    public function logStats($extraMessage = '', $type = null)
    {
        $this->getLogger()->logStats($extraMessage, $type);
    }
}