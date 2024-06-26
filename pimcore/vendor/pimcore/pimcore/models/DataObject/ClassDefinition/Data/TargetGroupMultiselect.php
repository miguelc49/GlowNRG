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
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\Tool;

class TargetGroupMultiselect extends Model\DataObject\ClassDefinition\Data\Multiselect
{
    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'targetGroupMultiselect';

    public function __wakeup(): void
    {
        $this->init();
    }

    /**
     * @internal
     *
     * @return $this
     */
    private function init(): static
    {
        $options = $this->getOptions();
        if (empty($options)) {
            $this->configureOptions();
        }

        return $this;
    }

    /**
     * @internal
     */
    public function configureOptions()
    {
        /** @var Tool\Targeting\TargetGroup\Listing|Tool\Targeting\TargetGroup\Listing\Dao $list */
        $list = new Tool\Targeting\TargetGroup\Listing();
        $list->setOrder('asc');
        $list->setOrderKey('name');

        $targetGroups = $list->load();

        $options = [];
        foreach ($targetGroups as $targetGroup) {
            $options[] = [
                'value' => $targetGroup->getId(),
                'key' => $targetGroup->getName(),
            ];
        }

        $this->setOptions($options);
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);
        if (\Pimcore::inAdmin()) {
            $obj->configureOptions();
        }

        return $obj;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        $this->init();

        return parent::getDataForResource($data, $object, $params);
    }

    /**
     * @return $this
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()// : static
    {
        if (Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveBlockedVars(): array
    {
        $blockedVars = parent::resolveBlockedVars();
        $blockedVars[] = 'options';

        return $blockedVars;
    }
}
