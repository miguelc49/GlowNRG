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

namespace Pimcore\Bundle\EcommerceFrameworkBundle;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;

/**
 * Interface for environment implementations of online shop framework
 */
interface EnvironmentInterface extends ComponentInterface
{
    /**
     * Returns current user id
     *
     * @return int
     */
    public function getCurrentUserId();

    /**
     * Sets current user id
     *
     * @param int $userId
     *
     * @return $this
     */
    public function setCurrentUserId($userId);

    /**
     * Checks if a user id is set
     *
     * @return bool
     */
    public function hasCurrentUserId();

    /**
     * Sets custom item to environment - which is saved to the session then
     * save()-call is needed to save the custom items
     *
     * @param string $key
     * @param mixed $value
     */
    public function setCustomItem($key, $value);

    /**
     * Removes custom item from the environment
     * save()-call is needed to save the custom items
     *
     * @param string $key
     */
    public function removeCustomItem($key);

    /**
     * Returns custom saved item from environment
     *
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getCustomItem($key, $defaultValue = null);

    /**
     * Returns all custom items from environment
     *
     * @return array
     */
    public function getAllCustomItems();

    /**
     * Resets environment
     * save()-call is needed to save changes
     */
    public function clearEnvironment();

    /**
     * Sets current assortment tenant which is used for indexing and product lists
     *
     * @param string|null $tenant
     */
    public function setCurrentAssortmentTenant($tenant);

    /**
     * Returns current assortment tenant which is used for indexing and product lists
     *
     * @return string|null
     */
    public function getCurrentAssortmentTenant();

    /**
     * Sets current assortment sub tenant which is used for indexing and product lists
     *
     * @param string|null $subTenant
     */
    public function setCurrentAssortmentSubTenant($subTenant);

    /**
     * Returns current sub assortment tenant which is used for indexing and product lists
     *
     * @return string|null
     */
    public function getCurrentAssortmentSubTenant();

    /**
     * Sets current checkout tenant which is used for cart and checkout manager
     *
     * @param string $tenant
     * @param bool $persistent - if set to false, tenant is not stored to session and only valid for current process
     */
    public function setCurrentCheckoutTenant($tenant, $persistent = true);

    /**
     * Returns current assortment tenant which is used for cart and checkout manager
     *
     * @return string
     */
    public function getCurrentCheckoutTenant();

    /**
     * Set the default currency in a multi-currency environment.
     *
     * @param Currency $currency
     */
    public function setDefaultCurrency(Currency $currency);

    /**
     * Returns instance of default currency
     *
     * @return Currency
     */
    public function getDefaultCurrency();

    /**
     * @return bool
     */
    public function getUseGuestCart();

    /**
     * @param bool $useGuestCart
     */
    public function setUseGuestCart($useGuestCart);

    /**
     * Returns current system locale
     *
     * @return null|string
     */
    public function getSystemLocale();
}
