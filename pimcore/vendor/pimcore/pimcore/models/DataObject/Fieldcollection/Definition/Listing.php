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

namespace Pimcore\Model\DataObject\Fieldcollection\Definition;

use Pimcore\Model\DataObject\Fieldcollection\Definition;

class Listing
{
    /**
     * @return Definition[]
     */
    public function load()
    {
        $fields = [];

        $fieldCollectionFolders = array_unique([PIMCORE_CLASS_DEFINITION_DIRECTORY . '/fieldcollections', PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY . '/fieldcollections']);

        foreach ($fieldCollectionFolders as $fieldCollectionFolder) {
            $files = glob($fieldCollectionFolder . '/*.php');

            foreach ($files as $file) {
                $fields[] = include $file;
            }
        }

        return $fields;
    }
}
