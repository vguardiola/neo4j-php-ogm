<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Metadata;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations\OrderBy;
use GraphAware\Neo4j\OGM\Annotations\Relationship;
use GraphAware\Neo4j\OGM\Common\Collection;
use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Lazy\LazyRelationshipCollection;
use GraphAware\Neo4j\OGM\Util\ClassUtils;

final class RelationshipMetadata
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var \ReflectionProperty
     */
    private $reflectionProperty;

    /**
     * @var \GraphAware\Neo4j\OGM\Annotations\Relationship
     */
    private $relationshipAnnotation;

    /**
     * @var bool
     */
    private $isLazy;

    /**
     * @var OrderBy
     */
    private $orderBy;

    /**
     * @param string                                         $className
     * @param \ReflectionProperty                            $reflectionProperty
     * @param \GraphAware\Neo4j\OGM\Annotations\Relationship $relationshipAnnotation
     * @param bool                                           $isLazy
     * @param OrderBy                                        $orderBy
     */
    public function __construct($className, \ReflectionProperty $reflectionProperty, Relationship $relationshipAnnotation, $isLazy = false, OrderBy $orderBy = null)
    {
        $this->className = $className;
        $this->propertyName = $reflectionProperty->getName();
        $this->reflectionProperty = $reflectionProperty;
        $this->relationshipAnnotation = $relationshipAnnotation;
        $this->isLazy = $isLazy;
        $this->orderBy = $orderBy;
        if (null !== $orderBy) {
            if (!in_array($orderBy->order, ['ASC', 'DESC'], true)) {
                throw new MappingException(sprintf('The order "%s" is not valid', $orderBy->order));
            }
        }
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return \ReflectionProperty
     */
    public function getReflectionProperty()
    {
        return $this->reflectionProperty;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->relationshipAnnotation->type;
    }

    /**
     * @return bool
     */
    public function isRelationshipEntity()
    {
        return null !== $this->relationshipAnnotation->relationshipEntity;
    }

    /**
     * @return bool
     */
    public function isCollection()
    {
        return true === $this->relationshipAnnotation->collection;
    }

    /**
     * @return bool
     */
    public function isLazy()
    {
        if (!$this->isCollection()) {
            return false;
        }

        return $this->isLazy;
    }

    /**
     * @return string
     */
    public function getDirection()
    {
        return $this->relationshipAnnotation->direction;
    }

    /**
     * @return string
     */
    public function getTargetEntity()
    {
        return ClassUtils::getFullClassName($this->relationshipAnnotation->targetEntity, $this->className);
    }

    /**
     * @return string
     */
    public function getRelationshipEntityClass()
    {
        return ClassUtils::getFullClassName($this->relationshipAnnotation->relationshipEntity, $this->className);
    }

    /**
     * @return bool
     */
    public function hasMappedByProperty()
    {
        return null !== $this->relationshipAnnotation->mappedBy;
    }

    /**
     * @return string
     */
    public function getMappedByProperty()
    {
        return $this->relationshipAnnotation->mappedBy;
    }

    /**
     * @return bool
     */
    public function hasOrderBy()
    {
        return null !== $this->orderBy;
    }

    /**
     * @return string
     */
    public function getOrderByPropery()
    {
        return $this->orderBy->property;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->orderBy->order;
    }

    /**
     * @param $object
     */
    public function initializeCollection($object)
    {
        if (!$this->isCollection()) {
            throw new \LogicException(sprintf('The property mapping this relationship is not of collection type in "%s"', $this->className));
        }

        if ($this->getValue($object) instanceof ArrayCollection || is_array($this->getValue($object)) || $this->getValue($object) instanceof LazyRelationshipCollection) {
            return;
        }

        if (null === $this->getValue($object)) {
            $this->setValue($object, new Collection());

            return;
        }

        //throw new \RuntimeException(sprintf('Unexpected initial value in %s', $this->className));
    }

    /**
     * @param object $object
     * @param mixed  $value
     */
    public function addToCollection($object, $value)
    {
        if (!$this->isCollection()) {
            throw new \LogicException(sprintf('The property mapping of this relationship is not of collection type in "%s"', $this->className));
        }

        /** @var Collection $coll */
        $coll = $this->getValue($object);
        $toAdd = true;
        $oid2 = spl_object_hash($value);
        foreach ($coll->toArray() as $el) {
            $oid1 = spl_object_hash($el);
            if ($oid1 === $oid2) {
                $toAdd = false;
            }
        }

        if ($toAdd) {
            $coll->add($value);
        }
    }

    public function addToCollectionAdvanced($object, $value, NodeEntityMetadata $valueMetadata)
    {
        if (!$this->isCollection()) {
            throw new \LogicException(sprintf('The property mapping of this relationship is not of collection type in "%s"', $this->className));
        }

        /** @var Collection $coll */
        $coll = $this->getValue($object);
        foreach ($coll->toArray() as $el) {
            $eid = $valueMetadata->getIdValue($valueMetadata);
            $vid = $valueMetadata->getIdValue($valueMetadata);
            if ($eid !== $vid) {
                $coll->add($value);
            }
        }
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    public function getValue($object)
    {
        $this->reflectionProperty->setAccessible(true);

        return $this->reflectionProperty->getValue($object);
    }

    /**
     * @param $object
     * @param $value
     */
    public function setValue($object, $value)
    {
        $this->reflectionProperty->setAccessible(true);
        $this->reflectionProperty->setValue($object, $value);
    }
}
