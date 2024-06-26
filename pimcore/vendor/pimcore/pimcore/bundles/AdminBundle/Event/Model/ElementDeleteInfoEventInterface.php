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

namespace Pimcore\Bundle\AdminBundle\Event\Model;

use Pimcore\Event\Model\ElementEventInterface;

interface ElementDeleteInfoEventInterface extends ElementEventInterface
{
    /**
     * @return bool
     */
    public function getDeletionAllowed(): bool;

    /**
     * @param bool $deletionAllowed
     */
    public function setDeletionAllowed(bool $deletionAllowed): void;

    /**
     * @return string
     */
    public function getReason(): string;

    /**
     * @param string $reason
     */
    public function setReason(string $reason): void;
}
