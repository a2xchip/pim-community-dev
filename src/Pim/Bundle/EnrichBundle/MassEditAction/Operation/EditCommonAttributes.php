<?php

namespace Pim\Bundle\EnrichBundle\MassEditAction\Operation;

use Akeneo\Component\FileStorage\File\FileStorerInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Pim\Bundle\CatalogBundle\Builder\ProductBuilderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Edit common attributes of given products
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class EditCommonAttributes extends AbstractMassEditOperation
{
    /** @var string */
    protected $values;

    /** @var string */
    protected $errors;

    /** @var ProductBuilderInterface */
    protected $productBuilder;

    /** @var FileStorerInterface */
    protected $fileStorer;

    /** @var ObjectUpdaterInterface */
    protected $productUpdater;

    /** @var ValidatorInterface */
    protected $productValidator;

    /** @var NormalizerInterface */
    protected $normalizer;

    /**
     * @param ProductBuilderInterface $productBuilder
     * @param FileStorerInterface     $fileStorer
     * @param ObjectUpdaterInterface  $productUpdater
     * @param ValidatorInterface      $productValidator
     */
    public function __construct(
        ProductBuilderInterface $productBuilder,
        FileStorerInterface $fileStorer,
        ObjectUpdaterInterface $productUpdater,
        ValidatorInterface $productValidator,
        NormalizerInterface $normalizer
    ) {
        $this->productBuilder   = $productBuilder;
        $this->fileStorer       = $fileStorer;
        $this->productUpdater   = $productUpdater;
        $this->productValidator = $productValidator;
        $this->normalizer       = $normalizer;
        $this->values           = '';
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addGetterConstraint('validValues', new IsTrue([
            'message' => 'There are errors in the attributes form'
        ]));
    }

    public function hasValidValues()
    {
        $data = json_decode($this->values, true);

        $product = $this->productBuilder->createProduct('0');
        $this->productUpdater->update($product, $data);
        $violations = $this->productValidator->validate($product);

        $errors = ['values' => $this->normalizer->normalize($violations, 'internal_api')];
        $this->errors = json_encode($errors);

        return $violations->count() === 0;
    }

    /**
     * @param string $values
     *
     * @return EditCommonAttributes
     */
    public function setValues($values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return string
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return 'pim_enrich_mass_edit_common_attributes';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        $this->values = '';
    }

    /**
     * {@inheritdoc}
     *
     * Before sending configuration to the job, we store uploaded files.
     * This way, the job process can have access to uploaded files.
     */
    public function finalize()
    {
        // TODO: We need to move the uploaded files for the job to retrieve them

//        foreach ($this->getValues() as $productValue) {
//            $media = $productValue->getMedia();
//
//            if (null !== $media && null !== $media->getUploadedFile()) {
//                $file = $this->fileStorer->store($media->getUploadedFile(), FileStorage::CATALOG_STORAGE_ALIAS, true);
//                $productValue->setMedia($file);
//            }
//        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOperationAlias()
    {
        return 'edit-common-attributes';
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        $actions = [
            'normalized_values' => $this->getValues()
        ];

        return $actions;
    }

    /**
     * Get the code of the JobInstance
     *
     * @return string
     */
    public function getBatchJobCode()
    {
        return 'edit_common_attributes';
    }

    /**
     * Get the name of items this operation applies to
     *
     * @return string
     */
    public function getItemsName()
    {
        return 'product';
    }

    /**
     * @return string
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
