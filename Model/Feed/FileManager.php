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
namespace Unbxd\ProductFeed\Model\Feed;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Exception\FileSystemException;

/**
 * Class FileManager
 * @package Unbxd\ProductFeed\Model\Feed
 */
class FileManager
{
    /**
     * Default file mime types
     */
    const DEFAULT_JSON_FILE_MIME_TYPE = 'application/json';
    const DEFAULT_ZIP_FILE_MIME_TYPE = 'application/zip';

    /**
     * Default sub dirs
     */
    const DEFAULT_SUB_DIR = 'unbxd';
    const DEFAULT_SUB_DIR_FOR_DOWNLOAD = 'unbxd/download';

    /**
     * Store parameter for feed file name
     */
    const STORE_PARAMETER = '_store';

    /**
     * @var WriteInterface
     */
    private $dir;

    /**
     * @var array
     */
    private $defaultMimeTypes = [
        self::DEFAULT_JSON_FILE_MIME_TYPE,
        self::DEFAULT_ZIP_FILE_MIME_TYPE
    ];

    /**
     * @var string
     */
    private $fileName = null;

    /**
     * @var string
     */
    private $filePath = null;

    /**
     * @var string
     */
    private $contentFormat = null;

    /**
     * @var null
     */
    private $archiveFormat = null;

    /**
     * @var null
     */
    private $subDir = null;

    /**
     * @var null
     */
    private $store = null;

    /**
     * @var array
     */
    private $allowedMimeTypes = [];

    /**
     * @var bool
     */
    private $isConvertedToArchive = false;

    /**
     * FileManager constructor.
     * @param Filesystem $filesystem
     * @param null $fileName
     * @param null $contentFormat
     * @param null $archiveFormat
     * @param null $subDir
     * @param null $store
     * @param array $allowedMimeTypes
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        $fileName = null,
        $contentFormat = null,
        $archiveFormat = null,
        $subDir = null,
        $store = null,
        array $allowedMimeTypes = []
    ) {
        $this->fileName = $fileName;
        $this->contentFormat = $contentFormat;
        $this->archiveFormat = $archiveFormat;
        $this->subDir = $subDir ?: self::DEFAULT_SUB_DIR;
        $this->store = $store;
        $this->filePath = sprintf(
            '%s%s%s%s.%s',
            $this->subDir,
            DIRECTORY_SEPARATOR,
            $this->fileName,
            $this->store,
            $this->contentFormat
            );
        $this->allowedMimeTypes = array_unique(array_merge($this->defaultMimeTypes, array_values($allowedMimeTypes)));
        $this->dir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @param $string
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function write($string)
    {
        $stream = $this->dir->openFile($this->getFilePath(), 'a');
        $stream->lock();
        // to prevent issues with big data during this action split string into small pieces
		$splitString = str_split($string, 1024 * 4);
		if (!empty($splitString)) {
			foreach ($splitString as $partString) {
				$stream->write($partString);
			}
		}
        $stream->unlock();
        $stream->close();

        return $this;
    }

    /**
     * Return source files location
     *
     * @return mixed
     */
    public function getSourcePath()
    {
        return $this->dir->getRelativePath($this->subDir);
    }

    /**
     * @return Filesystem\File\WriteInterface
     */
    public function getFileStream()
    {
        return $this->dir->openFile($this->getFilePath(), 'r');
    }

    /**
     * Check if feed file exist
     *
     * @return bool
     */
    public function isExist()
    {
        return $this->dir->isExist($this->getFilePath());
    }

    /**
     * Init file, if file not exist create new one with empty content
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function initFile()
    {
        if (!$this->isExist()) {
            // just create file with empty content
            $this->dir->writeFile($this->getFilePath(), '');
        }
    }

    /**
     * Retrieve file name
     *
     * @return string
     */
    public function getFileName()
    {
        $format = !$this->getIsConvertedToArchive() ? $this->contentFormat : $this->archiveFormat;
        return sprintf('%s.%s', $this->fileName, $format);
    }

    /**
     * Retrieve file content format
     *
     * @return string
     */
    public function getContentFormat()
    {
        return $this->contentFormat;
    }

    /**
     * Retrieve file archive format
     *
     * @return string
     */
    public function getArchiveFormat()
    {
        return $this->archiveFormat;
    }

    /**
     * Retrieve file sub path location
     *
     * @return string
     */
    private function getFilePath()
    {
        $path = $this->filePath;
        if ($this->getIsConvertedToArchive()) {
            $path = $this->getArchiveFilePath($path);
        }

        return $path;
    }

    /**
     * Retrieve archive file sub path location
     *
     * @param null $path
     * @return string|string[]|null
     */
    private function getArchiveFilePath($path = null)
    {
        return preg_replace(
            sprintf('/\.%s$/i', $this->getContentFormat()),
            sprintf('.%s', $this->getArchiveFormat()),
            $path ?: $this->filePath
        );
    }

    /**
     * Retrieve file location
     *
     * @return string
     */
    public function getFileLocation()
    {
        return $this->dir->getAbsolutePath($this->getFilePath());
    }

    /**
     * Retrieve file content
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
     * Retrieve file size
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFileSize()
    {
        $this->initFile();
        $fileStat = $this->dir->stat($this->getFileLocation());
        $size = isset($fileStat['size']) ? round($fileStat['size'], 2) : 0; // in bytes
        return $size;
    }

    /**
     * Retrieve file size
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getFileMtime()
    {
        $this->initFile();
        $fileStat = $this->dir->stat($this->getFileLocation());
        $mDate = isset($fileStat['mtime']) ? $fileStat['mtime'] : strtotime(date('Y-m-d-H-i-s'));

        return $mDate;
    }

    /**
     * Flush file content
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function flushFileContent()
    {
        $this->deleteFile();
        $this->initFile();
    }

    /**
     * Delete file
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteFile()
    {
        $this->dir->getDriver()->deleteFile($this->getFileLocation());
    }

    /**
     * Delete source path
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function deleteSourcePath()
    {
        $this->dir->delete($this->getSourcePath());
    }

    /**
     * Delete affected files
     *
     * @return void
     * @throws FileSystemException
     */
    public function deleteAffectedFiles()
    {
        $defaultFilePath = $this->filePath;
        $affectedFiles = [
            $this->dir->getAbsolutePath($defaultFilePath),
            $this->dir->getAbsolutePath($this->getArchiveFilePath($defaultFilePath))
        ];
        foreach ($affectedFiles as $file) {
            try {
                $this->dir->getDriver()->deleteFile($file);
            } catch (FileSystemException $e) {
                // ignore exception in case if file with specific type is not exist
            }
        }
    }

    /**
     * @return string|null
     */
    public function getMimeType()
    {
        $path = $this->getFileLocation();
        $ext = substr($path, strrpos($path, '.') + 1);
        $contentType = null;
        if ($ext == $this->contentFormat) {
            $contentType = self::DEFAULT_JSON_FILE_MIME_TYPE;
        } else if ($ext == $this->archiveFormat) {
            $contentType = self::DEFAULT_ZIP_FILE_MIME_TYPE;
        }

        return $this->isMimeTypeValid($contentType) ? $contentType : null;
    }

    /**
     * Check if given mime type is valid
     *
     * @param string $mimeType
     * @return bool
     */
    private function isMimeTypeValid($mimeType)
    {
        return in_array($mimeType, $this->allowedMimeTypes);
    }

    /**
     * Check if given filename is valid
     *
     * @param string $name
     * @return bool
     */
    public function isFileNameValid($name)
    {
        // cannot contain \ / : * ? " < > |
        if (!preg_match('/^[^\\/?*:";<>()|{}\\\\]+$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * Set flag which means that the content has been archived
     *
     * @param $flag
     */
    public function setIsConvertedToArchive($flag)
    {
        $this->isConvertedToArchive = (bool) $flag;
    }

    /**
     * Check if content was packed into archive or not
     *
     * @return bool
     */
    public function getIsConvertedToArchive()
    {
        return (bool) $this->isConvertedToArchive;
    }
}