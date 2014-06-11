<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mail;

use Zend\I18n\Translator\TranslatorInterface;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;
use Zend\Mail\Message as ZendMessage;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;
use Zend\Mime\Part as MimePart;

/**
 * Adds functionality to automatically translate the subject or body and
 * append the default (translated) signature if not specified otherwise.
 */
class Message extends ZendMessage implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * Sets the translator instance and defaults to UTF8 encoding.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
        $this->setEncoding('UTF-8');
    }

    /**
     * Translates and sets the subject.
     *
     * @param string $subject
     * @param bool $translate   will try to translate the $html if true
     * @param string $locale
     * @return self
     */
    public function setSubject($subject, $translate = true, $locale = null)
    {
        return parent::setSubject($translate
            ? $this->translate($subject, $locale)
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
     * @param string $locale
     * @return self
     */
    public function setBodyText($text, $appendSignature = true, $translate = true, $locale = null)
    {
        if (!is_string($text) && !is_array($text)) {
            throw new Exception\InvalidArgumentException('$text must be a string or array');
        }

        if ($translate) {
            $text = $this->translate($text, $locale);
        }
        if ($appendSignature) {
            $text .= $this->getSignature('text');
        }

        $part = new MimePart($text);
        $part->type = Mime::TYPE_TEXT.'; charset=UTF-8';

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
     * @param bool $appendSignature
     * @param bool $translate   will try to translate the $html if true
     * @param string $locale
     * @return self
     */
    public function setBodyHtml($html, $appendSignature = true, $translate = true, $locale = null)
    {
        if (!is_string($html) && !is_array($html)) {
            throw new InvalidArgumentException('$html must be a string or array');
        }

        if ($translate) {
            $html = $this->translate($html, $locale);
        }
        if ($appendSignature) {
            $html .= $this->getSignature('html');
        }

        $part = new MimePart($html);
        $part->type = Mime::TYPE_HTML.'; charset=UTF-8';

        $message = new MimeMessage();
        $message->addPart($part);

        return $this->setBody($message);
    }

    /**
     * Translates the given message, replacing placeholders with the given
     * parameters.
     *
     * @param string|array $message
     * @param string $locale
     * @return string
     */
    protected function translate($message, $locale = null)
    {
        $translator = $this->getTranslator();
        return $translator->translate(
            $message,
            $this->getTranslatorTextDomain(),
            $locale
        );
    }

    /**
     * Returns the default text or HTML signature.
     *
     * @param string $type
     * @return string
     */
    public function getSignature($type = 'text')
    {
        switch ($type) {
            case 'html':
            case 'text/html':
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
}
