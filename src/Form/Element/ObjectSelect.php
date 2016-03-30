<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\Element;

use DoctrineModule\Form\Element\ObjectSelect as DoctrineSelect;

/**
 * We need to overwrite the DoctrineModule class as it does not allow to configure
 * another proxy class.
 */
class ObjectSelect extends DoctrineSelect
{
    /**
     * Use our own proxy that supports reloading the value options.
     *
     * @return Proxy
     */
    public function getProxy()
    {
        if (null === $this->proxy) {
            $this->proxy = new Proxy();
        }

        return $this->proxy;
    }
}
