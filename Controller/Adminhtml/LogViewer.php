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
namespace Unbxd\ProductFeed\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class LogViewer
 * @package Unbxd\ProductFeed\Controller\Adminhtml
 */
abstract class LogViewer extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * LogViewer constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param FileFactory $fileFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        FileFactory $fileFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->fileFactory = $fileFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $type
     * @return \Unbxd\ProductFeed\Logger\File
     * @throws \Exception
     */
    protected function getLogger($type)
    {
        try {
            /** @var \Unbxd\ProductFeed\Logger\File $logger */
            $logger = $this->_objectManager->create(\Unbxd\ProductFeed\Logger\LoggerInterface::class)
                ->create($type);
        } catch (\Exception $e) {
            throw new \Exception(__('Can\'t create instance of logger object. Error: %1', $e->getMessage()));
        }

        return $logger;
    }
}