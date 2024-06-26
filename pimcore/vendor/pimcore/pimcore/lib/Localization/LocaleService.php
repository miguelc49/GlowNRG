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

namespace Pimcore\Localization;

use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleService implements LocaleServiceInterface
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var null|RequestStack
     */
    protected $requestStack;

    /**
     * @var Translator|null
     */
    protected $translator;

    /**
     * @param RequestStack|null $requestStack
     * @param Translator|null $translator
     */
    public function __construct(RequestStack $requestStack = null, Translator $translator = null)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * @param string $locale
     *
     * @return bool
     */
    public function isLocale($locale)
    {
        $locales = array_flip($this->getLocaleList());
        $exists = isset($locales[$locale]);

        return $exists;
    }

    /**
     * @return string
     */
    public function findLocale()
    {
        if ($requestLocale = $this->getLocaleFromRequest()) {
            return $requestLocale;
        }

        $defaultLocale = \Pimcore\Tool::getDefaultLanguage();
        if ($defaultLocale) {
            return $defaultLocale;
        }

        return '';
    }

    /**
     * @return null|string
     */
    protected function getLocaleFromRequest()
    {
        if ($this->requestStack) {
            $mainRequest = $this->requestStack->getMainRequest();

            if ($mainRequest) {
                return $mainRequest->getLocale();
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getLocaleList()
    {
        return \ResourceBundle::getLocales('');
    }

    /**
     * @param string|null $locale
     *
     * @return array
     */
    public function getDisplayRegions($locale = null)
    {
        if (!$locale) {
            $locale = $this->findLocale();
        }

        $dataPath = PIMCORE_COMPOSER_PATH . '/umpirsky/country-list/data/';
        if (file_exists($dataPath . $locale . '/country.php')) {
            $regions = include($dataPath . $locale . '/country.php');
        } else {
            $regions = include($dataPath . 'en/country.php');
        }

        return $regions;
    }

    /**
     * @return string|null
     */
    public function getLocale()
    {
        if (null === $this->locale) {
            $this->locale = $this->getLocaleFromRequest();
        }

        return $this->locale;
    }

    /**
     * @param string|null $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        if ($locale && is_string($locale)) {
            if ($this->requestStack) {
                $mainRequest = $this->requestStack->getMainRequest();
                if ($mainRequest) {
                    $mainRequest->setLocale($locale);
                }

                $currentRequest = $this->requestStack->getCurrentRequest();
                if ($currentRequest) {
                    $currentRequest->setLocale($locale);
                }
            }

            if ($this->translator) {
                $this->translator->setLocale($locale);
            }
        }
    }

    /**
     * @return bool
     */
    public function hasLocale()
    {
        return $this->getLocale() !== null;
    }
}
