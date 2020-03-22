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
namespace Unbxd\ProductFeed\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ShellInterface;
use Magento\Framework\Process\PhpExecutableFinderFactory;

/**
 * Class BackgroundTaskManager
 * @package Unbxd\ProductFeed\Model
 */
class BackgroundTaskManager
{
    /**
     * @var ShellInterface
     */
    protected $shell;

    /**
     * @var \Symfony\Component\Process\PhpExecutableFinder
     */
    protected $phpExecutableFinder;

    /**
     * BackgroundProcessManager constructor.
     * @param ShellInterface $shell
     * @param PhpExecutableFinderFactory $phpExecutableFinderFactory
     */
    public function __construct(
        ShellInterface $shell,
        PhpExecutableFinderFactory $phpExecutableFinderFactory
    ) {
        $this->shell = $shell;
        $this->phpExecutableFinder = $phpExecutableFinderFactory->create();
    }

    /**
     * @param array $commands
     * @param null $storeId
     * @return $this
     * @throws LocalizedException
     */
    public function execute(array $commands, $storeId = null)
    {
        $phpPath = $this->getPhpPath();
        foreach ($commands as $command) {
            $this->shell->execute(
                $phpPath . ' %s ' . $command . ' --store=' . $storeId,
                [
                    BP . '/bin/magento'
                ]
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getPhpPath()
    {
        return $this->phpExecutableFinder->find() ?: 'php';
    }
}