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

use Magento\Framework\DataObject;
use Unbxd\ProductFeed\Helper\Data as HelperData;
use Unbxd\ProductFeed\Model\Serializer;
use Laminas\Http\Response as LaminasResponse;

/**
 * Class Response
 * @package Unbxd\ProductFeed\Model\Feed\Api
 */
class Response extends DataObject
{
    /**
     * HTTP RESPONSE CODES
     *
     * on feed processing (indexing) by Unbxd service
     */
    const HTTP_RESPONSE_CODE_FEED_INDEXING = '100';
    /**
     * default success code
     */
    const HTTP_RESPONSE_CODE_SUCCESS_DEFAULT = '200';
    /**
     * on successful feed upload
     */
    const HTTP_RESPONSE_CODE_SUCCESS_FEED_UPLOAD = '201';
    /**
     * on bad request
     */
    const HTTP_RESPONSE_CODE_BAD_REQUEST = '400';
    /**
     * on authentication failures
     */
    const HTTP_RESPONSE_CODE_AUTHENTICATION_FAILURE = '401';
    /**
     * on internal server errors
     */
    const HTTP_RESPONSE_CODE_INTERNAL_SERVER_ERROR = '500';
    /**
     * on failed indexing (internal Unbxd error)
     */
    const HTTP_RESPONSE_CODE_FAILED_INDEXING = '504';

    /**
     * API Response fields
     */
    const RESPONSE_FIELD_UPLOAD_ID = 'uploadId';
    const RESPONSE_FIELD_STATUS = 'status';
    const RESPONSE_FIELD_CODE = 'code';
    /**
     * data processing statuses
     */
    const RESPONSE_FIELD_STATUS_VALUE_INDEXING = 'INDEXING';
    const RESPONSE_FIELD_STATUS_VALUE_FAILED = 'FAILED';
    const RESPONSE_FIELD_STATUS_VALUE_INDEXED = 'INDEXED';

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Response from API request
     *
     * @var null
     */
    private $response = null;

    /**
     * Response HTTP code form API request
     *
     * @var string
     */
    private $code = '';

    /**
     * Response body from API request
     *
     * @var string
     */
    private $body = '';

    /**
     * Response body from API request as array
     *
     * @var array
     */
    private $bodyAsArray = [];

    /**
     * Response message from API request
     *
     * @var string
     */
    private $message = '';

    /**
     * Upload ID from API response
     *
     * @var null
     */
    private $uploadId = null;

    /**
     * @var int
     */
    private $uploadedSize = 0;

    /**
     * @var bool
     */
    private $isSuccess = false;

    /**
     * @var bool
     */
    private $isError = false;

    /**
     * @var bool
     */
    private $isProcessing = false;

    /**
     * Error recollected after each API call
     *
     * @var array
     */
    private $errors = [];

    /**
     * Response constructor.
     * @param HelperData $helperData
     * @param Serializer $serializer
     * @param array $data
     */
    public function __construct(
        HelperData $helperData,
        Serializer $serializer,
        array $data = []
    ) {
        parent::__construct($data);
        $this->helperData = $helperData;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Serializer::class);
    }

    /**
     * Creates a new LaminasResponse object from a string.
     *
     * @param string $response
     * @return LaminasResponse
     */
    public function createResponse($response)
    {
        return LaminasResponse::fromString($response);
    }

    /**
     * Apply response data
     *
     * @param $response
     * @return $this
     */
    public function apply($response)
    {
        /** @var LaminasResponse $resultResponse */
        $resultResponse = $this->createResponse($response);
        if ($resultResponse instanceof LaminasResponse) {
            if ($code = $resultResponse->getStatusCode()) {
                $this->setResponseCode($code);
            }

            $body = $resultResponse->getBody();
            if ($this->isStringIsNumeric($body)) {
                // in case if API request related to retrieve only uploaded size
                $this->setUploadedSize($body);
                return $this;
            } else {
                $this->setResponseBody($body);
            }

            if ($message = $resultResponse->getReasonPhrase()) {
                $this->setResponseMessage($message);
            }

            $this->setIsError();
            $this->setIsProcessing();
            $this->setIsSuccess();

            if ($this->getIsError()) {
                // error message maybe come from body?
                $this->setErrorMessageByCode();
            }

            if ($this->getIsSuccess()) {
                // use for check upload status and additional API call(s)
                $this->setUploadId();
            }
        }

        $this->setResponseData();

        return $this;
    }

    /**
     * @param $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return DataObject|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $code
     * @return void
     */
    public function setResponseCode($code)
    {
        $this->code = (string) $code;
    }

    /**
     * @return string
     */
    public function getResponseCode()
    {
        return $this->code;
    }

    /**
     * @param string $body
     * @return void
     */
    public function setResponseBody($body)
    {
        $this->body = (string) $body;
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->body;
    }

    /**
     * @param $size
     */
    public function setUploadedSize($size)
    {
        $this->uploadedSize = $size;
    }

    /**
     * @return string
     */
    public function getUploadedSize()
    {
        return $this->uploadedSize;
    }

    /**
     * @return array|bool|float|int|mixed|string|null
     */
    public function getResponseBodyAsArray()
    {
        $body = $this->getResponseBody();
        if ($body && is_string($body) && (strlen($body) > 0)) {
            $this->bodyAsArray = $this->serializer->unserialize($body);
        }

        return $this->bodyAsArray;
    }

    /**
     * @param $message
     * @return $this
     */
    public function setResponseMessage($message)
    {
        $this->message = (string) $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->message;
    }

    /**
     * @return $this
     */
    public function setUploadId()
    {
        $bodyData = $this->getResponseBodyAsArray();
        if (array_key_exists(self::RESPONSE_FIELD_UPLOAD_ID, $bodyData)) {
            $this->uploadId = $bodyData[self::RESPONSE_FIELD_UPLOAD_ID];
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * @param null $flag
     * @return $this
     */
    public function setIsSuccess($flag = null)
    {
        if (null === $flag) {
            $flag = in_array($this->getResponseCode(), [
                self::HTTP_RESPONSE_CODE_SUCCESS_DEFAULT,
                self::HTTP_RESPONSE_CODE_SUCCESS_FEED_UPLOAD
            ]);
            $flag = $flag && !$this->getIsProcessing();
        }

        $this->isSuccess = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * @param null $flag
     * @return $this
     */
    public function setIsError($flag = null)
    {
        if (null === $flag) {
            $flag = in_array($this->getResponseCode(), [
                self::HTTP_RESPONSE_CODE_BAD_REQUEST,
                self::HTTP_RESPONSE_CODE_AUTHENTICATION_FAILURE,
                self::HTTP_RESPONSE_CODE_INTERNAL_SERVER_ERROR,
                self::HTTP_RESPONSE_CODE_FAILED_INDEXING,
            ]);

            // error also may occur during indexing in Unbxd service
            // so we must also check response body to retrieve real response code,
            // because even if error occur during indexing, HTTP response code always will be 200
            if ($this->getResponseCode() == self::HTTP_RESPONSE_CODE_SUCCESS_DEFAULT) {
                $bodyData = $this->getResponseBodyAsArray();
                if (is_array($bodyData)) {
                    $status = array_key_exists(self::RESPONSE_FIELD_STATUS, $bodyData)
                        ? trim($bodyData[self::RESPONSE_FIELD_STATUS]?? '')
                        : null;

                    $flag = ($status == self::RESPONSE_FIELD_STATUS_VALUE_FAILED);
                }
            }
        }

        $this->isError = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsError()
    {
        return $this->isError;
    }

    /**
     * @param null $flag
     * @return $this
     */
    public function setIsProcessing($flag = null)
    {
        if (null === $flag) {
            $bodyData = $this->getResponseBodyAsArray();
            $status = array_key_exists(self::RESPONSE_FIELD_STATUS, $bodyData)
                ? trim($bodyData[self::RESPONSE_FIELD_STATUS]?? '')
                : null;

            $flag = ($status == self::RESPONSE_FIELD_STATUS_VALUE_INDEXING);
        }

        $this->isProcessing = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsProcessing()
    {
        return $this->isProcessing;
    }

    /**
     * @return $this
     */
    public function setErrorMessageByCode()
    {
        $code = $this->getResponseCode();
        $message = $this->getErrorMessageByCode($code);
        if ($message) {
            $this->setErrorMessage($code, $message);
            return $this;
        }

        $responseBody = $this->getResponseBodyAsArray();
        $errorMessage = array_key_exists('message', $responseBody) ? $responseBody['message'] : 'N/A';
        switch ($code) {
            case self::HTTP_RESPONSE_CODE_AUTHENTICATION_FAILURE:
                $message = __(
                    sprintf(
                        'API Response Error. Invalid Authorization Credentials. <br/> Code - %s. <br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
            case self::HTTP_RESPONSE_CODE_BAD_REQUEST:
                $message = __(
                    sprintf(
                        'API Response Error. Bad Request.<br/> Code - %s.<br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
            case self::HTTP_RESPONSE_CODE_INTERNAL_SERVER_ERROR:
                $message = __(
                    sprintf(
                        'API Response Error. Internal Server Error.<br/> Code - %s.<br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
            case self::HTTP_RESPONSE_CODE_FAILED_INDEXING:
                $message = __(
                    sprintf(
                        'API Response Error. Unbxd service internal error.<br/> Code - %s.<br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
            default:
                $message = __(
                    sprintf(
                        'API Response Error. Unexpected Error.<br/> Code - %s.<br/> Message - %s.',
                        $code,
                        $errorMessage
                    )
                );
                break;
        }

        $this->setErrorMessage($code, $message);

        return $this;
    }

    /**
     * @param array $errors
     * @return $this
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $code
     * @param $message
     * @return $this
     */
    public function setErrorMessage($code, $message)
    {
        $this->errors[$code] = (string) $message;

        return $this;
    }

    /**
     * @param $code
     * @return mixed|null
     */
    public function getErrorMessageByCode($code)
    {
        return isset($this->errors[$code]) ? $this->errors[$code] : null;
    }

    /**
     * @return string
     */
    public function getErrorsAsString()
    {
        $errors = $this->getErrors();
        $errorsResult = [];
        if (!empty($errors)) {
            foreach ($errors as $code => $message) {
                $errorsResult[] = $message;
            }
        }

        return implode("\n", $errorsResult);
    }

    /**
     * @param $string
     * @return bool
     */
    private function isStringIsNumeric($string)
    {
        return (bool) (is_numeric($string) && ($string == round($string, 0)));
    }

    /**
     * @return $this
     */
    public function setResponseData()
    {
        $this->setData([
            'code' => $this->getResponseCode(),
            'body' => $this->getResponseBody(),
            'message' => $this->getResponseMessage(),
            'upload_id' => $this->getUploadId(),
            'is_success' => $this->getIsSuccess(),
            'is_error' => $this->getIsError(),
            'is_processing' => $this->getIsProcessing(),
            'errors' => $this->getErrors()
        ]);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponseData()
    {
        return $this->getData();
    }
}
