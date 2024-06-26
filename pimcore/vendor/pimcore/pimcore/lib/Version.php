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

namespace Pimcore;

use Composer\InstalledVersions;

/**
 * @internal
 */
final class Version
{
    const PACKAGE_NAME = 'pimcore/pimcore';

    private const MAJOR_VERSION = 10;

    public static function getMajorVersion(): int
    {
        return self::MAJOR_VERSION;
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return InstalledVersions::getPrettyVersion(self::PACKAGE_NAME);
    }

    /**
     * @return string
     */
    public static function getRevision()
    {
        return InstalledVersions::getReference(self::PACKAGE_NAME);
    }
}
