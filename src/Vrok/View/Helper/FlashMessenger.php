<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\View\Helper;

/**
 * Extends the Zend helper to render all messages at once.
 */
class FlashMessenger extends \Zend\View\Helper\FlashMessenger
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

        // we don't want to carry messages that were that in this page call
        // onto the next page
        $flashMessenger->clearCurrentMessagesFromContainer();
    }

    /**
     * Render Messages
     * The original helper would escape HTML, we don't want this to enable providing
     * links in the flash messages.
     *
     * @param  array $messages
     * @param  string  $class
     * @return string
     */
    protected function format($messages, $class)
    {
        if (empty($messages)) {
            return '';
        }

        // Flatten message array
        $messagesToPrint = array();

        $translator = $this->getTranslator();
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

        if (empty($messagesToPrint)) {
            return '';
        }

        // Generate markup
        $markup  = sprintf($this->getMessageOpenFormat(), $class);
        $markup .= implode(sprintf($this->getMessageSeparatorString(), ' class="'.$class.'"'), $messagesToPrint);
        $markup .= $this->getMessageCloseString();

        return $markup;
    }
}
