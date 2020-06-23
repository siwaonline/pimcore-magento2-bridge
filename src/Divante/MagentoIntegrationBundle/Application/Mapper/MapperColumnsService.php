<?php
/**
 * @category    pimcore5-module-magento2-integration
 * @date        20/01/2020
 * @author      Michał Bolka <mbolka@divante.co>
 * @copyright   Copyright (c) 2020 Divante Ltd. (https://divante.co)
 */

namespace Divante\MagentoIntegrationBundle\Application\Mapper;

use Divante\MagentoIntegrationBundle\Application\Mapper\Strategy\Custom\CustomStrategyInterface;
use Divante\MagentoIntegrationBundle\Domain\Common\ObjectTypeHelper;
use Divante\MagentoIntegrationBundle\Domain\IntegrationConfiguration\ObjectMappingsDefaults;
use Divante\MagentoIntegrationBundle\Domain\Mapper\Model\FromColumn;
use Divante\MagentoIntegrationBundle\Domain\Mapper\Model\ToColumn;
use Pimcore\Model\DataObject;

/**
 * Class MapperColumnsService
 * @package Divante\MagentoIntegrationBundle\Domain\Mapper
 */
class MapperColumnsService
{
    const PRODUCT_DEFAULT_FIELDS = [
        "name"
    ];

    /** @var MapperService  */
    private $mapperService;

    /** @var CustomStrategyInterface[] */
    private $strategies;

    /**
     * MapperColumnsService constructor.
     * @param MapperService $mapperService
     */
    public function __construct(iterable $strategies, MapperService $mapperService)
    {
        $this->strategies = iterator_to_array($strategies);
        $this->mapperService = $mapperService;
    }

    /**
     * @param        $configurationId
     * @param string $className
     * @return array
     */
    public function getColumnsForClass($configurationId, $objectId, string $className): array
    {
        $returnValue = ['success' => false];
        $definition = null;
        try {
            $configuration = DataObject\IntegrationConfiguration::getById($configurationId);
            if (!$configuration) {
                throw new \InvalidArgumentException();
            }
            $definition = DataObject\ClassDefinition::getById($objectId);
            if ($definition instanceof DataObject\ClassDefinition) {
                $mappingAttribute      = 'get' . ucfirst($className) . 'Mapping';
                $returnValue           = $this->getDataForClassDefinition(
                    $definition,
                    $configuration->{$mappingAttribute}(),
                    $className
                );
                $emptyValue            = new ToColumn();
                $emptyValue->fieldtype = 'input';
            }
        } catch (\Exception $exception) {
        }
        return $returnValue;
    }

    /**
     * @param $definition
     * @param $standardStructure
     * @param $type
     * @return array
     * @throws \Exception
     */
    protected function getDataForClassDefinition($definition, $standardStructure, $type): array
    {
        if (!$standardStructure) {
            switch ($type) {
                case ObjectTypeHelper::PRODUCT:
                    $standardStructure = ObjectMappingsDefaults::DEFAULT_PRODUCT_MAPPINGS;
                    break;
                case ObjectTypeHelper::CATEGORY:
                    $standardStructure = ObjectMappingsDefaults::DEFAULT_CATEGORY_MAPPINGS;
                    break;
                default:
                    throw new \InvalidArgumentException("Provided type (" . $type . ") is not supported");
            }
        }
        if (!$definition instanceof DataObject\ClassDefinition) {
            return ['success' => false];
        }
        $fromColumns = $this->mapperService->getClassDefinitionForFieldSelection($definition);
        $standardStructure =  array_filter($standardStructure, function ($elem) {
            return $elem[1] != null;
        });
        $toColumns = array_map([$this, 'getToObjectForMapElement'], $standardStructure);
        array_push($fromColumns, $this->getEmptyFromColumnValue());
        $mapping = array_map([$this, 'getMappingForMapElement'], $standardStructure);

        return [
            'success'          => true,
            'mapping'          => $mapping,
            'fromColumns'      => $fromColumns,
            'toColumns'        => $toColumns,
            'bricks'           => [],
            'fieldcollections' => [],
            'strategies'       => $this->strategies
        ];
    }

    /**
     * @param $mapElement
     * @return mixed
     */
    protected function getToObjectForMapElement($mapElement)
    {
        if (!$mapElement[1]) {
            return null;
        }
        $requiredFields = $this->mapperService->getRequiredFields();
        $obj = new ToColumn();
        $obj->setLabel($mapElement[1]);
        $obj->setIdentifier($mapElement[1]);
        $obj->setFieldtype('input');
        $obj->setConfig(['required' => in_array($mapElement[1], $requiredFields)]);
        return $obj;
    }

    /**
     * @param $mapElement
     * @return array
     */
    protected function getMappingForMapElement($mapElement)
    {
        if (!$mapElement[1]) {
            return null;
        }

        return [
            'config' => null,
            'fromColumn' => $mapElement[0] ?? null,
            'identifier' => $mapElement[0] ?? null,
            'strategy' => $mapElement[2] ?? null,
            'attributes' => $mapElement[3] ?? null,
            'searchable' => $mapElement[4],
            'filterable' => $mapElement[5],
            'comparable' => $mapElement[6],
            'visible_on_front' => $mapElement[7],
            'used_in_product_listing' => $mapElement[8],
            'interpreterConfig' => null,
            'primaryIdentifier' => false,
            'setter' => null,
            'setterConfig' => null,
            'toColumn' => $mapElement[1]
        ];
    }

    /**
     * @return FromColumn
     */
    protected function getEmptyFromColumnValue(): FromColumn
    {
        $emptyValue = new FromColumn();
        $emptyValue->setLabel('(Empty)');
        $emptyValue->setIdentifier('');
        return $emptyValue;
    }
}
