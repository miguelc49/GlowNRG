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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;
use Pimcore\Model\DataObject\Localizedfield;

/**
 * @internal
 */
trait ContextPersistenceTrait
{
    /**
     * Enrich relation / slug with type-specific data.
     *
     * @param Concrete|Localizedfield|\Pimcore\Model\DataObject\Objectbrick\Data\AbstractData|AbstractData $object
     * @param array $params
     * @param string|null $classId
     * @param array $row
     * @param string $srcCol
     */
    protected function enrichDataRow($object, array $params, ?string &$classId, &$row = [], string $srcCol = 'src_id')
    {
        if (!$row) {
            $row = [];
        }

        if ($object instanceof Concrete) {
            $row[$srcCol] = $object->getId();
            $row['ownertype'] = 'object';

            $classId = $object->getClassId();
        } elseif ($object instanceof AbstractData) {
            $row[$srcCol] = $object->getObject()->getId(); // use the id from the object, not from the field collection
            $row['ownertype'] = 'fieldcollection';
            $row['ownername'] = $object->getFieldname();
            $row['position'] = $object->getIndex();

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof Localizedfield) {
            $row[$srcCol] = $object->getObject()->getId();
            $row['ownertype'] = 'localizedfield';
            $row['ownername'] = 'localizedfield';
            $context = $object->getContext();
            if (isset($context['containerType']) && ($context['containerType'] === 'fieldcollection' || $context['containerType'] === 'objectbrick')) {
                $fieldname = $context['fieldname'];
                $index = $context['index'] ?? $context['containerKey'] ?? null;
                $row['ownername'] = '/' . $context['containerType'] . '~' . $fieldname . '/' . $index . '/localizedfield~' . $row['ownername'];
            }

            $row['position'] = $params['language'];

            $classId = $object->getObject()->getClassId();
        } elseif ($object instanceof \Pimcore\Model\DataObject\Objectbrick\Data\AbstractData) {
            $row[$srcCol] = $object->getObject()->getId();
            $row['ownertype'] = 'objectbrick';
            $row['ownername'] = $object->getFieldname();
            $row['position'] = $object->getType();

            $classId = $object->getObject()->getClassId();
        }
    }
}
