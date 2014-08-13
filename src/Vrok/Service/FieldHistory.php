<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Vrok\Entity\FieldHistory as HistoryEntity;
use Vrok\Entity\User;
use Vrok\Stdlib\Guard\ObjectGuardTrait;
use Zend\Authentication\AuthenticationServiceInterface;

/**
 * Allows to log changes to any column of any record. Keeps only the latest change
 * for each field.
 */
class FieldHistory
{
    use ObjectGuardTrait;

    /**
     * @var AuthenticationServiceInterface
     */
    protected $authService = null;

    /**
     * @var ObjectManager
     */
    protected $entityManager = null;

    /**
     * Class constructor - stores the dependencies.
     *
     * @param ObjectManager $entityManager
     * @param AuthenticationServiceInterface $authService
     */
    public function __construct(
            ObjectManager $entityManager,
            AuthenticationServiceInterface $authService
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
    }

    /**
     * Creates or updates the history entry for the given entity/field.
     *
     * @param object|string $entity
     * @param string $field
     * @param mixed $value
     * @param bool $flush   if true the current unitOfWork is committed to the DB
     */
    public function logChange($entity, $field, $value, $flush = false)
    {
        $this->guardForObject($entity);
        $entityName = get_class($entity);
        $meta = $this->entityManager->getClassMetadata($entityName);
        $identifier = $meta->getIdentifierValues($entity);

        $log = $this->getHistoryRepository()->find(array(
            'entity'     => $entityName,
            'field'      => $field,
            'identifier' => json_encode($identifier),
        ));
        if (!$log) {
            $log = new HistoryEntity();
            $log->setEntity($entityName);
            $log->setField($field);
            $log->setIdentifier($identifier);
        }

        $log->setValue($value);

        $user = $this->authService->getIdentity();
        if ($user instanceof User) {
            $log->setUser($user);
        }

        $this->entityManager->persist($log);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Logs multiple changes to the given entity at once.
     *
     * @see logChange
     * @param mixed $entity
     * @param array $changeset
     * @param bool $flush   if true the current unitOfWork is committed to the DB
     */
    public function logChangeset($entity, array $changeset, $flush = false)
    {
        // changeset is array(field => array(oldValue, newValue))
        foreach ($changeset as $field => $values) {
            $this->logChange($entity, $field, $values[0]);
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Retrieves the history record(s) for the given entity.
     * If a field name is given only the record for this field is returned,
     * else the array of all logged field changes for the entity.
     *
     * @param object $entity
     * @param string $field
     * @return HistoryEntity[]
     */
    public function getHistory($entity, $field = null)
    {
        $this->guardForObject($entity);
        $entityName = get_class($entity);
        $meta = $this->entityManager->getClassMetadata($entityName);
        $identifier = $meta->getIdentifierValues($entity);

        $criteria = array(
            'entity'     => $entityName,
            'identifier' => json_encode($identifier),
        );

        if ($field) {
            $criteria['field'] = $field;
            return $this->getHistoryRepository()->find($criteria);
        }

        $logs = $this->getHistoryRepository()->findBy($criteria);
        foreach ($logs as $k => $log) {
            $logs[$log->getField()] = $log;
            unset($logs[$k]);
        }

        return $logs;
    }

    /**
     * Retrieve the repository for the history entries.
     *
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getHistoryRepository()
    {
        return $this->entityManager->getRepository('Vrok\Entity\FieldHistory');
    }
}
