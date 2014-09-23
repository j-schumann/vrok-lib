<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

/**
 * Primitive form that holds a confirmation button.
 */
class ConfirmationForm extends Form
{
    /**
     * Creates the CSRF protection and the confirmation button.
     */
    public function init()
    {
        $this->addCsrfElement('csrfConfirm');

        $this->add(array(
            'name'       => 'confirm',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'form.confirm',
            )
        ));
    }

    /**
     * Set an additional message to confirm. Will be displayed as description
     * for the confirmation button.
     *
     * @param string|array $message
     */
    public function setConfirmationMessage($message)
    {
        $this->get('confirm')->setOptions(array('description' => $message));
    }
}
