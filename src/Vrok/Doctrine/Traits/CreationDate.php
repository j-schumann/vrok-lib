<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

// required in the using class:
//use Doctrine\ORM\Mapping as ORM;
//use Gedmo\Mapping\Annotation as Gedmo;

trait CreationDate
{
    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * Returns the creation date.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the creation date.
     *
     * @param  \DateTime $createdAt
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        // we allow NULL for the DoctrineHydrator as he calls this although the
        // value is not set in the data, simply ignore it, the creationDate can't be
        // resetted
        if ($createdAt) {
            $this->createdAt = $createdAt;
        }
        return $this;
    }
}
