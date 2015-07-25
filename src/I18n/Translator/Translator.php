<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\Translator;

use Zend\I18n\Translator\TextDomain;
use Zend\I18n\Translator\Translator as ZendTranslator;

/**
 * Overwrite the default translator to support parameters (placeholders) that
 * are replaced in the translated message and injection of translations other
 * than the default loaders.
 */
class Translator extends ZendTranslator
{
    /**
     * Event fired when messages for a textDomain/locale are loaded.
     */
    const EVENT_LOAD_MESSAGES = 'loadMessages';

    /**
     * Translate a message.
     *
     * @param mixed  $msg
     * @param string $textDomain
     * @param string $locale
     *
     * @return string
     */
    public function translate($msg, $textDomain = 'default', $locale = null)
    {
        $message = $msg;
        $params  = [];

        if (is_array($msg)) {
            $message = array_shift($msg);

            // the parameters can even be a single value, in this case %0% is
            // replaced
            $params = (array) array_shift($msg);
        }

        $locale      = ($locale ?: $this->getLocale());
        $translation = $this->getTranslatedMessage($message, $locale, $textDomain);

        // the original code checked for $translation !== '',
        // for us an empty string is perfectly valid
        if ($translation !== null) {
            // additionally replace the parameters in the message, we don't
            // want to implement this functionality in all view helpers etc
            // that use the injected translator, the translate()-ViewHelper
            // and the controller plugin translate().
            foreach ($params as $k => $v) {
                $translation = str_replace('%'.$k.'%', $v, $translation);
            }

            return $translation;
        }

        if (null !== ($fallbackLocale = $this->getFallbackLocale())
            && $locale !== $fallbackLocale
        ) {
            return $this->translate([$message, $params], $textDomain, $fallbackLocale);
        }

        return $message;
    }

    /**
     * Load messages for a given language and domain.
     *
     * @triggers loadMessages.load-messages
     * @triggers loadMessages.no-messages-loaded
     *
     * @param string $textDomain
     * @param string $locale
     *
     * @throws Exception\RuntimeException
     */
    protected function loadMessages($textDomain, $locale)
    {
        if (!isset($this->messages[$textDomain])) {
            $this->messages[$textDomain] = [];
        }

        if (null !== ($cache = $this->getCache())) {
            $cacheId = 'Zend_I18n_Translator_Messages_'.md5($textDomain.$locale);

            if (null !== ($result = $cache->getItem($cacheId))) {
                $this->messages[$textDomain][$locale] = $result;

                return;
            }
        }

        $messagesLoaded = false;
        $messagesLoaded |= $this->loadMessagesFromRemote($textDomain, $locale);
        $messagesLoaded |= $this->loadMessagesFromPatterns($textDomain, $locale);
        $messagesLoaded |= $this->loadMessagesFromFiles($textDomain, $locale);

        // We want to allow a translation storage mechanism that can not use
        // one of the default loaders, e.g. because it uses multiple files per
        // locale.
        // We also need to ensure our translations get loaded after all module
        // default translations so instead of implementing a new function
        // "loadMessagesCustom" and to be as open as possible we use a new event
        // for this use case.
        if ($this->isEventManagerEnabled()) {
            $results = $this->getEventManager()->trigger(
                self::EVENT_LOAD_MESSAGES, $this, [
                    'locale'     => $locale,
                    'textDomain' => $textDomain,
            ]);

            // no listener should stop the event: if the event is stopped all
            // previous messages in the results are ignored
            if (!$results->stopped()) {
                $messagesLoaded |=
                    $this->addTextDomains($results, $textDomain, $locale);
            }
        }

        if (!$messagesLoaded) {
            // ZF would normally set NULL here but this causes isset() to fail
            // in getTranslatedMessage() and to trigger loadMessages() again
            // for each single message so we use an empty TextDomain here to
            // prevent that
            $discoveredTextDomain = new TextDomain();

            if ($this->isEventManagerEnabled()) {
                $results = $this->getEventManager()->trigger(
                    self::EVENT_NO_MESSAGES_LOADED,
                    $this,
                    [
                        'locale'      => $locale,
                        'text_domain' => $textDomain,
                    ],
                    function ($r) {
                        return ($r instanceof TextDomain);
                    }
                );
                $last = $results->last();
                if ($last instanceof TextDomain) {
                    $discoveredTextDomain = $last;
                }
            }

            $this->messages[$textDomain][$locale] = $discoveredTextDomain;
            $messagesLoaded                       = true;
        }

        if ($messagesLoaded && $cache !== null) {
            $cache->setItem($cacheId, $this->messages[$textDomain][$locale]);
        }
    }

    /**
     * Retrieve the TextDomain object.
     *
     * @param string $textDomain
     * @param string $locale
     *
     * @return TextDomain
     */
    public function getTextDomain($textDomain, $locale = null)
    {
        if (!isset($this->messages[$textDomain])) {
            $this->messages[$textDomain] = [];
        }
        $locale = $locale ?: $this->getLocale();

        if (!isset($this->messages[$textDomain][$locale])) {
            $this->loadMessages($textDomain, $locale);
        }

        return $this->messages[$textDomain][$locale];
    }

    /**
     * Allows to inject multiple TextDomain objects at once.
     *
     * @todo validate $textDomains
     *
     * @param array|ArrayAccess $textDomains
     * @param string            $domainName
     * @param string            $locale
     *
     * @return bool true if at least one TextDomain was injected.
     */
    public function addTextDomains($textDomains, $domainName, $locale)
    {
        $messagesLoaded = false;
        foreach ($textDomains as $object) {
            if ($object instanceof TextDomain) {
                $this->addTextDomain($object, $domainName, $locale);
                $messagesLoaded = true;
            }
        }

        return $messagesLoaded;
    }

    /**
     * Adds the given TextDomain to the available translated messages.
     * This should be implemented in the original Translator as this coulde
     * is duplicated multiple times and allows custom code to inject messages
     * that could not be loaded using the default loaders or should be loaded
     * in another order/priority.
     *
     * @todo Issue + PullRequest for ZF Repo
     *
     * @param TextDomain $textDomain
     * @param string     $domainName
     * @param string     $locale
     */
    public function addTextDomain(TextDomain $textDomain, $domainName, $locale)
    {
        if (!isset($this->messages[$domainName])) {
            $this->messages[$domainName] = [];
        }

        if (isset($this->messages[$domainName][$locale])
            && $this->messages[$domainName][$locale] instanceof TextDomain
        ) {
            $this->messages[$domainName][$locale]->merge($textDomain);
        } else {
            $this->messages[$domainName][$locale] = $textDomain;
        }
    }
}
