<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;
use JMS\Serializer\Annotation\Exclude;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Symfony\Component\Validator\ExecutionContext;

/**
 * Typed Address
 *
 * @ORM\MappedSuperclass
 */
class TypedAddress extends AddressBase
{
    /**
     * @var AddressType
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\AddressType")
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_primary", type="boolean", nullable=true)
     * @Soap\ComplexType("boolean", nillable=true)
     */
    protected $primary;

    /**
     * @var \Oro\Bundle\FlexibleEntityBundle\Model\AbstractFlexibleValue[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\AddressBundle\Entity\Value\AddressValue", mappedBy="entity", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Exclude
     */
    protected $values;

    /**
     * @param AddressType $type
     * @return TypedAddress
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return AddressType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param bool $primary
     */
    public function setPrimary($primary)
    {
        $this->primary = $primary;
    }

    /**
     * @return bool
     */
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return parent::isEmpty()
            && empty($this->type)
            && empty($this->primary);
    }
}
