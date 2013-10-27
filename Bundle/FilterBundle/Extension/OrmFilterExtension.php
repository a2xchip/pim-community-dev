<?php

namespace Oro\Bundle\FilterBundle\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameters;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FilterBundle\Extension\Orm\FilterInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

class OrmFilterExtension extends AbstractExtension
{
    /**
     * Query param
     */
    const FILTER_ROOT_PARAM = '_filter';

    /** @var FilterInterface[] */
    protected $filters = [];

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(RequestParameters $requestParams, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        parent::__construct($requestParams);
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(array $config)
    {
        $filters = $this->accessor->getValue($config, Configuration::COLUMNS_PATH) ? : [];

        if (!$filters) {
            return false;
        }

        // validate extension configuration
        $this->validateConfiguration(
            new Configuration(array_keys($this->filters)),
            ['filters' => $this->accessor->getValue($config, Configuration::FILTERS_PATH)]
        );

        return $this->accessor->getValue($config, Builder::DATASOURCE_TYPE_PATH) == OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(array $config, DatasourceInterface $datasource)
    {
        $filters = $this->getFiltersToApply($config);
        $values  = $this->getValuesToApply($config);

        foreach ($filters as $filter) {
            if ($value = $this->accessor->getValue($values, sprintf('[%s]', $filter->getName()))) {
                $form = $filter->getForm();
                if (!$form->isSubmitted()) {
                    $form->submit($value);
                }

                if (!($form->isValid() && $filter->apply($datasource->getQuery(), $form->getData()))) {
                    throw new \LogicException(sprintf('Filter %s is not valid', $filter->getName()));
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(array $config, \stdClass $data)
    {
        $data->filters         = isset($data->filters) && is_array($data->filters) ? $data->filters : [];

        $data->state            = isset($data->state) && is_array($data->state) ? $data->state : [];
        $data->state['filters'] = isset($data->state['filters']) && is_array($data->state['filters'])
            ? $data->state['filters'] : [];


        $filters = $this->getFiltersToApply($config);
        $values  = $this->getValuesToApply($config);

        foreach ($filters as $filter) {
            if ($value = $this->accessor->getValue($values, sprintf('[%s]', $filter->getName()))) {
                $form = $filter->getForm();
                if (!$form->isSubmitted()) {
                    $form->submit($value);
                }

                if ($form->isValid()) {
                    $data->state['filters'][$filter->getName()] = $value;
                }
            }

            $metadata                = $filter->getMetadata();
            $data->filters[] = array_merge(
                $metadata,
                ['label' => $this->translator->trans($metadata['label'])]
            );
        }
    }

    /**
     * Add filter to array of available filters
     *
     * @param string          $name
     * @param FilterInterface $filter
     *
     * @return $this
     */
    public function addFilter($name, FilterInterface $filter)
    {
        $this->filters[$name] = $filter;

        return $this;
    }

    /**
     * Prepare filters array
     *
     * @param array $config
     *
     * @return FilterInterface[]
     */
    protected function getFiltersToApply(array $config)
    {
        $filters       = [];
        $filtersConfig = $this->accessor->getValue($config, Configuration::COLUMNS_PATH);

        foreach ($filtersConfig as $column => $filter) {
            $filters[] = $this->getFilterObject($column, $filter);
        }

        return $filters;
    }

    /**
     * Takes param from request and merge with default filters
     *
     * @param array $config
     *
     * @return array
     */
    protected function getValuesToApply(array $config)
    {
        $result = [];

        $filters = $this->accessor->getValue($config, Configuration::COLUMNS_PATH);

        $defaultFilters = $this->accessor->getValue($config, Configuration::DEFAULT_FILTERS_PATH) ? : [];
        $filterBy       = $this->requestParams->get(self::FILTER_ROOT_PARAM) ? : $defaultFilters;

        foreach ($filterBy as $column => $value) {
            if ($this->accessor->getValue($filters, sprintf('[%s]', $column))) {
                $result[$column] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns prepared filter object
     *
     * @param string $name
     * @param array  $config
     *
     * @return FilterInterface
     */
    protected function getFilterObject($name, array $config)
    {
        $type = $this->accessor->getValue($config, sprintf('[%s]', Configuration::TYPE_KEY));

        $filter = $this->filters[$type];
        $filter->init($name, $config);

        return clone $filter;
    }
}
