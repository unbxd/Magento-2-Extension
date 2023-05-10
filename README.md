# Unbxd Product Feed Module For Magento 2
[![Latest Stable Version](https://poser.pugx.org/unbxd/magento2-product-feed/v/stable)](https://packagist.org/packages/unbxd/magento2-product-feed)
[![Total Downloads](https://poser.pugx.org/unbxd/magento2-product-feed/downloads)](https://packagist.org/packages/unbxd/magento2-product-feed)
[![License](https://poser.pugx.org/unbxd/magento2-product-feed/license)](https://packagist.org/packages/unbxd/magento2-product-feed)
[![composer.lock](https://poser.pugx.org/unbxd/magento2-product-feed/composerlock)](https://packagist.org/packages/unbxd/magento2-product-feed)

This module provide possibility for synchronization product catalog with Unbxd service.

Support Magento 2.1.\* || 2.2.\* || 2.3.\* || 2.4.\*

# Installation Guide

### Install by composer

```
composer require unbxd/magento2-product-feed
php bin/magento module:enable Unbxd_ProductFeed
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Manual installation

1. Download this module [Link](https://github.com/unbxd/Magento-2-Extension/archive/2.0.12.zip)
3. Unzip module in the folder:

    app\code\Unbxd\ProductFeed

4. Access the root of you Magento 2 instance from command line and run the following commands:

```
php bin/magento module:enable Unbxd_ProductFeed
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

5. Configure module in backend


