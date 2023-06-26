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
namespace Unbxd\ProductFeed\Model\Feed\Api;

use Magento\Framework\HTTP\Adapter\CurlFactory;
use Unbxd\ProductFeed\Model\Feed\Api\Response\Factory as ResponseFactory;
use Unbxd\ProductFeed\Model\Serializer;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\Feed\Config as FeedConfig;
use Unbxd\ProductFeed\Api\Data\FeedViewInterface;
use Unbxd\ProductFeed\Logger\LoggerInterface;

/**
 * Class Connector
 * @package Unbxd\ProductFeed\Model\Feed\Api
 */
class Connector
{
    /**
     * Content-Type HTTP header types
     */
    const CONTENT_TYPE_HEADER_JSON = "Content-Type: application/json";
    const CONTENT_TYPE_HEADER_MULTIPART = "Content-Type: multipart/form-data";

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * API request headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * API request params
     *
     * @var array
     */
    private $params = [];

    /**
     * API request extra params
     *
     * @var array
     */
    private $extraParams = [];

    /**
     * API request method
     *
     * @var string
     */
    private $requestMethod = '';

    /**
     * API endpoint
     *
     * @var string
     */
    private $url = '';

    /**
     * API site key
     *
     * @var string
     */
    private $siteKey = '';

    /**
     * @var \Unbxd\ProductFeed\Model\Feed\Api\Response
     */
    private $responseManager = null;

    /**
     * Connector constructor.
     * @param CurlFactory $curlFactory
     * @param ResponseFactory $responseFactory
     * @param HelperData $helperData
     * @param Serializer $serializer
     */
    public function __construct(
        CurlFactory $curlFactory,
        ResponseFactory $responseFactory,
        HelperData $helperData,
        Serializer $serializer,
        LoggerInterface $logger
    ) {
        $this->curlFactory = $curlFactory;
        $this->responseFactory = $responseFactory;
        $this->helperData = $helperData;
        $this->logger = $logger->create("feed");
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * @param array $headers
     * @return $this
     */
    private function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return $this
     */
    public function resetHeaders()
    {
        $this->headers = [];
        $this->setHeaders($this->headers);

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    private function setParams(array $params)
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * @return array
     */
    private function getParams()
    {
        return $this->params;
    }

    /**
     * @return $this
     */
    public function resetParams()
    {
        $this->params = [];
        $this->setParams($this->params);

        return $this;
    }

    /**
     * @param array $extraParams
     * @return $this
     */
    public function setExtraParams(array $extraParams)
    {
        $this->extraParams = array_merge($this->extraParams, $extraParams);

        return $this;
    }

    /**
     * @return array
     */
    private function getExtraParams()
    {
        return $this->extraParams;
    }

    /**
     * @return $this
     */
    public function resetExtraParams()
    {
        $this->extraParams = [];
        $this->setExtraParams($this->extraParams);

        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    private function setRequestMethod($method = \Laminas\Http\Request::METHOD_POST)
    {
        $this->requestMethod = (string) $method;

        return $this;
    }

    /**
     * @return string
     */
    private function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setApiUrl($url)
    {
        $this->url = (string) $url;

        return $this;
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        return $this->url;
    }

    /**
     * @param string $siteKey
     * @return $this
     */
    private function setSiteKey($siteKey)
    {
        $this->siteKey = (string) $siteKey;

        return $this;
    }

    /**
     * @return mixed
     */
    private function getSiteKey()
    {
        return $this->siteKey;
    }

    /**
     * Prepare API authorization params for request
     *
     * @param null $store
     * @return bool
     */
    private function prepareAuthorizationParams($store = null)
    {
        $secretKey = $this->helperData->getSecretKey($store);
        $siteKey = $this->helperData->getSiteKey($store);
        if (!$secretKey || !$siteKey) {
            return false;
        }
        $this->resetHeaders();
        $this->setHeaders([
            "Authorization: {$secretKey}"
        ]);

        $this->setSiteKey($siteKey);

        return true;
    }

    /**
     * @return mixed|string|null
     */
    private function retrieveUploadId()
    {
        $uploadId = $this->getResponseManager()->getUploadId();
        if (!$uploadId && !empty($this->getExtraParams())) {
            $extraParams = $this->getExtraParams();
            $uploadId = array_key_exists(FeedViewInterface::UPLOAD_ID, $extraParams)
                ? $extraParams[FeedViewInterface::UPLOAD_ID]
                : null;
        }

        return $uploadId;
    }

    /**
     * Prepare API url for request
     *
     * @param $type
     * @param null $store
     * @param null $queryParameter
     * @return bool
     */
    private function prepareApiUrl($type, $store = null,$queryParameter = "")
    {
        if (!$siteKey = $this->getSiteKey()) {
            return false;
        }

        if ($this->getApiUrl()) {
            $analyticsType = class_exists(\Unbxd\Analytics\Model\Config::class)
                ? \Unbxd\Analytics\Model\Config::API_REQUEST_TYPE_ANALYTICS
                : FeedConfig::FEED_TYPE_ANALYTICS;
            if ($type == $analyticsType) {
                $this->resetHeaders();
                return true;
            }
        }

        if ($type == FeedConfig::FEED_TYPE_FULL) {
            $apiEndpoint = $this->helperData->getFullFeedApiEndpoint($store);
            if($this->helperData->isMultiPartUploadEnabled()){
                $apiEndpoint = $apiEndpoint."/write".$queryParameter;
            }
            $this->setApiUrl(sprintf($apiEndpoint, $siteKey));
        } else if ($type == FeedConfig::FEED_TYPE_FULL_MULTI_START) {
            $apiEndpoint = $this->helperData->getFullFeedApiEndpoint($store)."/start".$queryParameter;
            $this->setApiUrl(sprintf($apiEndpoint, $siteKey));
        }else if ($type == FeedConfig::FEED_TYPE_FULL_MULTI_END) {
            $apiEndpoint = $this->helperData->getFullFeedApiEndpoint($store)."/end".$queryParameter;
            $this->setApiUrl(sprintf($apiEndpoint, $siteKey));
        }else if ($type == FeedConfig::FEED_TYPE_INCREMENTAL) {
            $apiEndpoint = $this->helperData->getIncrementalFeedApiEndpoint($store);
            $this->setApiUrl(sprintf($apiEndpoint, $siteKey));
        } else if ($type == FeedConfig::FEED_TYPE_FULL_UPLOADED_STATUS) {
            $apiEndpoint = $this->helperData->getFullUploadedStatusApiEndpoint($store);
            $uploadId = $this->retrieveUploadId();
            if (!$uploadId) {
                return false;
            }
            $this->setApiUrl(sprintf($apiEndpoint, $siteKey, $uploadId));
        } else if ($type == FeedConfig::FEED_TYPE_INCREMENTAL_UPLOADED_STATUS) {
            $apiEndpoint = $this->helperData->getIncrementalUploadedStatusApiEndpoint($store);
            $uploadId = $this->retrieveUploadId();
            if (!$uploadId) {
                return false;
            }
            $this->setApiUrl(sprintf($apiEndpoint, $siteKey, $uploadId));
        } else if ($type == FeedConfig::FEED_TYPE_UPLOADED_SIZE) {
            $apiEndpoint = $this->helperData->getUploadedSizeApiEndpoint($store);
            $this->setApiUrl(sprintf($apiEndpoint, $siteKey));
        }

        return true;
    }

    /**
     * Throw error exception
     *
     * @param $string
     * @throws \Exception
     */
    private function doError($string)
    {
        throw new \Exception($string);
    }

    /**
     * Prepare and execute API call
     *
     * @param string $type
     * @param string $method
     * @param array $headers
     * @param array $params
     * @param null $store
     * @return $this
     * @throws \Exception
     */
    public function execute(
        $type = FeedConfig::FEED_TYPE_FULL,
        $method = \Laminas\Http\Request::METHOD_POST,
        $headers = [],
        $params = [],
        $store = null,
        $queryParameter = ""
    ) {
        $this->buildRequest($type, $method, $headers, $params, $store,$queryParameter);
        $this->call();

        return $this;
    }

    /**
     * Build API request
     *
     * @param string $type
     * @param string $method
     * @param array $headers
     * @param array $params
     * @param null $store
     * @return $this
     * @throws \Exception
     */
    private function buildRequest(
        $type = FeedConfig::FEED_TYPE_FULL,
        $method = \Laminas\Http\Request::METHOD_POST,
        $headers = [],
        $params = [],
        $store = null,
        $queryParameter = ""
    ) {
        if (!$this->prepareAuthorizationParams($store)) {
            $this->doError(__('Please provide API credentials to perform this operation.'));
        }

        if (!$this->prepareApiUrl($type, $store,$queryParameter)) {
            $this->doError(__('API url must be set up before using API calls.'));
        }

        $this->setHeaders($headers);
        $this->setParams($params);
        $this->setRequestMethod($method);

        return $this;
    }

    private function log($info){
        try{
            $this->logger->info($info);
        }catch(\Exception $e){
            $this->logger->error("Error printing url details");
        }
    }

    private function logObject($message,$object){
        try{
            $objectSerialised = print_r($object,true);
            $this->logger->info($message." ".$objectSerialised);
        }catch(\Exception $e){
            $this->logger->error("Error printing object details");
        }
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function call()
    {
        try {
            /** @var \Magento\Framework\HTTP\Adapter\Curl $httpAdapter */
            $httpAdapter = $this->curlFactory->create();
            $body = !empty($this->getParams()) ? $this->getParams() : '';
            $httpAdapter->write(
                $this->getRequestMethod(),
                $this->getApiUrl(),
                '1.1',
                $this->getHeaders(),
                $body
            );
            $this->log("Request URL".$httpAdapter->getInfo(CURLINFO_EFFECTIVE_URL));
            $result = $httpAdapter->read();
            $this->logObject("Raw Response",$result);
            if ($httpAdapter->getErrno()) {
                $this->doError(sprintf(
                    'API service connection error #%s: %s',
                    $httpAdapter->getErrno(),
                    $httpAdapter->getError()
                ));
            }
            $this->getResponseManager()->apply($result);
            $httpAdapter->close();
        } catch (\Exception $e) {
            $this->doError(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Retrieve response manager instance. Init if needed
     *
     * @return Response
     */
    private function getResponseManager()
    {
        if (null == $this->responseManager) {
            /** @var \Unbxd\ProductFeed\Model\Feed\Api\Response */
            $this->responseManager = $this->responseFactory->create();
        }

        return $this->responseManager;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->getResponseManager();
    }

    /**
     * @return $this
     */
    public function resetResponse()
    {
        $this->responseManager = null;

        return $this;
    }
}