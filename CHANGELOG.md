# Version 1.0.35
## 1.0.35 - Feb 20, 2020
### Improvements
- Implemented ability to manage data fields mapping between Unbxd and Magento.
- Code refactoring.

## 1.0.34 - Feb 14, 2020
### Improvements
- Added support all available product types.
- Added global notification, when product feed generation was initiated for download and was completed.

## 1.0.33 - Feb 12, 2020
### Fix Issues
- Fixed issue with incorrect field which responsible for link between product entity table and status attribute value table.

## 1.0.32 - Jan 10, 2019
### Improvements
- Implemented ability to generate/download product feed via backend.

## 1.0.31 - Dec 23, 2019
### Improvements
- Format 'Affected Entities' cell in indexing queue/feed view grids to prevent excessive display of information.
### Fix Issues
- Fixed issue when product attribute(s) sometimes are not included and not described in schema fields in feed (detected for EE).

## 1.0.30 - Dec 12, 2019
### Improvements
- Added the ability to setup API endpoints from backend.

## 1.0.29 - Nov 07, 2019
### Fix Issues
- Fixed factory name.

## 1.0.28 - Oct 30, 2019
### Fix Issues
- Fixed issue with missing type of argument in phpdoc. This caused a compilation error.

## 1.0.27 - Sep 17, 2019
### Fix Issues
- Fixed bug with incorrect category data in product feed, when the active child category 
belongs to an inactive parent category. 

## 1.0.26 - Sep 17, 2019
### New Features
- Implemented Related Cron Jobs UI grid.
- Added additional toolbar menu on Indexing Queue/Feed View/Related Cron Jobs listing pages.
- Added parameter to attributes which will allow to specify whether or not the attribute will be included in the product feed.
By default, all the attributes that the product uses will be included.
- Added badges to readme.
### Improvements
- Compatible with Magento ~2.1.
- Removed unused custom xml/xsd files and related classes.
- Added 'Upload ID' column on Feed View details layout.
- Display success message in 'Additional Information' column on Indexing Queue listing page,
if related index data has been rebuilt successfully.
- Updating the column 'Additional Information' on Feed View listing page, with information about total upload feed size, 
only after the corresponding cron task has been completed. In some cases, the Unbxd service doesn't 
returned the correct upload feed size immediately after synchronization.
### Fix Issues
- Fixed issue associated with not clearing the configuration cache after related operations are executed.
- Fixed issue with incorrect argument for product processing method after saving category, if affected product IDs is NULL. 
- Fixed issue with non-existing column in 'unbxd_productfeed_feed_view' table.
- Fixed issue with Unbxd logo in configuration tab.
- Fixed issue with Unbxd documentation reference links in configuration tab.

## 1.0.20 - Aug 21, 2019
### New Features
- Implemented new cron job for re-process product feed operation(s) which are in 'Error' state. 
Available to set the max number of attempts from backend.
- Added 'Repeat' action to Actions column on Indexing Queue listing page.
- Added 'Repeat' action to Actions column on Feed View listing page.
### Improvements
- Changed setup config section header block.
- Moved header block about product feed module from setup config section to catalog config section.
- Optimization of the process of forming the list of categories in the appropriate format.
- Removed 'System Information' column from Indexing Queue/Feed View details layout.
- Added number of attempts information on Indexing Queue/Feed View details layout.
- Improved Actions on Indexing Queue listing page. Now, only available action(s) for current record will be displayed.
- Improved Actions on Feed View listing page. Now, only available action(s) for current record will be displayed. 
### Fix Issues
- Don't logging information about empty operations related to product reindex into related log file.
This caused a problematic rendering Indexing Queue Grid on backend.

## 1.0.19 - Aug 08, 2019
### Fix Issues
- Uncaught Error: Call to a member function getBackend() on null in /app/vendor/unbxd/magento2-product-feed/Model/CacheManager.php:182
- Warning: date_format() expects parameter 1 to be DateTimeInterface, boolean given in /app/vendor/unbxd/magento2-product-feed/Helper/Data.php on line 487 
### Improvements
- Removed logic related to search module
- Added CHANGELOG.md
