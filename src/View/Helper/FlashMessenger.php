<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\View\Helper;

use Zend\View\Helper\FlashMessenger as ZendMessenger;

/**
 * Extends the Zend helper to render all messages at once.
 */
class FlashMessenger extends ZendMessenger
{
    protected $messageOpenFormat      = '<div class="alert alert-%s">';
    protected $messageSeparatorString = '</div><div class="alert alert-%s">';
    protected $messageCloseString     = '</div>';

    public function renderAll()
    {
        // use the injected instance if available as getPLuginFlashMessenger
        // creates a new instance and messages from the previous page call are
        // lost if messages were added in this page call too!
        $flashMessenger = $this->getView()->flashMessenger ?:
                $this->getPluginFlashMessenger();

        // show all messages from a previous page call
        echo $this->format($flashMessenger->getMessages(), 'default');
        echo $this->format($flashMessenger->getSuccessMessages(), 'success');
        echo $this->format($flashMessenger->getInfoMessages(), 'info');
        echo $this->format($flashMessenger->getWarningMessages(), 'warning');
        echo $this->format($flashMessenger->getErrorMessages(), 'error');

        // show all messages from the current page call
        echo $this->format($flashMessenger->getCurrentMessages(), 'default');
        echo $this->format($flashMessenger->getCurrentSuccessMessages(), 'success');
        echo $this->format($flashMessenger->getCurrentInfoMessages(), 'info');
        echo $this->format($flashMessenger->getCurrentWarningMessages(), 'warning');
        echo $this->format($flashMessenger->getCurrentErrorMessages(), 'error');

        // we don't want to carry messages that were in this page call
        // onto the next page
        $flashMessenger->clearCurrentMessagesFromContainer();
    }

    /**
     * Retrieve all stored messages.
     * Used for custom rendering.
     *
     * @return array
     */
    public function getAll()
    {
        // use the injected instance if available as getPLuginFlashMessenger
        // creates a new instance and messages from the previous page call are
        // lost if messages were added in this page call too!
        $flashMessenger = $this->getView()->flashMessenger ?:
                $this->getPluginFlashMessenger();

        // retrieve all messages from a previous page call and all messages from
        // the current page call

        $messages['default'] = array_merge(
            $this->prepareMessages($flashMessenger->getMessages()),
            $this->prepareMessages($flashMessenger->getCurrentMessages())
        );
        $messages['success'] = array_merge(
            $this->prepareMessages($flashMessenger->getSuccessMessages()),
            $this->prepareMessages($flashMessenger->getCurrentSuccessMessages())
        );
        $messages['info'] = array_merge(
            $this->prepareMessages($flashMessenger->getInfoMessages()),
            $this->prepareMessages($flashMessenger->getCurrentInfoMessages())
        );
        $messages['warning'] = array_merge(
            $this->prepareMessages($flashMessenger->getWarningMessages()),
            $this->prepareMessages($flashMessenger->getCurrentWarningMessages())
        );
        $messages['error'] = array_merge(
            $this->prepareMessages($flashMessenger->getErrorMessages()),
            $this->prepareMessages($flashMessenger->getCurrentErrorMessages())
        );

        // we don't want to carry messages that were in this page call
        // onto the next page
        $flashMessenger->clearCurrentMessagesFromContainer();

        return $messages;
    }

    /**
     * Flatten the given array and translate the messages.
     *
     * @param array $messages
     * @return array
     */
    protected function prepareMessages($messages)
    {
        // Flatten message array
        $messagesToPrint = [];

        $translator           = $this->getTranslator();
        $translatorTextDomain = $this->getTranslatorTextDomain();

        array_walk_recursive($messages, function ($item) use (&$messagesToPrint, $translator, $translatorTextDomain) {
            if ($translator !== null) {
                $item = $translator->translate(
                    $item,
                    $translatorTextDomain
                );
            }
            $messagesToPrint[] = $item;
        });

        return $messagesToPrint;
    }

    /**
     * Render Messages
     * The original helper would escape HTML, we don't want this to enable providing
     * links in the flash messages.
     *
     * @param array  $messages
     * @param string $class
     *
     * @return string
     */
    protected function format($messages, $class)
    {
        if (empty($messages)) {
            return '';
        }

        $messagesToPrint = $this->prepareMessages($messages);
        if (empty($messagesToPrint)) {
            return '';
        }

        // Generate markup
        $markup = sprintf($this->getMessageOpenFormat(), $class);
        $markup .= implode(sprintf($this->getMessageSeparatorString(), $class), $messagesToPrint);
        $markup .= $this->getMessageCloseString();

        return $markup;
    }
}
