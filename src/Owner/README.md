Owner system
============
Many applications have polymorphic relations where we don't know (and don't want
to know in advance) which entities may assigned to which (which entity is owned
by which). E.g. a bank account may be owned by an user or an organization,
a validation may belong to a bank account or an user.

To support this loose coupling we store the class of the referenced entity and
the identifiers forming the primary key (e.g. autoincrement or composite keys)
using the Vrok\Doctrine\HasReferenceInterface and Vrok\Doctrine\EntityInterface.
They are implemented in Vrok\Doctrine\Traits\ObjectReference and Vrok\Doctrine\Entity.
You can use these to reference any entity without restrictions.

The OwnerService extends this principle by providing functions to retrieve information
about the referenced objects using a shared API by using the strategy pattern.
This way the owner (and his strategy) don't need to know which entities
(a user can have bank accounts, meta data, ...) may be assigned to them and the
entities (validation, log entries, ...) don't need to know who can use them.
Configuration allows to define which entity can have which owner.

We could use an "Owner" entity in a separate table which has subclasses for each
owner type but that would cost us more joins/queries, we still would not know
which owners are allowed for which entities and the "Owner" would need to know
about the search and admin URL.

You can implement an OwnerStrategy (following the Vrok\Owner\StrategyInterface)
for any entity that may be used as an owner. Then add an event listener to
"getOwnerStrategy" that retrieves the strategy instance when the owner class
name is requested. The listener may decide if it supports only the base class or
all its subclasses.

If you add a new entity that may be used as owner or that may have an owner add
it to your modules configuration as follows:
[
    'owner_service' => [
        'allowed_owners' => [
            '\New\Entity\Class' => [
                '\Existing\Owner\Class',
                '\Other\Existing\Owner',
            ],
            '\Existing\Entity\Class' => [
                '\New\Owner\Class',
            ],
        ],
    ],
],
