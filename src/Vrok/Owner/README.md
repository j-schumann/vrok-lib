Owner system
============
Many applications have polymorphic relations where we don't know (and don't want
to know in advance) which entities may assigned to which (which entity is owned
by which). E.g. an bank account may be owned by an user or an organization,
a validation may belong to a bank account or an user.
The OwnerService supports this loose coupling by using the strategy pattern.
This way the owner (and his strategy) don't need to know which entities
(a user can have bank accounts, meta data, ...) may be assigned to them and the
entities (validation, log entries, ...) don't need to know who can use them.
Configuration allows to define which entity can have which owner.

We could use an "Owner" entity in a separate table which has subclasses for each
owner type but that would cost us more joins/queries, we still would not know
which owners are allowed for which entities and the "Owner" would need to know
about the search and admin URL.

You can implement an OwnerStrategy (following the \Vrok\Doctrine\OwnerStrategyInterface)
for any entity that may be used as an owner (restriction: only owners with
scalar identifiers are supported). Then add an event listener to
"getOwnerStrategy" that retrieves the strategy instance when the owner class
name is requested. The listener may decide if it supports only the base class or
all it's subclasses.

The service (using the strategy) allows to assign the owner to the entity
(by storing the owner class and the owners identifier in the entity). Thus the
entity has to implement \Vrok\Doctrine\HasOwnerInterface.
The \Vrok\Doctrine\Traits\HasOwner implements the necessary functionality, you
can override the properties for nullable=false on the fields to enforce an owner.

If you add a new entity that may be used as owner or that may have an owner add
it to your modules configuration as follows:
array (
    'owner_service' => array(
        'allowed_owners' => array(
            '\New\Entity\Class' => array(
                '\Existing\Owner\Class',
                '\Other\Existing\Owner',
            ),
            '\Existing\Entity\Class' => array(
                '\New\Owner\Class',
            ),
        ),
    ),
),
