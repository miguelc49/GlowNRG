<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element\ElementInterface;

/**
 * @method \Pimcore\Model\DataObject\Data\ElementMetadata\Dao getDao()
 */
class ElementMetadata extends Model\AbstractModel implements DataObject\OwnerAwareFieldInterface
{
    use DataObject\Traits\OwnerAwareFieldTrait;

    /**
     * @var string|null
     */
    protected $elementType;

    /**
     * @var int|null
     */
    protected $elementId;

    /**
     * @var string|null
     */
    protected $fieldname;

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param string|null $fieldname
     * @param array $columns
     * @param ElementInterface|null $element
     *
     * @throws \Exception
     */
    public function __construct($fieldname, $columns = [], $element = null)
    {
        $this->fieldname = $fieldname;
        $this->columns = $columns;
        $this->setElement($element);
    }

    /**
     * @param string|null $elementType
     * @param int|null $elementId
     */
    public function setElementTypeAndId($elementType, $elementId)
    {
        $this->elementType = $elementType;
        $this->elementId = $elementId;
        $this->markMeDirty();
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed|void
     *
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (str_starts_with($name, 'get')) {
            $key = substr($name, 3, strlen($name) - 3);
            $idx = array_searchi($key, $this->columns);

            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];

                return isset($this->data[$correctedKey]) ? $this->data[$correctedKey] : null;
            }

            throw new \Exception("Requested data $key not available");
        }

        if (str_starts_with($name, 'set')) {
            $key = substr($name, 3);
            $idx = array_searchi($key, $this->columns);

            if ($idx !== false) {
                $correctedKey = $this->columns[$idx];
                $this->data[$correctedKey] = $arguments[0];
                $this->markMeDirty();
            } else {
                throw new \Exception("Requested data $key not available");
            }
        }
    }

    /**
     * @param DataObject\Concrete $object
     * @param string $ownertype
     * @param string $ownername
     * @param string $position
     * @param int $index
     */
    public function save($object, $ownertype, $ownername, $position, $index)
    {
        $element = $this->getElement();
        $type = Model\Element\Service::getElementType($element);
        $this->getDao()->save($object, $ownertype, $ownername, $position, $index, $type);
    }

    /**
     * @param DataObject\Concrete $source
     * @param int $destinationId
     * @param string $fieldname
     * @param string $ownertype
     * @param string $ownername
     * @param string $position
     * @param int $index
     * @param string $destinationType
     *
     * @return DataObject\Data\ElementMetadata|null
     */
    public function load(DataObject\Concrete $source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index, $destinationType)
    {
        $return = $this->getDao()->load($source, $destinationId, $fieldname, $ownertype, $ownername, $position, $index, $destinationType);
        $this->markMeDirty(false);

        return $return;
    }

    /**
     * @param string $fieldname
     *
     * @return $this
     */
    public function setFieldname($fieldname)
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname()
    {
        return $this->fieldname;
    }

    /**
     * @param Model\Element\ElementInterface|null $element
     *
     * @return $this
     */
    public function setElement($element)
    {
        $this->markMeDirty();
        if (!$element) {
            $this->setElementTypeAndId(null, null);

            return $this;
        }

        $elementType = Model\Element\Service::getElementType($element);
        $elementId = $element->getId();
        $this->setElementTypeAndId($elementType, $elementId);

        return $this;
    }

    /**
     * @return Model\Element\ElementInterface|null
     */
    public function getElement()
    {
        if ($this->getElementType() && $this->getElementId()) {
            $element = Model\Element\Service::getElementById($this->getElementType(), $this->getElementId());
            if (!$element) {
                Logger::info('element ' . $this->getElementType() . ' ' . $this->getElementId() . ' does not exist anymore');
            }

            return $element;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getElementType()
    {
        return $this->elementType;
    }

    /**
     * @return int|null
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getElement()->__toString();
    }
}