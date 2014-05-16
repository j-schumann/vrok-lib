<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mail;

use Zend\I18n\Translator\Translator;
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

    public function __construct(Translator $translator)
    {
        $this->setTranslator($translator);
        $this->setEncoding('UTF-8');
    }

    /**
     * Translates and sets the subject.
     *
     * @param string $subject
     * @param array $params
     * @param string $locale
     * @return self
     */
    public function setSubject($subject, array $params = array(), $locale = null)
    {
        return parent::setSubject($this->translate($subject, $params, $locale));
    }

    /*
     * Translates the given text, replaces the parameters and sets the mime
     * message as body.
     *
     * @param string $text
     * @param bool $appendSignature
     * @param array $params
     * @param string $locale
     * @return self
     */
    public function setBodyText($text, $appendSignature, array $params = array(), $locale = null)
    {
        if (!is_string($text)) {
            throw new InvalidArgumentException('$text must be a string');
        }
        $text = $this->translate($text, $params, $locale);
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
     * @param string $text
     * @param bool $appendSignature
     * @param array $params
     * @param string $locale
     * @return self
     */
    public function setBodyHtml($html, $appendSignature, array $params = array(), $locale = null)
    {
        if (!is_string($html)) {
            throw new InvalidArgumentException('$html must be a string');
        }
        $html = $this->translate($html, $params, $locale);
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
     * @param string $message
     * @param array $params
     * @param string $locale
     * @return string
     */
    protected function translate($message, array $params = array(), $locale = null)
    {
        $translator = $this->getTranslator();
        return $translator->translate(
                array($message, $params),
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
    protected function getSignature($type = 'text')
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
