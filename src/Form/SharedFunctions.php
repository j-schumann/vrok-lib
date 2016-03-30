<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

use Doctrine\ORM\EntityManager;
use Zend\Mvc\I18n\Translator;

/**
 * Used to extend our fieldset and form classes by this functions because they
 * can not inherit from the same base.
 */
trait SharedFunctions
{
    /**
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * @var Translator
     */
    protected $translator = null;

    /**
     * Sets the EM instance to use.
     *
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Sets the translator instance to use.
     *
     * @param Translator $t
     */
    public function setTranslator(Translator $t)
    {
        $this->translator = $t;
    }

    /**
     * Adds a CSRF protection element to the form.
     *
     * @param string $name
     * @param int    $timeout
     *
     * @return self
     */
    public function addCsrfElement($name, $timeout = 600)
    {
        return $this->add([
            'type'    => 'Vrok\Form\Element\Csrf',
            'name'    => $name,
            'options' => [
                'csrf_options' => [
                    'timeout' => $timeout,

                     // Injects the default translator or else the error message is
                     // not translated because Zend\Form\Element\Csrf would not do
                     // this by default and we have no access to the translator in
                     // the element class.
                    'translator' => $this->translator,
                ],
            ],
        ]);
    }

    /**
     * Translates the messages before setting.
     * This is necessary because the validators generated by the InputFilter from
     * the filterSpecification return translated messages already so the views
     * errorHelper does not translate them again.
     * We don't want to extend the errorHelper to translate everything he recieves
     * as this is expensive.
     *
     * @param array $messages
     */
    public function setUntranslatedMessages($messages)
    {
        foreach ($messages as &$messageSet) {
            foreach ($messageSet as &$message) {
                $message = $this->translator->translate($message);
            }
        }

        $this->setMessages($messages);
    }

    /**
     * Sets a single error message for the given element.
     *
     * @param string $element
     * @param string $message
     */
    public function setElementMessage($element, $message)
    {
        $this->get($element)->setMessages([$this->translator->translate($message)]);
    }

    /**
     * Retrieve the entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
}
