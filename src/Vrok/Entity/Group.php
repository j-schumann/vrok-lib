<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use BjyAuthorize\Acl\HierarchicalRoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;

/**
 * Group object for providing privileges to the members.
 *
 * @ORM\Entity(repositoryClass="Vrok\Entity\GroupRepository")
 * @ORM\Table(name="groups")
 */
class Group extends Entity implements HierarchicalRoleInterface
{
    use \Vrok\Doctrine\Traits\AutoincrementId;

    /**
     * Initialize collection for lazy loading.
     */
    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * For HierarchicalRoleInterface
     * {@inheritdoc}
     */
    public function getRoleId()
    {
        return $this->getName();
    }

// <editor-fold defaultstate="collapsed" desc="children">
    /**
     * @ORM\OneToMany(targetEntity="Group", mappedBy="parent", fetch="EXTRA_LAZY")
     */
    protected $children;

    /**
     * Retrieve the Groups inheriting from this one.
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Adds the given Group to the collection.
     * Called by $group->setParent to keep the collection consistent.
     *
     * @param Group $child
     * @return boolean  false if the Group was already in the collection,
     *  else true
     */
    public function addChild(Group $child)
    {
        if ($this->children->contains($child)) {
            return false;
        }
        return $this->children->add($child);
    }

    /**
     * Removes the given Group from the collection.
     * Called by $group->setParent to keep the collection consistent.
     *
     * @param Group $child
     * @return boolean     true if the Group was in the collection and was
     *     removed, else false
     */
    public function removeChild(Group $child)
    {
        return $this->children->removeElement($child);
    }

    /**
     * Proxies to addChild for multiple elements.
     *
     * @param Collection $children
     */
    public function addChildren(Collection $children)
    {
        foreach($children as $child) {
            $this->addChild($child);
        }
    }

    /**
     * Proxies to removeChild for multiple elements.
     *
     * @param Collection $children
     */
    public function removeChildren(Collection $children)
    {
        foreach($children as $child) {
            $this->removeChild($child);
        }
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="description">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * Returns the groups description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the groups description.
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = (string) $description;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="members">
    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="groups", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="groups_users")
     **/
    protected $members;

    /**
     * Returns the list of all group members.
     *
     * @return Collection
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Adds the given user to the group members.
     *
     * @param User $user
     * @return boolean  false if the user was already a member, else true
     */
    public function addMember(User $user)
    {
        if ($this->members->contains($user)) {
            return false;
        }

        $user->addGroup($this); // synchronously updating inverse side
        $this->members[] = $user;
        return true;
    }

    /**
     * Removes the given User from the group members.
     *
     * @param User $user
     * @return boolean     true if the User was in the collection and was
     *     removed, else false
     */
    public function removeMember(User $user)
    {
        if (!$this->members->contains($user)) {
            return false;
        }

        $user->removeGroup($this); // synchronously updating inverse side
        return $this->members->removeElement($user);
    }

    /**
     * Proxies to addMember for multiple elements.
     *
     * @param Collection $members
     */
    public function addMembers(Collection $members)
    {
        foreach($members as $member) {
            $this->addMember($member);
        }
    }

    /**
     * Proxies to removeMember for multiple elements.
     *
     * @param Collection $members
     */
    public function removeMembers(Collection $members)
    {
        foreach($members as $member) {
            $this->removeMember($member);
        }
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="name">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=false, unique=true)
     */
    protected $name;

    /**
     * Returns the groups name.
     * Used to assign privileges.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the groups name.
     * Must be unique.
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
// <editor-fold defaultstate="collapsed" desc="parent">
    /**
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="RESTRICT")
     */
    protected $parent;

    /**
     * Retrieve the Group this one inherits from.
     *
     * @return Group
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent Group.
     *
     * @param Group $parent
     * @return self
     */
    public function setParent(Group $parent = null)
    {
        if ($this->parent && $this->parent !== $parent) {
            $this->parent->removeChild($this);
        }

        $this->parent = $parent;
        if ($parent) {
            $this->parent->addChild($this);
        }

        return $this;
    }
// </editor-fold>
}
