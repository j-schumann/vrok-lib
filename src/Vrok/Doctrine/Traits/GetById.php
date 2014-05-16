<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

/**
 * For repository classes with entities using an (autoincrement) id field.
 */
trait GetById
{
    /**
     * Retrieve a matching entity by its ID.
     *
     * @param int $id
     * @return object
     */
    public function getById($id)
    {
        return $this->findOneBy(array('id' => $id));
    }
}
