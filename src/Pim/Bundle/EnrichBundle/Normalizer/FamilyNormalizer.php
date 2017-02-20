<?php

namespace Pim\Bundle\EnrichBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Filter\CollectionFilterInterface;
use Pim\Bundle\VersioningBundle\Manager\VersionManager;
use Pim\Component\Catalog\Model\FamilyInterface;
use Pim\Component\Catalog\Normalizer\Standard\FamilyNormalizer as StandardFamilyNormalizer;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Catalog\Repository\AttributeRequirementRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class FamilyNormalizer
 *
 * @author Alexandr Jeliuc <alex@jeliuc.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class FamilyNormalizer extends StandardFamilyNormalizer
{
    /** @var array */
    protected $supportedFormats = ['internal_api'];

    /** @var VersionManager */
    protected $versionManager;

    /** @var NormalizerInterface */
    protected $versionNormalizer;

    /** @var CollectionFilterInterface */
    protected $collectionFilter;

    /**
     * @param NormalizerInterface                     $translationNormalizer
     * @param CollectionFilterInterface               $collectionFilter
     * @param AttributeRepositoryInterface            $attributeRepository
     * @param AttributeRequirementRepositoryInterface $attributeRequirementRepo
     * @param VersionManager                          $versionManager
     * @param NormalizerInterface                     $versionNormalizer
     */
    public function __construct(
        NormalizerInterface $translationNormalizer,
        CollectionFilterInterface $collectionFilter,
        AttributeRepositoryInterface $attributeRepository,
        AttributeRequirementRepositoryInterface $attributeRequirementRepo,
        VersionManager $versionManager,
        NormalizerInterface $versionNormalizer
    ) {
        parent::__construct(
            $translationNormalizer,
            $collectionFilter,
            $attributeRepository,
            $attributeRequirementRepo
        );

        $this->versionManager = $versionManager;
        $this->versionNormalizer = $versionNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($family, $format = null, array $context = array())
    {
        $normalizedFamily = [
            'code'                   => $family->getCode(),
            'attributes'             => $this->normalizeAttributes($family),
            'attribute_as_label'     => null !== $family->getAttributeAsLabel()
                ? $family->getAttributeAsLabel()->getCode() : null,
            'attribute_requirements' => $this->normalizeRequirements($family),
            'labels'                 => $this->translationNormalizer->normalize($family, 'standard', $context),
        ];

        $firstVersion = $this->versionManager->getOldestLogEntry($family);
        $lastVersion = $this->versionManager->getNewestLogEntry($family);

        $created = null === $firstVersion ? null :
            $this->versionNormalizer->normalize($firstVersion, 'internal_api');
        $updated = null === $lastVersion ? null :
            $this->versionNormalizer->normalize($lastVersion, 'internal_api');

        $normalizedFamily['meta'] = [
            'id'      => $family->getId(),
            'form'    => 'pim-family-edit-form',
            'created' => $created,
            'updated' => $updated,
        ];

        return $normalizedFamily;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($family, $format = null)
    {
        return $family instanceof FamilyInterface &&
            in_array($format, $this->supportedFormats);
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeAttributes(FamilyInterface $family)
    {
        $attributes = $this->collectionFilter->filterCollection(
            $this->attributeRepository->findAttributesByFamily($family),
            'pim.internal_api.attribute.view'
        );

        $normalizedAttributes = [];
        foreach ($attributes as $attribute) {
            $normalizedAttributes[] = [
                'code' => $attribute->getCode(),
                'type' => $attribute->getAttributeType(),
                'group_code' => $attribute->getGroup()->getCode(),
                'labels' => $this->translationNormalizer->normalize($attribute, 'standard', []),
                'sort_order' => $attribute->getSortOrder(),
            ];
        }

        return $normalizedAttributes;
    }
}
