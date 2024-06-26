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

namespace Pimcore\Model\Tool\Email;

use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool\Storage;

/**
 * @internal
 *
 * @method \Pimcore\Model\Tool\Email\Log\Dao getDao()
 */
class Log extends Model\AbstractModel
{
    /**
     * EmailLog Id
     *
     * @var null|int
     */
    protected $id;

    /**
     * Id of the email document or null if no document was given
     *
     * @var int|null
     */
    protected $documentId;

    /**
     * Parameters passed for replacement
     *
     * @var string|array
     */
    protected $params;

    /**
     * Modification date as timestamp
     *
     * @var int
     */
    protected $modificationDate;

    /**
     * The request URI from were the email was sent
     *
     * @var string
     */
    protected $requestUri;

    /**
     * The "from" email address
     *
     * @var string
     */
    protected $from;

    /**
     * Contains the reply to email addresses (multiple recipients are separated by a ",")
     *
     * @var string|null
     */
    protected $replyTo;

    /**
     * The "to" recipients (multiple recipients are separated by a ",")
     *
     * @var string|null
     */
    protected $to;

    /**
     * The carbon copy recipients (multiple recipients are separated by a ",")
     *
     * @var string|null
     */
    protected $cc;

    /**
     * The blind carbon copy recipients (multiple recipients are separated by a ",")
     *
     * @var string|null
     */
    protected $bcc;

    /**
     * Contains 1 if a html logfile exists and 0 if no html logfile exists
     *
     * @var int
     */
    protected $emailLogExistsHtml;

    /**
     * Contains 1 if a text logfile exists and 0 if no text logfile exists
     *
     * @var int
     */
    protected $emailLogExistsText;

    /**
     * Contains the timestamp when the email was sent
     *
     * @var int
     */
    protected $sentDate;

    /**
     * Contains the rendered html content of the email
     *
     * @var string
     */
    protected $bodyHtml;

    /**
     * Contains the rendered text content of the email
     *
     * @var string
     */
    protected $bodyText;

    /**
     * Contains the rendered subject of the email
     *
     * @var string
     */
    protected $subject;

    /**
     * Error log, when mail send resulted in failure - empty if successfully sent
     *
     * @var string|null
     */
    protected $error;

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setDocumentId($id)
    {
        $this->documentId = $id;

        return $this;
    }

    /**
     * @param string $requestUri
     *
     * @return $this
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;

        return $this;
    }

    /**
     * Returns the request uri
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * Returns the email log id
     *
     * @return int|null
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int)$id;

        return $this;
    }

    /**
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Returns the subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Returns the EmailLog entry by the given id
     *
     * @static
     *
     * @param int $id
     *
     * @return Log|null
     */
    public static function getById($id)
    {
        $id = (int)$id;
        if ($id < 1) {
            return null;
        }

        $emailLog = new Model\Tool\Email\Log();
        $emailLog->getDao()->getById($id);
        $emailLog->setEmailLogExistsHtml();
        $emailLog->setEmailLogExistsText();

        return $emailLog;
    }

    /**
     * Returns the email document id
     *
     * @return int|null
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param array|string $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Returns the dynamic parameter
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Sets the modification date
     *
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * Returns the modification date
     *
     * @return int - Timestamp
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * Sets the sent date and time
     *
     * @param int $sentDate - Timestamp
     *
     * @return $this
     */
    public function setSentDate($sentDate)
    {
        $this->sentDate = $sentDate;

        return $this;
    }

    /**
     * Returns the sent date and time as unix timestamp
     *
     * @return int
     */
    public function getSentDate()
    {
        return $this->sentDate;
    }

    /**
     *  Checks if a html log file exits and sets $this->emailLogExistsHtml to 0 or 1
     *
     * @return $this
     */
    public function setEmailLogExistsHtml()
    {
        $storage = Storage::get('email_log');
        $storageFile = $this->getHtmlLogFilename();
        $this->emailLogExistsHtml = $storage->fileExists($storageFile) ? 1 : 0;

        return $this;
    }

    /**
     * Returns 1 if a html email log file exists and 0 if no html log file exists
     *
     * @return int - 0 or 1
     */
    public function getEmailLogExistsHtml()
    {
        return $this->emailLogExistsHtml;
    }

    /**
     * Checks if a text log file exits and sets $this->emailLogExistsText to 0 or 1
     *
     * @return $this
     */
    public function setEmailLogExistsText()
    {
        $storage = Storage::get('email_log');
        $storageFile = $this->getTextLogFilename();
        $this->emailLogExistsText = $storage->fileExists($storageFile) ? 1 : 0;

        return $this;
    }

    /**
     * Returns 1 if a text email log file exists and 0 if no text log file exists
     *
     * @return int - 0 or 1
     */
    public function getEmailLogExistsText()
    {
        return $this->emailLogExistsText;
    }

    /**
     * Returns the filename of the html log
     *
     * @return string
     */
    public function getHtmlLogFilename()
    {
        return 'email-' . $this->getId() . '-html.log';
    }

    /**
     * Returns the filename of the text log
     *
     * @return string
     */
    public function getTextLogFilename()
    {
        return 'email-' . $this->getId() . '-txt.log';
    }

    /**
     * Returns the content of the html log file
     *
     * @return string|false
     */
    public function getHtmlLog()
    {
        if ($this->getEmailLogExistsHtml()) {
            $storage = Storage::get('email_log');

            return $storage->read($this->getHtmlLogFilename());
        }

        return false;
    }

    /**
     * Returns the content of the text log file
     *
     * @return string|false
     */
    public function getTextLog()
    {
        if ($this->getEmailLogExistsText()) {
            $storage = Storage::get('email_log');

            return $storage->read($this->getTextLogFilename());
        }

        return false;
    }

    /**
     * Removes the log file entry from the db and removes the log files on the system
     */
    public function delete()
    {
        $storage = Storage::get('email_log');
        $storage->delete($this->getHtmlLogFilename());
        $storage->delete($this->getTextLogFilename());
        $this->getDao()->delete();
    }

    public function save()
    {
        $this->getDao()->save();

        $storage = Storage::get('email_log');

        if ($html = $this->getBodyHtml()) {
            try {
                $storage->write($this->getHtmlLogFilename(), $html);
            } catch (FilesystemException | UnableToWriteFile $exception) {
                Logger::warn('Could not write html email log file.'.$exception.' LogId: ' . $this->getId());
            }
        }

        if ($text = $this->getBodyText()) {
            try {
                $storage->write($this->getTextLogFilename(), $text);
            } catch (FilesystemException | UnableToWriteFile $exception) {
                Logger::warn('Could not write text email log file.'.$exception.' LogId: ' . $this->getId());
            }
        }
    }

    /**
     * @param string|null $to
     *
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Returns the "to" recipients
     *
     * @return string|null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string|null $cc
     *
     * @return $this
     */
    public function setCc($cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * Returns the carbon copy recipients
     *
     * @return string|null
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param string|null $bcc
     *
     * @return $this
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * Returns the blind carbon copy recipients
     *
     * @return string|null
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @param string $from
     *
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Returns the "from" email address
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $replyTo
     *
     * @return $this
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * Returns the "replyTo" email address
     *
     * @return string|null
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * @param string $html
     *
     * @return $this
     */
    public function setBodyHtml($html)
    {
        $this->bodyHtml = $html;

        return $this;
    }

    /**
     * returns the html content of the email
     *
     * @return string|null
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setBodyText($text)
    {
        $this->bodyText = $text;

        return $this;
    }

    /**
     * Returns the text version of the email
     *
     * @return string
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string|null $error
     */
    public function setError(?string $error): void
    {
        $this->error = $error;
    }
}
