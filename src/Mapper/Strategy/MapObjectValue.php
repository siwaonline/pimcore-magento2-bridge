<?php
/**
 * @category    pimcore5-module-magento2-integration
 * @date        23/03/2018
 * @author      Michał Bolka <mbolka@divante.co>
 * @copyright   Copyright (c) 2018 DIVANTE (https://divante.co)
 */

namespace Divante\MagentoIntegrationBundle\Mapper\Strategy;

use Pimcore\Model\Webservice\Data\DataObject\Element;
use Divante\MagentoIntegrationBundle\Helper\MapperHelper;

/**
 * Class MapObjectValue
 * @package Divante\MagentoIntegrationBundle\Service\MapperService\Strategy
 */
class MapObjectValue extends AbstractMapStrategy
{
    const TYPE = 'object';
    const ALLOWED_TYPES_ARRAY = MapperHelper::OBJECT_TYPES;

    /**
     * @param Element $field
     * @param \stdClass $obj
     * @param array $arrayMapping
     * @param string|null $language
     * @param mixed $definition
     * @param string $className
     */
    public function map(Element $field, \stdClass &$obj, array $arrayMapping, $language, $definition, $className): void
    {
        $names = $this->getFieldNames($field, $arrayMapping);
        $pasredData = [
            'type' => $this->getFieldType($field),
            'value' => $this->getFieldValue($field),
            'label' => $this->getLabel($field, $language)
        ];
        foreach ($names as $name) {
            $obj->{$name} = $pasredData;
        }
    }

    /**
     * @param Element $field
     * @return array|object[]
     */
    protected function getFieldValue(Element $field)
    {
        if ($field->value) {
            if (in_array($field->type, MapperHelper::IMAGE_TYPES)) {
                return ['id' => $field->value, 'type' => 'asset'];
            } else {
                if ($field->value['type'] == 'asset') {
                    try {
                        $asset = \Pimcore\Model\Asset::getById($field->value['id']);
                        if (is_null($asset)) {
                            return $field->value;
                        } else {
                            return \Pimcore\Tool::getHostUrl() . $asset->getFullPath();
                        }
                    } catch (\Exception $e) {
                        return $field->value;
                    }
                }
                return $field->value;
            }
        }
    }

    protected function getFieldType(Element $field)
    {
        if ($field->value) {
            if (in_array($field->type, MapperHelper::IMAGE_TYPES)) {
                return self::TYPE;
            } else {
                if ($field->value['type'] == 'asset') {
                    try {
                        $asset = \Pimcore\Model\Asset::getById($field->value['id']);
                        if (is_null($asset)) {
                            return self::TYPE;
                        } else {
                            return MapTextValue::TYPE;
                        }
                    } catch (\Exception $e) {
                        return self::TYPE;
                    }
                }
                return self::TYPE;
            }
        } else {
            return self::TYPE;
        }
    }
}
