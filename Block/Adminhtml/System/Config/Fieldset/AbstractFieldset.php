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
namespace Unbxd\ProductFeed\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Backend\Block\Template\Context;
use Unbxd\ProductFeed\Helper\Module as ModuleHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Unbxd\ProductFeed\Helper\Feed as FeedHelper;
use Unbxd\ProductFeed\Model\FeedView;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;

/**
 * Class AbstractFieldset
 * @package Unbxd\ProductFeed\Block\Adminhtml\System\Config\Fieldset
 */
abstract class AbstractFieldset extends Template implements RendererInterface
{
    /**
     * Static resources
     *
     * @var array
     */
    protected static $unbxdReferenceUrls = [
        'base' => '//unbxd.com',
        'intro' => '//unbxd.com/documentation/site-search/v2-search-how-it-works/',
        'feed' => '//unbxd.com/documentation/site-search/v2-search-product-feed/'
    ];

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;

    /**
     * @var \Magento\Config\Model\Config\Structure
     */
    private $configStructure;

    /**
     * @var ModuleHelper
     */
    private $moduleHelper;

    /**
     * @var FeedHelper
     */
    private $feedHelper;

    /**
     * @var FeedView
     */
    protected $feedView;

    /**
     * @var TimezoneInterface
     */
    protected $dateTime;

    /**
     * AbstractFieldset constructor.
     * @param Context $context
     * @param ProductMetadataInterface $productMetadata
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param ModuleHelper $moduleHelper
     * @param FeedHelper $feedHelper
     * @param FeedView $feedView
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductMetadataInterface $productMetadata,
        \Magento\Config\Model\Config\Structure $configStructure,
        ModuleHelper $moduleHelper,
        FeedHelper $feedHelper,
        FeedView $feedView,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productMetadata = $productMetadata;
        $this->assetRepo = $context->getAssetRepository();
        $this->configStructure = $configStructure;
        $this->moduleHelper = $moduleHelper;
        $this->feedHelper = $feedHelper;
        $this->feedView = $feedView;
        $this->dateTime = $context->getLocaleDate();
    }

    /**
     * @return string
     */
    private function getAppVersion()
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * @return string
     */
    public function getLogoSrc()
    {
        return $this->assetRepo->getUrl("Unbxd_ProductFeed::images/unbxd_logo.png");
    }

    /**
     * @param null $moduleName
     * @return mixed
     */
    public function getModuleVersion()
    {
        return $this->moduleHelper->getModuleInfo()->getVersion();
    }

    /**
     * @return string
     */
    public function getLastUploadId()
    {
        return $this->feedHelper->getLastUploadId();
    }

    /**
     * @return string
     */
    public function getUploadedSize()
    {
        return $this->feedHelper->getUploadedSize();
    }

    /**
     * @return bool
     */
    public function isSynchronizationAttempt()
    {
        return (bool) $this->getLastUploadId();
    }

    /**
     * @return bool
     */
    public function isFullCatalogSynchronized()
    {
        return (bool) $this->feedHelper->isFullCatalogSynchronized();
    }

    /**
     * @return bool
     */
    public function isIncrementalProductSynchronized()
    {
        return (bool) $this->feedHelper->isIncrementalProductSynchronized();
    }

    /**
     * @return string|null
     */
    public function getLastCatalogSyncDatetime()
    {
        $date = $this->feedHelper->getLastSynchronizationDatetime();
        if (!$date || !$this->isSynchronizationAttempt()) {
            return null;
        }

        return $this->dateTime->formatDate($date,\IntlDateFormatter::MEDIUM,true);
    }

    /**
     * @return string|null
     */
    public function getLastSynchronizationOperationType()
    {
        $type = $this->feedHelper->getLastSynchronizationOperationType();
        if (!$type || !$this->isSynchronizationAttempt()) {
            return null;
        }

        return ucfirst($type);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getMessageByStatus()
    {
        $message = __('Product catalog is not synchronized.');
        if ($this->getIsRunning()) {
            $message = __(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_RUNNING);
        } else if ($this->getIsProcessing()) {
            $message = __(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_INDEXING);
        } else if ($this->getIsComplete()) {
            $message = __(FeedConfig::FEED_MESSAGE_BY_RESPONSE_TYPE_COMPLETE);
        }

        return $message;
    }

    /**
     * @return bool
     */
    public function isUploadedFeedDetailsEnabled()
    {
        return (bool) ($this->getIsProcessing() || $this->getIsComplete());
    }

    /**
     * @return bool
     */
    public function getIsRunning()
    {
        return (bool) ($this->getLastSynchronizationStatus() == FeedView::STATUS_RUNNING);
    }

    /**
     * @return bool
     */
    public function getIsProcessing()
    {
        return (bool) (($this->getLastSynchronizationStatus() == FeedView::STATUS_INDEXING)
            && $this->getLastUploadId());
    }

    /**
     * @return bool
     */
    public function getIsComplete()
    {
        return (bool) (($this->getLastSynchronizationStatus() == FeedView::STATUS_COMPLETE)
            && $this->getLastUploadId());
    }

    /**
     * @return bool
     */
    public function getIsError()
    {
        return (bool) ($this->getLastSynchronizationStatus() == FeedView::STATUS_ERROR);
    }

    /**
     * @return bool
     */
    public function getLastSynchronizationStatus()
    {
        return (int) $this->feedHelper->getLastSynchronizationStatus();
    }

    /**
     * @return string|null
     */
    public function getLastSynchronizationStatusHtml()
    {
        $status = $this->getLastSynchronizationStatus();
        if (!$this->isSynchronizationAttempt() || !$status) {
            return null;
        }

        $availableStatuses = $this->feedView->getAvailableStatuses();
        $decoratorClassPath = 'undefined';
        $title = 'Undefined';
        $statusHtml = '';
        if (array_key_exists($status, $availableStatuses)) {
            $title = $availableStatuses[$status];
            if (in_array($status, [FeedView::STATUS_RUNNING, FeedView::STATUS_INDEXING])) {
                $decoratorClassPath = 'minor';
            } elseif ($status == FeedView::STATUS_COMPLETE) {
                $decoratorClassPath = 'notice';
            } elseif ($status == FeedView::STATUS_ERROR) {
                $decoratorClassPath = 'critical';
            }
        }

        $statusHtml .= '<span class="grid-severity-' . $decoratorClassPath .'" style="display: inline-block"><span>' . __($title) . '</span></span>';

        return $statusHtml;
    }

    /**
     * @param string $type
     * @return mixed|string
     */
    public static function getUnbxdReferenceUrl($type = null)
    {
        return isset(self::$unbxdReferenceUrls[$type])
            ? self::$unbxdReferenceUrls[$type]
            : self::$unbxdReferenceUrls['base'];
    }

    /**
     * Create action url by path
     *
     * @param string $path
     * @return string
     */
    private function getActionUrl($path = '')
    {
        return $this->getUrl($path,
            [
                '_secure' => $this->getRequest()->isSecure()
            ]
        );
    }

    /**
     * @return string
     */
    public function getCatalogFeedConfigurationUrl()
    {
        return $this->getActionUrl('adminhtml/system_config/edit/section/unbxd_catalog');
    }

    /**
     * @return string
     */
    public function getCommandLineReferenceUrl()
    {
        $appVersion = $this->getAppVersion();
        $appShortVersion = substr($appVersion,0, strrpos($appVersion,'.'));

        return sprintf(
            'https://devdocs.magento.com/guides/v%s/config-guide/cli/config-cli-subcommands.html',
            $appShortVersion
        );
    }
}