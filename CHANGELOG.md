# Version 1.0.74
## 1.0.74 - February 26, 2021
- Updated link attribute datatype in field schema
## 1.0.73 - February 17, 2021
- Updated link attribute datatype in field schema
## 1.0.72 - February 10, 2021
### Feature
- Updated the full job scheduler in multi site environment.
## 1.0.71 - January 04, 2021
### Feature
- Changed product url to be secure by default.
## 1.0.70 - November 26, 2020
### Feature
- Fixed media path to be store specific in a multistore setup.
## 1.0.69 - November 05, 2020
### Feature
- Fixed encoding issue
## 1.0.68 - October 05, 2020
### Feature
- Extended support for magento 2.4
- Fixed the index queue cleanup process to limit the scope to the respective stores.

## 1.0.67 - September 03, 2020
### Feature
- updated module helper function to be reused across unbxd extensions
## 1.0.66 - August 28, 2020
### Fix
- Added capability to download search data from Default Magento query log
## 1.0.65 - August 11, 2020
### Fix
- Updated the identity column for indexing queue view table
- Updated the documentation links to that of v2
## 1.0.64 - August 11, 2020
### Fix
- Extended support for php version 7.3.0
## 1.0.63 - August 11, 2020
### Fix
- Rearrange the setup form fields
- Updated the index view queueid from smallint to bigint
## 1.0.62 - August 06, 2020
### Fix
- Fixed the error in missing fields in schema
- Provision cleanup/archival of indexing queue and feedview logs
## 1.0.61 - July 20, 2020
### Fix
- Optimise incremental feed to cater for large loads and reimplement the incremental from date job to be same as the the scheduled one.
- Provision cleanup/archival of indexing queue and feedview logs
## 1.0.60 - July 20, 2020
### Fix
- Reset category cache to handle store specific labels in a multi store environment 
## 1.0.59 - July 6, 2020
### Fix
- Handle simple variants which are not visible and not in seqeunce 
- Handle top level category names in categorypath
## 1.0.58 - July 1, 2020
### Feature
- Skip products with visibility status not visible individually


## 1.0.57 - June 17, 2020
### Feature
- Added support for RECS V1

## 1.0.56 - June 15, 2020
### Fix Issues
- Fixed an issue with out of memory exception while loading feed view.
- Fixed an issue where category names were mapped incorrectly when the category has the same url_key

## 1.0.55 - May 30, 2020
### Fix Issues
- Fixed an issue to exclude products with visibility status as Not Visible Individually.


## 1.0.54 - May 27, 2020
### Fix Issues
- Fixed an issue where inactive categories where exported in the feed.


## 1.0.53 - May 18, 2020
### Fix Issues
- Fixed an issue where specific variant attributes where not part of the schema.

## 1.0.52 - May 11, 2020
### Fix Issues
- Fixed an issue with final price for default products.
- Added feature to export the components of the bundle which are also sold individually.


## 1.0.51 - Apr 28, 2020
### Fix Issues
- Fixed an issue with incorrect feed fields building.

## 1.0.50 - Apr 26, 2020
### Fix Issues
- Fixed issue with incorrect root directory formation in some environments. Being used to validate product cached images.

## 1.0.49 - Apr 25, 2020
### Fix Issues
- Fixed issue with incorrect validation for cached product images.

## 1.0.48 - Apr 20, 2020
### Improvements
- Added the ability to manage which product images will be transmitted in the feed.

## 1.0.47 - Apr 20, 2020
### Improvements
- Added encoding for unicode string for serialize feed content.
- Updated method to check if fields can be multi-valued.

## 1.0.46 - Apr 14, 2020
### Fix Issues
- Fixed issue with incorrect arguments order for column 'additional_information' in table 'unbxd_productfeed_feed_view'.

## 1.0.45 - Apr 09, 2020
### Improvements
- Included additional media attributes in feed.

## 1.0.44 - Apr 06, 2020
### Improvements
- Implemented CLI command for incremental catalog products synchronization from specific date.

## 1.0.43 - Apr 05, 2020
### Fix Issues
- Fixed issue with serialization of category data for some specific cases.
### Improvements
- Moved notification of maximum sync attempts to system information.

## 1.0.42 - Apr 03, 2020
### Fix Issues
- Fixed issue with custom data mapping in schema.

## 1.0.41 - Apr 02, 2020
### Fix Issues
- Changed definition for some date fields in related tables.
- Fixed issue with incorrect category building for multi store.

## 1.0.40 - Mar 20, 2020
### Improvements
- Added cron job for full feed synchronization.
- Reindex operation linked with synchronization process and vice versa.
- The feed file generated separately for each store (store ID in file name).
- Moved generated feed file for download to separate folder.
- Added delete product feed button in configuration.

## 1.0.38 - Mar 20, 2020
### Improvements
- Exposed Site Key in configuration. Changed field type to 'text'.

## 1.0.37 - Mar 02, 2020
### Improvements
- Implemented CLI command responsible for generate product feed for download.
- Code refactoring.
### Fix Issues
- Fixed issues related to multi store.
- Fixed issue related to generating feed for download.

## 1.0.36 - Feb 22, 2020
### Fix Issues
- Fixed an issue with some mapped fields that were not declared in the product feed schema.

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
