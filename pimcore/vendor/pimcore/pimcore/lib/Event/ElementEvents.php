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

namespace Pimcore\Event;

final class ElementEvents
{
    /**
     * @Event("Pimcore\Event\Model\ElementEvent")
     *
     * @var string
     *
     * @deprecated Remove with Pimcore 11
     */
    const POST_ADD = 'pimcore.element.note.postAdd';
}
