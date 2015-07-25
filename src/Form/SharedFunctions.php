<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Used to extend our fieldset and form classes by this functions because they
 * can not inherit from the same base.
 */
trait SharedFunctions
{
    // @todo bad practice to make every form use the serviceLocator...
    use ServiceLocatorAwareTrait;

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
                    'translator' => $this->getServiceLocator()->getServiceLocator()
                        ->get('translator'),
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
        $translator = $this->getServiceLocator()->getServiceLocator()->get('translator');
        foreach ($messages as &$messageSet) {
            foreach ($messageSet as &$message) {
                $message = $translator->translate($message);
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
        $translator = $this->getServiceLocator()->getServiceLocator()->get('translator');
        $this->get($element)->setMessages([$translator->translate($message)]);
    }

    /**
     * Retrieve the entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator() // returns the FormElementManager
            ->getServiceLocator() // returns the ServiceManager
            ->get('Doctrine\ORM\EntityManager');
    }
}
