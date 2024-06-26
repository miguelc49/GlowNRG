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

namespace Pimcore\Model\Tool\Targeting;

use Pimcore\Model;

/**
 * @internal
 *
 * @method Rule\Dao getDao()
 * @method void save()
 * @method void update()
 * @method void delete()
 */
class Rule extends Model\AbstractModel
{
    const SCOPE_HIT = 'hit';

    const SCOPE_SESSION = 'session';

    const SCOPE_SESSION_WITH_VARIABLES = 'session_with_variables';

    const SCOPE_VISITOR = 'visitor';

    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $scope = self::SCOPE_HIT;

    /**
     * @var bool
     */
    protected $active = true;

    /**
     * @var int
     */
    protected $prio = 0;

    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * @var array
     */
    protected $actions = [];

    /**
     * @param mixed $target
     *
     * @return bool
     */
    public static function inTarget($target)
    {
        if ($target instanceof Model\Tool\Targeting\Rule) {
            $targetId = $target->getId();
        } elseif (is_string($target)) {
            $target = self::getByName($target);
            if (!$target) {
                return false;
            } else {
                $targetId = $target->getId();
            }
        } else {
            $targetId = (int) $target;
        }

        if (array_key_exists('_ptc', $_GET) && (int)$targetId == (int)$_GET['_ptc']) {
            return true;
        }

        return false;
    }

    /**
     * Static helper to retrieve an instance of Tool\Targeting\Rule by the given ID
     *
     * @param int $id
     *
     * @return self|null
     */
    public static function getById($id)
    {
        try {
            $target = new self();
            $target->getDao()->getById((int)$id);

            return $target;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName($name)
    {
        try {
            $target = new self();
            $target->getDao()->getByName($name);

            return $target;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $actions
     *
     * @return $this
     */
    public function setActions($actions)
    {
        if (!$actions) {
            $actions = [];
        }

        $this->actions = $actions;

        return $this;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param array $conditions
     *
     * @return $this
     */
    public function setConditions($conditions)
    {
        if (!$conditions) {
            $conditions = [];
        }

        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        if (!empty($scope)) {
            $this->scope = $scope;
        }
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = (bool) $active;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return int
     */
    public function getPrio(): int
    {
        return $this->prio;
    }

    /**
     * @param int $prio
     */
    public function setPrio(int $prio)
    {
        $this->prio = $prio;
    }
}
