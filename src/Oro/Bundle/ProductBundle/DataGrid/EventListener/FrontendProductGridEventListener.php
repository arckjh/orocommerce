<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Updates configuration of frontend products grid and add filter or sorter on it
 * depends on product attributes configuration and information about product families that are used in products
 */
class FrontendProductGridEventListener
{
    /** @var AttributeManager */
    private $attributeManager;

    /** @var AttributeTypeRegistry */
    private $attributeTypeRegistry;

    /** @var AttributeConfigurationProviderInterface */
    private $configurationProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ProductRepository */
    private $productRepository;

    /** @var ServiceLink */
    private $datagridManagerLink;

    /** @var DatagridStateProviderInterface */
    private $filtersStateProvider;

    /** @var DatagridStateProviderInterface */
    private $sortersStateProvider;

    /** @var ConfigManager */
    private $configManager;

    /** @var bool */
    private $inProgress = false;

    /**
     * @param AttributeManager $attributeManager
     * @param AttributeTypeRegistry $attributeTypeRegistry
     * @param AttributeConfigurationProviderInterface $configurationProvider
     * @param ProductRepository $productRepository
     * @param DoctrineHelper $doctrineHelper
     * @param ServiceLink $datagridManagerLink
     * @param DatagridStateProviderInterface $filtersStateProvider
     * @param DatagridStateProviderInterface $sortersStateProvider
     * @param ConfigManager $configManager
     */
    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProviderInterface $configurationProvider,
        ProductRepository $productRepository,
        DoctrineHelper $doctrineHelper,
        ServiceLink $datagridManagerLink,
        DatagridStateProviderInterface $filtersStateProvider,
        DatagridStateProviderInterface $sortersStateProvider,
        ConfigManager $configManager
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->productRepository = $productRepository;
        $this->doctrineHelper = $doctrineHelper;
        $this->datagridManagerLink = $datagridManagerLink;
        $this->filtersStateProvider = $filtersStateProvider;
        $this->sortersStateProvider = $sortersStateProvider;
        $this->configManager = $configManager;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        if ($this->inProgress) {
            return;
        }
        $this->inProgress = true;

        $config = $event->getConfig();
        $attrs = $this->attributeManager->getAttributesByClass(Product::class);

        $addedFilterAttrs = [];
        $addedSorterAttrs = [];

        foreach ($attrs as $attr) {
            $attributeType = $this->getAttributeType($attr);
            if (!$attributeType) {
                continue;
            }

            $label = $this->configurationProvider->getAttributeLabel($attr);

            if ($attributeType->isFilterable($attr) && $this->configurationProvider->isAttributeFilterable($attr)) {
                $addedFilterAttrs[$attr->getId()] = $this->addFilter($config, $attr, $attributeType, $label);
            }

            if ($attributeType->isSortable($attr) && $this->configurationProvider->isAttributeSortable($attr)) {
                $addedSorterAttrs[$attr->getId()] = $this->addSorter($config, $attr, $attributeType, $label);
            }
        }

        $hideAttrs = $this->getAttributesToHide(
            $config,
            array_unique(array_merge(array_keys($addedFilterAttrs), array_keys($addedSorterAttrs)))
        );
        if ($hideAttrs) {
            $this->checkFilters($event, $addedFilterAttrs, $hideAttrs);
            $this->checkSorters($event, $addedSorterAttrs, $hideAttrs);
        }

        $this->inProgress = false;
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return null|SearchAttributeTypeInterface
     */
    protected function getAttributeType(FieldConfigModel $attribute)
    {
        if (!$this->configurationProvider->isAttributeActive($attribute)) {
            return null;
        }

        $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
        if (!$attributeType instanceof SearchAttributeTypeInterface) {
            return null;
        }

        return $attributeType;
    }

    /**
     * @param DatagridConfiguration $config
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $attributeType
     * @param string $label
     * @return string
     */
    protected function addFilter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        $label
    ) {
        $name = $attributeType->getFilterableFieldNames($attribute)[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';
        $alias = $this->clearName($name);
        $type = $attributeType->getFilterStorageFieldTypes()[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';

        $params = [
            'type' => $attributeType->getFilterType(),
            'data_name' => sprintf('%s.%s', $type, $name),
            'label' => $label
        ];

        if ($type && $this->configurationProvider->isAttributeFilterByExactValue($attribute)) {
            $params['force_like'] = true;
        }

        $config->addFilter($alias, $this->applyAdditionalParams($attribute, $attributeType, $params));

        return $alias;
    }

    /**
     * @param PreBuild $event
     * @param array $addedFilterAttrs
     * @param array $hideAttrs
     */
    protected function checkFilters(PreBuild $event, array $addedFilterAttrs, array $hideAttrs): void
    {
        $config = $event->getConfig();

        $filtersState = $this->filtersStateProvider->getState($config, $event->getParameters());
        foreach ($addedFilterAttrs as $attrId => $filterAlias) {
            // check that filter must be hidden and not in use
            if (in_array($attrId, $hideAttrs, true) && !array_key_exists($filterAlias, $filtersState)) {
                $config->removeFilter($filterAlias);
            }
        }
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $attributeType
     * @param array $params
     * @return array
     */
    protected function applyAdditionalParams(
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        array $params
    ) {
        $fieldType = $attributeType->getFilterStorageFieldTypes()[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';

        $entityFilterTypes = [
            SearchAttributeTypeInterface::FILTER_TYPE_ENUM,
            SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
            SearchAttributeTypeInterface::FILTER_TYPE_ENTITY,
        ];

        if (\in_array($attributeType->getFilterType(), $entityFilterTypes, true)) {
            $params['class'] = $this->getEntityClass($attribute);
        } elseif ($fieldType === Query::TYPE_TEXT) {
            $params['max_length'] = 255;
        } elseif ($fieldType === Query::TYPE_DECIMAL) {
            $params['options']['data_type'] = NumberFilterTypeInterface::DATA_DECIMAL;
        }

        return $params;
    }

    /**
     * @param DatagridConfiguration $config
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $attributeType
     * @param string $label
     * @return string
     */
    protected function addSorter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        $label
    ) {
        $name = $attributeType->getSortableFieldName($attribute);
        $alias = $this->clearName($name);

        $config->addColumn($alias, ['label' => $label]);
        $config->addSorter(
            $alias,
            [
                'data_name' => sprintf('%s.%s', $attributeType->getSorterStorageFieldType(), $name),
            ]
        );

        return $alias;
    }

    /**
     * @param PreBuild $event
     * @param array $addedSorterAttrs
     * @param array $hideAttrs
     */
    protected function checkSorters(PreBuild $event, array $addedSorterAttrs, array $hideAttrs): void
    {
        $config = $event->getConfig();

        $sortersState = $this->sortersStateProvider->getState($config, $event->getParameters());
        foreach ($addedSorterAttrs as $attrId => $sorterAlias) {
            // check that sorter must be hidden and not in use
            if (in_array($attrId, $hideAttrs, true) && !array_key_exists($sorterAlias, $sortersState)) {
                $config->removeSorter($sorterAlias);
            }
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function clearName($name)
    {
        $placeholders = ['_' . LocalizationIdPlaceholder::NAME => '', '_' . EnumIdPlaceholder::NAME => ''];

        return strtr($name, $placeholders);
    }

    /**
     * @param FieldConfigModel|null $attribute
     *
     * @return string|null
     */
    private function getEntityClass(FieldConfigModel $attribute)
    {
        $config = $attribute->toArray('extend');
        if (isset($config['target_entity'])) {
            return $config['target_entity'];
        }

        $fieldName = $attribute->getFieldName();
        $metadata = $this->doctrineHelper->getEntityMetadata($attribute->getEntity()->getClassName(), false);
        if (!$metadata || !$metadata->hasAssociation($fieldName)) {
            return null;
        }

        $mapping = $metadata->getAssociationMapping($fieldName);

        return $mapping['targetEntity'] ?? null;
    }

    /**
     * @param DatagridConfiguration $config
     * @param array $attributes
     * @return array
     */
    private function getAttributesToHide(DatagridConfiguration $config, array $attributes): array
    {
        $hideAttributes = [];

        $configKey = Configuration::getConfigKeyByName(Configuration::LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING);
        if ($this->configManager->get($configKey)) {
            /** @var Manager $datagridManager */
            $datagridManager = $this->datagridManagerLink->getService();

            /** @var SearchDatasource $datasource */
            $datasource = $datagridManager->getDatagrid($config->getName())
                ->acceptDatasource()
                ->getDatasource();

            $data = $this->productRepository
                ->getFamilyAttributeCountsQuery($datasource->getSearchQuery(), 'familyAttributesCount')
                ->getResult()
                ->getAggregatedData();

            $activeAttributeFamilyIds = [];
            if (!empty($data['familyAttributesCount'])) {
                $activeAttributeFamilyIds = array_keys($data['familyAttributesCount']);
            }
            $hideAttributes = $this->getDisabledSortAndFilterAttributes($attributes, $activeAttributeFamilyIds);
        }

        return $hideAttributes;
    }

    /**
     * @param array $attributes
     * @param array $activeAttributeFamilyIds
     *
     * @return array
     */
    private function getDisabledSortAndFilterAttributes(array $attributes, array $activeAttributeFamilyIds): array
    {
        /** @var AttributeFamilyRepository $attributeFamilyRepository */
        $attributeFamilyRepository = $this->doctrineHelper->getEntityRepository(AttributeFamily::class);
        $familyIdsForAttributes = $attributeFamilyRepository->getFamilyIdsForAttributes($attributes);

        return array_filter(
            $attributes,
            function ($attrId) use ($familyIdsForAttributes, $activeAttributeFamilyIds) {
                // skip attributes without product families or
                return empty($familyIdsForAttributes[$attrId]) ||
                    // skip attributes that are not included to active attribute families
                    empty(array_intersect($activeAttributeFamilyIds, $familyIdsForAttributes[$attrId]));
            }
        );
    }
}
