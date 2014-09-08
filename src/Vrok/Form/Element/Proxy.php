<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\Element;

use DoctrineModule\Form\Element\Proxy as DoctrineProxy;

class Proxy extends DoctrineProxy
{
    /**
     * Allows to reload the valueOptions, e.g. after find_method changed.
     */
    public function reloadValueOptions()
    {
        // loadValueOptions use the already loaded objects -> reload them first
        $this->objects = array();
        $this->loadObjects();

        $this->valueOptions = array();
        $this->loadValueOptions();
    }

    /**
     * Automatically reload the valueOptions if the find_method was updated.
     *
     * @param mixed $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);

        if (isset($options['find_method'])) {
            $this->reloadValueOptions();
        }
    }
}
