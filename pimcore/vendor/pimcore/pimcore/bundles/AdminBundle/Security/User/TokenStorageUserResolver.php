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

namespace Pimcore\Bundle\AdminBundle\Security\User;

use Pimcore\Bundle\AdminBundle\Security\User\User as UserProxy;

/**
 * @deprecated and will be removed in Pimcore 11. Use \Pimcore\Security\User\TokenStorageUserResolver instead.
 */
class TokenStorageUserResolver extends \Pimcore\Security\User\TokenStorageUserResolver
{
    /**
     * Taken and adapted from framework base controller.
     *
     * The proxy is the wrapping Pimcore\Security\User\User object implementing UserInterface.
     *
     * @return UserProxy|null
     */
    public function getUserProxy()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        if ($user instanceof UserProxy) {
            return $user;
        }

        return null;
    }
}
