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
namespace Unbxd\ProductFeed\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Unbxd\ProductFeed\Model\IndexingQueue;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class Date
 * @package Unbxd\ProductFeed\Ui\Component\Listing\Column
 */
class Date extends Column
{
    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var BooleanUtils
     */
    private $booleanUtils;

    /**
     * Date constructor.
     * @param IndexingQueue $indexingQueue
     * @param FilterManager $filterManager
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param BooleanUtils $booleanUtils
     * @param array $components
     * @param array $data
     */
    public function __construct(
        FilterManager $filterManager,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        BooleanUtils $booleanUtils,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->filterManager = $filterManager;
        $this->timezone = $timezone;
        $this->booleanUtils = $booleanUtils;
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws \Exception
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$this->getData('name')])) {
                    $value = $item[$this->getData('name')];
                    if (!$value || (strpos($value, '0000') !== false)) {
                        $item[$this->getData('name')] = __('Not Performed Yet');
                    } else {
                        $date = $this->timezone->date(new \DateTime($value));
                        $timezone = isset($this->getConfiguration()['timezone'])
                            ? $this->booleanUtils->convert($this->getConfiguration()['timezone'])
                            : true;
                        if (!$timezone) {
                            $date = new \DateTime($value);
                        }
                        $item[$this->getData('name')] = $date->format('Y-m-d H:i:s');
                    }
                }
            }
        }

        return $dataSource;
    }
}
