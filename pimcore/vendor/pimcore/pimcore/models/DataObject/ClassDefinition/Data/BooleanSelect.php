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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

class BooleanSelect extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    NormalizerInterface
{
    use Model\DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\SimpleNormalizerTrait;

    /** storage value for yes */
    const YES_VALUE = 1;

    /** storage value for no */
    const NO_VALUE = -1;

    /** storage value for empty */
    const EMPTY_VALUE = null;

    /** edit mode valze for empty */
    const EMPTY_VALUE_EDITMODE = 0;

    /**
     * Available options to select - Default options
     *
     * @var array
     */
    const DEFAULT_OPTIONS = [
        [
            'key' => 'empty',
            'value' => self::EMPTY_VALUE_EDITMODE,
        ],
        [
            'key' => 'yes',
            'value' => self::YES_VALUE,
        ],
        [
            'key' => 'no',
            'value' => self::NO_VALUE,
        ],
    ];

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'booleanSelect';

    /**
     * @internal
     *
     * @var string
     */
    public $yesLabel;

    /**
     * @internal
     *
     * @var string
     */
    public $noLabel;

    /**
     * @internal
     *
     * @var string
     */
    public $emptyLabel;

    /**
     * @internal
     *
     * @var array|array[]
     */
    public $options = self::DEFAULT_OPTIONS;

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'tinyint(1) null';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'tinyint(1) null';

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        // nothing to do
        return $this;
    }

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param int|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            $data = (int) $data;
        }

        if ($data === self::YES_VALUE) {
            return true;
        } elseif ($data === self::NO_VALUE) {
            return false;
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param int|bool|null $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param int|bool|null $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return int|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_numeric($data)) {
            $data = (bool) $data;
        }
        if ($data === true) {
            return self::YES_VALUE;
        } elseif ($data === false) {
            return self::NO_VALUE;
        }

        return null;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param bool|null $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if ($data === true) {
            return $this->getYesLabel();
        }
        if ($data === false) {
            return $this->getNoLabel();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** See parent class.
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $result = [];

        $diffdata = [];
        $diffdata['data'] = $data;
        $diffdata['disabled'] = false;
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->fieldtype;

        $value = '';
        foreach ($this->options as $option) {
            if ($option['value'] == $data) {
                $value = $option['key'];

                break;
            }
        }

        $diffdata['value'] = $value;
        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;

        $result[] = $diffdata;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        //TODO mandatory probably doesn't make much sense
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    /**
     * @param bool|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        return $data !== true && $data !== false;
    }

    /**
     * @param DataObject\ClassDefinition\Data\BooleanSelect $mainDefinition
     */
    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition)
    {
        $this->options = $mainDefinition->options;
        $this->width = $mainDefinition->width;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param DataObject\ClassDefinition\Data\BooleanSelect $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('Master', 'Main', __METHOD__))
        );

        $this->synchronizeWithMainDefinition($masterDefinition);
    }

    /**
     * @return string
     */
    public function getYesLabel()
    {
        return $this->yesLabel;
    }

    /**
     * @param string|null $yesLabel
     *
     * @return $this
     */
    public function setYesLabel($yesLabel)
    {
        $this->yesLabel = $yesLabel;
        $this->setOptionsEntry(self::YES_VALUE, $yesLabel);

        return $this;
    }

    /**
     * @param int $value
     * @param string $label
     *
     * @return void
     */
    public function setOptionsEntry($value, $label)
    {
        if (!is_array($this->options)) {
            $this->options = [
                ['key' => $label,
                'value' => $value,
                ],

            ];
        } else {
            foreach ($this->options as $idx => $option) {
                if ($option['value'] == $value) {
                    $option['key'] = $label;
                    $this->options[$idx] = $option;

                    break;
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getNoLabel()
    {
        return $this->noLabel;
    }

    /**
     * @param string $noLabel
     *
     * @return $this
     */
    public function setNoLabel($noLabel)
    {
        $this->noLabel = $noLabel;
        $this->setOptionsEntry(self::NO_VALUE, $noLabel);

        return $this;
    }

    /**
     * @return string
     */
    public function getEmptyLabel()
    {
        return $this->emptyLabel;
    }

    /**
     * @param string $emptyLabel
     *
     * @return $this
     */
    public function setEmptyLabel($emptyLabel)
    {
        $this->emptyLabel = $emptyLabel;
        $this->setOptionsEntry(self::EMPTY_VALUE_EDITMODE, $emptyLabel);

        return $this;
    }

    /**
     * @param bool|null $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        return $this->getDataForEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param bool|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return int
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if ($data === true) {
            return self::YES_VALUE;
        }
        if ($data === false) {
            return self::NO_VALUE;
        }

        return self::EMPTY_VALUE_EDITMODE;
    }

    /**
     * @param string $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return bool|null
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return bool|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        if ((int)$data === 1) {
            return true;
        } elseif ((int)$data === -1) {
            return false;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $value = $this->getDataFromObjectParam($object, $params);
        if ($value === null) {
            $value = '';
        } elseif ($value) {
            $value = '1';
        } else {
            $value = '0';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?bool';
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?bool';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return 'bool|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return 'bool|null';
    }
}
