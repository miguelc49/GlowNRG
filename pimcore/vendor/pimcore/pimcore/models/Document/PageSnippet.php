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

namespace Pimcore\Model\Document;

use Pimcore\Document\Editable\EditableUsageResolver;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Http\RequestHelper;
use Pimcore\Logger;
use Pimcore\Messenger\VersionDeleteMessage;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Loader\EditableLoaderInterface;

/**
 * @method \Pimcore\Model\Document\PageSnippet\Dao getDao()
 * @method \Pimcore\Model\Version|null getLatestVersion(?int $userId = null)
 */
abstract class PageSnippet extends Model\Document
{
    use Model\Element\Traits\ScheduledTasksTrait;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $controller;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $template;

    /**
     * Contains all content-editables of the document
     *
     * @internal
     *
     * @var array|null
     *
     */
    protected $editables = null;

    /**
     * Contains all versions of the document
     *
     * @internal
     *
     * @var array
     */
    protected $versions = null;

    /**
     * @internal
     *
     * @var null|int
     */
    protected $contentMainDocumentId;

    /**
     * @internal
     *
     * @var null|int
     */
    protected $contentMasterDocumentId;

    /**
     * @internal
     *
     * @var bool
     */
    protected bool $supportsContentMain = true;

    /**
     * @internal
     *
     * @var null|bool
     */
    protected $missingRequiredEditable = null;

    /**
     * @internal
     *
     * @var null|bool
     */
    protected $staticGeneratorEnabled = null;

    /**
     * @internal
     *
     * @var null|int
     */
    protected $staticGeneratorLifetime = null;

    /**
     * @internal
     *
     * @var array
     */
    protected $inheritedEditables = [];

    private static bool $getInheritedValues = false;

    public function __construct()
    {
        $this->contentMasterDocumentId = & $this->contentMainDocumentId;
    }

    public static function setGetInheritedValues(bool $getInheritedValues): void
    {
        self::$getInheritedValues = $getInheritedValues;
    }

    public static function getGetInheritedValues(): bool
    {
        return self::$getInheritedValues;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        // checking the required editables renders the document, so this needs to be
        // before the database transaction, see also https://github.com/pimcore/pimcore/issues/8992
        $this->checkMissingRequiredEditable();
        if ($this->getMissingRequiredEditable() && $this->getPublished()) {
            throw new Model\Element\ValidationException('Prevented publishing document - missing values for required editables');
        }

        return parent::save(...func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    protected function update($params = [])
    {
        // update elements
        $editables = $this->getEditables();
        $this->getDao()->deleteAllEditables();

        parent::update($params);

        if (is_array($editables) && count($editables)) {
            foreach ($editables as $editable) {
                if (!$editable->getInherited()) {
                    $editable->setDao(null);
                    $editable->setDocumentId($this->getId());
                    $editable->save();
                }
            }
        }

        // scheduled tasks are saved in $this->saveVersion();
        // save version if needed
        $this->saveVersion(false, false, $params['versionNote'] ?? null);
    }

    /**
     * @param bool $setModificationDate
     * @param bool $saveOnlyVersion
     * @param string $versionNote
     * @param bool $isAutoSave
     *
     * @return null|Model\Version
     *
     * @throws \Exception
     */
    public function saveVersion($setModificationDate = true, $saveOnlyVersion = true, $versionNote = null, $isAutoSave = false)
    {
        try {
            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $preUpdateEvent = new DocumentEvent($this, [
                    'saveVersionOnly' => true,
                    'isAutoSave' => $isAutoSave,
                ]);
                \Pimcore::getEventDispatcher()->dispatch($preUpdateEvent, DocumentEvents::PRE_UPDATE);
            }

            // set date
            if ($setModificationDate) {
                $this->setModificationDate(time());
            }

            // scheduled tasks are saved always, they are not versioned!
            $this->saveScheduledTasks();

            // create version
            $version = null;

            // only create a new version if there is at least 1 allowed
            // or if saveVersion() was called directly (it's a newer version of the object)
            $documentsConfig = \Pimcore\Config::getSystemConfiguration('documents');
            if ((is_null($documentsConfig['versions']['days'] ?? null) && is_null($documentsConfig['versions']['steps'] ?? null))
                || (!empty($documentsConfig['versions']['steps']))
                || !empty($documentsConfig['versions']['days'])
                || $setModificationDate) {
                $saveStackTrace = !($documentsConfig['versions']['disable_stack_trace'] ?? false);
                $version = $this->doSaveVersion($versionNote, $saveOnlyVersion, $saveStackTrace, $isAutoSave);
            }

            // hook should be also called if "save only new version" is selected
            if ($saveOnlyVersion) {
                $postUpdateEvent = new DocumentEvent($this, [
                    'saveVersionOnly' => true,
                    'isAutoSave' => $isAutoSave,
                ]);
                \Pimcore::getEventDispatcher()->dispatch($postUpdateEvent, DocumentEvents::POST_UPDATE);
            }

            return $version;
        } catch (\Exception $e) {
            $postUpdateFailureEvent = new DocumentEvent($this, [
                'saveVersionOnly' => true,
                'exception' => $e,
                'isAutoSave' => $isAutoSave,
            ]);
            \Pimcore::getEventDispatcher()->dispatch($postUpdateFailureEvent, DocumentEvents::POST_UPDATE_FAILURE);

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete()
    {
        // Dispatch Symfony Message Bus to delete versions
        \Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
            new VersionDeleteMessage(Service::getElementType($this), $this->getId())
        );

        // remove all tasks
        $this->getDao()->deleteAllTasks();

        parent::doDelete();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags(array $tags = []): array
    {
        $tags = parent::getCacheTags($tags);

        foreach ($this->getEditables() as $editable) {
            $tags = $editable->getCacheTags($this, $tags);
        }

        return $tags;
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveDependencies(): array
    {
        $dependencies = [parent::resolveDependencies()];

        foreach ($this->getEditables() as $editable) {
            $dependencies[] = $editable->resolveDependencies();
        }

        if ($this->getContentMainDocument() instanceof Document) {
            $mainDocumentId = $this->getContentMainDocument()->getId();
            $dependencies[] = [
                'document_' . $mainDocumentId => [
                    'id' => $mainDocumentId,
                    'type' => 'document',
                ],
            ];
        }

        return array_merge(...$dependencies);
    }

    /**
     * @return string
     */
    public function getController()
    {
        if (empty($this->controller)) {
            $this->controller = \Pimcore::getContainer()->getParameter('pimcore.documents.default_controller');
        }

        return $this->controller;
    }

    /**
     * @return string|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string|null $controller
     *
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @param string|null $template
     *
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set raw data of an editable (eg. for editmode)
     *
     * @internal
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     *
     * @return $this
     */
    public function setRawEditable(string $name, string $type, $data)
    {
        try {
            if ($type) {
                /** @var EditableLoaderInterface $loader */
                $loader = \Pimcore::getContainer()->get(Document\Editable\Loader\EditableLoader::class);
                $editable = $loader->build($type);

                $this->editables = $this->editables ?? [];
                $this->editables[$name] = $editable;
                $this->editables[$name]->setDataFromEditmode($data);
                $this->editables[$name]->setName($name);
                $this->editables[$name]->setDocument($this);
            }
        } catch (\Exception $e) {
            Logger::warning("can't set element " . $name . ' with the type ' . $type . ' to the document: ' . $this->getRealFullPath());
        }

        return $this;
    }

    /**
     * Set an element with the given key/name
     *
     * @param Editable $editable
     *
     * @return $this
     */
    public function setEditable(Editable $editable)
    {
        $this->getEditables();
        $this->editables[$editable->getName()] = $editable;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function removeEditable(string $name)
    {
        $this->getEditables();
        if (isset($this->editables[$name])) {
            unset($this->editables[$name]);
        }

        return $this;
    }

    /**
     * Get an editable with the given key/name
     *
     * @param string $name
     *
     * @return Editable|null
     */
    public function getEditable(string $name)
    {
        $editables = $this->getEditables();
        if (isset($this->editables[$name])) {
            return $editables[$name];
        }

        $inheritedEditable = null;
        if (array_key_exists($name, $this->inheritedEditables)) {
            $inheritedEditable = $this->inheritedEditables[$name];
        }

        if (!$inheritedEditable) {
            // check for content main document (inherit data)
            $contentMainDocument = $this->getContentMainDocument();
            if ($contentMainDocument instanceof self && $contentMainDocument->getId() != $this->getId()) {
                $inheritedEditable = $contentMainDocument->getEditable($name);
                if ($inheritedEditable) {
                    $inheritedEditable = clone $inheritedEditable;
                    $inheritedEditable->setInherited(true);
                    $this->inheritedEditables[$name] = $inheritedEditable;
                }
            }
        }

        return $inheritedEditable;
    }

    /**
     * @param int|string|null $contentMainDocumentId
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setContentMainDocumentId($contentMainDocumentId/*, bool $validate*/)
    {
        // this is that the path is automatically converted to ID => when setting directly from admin UI
        if (!is_numeric($contentMainDocumentId) && !empty($contentMainDocumentId)) {
            if ($contentMainDocument = Document\PageSnippet::getByPath($contentMainDocumentId)) {
                $contentMainDocumentId = $contentMainDocument->getId();
            } else {
                // Content main document was deleted or don't exist
                $contentMainDocumentId = null;
            }
        }

        // Don't set the content main document if the document is already part of the main document chain
        if ($contentMainDocumentId) {
            if ($currentContentMainDocument = Document\PageSnippet::getById($contentMainDocumentId)) {
                $validate = \func_get_args()[1] ?? false;
                $maxDepth = 20;
                do {
                    if ($currentContentMainDocument->getId() === $this->getId()) {
                        throw new \Exception('This document is already part of the main document chain, please choose a different one.');
                    }
                    $currentContentMainDocument = $currentContentMainDocument->getContentMainDocument();
                } while ($currentContentMainDocument && $maxDepth-- > 0 && $validate);
            } else {
                // Content main document was deleted or don't exist
                $contentMainDocumentId = null;
            }
        }

        $this->contentMainDocumentId = $contentMainDocumentId;

        return $this;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param int|string|null $contentMasterDocumentId
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setContentMasterDocumentId($contentMasterDocumentId/*, bool $validate*/)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('Master', 'Main', __METHOD__))
        );

        $this->setContentMainDocumentId($contentMasterDocumentId);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getContentMainDocumentId()
    {
        return $this->contentMainDocumentId;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @return int|null
     */
    public function getContentMasterDocumentId()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('Master', 'Main', __METHOD__))
        );

        return $this->getContentMainDocumentId();
    }

    /**
     * @return Document\PageSnippet|null
     */
    public function getContentMainDocument()
    {
        if ($mainDocumentId = $this->getContentMainDocumentId()) {
            return Document\PageSnippet::getById($mainDocumentId);
        }

        return null;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @return Document\PageSnippet|null
     */
    public function getContentMasterDocument()
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('Master', 'Main', __METHOD__))
        );

        return $this->getContentMainDocument();
    }

    /**
     * @param Document\PageSnippet|null $document
     *
     * @return $this
     */
    public function setContentMainDocument($document)
    {
        if ($document instanceof self) {
            $this->setContentMainDocumentId($document->getId(), true);
        } else {
            $this->setContentMainDocumentId(null);
        }

        return $this;
    }

    /**
     * @deprecated will be removed in Pimcore 11
     *
     * @param Document\PageSnippet|null $document
     *
     * @return $this
     */
    public function setContentMasterDocument($document)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.6.0',
            sprintf('%s is deprecated and will be removed in Pimcore 11. Use %s instead.', __METHOD__, str_replace('Master', 'Main', __METHOD__))
        );

        return $this->setContentMainDocument($document);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasEditable(string $name)
    {
        return $this->getEditable($name) !== null;
    }

    /**
     * @return Editable[]
     */
    public function getEditables(): array
    {
        if ($this->editables === null) {
            $documentEditables = $this->getDao()->getEditables();

            if (self::getGetInheritedValues() && $this->supportsContentMain() && $this->getContentMainDocument()) {
                $contentMainEditables = $this->getContentMainDocument()->getEditables();
                $documentEditables = array_merge($contentMainEditables, $documentEditables);
                $this->inheritedEditables = $documentEditables;
            }

            $this->setEditables($documentEditables);
        }

        return $this->editables;
    }

    /**
     * @param array|null $editables
     *
     * @return $this
     *
     */
    public function setEditables(?array $editables)
    {
        $this->editables = $editables;

        return $this;
    }

    /**
     * @return Model\Version[]
     */
    public function getVersions()
    {
        if ($this->versions === null) {
            $this->setVersions($this->getDao()->getVersions());
        }

        return $this->versions;
    }

    /**
     * @param array $versions
     *
     * @return $this
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @see Document::getFullPath
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getFullPath();
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();

        $blockedVars = ['inheritedEditables'];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * @param string|null $hostname
     * @param string|null $scheme
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getUrl($hostname = null, $scheme = null)
    {
        if (!$scheme) {
            $scheme = 'http://';

            /** @var RequestHelper $requestHelper */
            $requestHelper = \Pimcore::getContainer()->get(RequestHelper::class);
            if ($requestHelper->hasMainRequest()) {
                $scheme = $requestHelper->getMainRequest()->getScheme() . '://';
            }
        }

        if (!$hostname) {
            $hostname = \Pimcore\Config::getSystemConfiguration('general')['domain'];
            if (empty($hostname)) {
                if (!$hostname = \Pimcore\Tool::getHostname()) {
                    throw new \Exception('No hostname available');
                }
            }
        }

        $url = $scheme . $hostname;
        if ($this instanceof Page && $this->getPrettyUrl()) {
            $url .= $this->getPrettyUrl();
        } else {
            $url .= $this->getFullPath();
        }

        $site = \Pimcore\Tool\Frontend::getSiteForDocument($this);
        if ($site instanceof Model\Site && $site->getMainDomain()) {
            $url = $scheme . $site->getMainDomain() . preg_replace('@^' . $site->getRootPath() . '/?@', '/', $this->getRealFullPath());
        }

        return $url;
    }

    /**
     * checks if the document is missing values for required editables
     *
     * @return bool|null
     */
    public function getMissingRequiredEditable()
    {
        return $this->missingRequiredEditable;
    }

    /**
     * @param bool|null $missingRequiredEditable
     *
     * @return $this
     */
    public function setMissingRequiredEditable($missingRequiredEditable)
    {
        if ($missingRequiredEditable !== null) {
            $missingRequiredEditable = (bool) $missingRequiredEditable;
        }

        $this->missingRequiredEditable = $missingRequiredEditable;

        return $this;
    }

    /**
     * @internal
     *
     * @return bool
     */
    public function supportsContentMain(): bool
    {
        return $this->supportsContentMain;
    }

    /**
     * Validates if there is a missing value for required editable
     *
     * @internal
     */
    protected function checkMissingRequiredEditable()
    {
        // load data which must be requested
        $this->getProperties();
        $this->getEditables();

        //Allowed tags for required check
        $allowedTypes = ['input', 'wysiwyg', 'textarea', 'numeric'];

        if ($this->getMissingRequiredEditable() === null) {
            /** @var EditableUsageResolver $editableUsageResolver */
            $editableUsageResolver = \Pimcore::getContainer()->get(EditableUsageResolver::class);

            try {
                $documentCopy = Service::cloneMe($this);
                if ($documentCopy instanceof self) {
                    // rendering could fail if the controller/action doesn't exist, in this case we can skip the required check
                    $editableNames = $editableUsageResolver->getUsedEditableNames($documentCopy);
                    foreach ($editableNames as $editableName) {
                        $editable = $documentCopy->getEditable($editableName);
                        if ($editable instanceof Editable && in_array($editable->getType(), $allowedTypes)) {
                            $editableConfig = $editable->getConfig();
                            if ($editable->isEmpty() && isset($editableConfig['required']) && $editableConfig['required'] == true) {
                                $this->setMissingRequiredEditable(true);

                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // noting to do, as rendering the document failed for whatever reason
            }
        }
    }

    /**
     * @return bool|null
     */
    public function getStaticGeneratorEnabled(): ?bool
    {
        return $this->staticGeneratorEnabled;
    }

    /**
     * @param bool|null $staticGeneratorEnabled
     */
    public function setStaticGeneratorEnabled(?bool $staticGeneratorEnabled): void
    {
        $this->staticGeneratorEnabled = $staticGeneratorEnabled;
    }

    /**
     * @return int|null
     */
    public function getStaticGeneratorLifetime(): ?int
    {
        return $this->staticGeneratorLifetime;
    }

    /**
     * @param int|null $staticGeneratorLifetime
     */
    public function setStaticGeneratorLifetime(?int $staticGeneratorLifetime): void
    {
        $this->staticGeneratorLifetime = $staticGeneratorLifetime;
    }

    public function __wakeup()
    {
        $propertyMappings = [
            'contentMasterDocumentId' => 'contentMainDocumentId',
        ];

        foreach ($propertyMappings as $oldProperty => $newProperty) {
            if ($this->$newProperty === null) {
                $this->$newProperty = $this->$oldProperty;
                $this->$oldProperty = & $this->$newProperty;
            }
        }

        parent::__wakeup();
    }
}
