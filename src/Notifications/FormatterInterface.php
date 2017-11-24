<?php

/**
 * @copyright   (c) 2014-17, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Notifications;

use Vrok\Entity\Notification;

/**
 * Define which functions a notification formatter must implement.
 */
interface FormatterInterface
{
    /**
     * Retrieve the subject to use for emails.
     *
     * @return string
     */
    public function getMailSubject(Notification $notification) : string;

    /**
     * Retrieve the text body to use for emails.
     *
     * @return string
     */
    public function getMailBodyText(Notification $notification) : string;

    /**
     * Retrieve the HTML body to use for emails.
     *
     * @return string
     */
    public function getMailBodyHTML(Notification $notification) : string;

    /**
     * Retrieve the (short) text to use for this notification, should be simple,
     * e.g. for text-to-speech output.
     *
     * @return string
     */
    public function getTextShort(Notification $notification) : string;

    /**
     * Retrieve the (long) text to use for this notification, could contain URLs,
     * e.g if the notification is sent to a chat/messenger/etc.
     *
     * @return string
     */
    public function getTextLong(Notification $notification) : ?string;

    /**
     * Retrieve the HTML to use for this notification, e.g. for display on the
     * website, can contain markup/links/buttons.
     *
     * @return string
     */
    public function getHtml(Notification $notification) : ?string;

    /**
     * Retrieve the title to use for this notification, may be empty.
     *
     * @return string
     */
    public function getTitle(Notification $notification) : ?string;
}
