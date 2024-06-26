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

namespace Pimcore\Bundle\AdminBundle\Event\Login;

use Pimcore\Event\Traits\ResponseAwareTrait;
use Pimcore\Model\User;
use Symfony\Contracts\EventDispatcher\Event;

class LostPasswordEvent extends Event
{
    use ResponseAwareTrait;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $loginUrl;

    /**
     * @var bool
     */
    protected $sendMail = true;

    /**
     * @param User $user
     * @param string $loginUrl
     */
    public function __construct(User $user, $loginUrl)
    {
        $this->user = $user;
        $this->loginUrl = $loginUrl;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->loginUrl;
    }

    /**
     * Determines if lost password mail should be sent
     *
     * @return bool
     */
    public function getSendMail()
    {
        return $this->sendMail;
    }

    /**
     * Sets flag whether to send lost password mail or not
     *
     * @param bool $sendMail
     *
     * @return $this
     */
    public function setSendMail($sendMail)
    {
        $this->sendMail = (bool)$sendMail;

        return $this;
    }
}
