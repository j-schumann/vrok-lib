<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mail;

use Zend\Mail\Message as ZendMessage;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;
use Zend\Mime\Part as MimePart;
use Zend\View\HelperPluginManager as ViewHelperManager;

/**
 * Adds functionality to automatically translate the subject or body and
 * append the default (translated) signature if not specified otherwise.
 * Allows to use layouts for HTML mails where the content is embedded.
 */
class Message extends ZendMessage
{
    /**
     * Partial name to use as surrounding layout for the HTML email body.
     *
     * @var string
     */
    protected $layout = '';

    /**
     * The locale to use for translations.
     *
     * @var string
     */
    protected $locale = null;

    /**
     * The textDomain to use for translations.
     *
     * @var string
     */
    protected $textDomain = null;

    /**
     * View helper service locator.
     *
     * @var ViewHelperManager
     */
    protected $viewHelperManager = null;

    /**
     * Sets the ViewHelperManager instance and defaults to UTF8 encoding.
     *
     * @param ViewHelperManager $vhm
     */
    public function __construct(ViewHelperManager $vhm)
    {
        $this->viewHelperManager = $vhm;
        $this->setEncoding('UTF-8');
    }

    /**
     * Translates and sets the subject.
     *
     * @param string $subject
     * @param bool   $translate will try to translate the $html if true
     *
     * @return self
     */
    public function setSubject($subject, $translate = true)
    {
        return parent::setSubject($translate
            ? $this->translate($subject)
            : $subject
        );
    }

    /*
     * Translates the given text, replaces the parameters and sets the mime
     * message as body.
     *
     * @param string|array $text    the HTML body, may be a an array consisting of a
     *     translation-string and the params to replace within the translation
     * @param bool $appendSignature
     * @param bool $translate   will try to translate the $html if true
     * @return self
     */
    public function setTextBody($text, $appendSignature = true, $translate = true)
    {
        $part = $this->getTextPart($text, $translate, $appendSignature);

        $message = new MimeMessage();
        $message->addPart($part);

        return $this->setBody($message);
    }

    /*
     * Translates the given html, replaces the parameters and sets the mime
     * message as body.
     *
     * @param string|array $html    the HTML body, may be a an array consisting of a
     *     translation-string and the params to replace within the translation
     * @param bool $translate   will try to translate the $html if true
     * @param bool $appendSignature
     * @return self
     */
    public function setHtmlBody($html, $translate = true, $appendSignature = false)
    {
        $part = $this->getHtmlPart($html, $translate, $appendSignature);

        $message = new MimeMessage();
        $message->addPart($part);

        return $this->setBody($message);
    }

    /**
     * Creates a multipart/alternative message containing the HTML and text
     * content and adds it to the multipart/mixed body to allow further
     * attachments.
     *
     * @param MimePart $text
     * @param MimePart $html
     */
    public function setAlternativeBody(MimePart $text, MimePart $html)
    {
        $alternatives = new \Zend\Mime\Message();
        $alternatives->setParts([$text, $html]);

        $alternativesPart           = new \Zend\Mime\Part($alternatives->generateMessage());
        $alternativesPart->type     = 'multipart/alternative';
        $alternativesPart->boundary = $alternatives->getMime()->boundary();

        $body = new \Zend\Mime\Message();
        $body->addPart($alternativesPart);

        $this->setBody($body);
    }

    /**
     * Creates a Mime part for the given text content.
     *
     * @param string|array $text
     * @param bool         $translate
     * @param bool         $appendSignature
     *
     * @return MimePart
     *
     * @throws Exception\InvalidArgumentException
     */
    public function getTextPart($text, $translate = true, $appendSignature = true)
    {
        if (!is_string($text) && !is_array($text)) {
            throw new Exception\InvalidArgumentException('$text must be a string or array');
        }

        if ($translate) {
            $text = $this->translate($text);
        }

        if ($appendSignature) {
            $text .= $this->getSignature('text');
        }

        $part       = new MimePart($text);
        $part->type = Mime::TYPE_TEXT.'; charset=UTF-8';

        return $part;
    }

    /**
     * Creates a Mime part for the given HTML content.
     *
     * @param string|array $html
     * @param bool         $translate
     * @param bool         $appendSignature
     *
     * @return MimePart
     *
     * @throws InvalidArgumentException
     */
    public function getHtmlPart($html, $translate = true, $appendSignature = false)
    {
        if (!is_string($html) && !is_array($html)) {
            throw new InvalidArgumentException('$html must be a string or array');
        }

        if ($translate) {
            $html = $this->translate($html);
        }

        if ($appendSignature) {
            $html .= $this->getSignature('html');
        }

        if ($this->layout) {
            $partial = $this->getPartialHelper();
            $html    = $partial($this->layout, [
                'body'    => $html,
                'subject' => $this->getSubject(),
            ]);
        }

        $part       = new MimePart($html);
        $part->type = Mime::TYPE_HTML.'; charset=UTF-8';

        return $part;
    }

    /**
     * Translates the given message, replacing placeholders with the given
     * parameters.
     *
     * @param string|array $message
     *
     * @return string
     */
    protected function translate($message)
    {
        $translator = $this->getTranslateHelper();

        return $translator($message, $this->textDomain, $this->locale);
    }

    /**
     * Returns the default text or HTML signature.
     *
     * @param string $type
     *
     * @return string
     */
    public function getSignature($type = 'text')
    {
        switch ($type) {
            case 'html':
            case Mime::TYPE_HTML:
                $signature = $this->translate('mail.signature.html');

                return $signature !== 'mail.signature.html'
                    ? '<br /><br />--<br />'.$signature
                    : '';

            default:
                $signature = $this->translate('mail.signature.text');

                return $signature !== 'mail.signature.text'
                    ? "\n\n--\n".$signature
                    : '';
        }
    }

    /**
     * Retrieve the HTML mail layout partial.
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Sets the HTML mail layout partial.
     *
     * @param string $layout
     *
     * @return self
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Retrieve the used locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Sets the locale used.
     *
     * @param string $locale
     *
     * @return self
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Retrieve the used textDomain.
     *
     * @return string
     */
    public function getTextDomain()
    {
        return $this->textDomain;
    }

    /**
     * Sets the textDomain used.
     *
     * @param string $textDomain
     *
     * @return self
     */
    public function setTextDomain($textDomain)
    {
        $this->textDomain = $textDomain;

        return $this;
    }

    /**
     * @return \Zend\View\Helper\Partial
     */
    protected function getPartialHelper()
    {
        return $this->viewHelperManager->get('partial');
    }

    /**
     * @return \Zend\I18n\View\Helper\Translate
     */
    protected function getTranslateHelper()
    {
        return $this->viewHelperManager->get('translate');
    }
}
