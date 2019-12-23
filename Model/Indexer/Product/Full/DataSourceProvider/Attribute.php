<?php
/**
 * Copyright (c) 2019 Unbxd Inc.
 */

/**
 * Init development:
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */
namespace Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider;

use Unbxd\ProductFeed\Model\Eav\Indexer\Full\DataSourceProvider\AbstractAttribute;
use Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProviderInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Data source used to append attributes data to product during indexing.
 *
 * Class Attribute
 * @package Unbxd\ProductFeed\Model\Indexer\Product\Full\DataSourceProvider
 */
class Attribute extends AbstractAttribute implements DataSourceProviderInterface
{
    /**
     * Related data source code
     */
	const DATA_SOURCE_CODE = 'attribute';
	
    /**
     * @var array
     */
    private $forbiddenChildrenAttributeCode = ['visibility', 'status', 'price', 'tax_class_id'];

    /**
     * {@inheritdoc}
     */
	public function getDataSourceCode()
	{
		return self::DATA_SOURCE_CODE;
	}

    /**
     * {@inheritdoc}
     */
    public function appendData($storeId, array $indexData)
    {
        $productIds = array_keys($indexData);
        $indexData = $this->addAttributeData($storeId, $productIds, $indexData);

        $relationsByChildId = $this->resourceModel->loadChildren($productIds, $storeId);

        if (!empty($relationsByChildId)) {
            $allChildrenIds = array_keys($relationsByChildId);
            $childrenIndexData = $this->addAttributeData($storeId, $allChildrenIds);

            foreach ($childrenIndexData as $childrenId => $childrenData) {
                $enabled = isset($childrenData['status']) && current($childrenData['status']) == Status::STATUS_ENABLED;
                if ($enabled === false) {
                    unset($childrenIndexData[$childrenId]);
                }
            }

            foreach ($relationsByChildId as $childId => $relations) {
                foreach ($relations as $relation) {
                    $parentId = (int) $relation['parent_id'];
                    if (isset($indexData[$parentId]) && isset($childrenIndexData[$childId])) {
                        $indexData[$parentId]['children_ids'][] = $childId;
                        $this->addRelationData(
                            $indexData[$parentId],
                            $childrenIndexData[$childId],
                            $relation
                        );
                        $this->addChildData($indexData[$parentId], $childrenIndexData[$childId]);
                        $this->addChildSku($indexData[$parentId], $relation);
                    }
                }
            }
        }

        $indexData = $this->filterCompositeProducts($indexData);
        $indexData = $this->addIndexedFields($indexData);

        return $indexData;
    }

    /**
     * Add fields for schema
     *
     * @param $indexData
     * @return mixed
     */
    private function addIndexedFields($indexData)
    {
        $allAttributeFields = $this->getFields(); // prepared attribute data for schema fields
        $indexedAttributeFields = $this->getIndexedFields(); // indexed attribute codes as keys
        if (!empty($allAttributeFields) && !empty($indexedAttributeFields)) {
            $alreadyExistFields = array_key_exists('fields', $indexData) ? $indexData['fields'] : [];
            $schemaFields = array_intersect_key($allAttributeFields, $indexedAttributeFields);
            $indexData['fields'] = array_merge($alreadyExistFields, $allAttributeFields);
        }

        return $indexData;
    }

    /**
     * Append attribute data to the index.
     *
     * @param $storeId
     * @param $productIds
     * @param array $indexData
     * @return array
     * @throws \Exception
     */
    private function addAttributeData($storeId, $productIds, $indexData = [])
    {
        foreach ($this->attributeIdsByTable as $backendTable => $attributeIds) {
            $attributesData = $this->loadAttributesRawData($storeId, $productIds, $backendTable, $attributeIds);
            if (!empty($attributesData)) {
                foreach ($attributesData as $row) {
                    $productId = (int) $row['entity_id'];
                    $attribute = $this->attributesById[$row['attribute_id']];
                    $indexValues = $this->attributeHelper->prepareIndexValue($attribute, $storeId, $row['value']);
                    if (!isset($indexData[$productId])) {
                        $indexData[$productId] = [];
                    }
                    $indexData[$productId] += $indexValues;

                    if (!isset($indexData[$productId]['indexed_attributes'])) {
                        $indexData[$productId]['indexed_attributes'] = [];
                    }
                    $indexData[$productId]['indexed_attributes'][] = $attribute->getAttributeCode();

                    $this->setIndexedField($attribute->getAttributeCode());
                }
            }
        }

        return $indexData;
    }

    /**
     * Append data of child products to the parent.
     *
     * @param $parentData
     * @param $childAttributes
     */
    private function addChildData(&$parentData, $childAttributes)
    {
        $authorizedChildAttributes = $parentData['children_attributes'];
        $addedChildAttributesData = array_filter(
            $childAttributes,
            function ($attributeCode) use ($authorizedChildAttributes) {
                return in_array($attributeCode, $authorizedChildAttributes);
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($addedChildAttributesData as $attributeCode => $value) {
            if (!isset($parentData[$attributeCode])) {
                $parentData[$attributeCode] = [];
            }

            $parentData[$attributeCode] = array_values(array_unique(array_merge($parentData[$attributeCode], $value)));
        }
    }

    /**
     * Append relation information to the index for composite products.
     *
     * @param $parentData
     * @param $childAttributes
     * @param $relation
     */
    private function addRelationData(&$parentData, $childAttributes, $relation)
    {
        $childAttributeCodes = array_keys($childAttributes);
        $parentAttributeCodes = array_keys($parentData);

        if (!isset($parentData['children_attributes'])) {
            $parentData['children_attributes'] = ['indexed_attributes'];
        }

        $childrenAttributes = array_merge(
            $parentData['children_attributes'],
            array_diff($childAttributeCodes, $this->forbiddenChildrenAttributeCode)
        );

        if (isset($relation['configurable_attributes']) && !empty($relation['configurable_attributes'])) {
            $addedChildrenAttributes = array_diff(
                $childAttributeCodes,
                $this->forbiddenChildrenAttributeCode,
                $parentAttributeCodes
            );
            $childrenAttributes = array_merge($addedChildrenAttributes, $parentData['children_attributes']);

            if (!isset($parentData['configurable_attributes'])) {
                $parentData['configurable_attributes'] = [];
            }

            $configurableAttributesCodes = array_map(
                function ($attributeId) {
                    if (isset($this->attributesById[(int) $attributeId])) {
                        return $this->attributesById[(int) $attributeId]->getAttributeCode();
                    }
                },
                $relation['configurable_attributes']
            );

            $parentData['configurable_attributes'] = array_values(
                array_unique(array_merge($configurableAttributesCodes, $parentData['configurable_attributes']))
            );
        }

        $parentData['children_attributes'] = array_values(array_unique($childrenAttributes));
    }

    /**
     * Filter out composite product when no enabled children are attached.
     *
     * @param array $indexData Indexed data.
     *
     * @return array
     */
    private function filterCompositeProducts($indexData)
    {
        $compositeProductTypes = $this->resourceModel->getCompositeTypes();

        foreach ($indexData as $productId => $productData) {
            $isComposite = in_array($productData['type_id'], $compositeProductTypes);
            $hasChildren = isset($productData['children_ids']) && !empty($productData['children_ids']);
            if ($isComposite && !$hasChildren) {
                unset($indexData[$productId]);
            }
        }

        return $indexData;
    }

    /**
     * Append SKU of children product to the parent product index data.
     *
     * @param $parentData
     * @param $relation
     */
    private function addChildSku(&$parentData, $relation)
    {
        if (isset($parentData['children_sku']) && !is_array($parentData['children_sku'])) {
            $parentData['children_sku'] = [$parentData['children_sku']];
        }

        $parentData['children_sku'][] = $relation['sku'];
        $parentData['children_sku'] = array_unique($parentData['children_sku']);
    }
}
