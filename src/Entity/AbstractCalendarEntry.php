<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Doctrine\HasReferenceInterface;
use Vrok\Doctrine\Traits\AutoincrementId;
use Vrok\Doctrine\Traits\CreationDate;
use Vrok\Doctrine\Traits\ModificationDate;
use Vrok\Doctrine\Traits\ObjectReference;

/**
 * Represents a simple calendar entry.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\Table(name="calendar_entries", indexes={
 *      @ORM\Index(name="startDate", columns={"startDate"}),
 *      @ORM\Index(name="endDate", columns={"endDate"})
 * })
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
abstract class AbstractCalendarEntry extends Entity implements HasReferenceInterface
{
    use AutoincrementId;
    use CreationDate;
    use ModificationDate;
    use ObjectReference;

    /**
     * Gets the lower-case short name of the entry class as used in the discriminatorMap.
     * Copied from Doctrine\ORM\Mapping\ClassMetadataFactory, used to construct the
     * css class.
     *
     * @return string
     */
    final public function getShortName()
    {
        $className = get_class($this);
        if (strpos($className, '\\') === false) {
            return strtolower($className);
        }

        $parts = explode('\\', $className);

        return strtolower(end($parts));
    }

// <editor-fold defaultstate="collapsed" desc="description">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * Returns the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     * @param string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = (string) $description;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="endDate">
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $endDate;

    /**
     * Returns the end date.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Sets the end date.
     *
     * @param \DateTime $value
     *
     * @return self
     */
    public function setEndDate(\DateTime $value)
    {
        $this->endDate = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="startDate">
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $startDate;

    /**
     * Returns the start date.
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Sets the start date.
     *
     * @param \DateTime $value
     *
     * @return self
     */
    public function setStartDate(\DateTime $value)
    {
        $this->startDate = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="title">
    /**
     * @var string
     * @Doctrine\ORM\Mapping\Column(type="string", length=100, nullable=true)
     */
    protected $title;

    /**
     * Returns the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

// </editor-fold>
}
