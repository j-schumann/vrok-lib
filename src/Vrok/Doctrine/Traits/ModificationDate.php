<?php

namespace Vrok\Doctrine\Traits;

trait ModificationDate
{
    /**
     * @var \DateTime
     * @Gedmo\Mapping\Annotation\Timestampable(on="update")
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=false)
     */
    protected $updatedAt;

    /**
     * Returns the modification date.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets the modification date..
     *
     * @param  \DateTime $updatedAt
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
