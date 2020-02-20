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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Module\Dir as ModuleDir;
use Unbxd\ProductFeed\Model\Serializer;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;

/**
 * Class Module
 * @package Unbxd\ProductFeed\Helper
 */
class Module extends AbstractHelper
{
    /**#@+
     * Composer filename
     */
    const COMPOSER_FILENAME = 'composer.json';
    /**#@-*/

    /**
     * @var ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    private $moduleReader;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileSystem;

    /**
     * @var array|null
     */
    private $moduleData = null;

    /**
     * @var string
     */
    private $moduleName = '';

    /**
     * Module constructor.
     * @param ComponentRegistrar $componentRegistrar
     * @param \Magento\Framework\App\Helper\Context $context
     * @param ModuleDir\Reader $moduleReader
     * @param \Magento\Framework\Filesystem\Driver\File $fileSystem
     * @param Serializer|null $serializer
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        Serializer $serializer = null
    ) {
        parent::__construct($context);
        $this->componentRegistrar = $componentRegistrar;
        $this->moduleManager = $context->getModuleManager();
        $this->moduleReader = $moduleReader;
        $this->fileSystem = $fileSystem;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * @return string
     */
    public function getModuleName()
    {
        if (!$this->moduleName) {
            $class = get_class($this);
            if ($class) {
                $class = explode('\\', $class);
                if (isset($class[0]) && isset($class[1])) {
                    $this->moduleName = sprintf('%s_%s', $class[0], $class[1]);
                }
            }
        }

        return $this->moduleName;
    }

    /**
     * @param $moduleName
     * @return bool
     */
    public function isModuleEnable($moduleName = '')
    {
        $moduleName = $moduleName ?: $this->getModuleName();

        return $this->moduleManager->isEnabled($moduleName);
    }

    /**
     * @param null $moduleName
     * @param string $type
     * @return string
     */
    public function getModuleDir($moduleName = null, $type = '')
    {
        if (null === $moduleName) {
            $moduleName = $this->getModuleName();
        }

        return $this->moduleReader->getModuleDir($type, $moduleName);
    }

    /**
     * Read info about extension from composer json file
     *
     * @param null $moduleName
     * @return array|\Magento\Framework\DataObject|null
     */
    public function getModuleInfo($moduleName = null)
    {
        if (!$this->moduleData) {
            $moduleName = $moduleName ?: $this->getModuleName();
            $this->moduleData = new DataObject();
            try {
                $moduleDir = $this->getModuleDir($moduleName);
                $composerPath = $moduleDir . DIRECTORY_SEPARATOR . self::COMPOSER_FILENAME;
                if ($this->fileSystem->isExists($composerPath)) {
                    $composerJsonContent = $this->fileSystem->fileGetContents($composerPath);
                    $moduleData = $this->serializer->unserialize($composerJsonContent);
                    if ($moduleData) {
                        $this->moduleData->addData($moduleData);
                    }
                }
            } catch (\LogicException $e) {
                return $this->moduleData;
            } catch (FileSystemException $e) {
                return $this->moduleData;
            }
        }

        return $this->moduleData;
    }
}
