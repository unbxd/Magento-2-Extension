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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Unbxd\ProductFeed\Logger\OptionsListConstants;
use Magento\Framework\Profiler\Driver\Standard\Stat;
use Magento\Framework\Profiler\Driver\Standard\StatFactory;

/**
 * Logging to file
 *
 * Class File
 * @package Unbxd\ProductFeed\Logger
 */
class File extends LoggerAbstract
{
    /**
     * @var WriteInterface
     */
    private $dir;

    /**
     * Debug log file types
     *
     * @var array
     */
    private $types = [];

    /**
     * @var string|null
     */
    protected $debugFile = null;

    /**
     * File constructor.
     * @param Filesystem $filesystem
     * @param $types
     * @param $logFileName
     * @param bool $logAll
     * @param bool $logCallStack
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        $types,
        $logFileName,
        $logAll = true,
        $logCallStack = false
    ) {
        $this->dir = $filesystem->getDirectoryWrite(DirectoryList::LOG);
        $this->types = $types;
        $logFileName = $this->getFileName($logFileName);
        $this->debugFile = OptionsListConstants::LOGGER_SUB_DIR . DIRECTORY_SEPARATOR . $logFileName;
        parent::__construct(
            $logAll,
            $logCallStack
        );
    }

    /**
     * @param $string
     * @param $type
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function log($string, $type)
    {
        $record = '[' . date('Y-m-d H:i:s') . ']' . ' ' . $type . ': ' . $string . "\r\n";

        $stream = $this->dir->openFile($this->debugFile, 'a');
        $stream->lock();
        $stream->write($record);
        $stream->unlock();
        $stream->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function info($string)
    {
        return $this->log($string, self::INFO);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($string)
    {
        return $this->log($string, self::DEBUG);
    }

    /**
     * {@inheritdoc}
     */
    public function error($string)
    {
        return $this->log($string, self::ERROR);
    }

    /**
     * {@inheritdoc}
     */
    public function critical(\Exception $e)
    {
        return $this->log("EXCEPTION \n$e\n\n", self::CRITICAL);
    }

    /**
     * {@inheritdoc}
     */
    public function logStats($extraMessage = '', $type = null)
    {
        $stats = $this->getStats($extraMessage, $type);
        if ($stats) {
            return $this->debug($stats);
        }

        return $this;
    }

    /**
     * Check if log file exist, if not create new file
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function initFile()
    {
        if (!$this->dir->isExist($this->debugFile)) {
            // just create file with empty content
            $this->dir->writeFile($this->debugFile, '');
        }
    }

    /**
     * Retrieve log file location
     *
     * @return string
     */
    public function getFileLocation()
    {
        return $this->dir->getAbsolutePath($this->debugFile);
    }

    /**
     * Retrieve log file content
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFileContent()
    {
        $this->initFile();
        return $this->dir->getDriver()->fileGetContents($this->getFileLocation());
    }

    /**
     * Retrieve log file size
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFileSize()
    {
        $this->initFile();
        $filePath = $this->getFileLocation();
        try {
            $fileStat = $this->dir->stat($filePath);
            $size = isset($fileStat['size']) ? round($fileStat['size'] / 1024, 2) : 0; // in KB
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Cannot gather stats') !== false) {
                // detected for magento ~2.1
                clearstatcache();
                $fileStat = @stat($filePath);
                $size = isset($fileStat['size']) ? round($fileStat['size'] / 1024, 2) : 0; // in KB
            } else {
                $size = 0;
            }
        }

        return $size;
    }

    /**
     * Flush log file content
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function flushFileContent()
    {
        $this->dir->getDriver()->deleteFile($this->getFileLocation());
        $this->initFile();
    }

    /**
     * Retrieve log file name
     *
     * @param $type
     * @return mixed
     */
    public function getFileName($type)
    {
        $fileName = $this->types['default'];
        if (isset($this->types[$type])) {
            $fileName = $this->types[$type];
        }

        return $fileName;
    }
}