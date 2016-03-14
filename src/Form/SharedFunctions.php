<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Used to extend our fieldset and form classes by this functions because they
 * can not inherit from the same base.
 *
 * @todo dont use the servicelocator, inject only the necessary dependencies.
 * but how can we do that without writing hundreds of factories?
 */
trait SharedFunctions
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = null;

    /**
     * Retrieve the stored service manager instance.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Retrieve the stored service manager instance.
     *
     * @param ServiceLocatorInterface
     */
    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;
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
                    'translator' => $this->getServiceLocator()
                        ->getServiceLocator()
                        ->get('MvcTranslator'),
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
        $translator = $this->getServiceLocator()->getServiceLocator()->get('MvcTranslator');
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
        $translator = $this->getServiceLocator()->getServiceLocator()->get('MvcTranslator');
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
