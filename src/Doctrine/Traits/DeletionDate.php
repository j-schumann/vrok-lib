<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

trait DeletionDate
{
    /**
     * @var \DateTime
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=true)
     */
    protected $deletedAt;

    /**
     * Returns the deletion date.
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Sets the deletion date.
     * This marks the object as deleted.
     *
     * @param \DateTime $deletedAt
     *
     * @return self
     */
    public function setDeletedAt(\DateTime $deletedAt = null)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}
