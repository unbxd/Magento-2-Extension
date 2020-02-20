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
namespace Unbxd\ProductFeed\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\FormKey;
use Unbxd\ProductFeed\Model\Serializer;
use Unbxd\ProductFeed\Logger\LoggerInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class LogViewer
 * @package Unbxd\ProductFeed\Block\Adminhtml
 */
class LogViewer extends Template
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Log file type
     *
     * @var string
     */
    private $type;

    /**
     * LogViewer constructor.
     * @param Template\Context $context
     * @param LoggerInterface $logger
     * @param array $data
     * @param Serializer|null $serializer
     */
    public function __construct(
        Template\Context $context,
        LoggerInterface $logger,
        array $data = [],
        Serializer $serializer = null
    ) {
        parent::__construct($context, $data);
        $this->formKey = $context->getFormKey();
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->type = isset($data['type']) ? $data['type'] : 'default';
        $this->logger = $logger->create($this->type);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getConfig()
    {
        return [
            'formKey' => $this->getFormKey(),
            'file' => [
                'location' => $this->getLogFileLocation(),
                'content' => $this->getLogFileContent(),
                'size' => $this->getLogFileSize()
            ],
            'url' => [
                'downloadFile' => $this->getDownloadFileUrl(),
                'refreshContent' => $this->getRefreshContentUrl(),
                'flushContent' => $this->getFlushContentUrl()
            ]
        ];
    }

    /**
     * @return bool|string
     */
    public function getJsLayout()
    {
        return $this->serializer->serialize($this->jsLayout);
    }

    /**
     * @return bool|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getSerializedConfig()
    {
        return $this->serializer->serialize($this->getConfig());
    }

    /**
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Create action url by path and params
     *
     * @param string $path
     * @param array $params
     * @return mixed
     */
    private function getActionUrl($path = '', $params = [])
    {
        $params = array_merge($params, [
            '_secure' => $this->getRequest()->isSecure()
        ]);

        return $this->getUrl($path, $params);
    }

    /**
     * Retrieve action url for download log file
     *
     * @return string
     */
    private function getDownloadFileUrl()
    {
        return $this->getActionUrl('unbxd_productfeed/logViewer/downloadFile',
            [
                'type' => $this->type
            ]
        );
    }

    /**
     * Retrieve action url for refresh file content
     *
     * @return string
     */
    private function getRefreshContentUrl()
    {
        return $this->getActionUrl('unbxd_productfeed/logViewer/refreshContent',
            [
                'type' => $this->type
            ]
        );
    }

    /**
     * Retrieve action url for flush file content
     *
     * @return string
     */
    private function getFlushContentUrl()
    {
        return $this->getActionUrl('unbxd_productfeed/logViewer/flushContent',
            [
                'type' => $this->type
            ]
        );
    }

    /**
     * Retrieve log file location
     *
     * @return string
     */
    private function getLogFileLocation()
    {
        return $this->logger->getFileLocation();
    }

    /**
     * Retrieve log file content
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getLogFileContent()
    {
        return nl2br($this->_escaper->escapeHtml($this->logger->getFileContent()));
    }

    /**
     * Retrieve log file size
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getLogFileSize()
    {
        return sprintf('(%s KB)', $this->logger->getFileSize());
    }
}
