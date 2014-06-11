<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;

/**
 * Stores a single meta value (used system-wide).
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="meta_system")
 */
class SystemMeta extends Entity
{
    use \Vrok\Doctrine\Traits\CreationDate;
    use \Vrok\Doctrine\Traits\ModificationDate;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

// <editor-fold defaultstate="collapsed" desc="name">
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", nullable=false, unique=true)
     */
    protected $name;

    /**
     * Returns the meta name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the meta name.
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="value">
    /**
     * @var string
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    protected $value;

    /**
     * Returns the meta value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the meta value.
     *
     * @param string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = (string) $value;
        return $this;
    }
// </editor-fold>
}
