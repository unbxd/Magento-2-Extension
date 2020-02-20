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

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\App\RequestInterface;

/**
 * Customer IP manager
 *
 * Class CustomerIp
 * @package Unbxd\ProductFeed\Model
 */
class CustomerIp
{
    /**
     * Local IP address
     */
    const LOCAL_IP = '127.0.0.1';

    /**
     * @var array
     */
    protected $addressPath = [
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR'
    ];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * CustomerIp constructor.
     * @param RequestInterface $request
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        RequestInterface $request,
        RemoteAddress $remoteAddress
    ) {
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @return string
     */
    public function getCurrentIp()
    {
        foreach ($this->addressPath as $path) {
            $ip = $this->request->getServer($path);
            if ($ip) {
                if (strpos($ip, ',') !== false) {
                    $addresses = explode(',', $ip);
                    foreach ($addresses as $address) {
                        if (trim($address) != self::LOCAL_IP) {
                            return trim($address);
                        }
                    }
                } else {
                    if ($ip != self::LOCAL_IP) {
                        return $ip;
                    }
                }
            }
        }

        return $this->remoteAddress->getRemoteAddress();
    }
}
