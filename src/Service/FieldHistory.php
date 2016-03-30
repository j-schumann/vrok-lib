<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\ORM\EntityManager;
use Vrok\Doctrine\EntityInterface;
use Vrok\Entity\FieldHistory as HistoryEntity;
use Vrok\Entity\Filter\FieldHistoryFilter;
use Vrok\Entity\User;
use Zend\Authentication\AuthenticationServiceInterface;

/**
 * Allows to log changes to any column of any record. Keeps only the latest change
 * for each field.
 */
class FieldHistory
{
    /**
     * @var AuthenticationServiceInterface
     */
    protected $authService = null;

    /**
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * Class constructor - stores the dependencies.
     *
     * @param EntityManager                  $entityManager
     * @param AuthenticationServiceInterface $authService
     */
    public function __construct(
            EntityManager $entityManager,
            AuthenticationServiceInterface $authService
    ) {
        $this->entityManager = $entityManager;
        $this->authService   = $authService;
    }

    /**
     * Creates or updates the history entry for the given entity/field.
     *
     * @param EntityInterface $entity
     * @param string          $field
     * @param mixed           $value
     * @param bool            $flush  if true the current unitOfWork is committed to the DB
     */
    public function logChange(EntityInterface $entity, $field, $value, $flush = false)
    {
        $filter = $this->getHistoryFilter();
        $filter->byObject($entity);
        $filter->byField($field);

        $log = $filter->getQuery()->getOneOrNullResult();
        if (!$log) {
            $log = new HistoryEntity();
            $log->setReference($this->entityManager, $entity);
            $log->setField($field);
            $this->entityManager->persist($log);
        }

        $log->setValue($value);

        $user = $this->authService->getIdentity();
        if ($user instanceof User) {
            $log->setUser($user);
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Logs multiple changes to the given entity at once.
     *
     * @see logChange
     *
     * @param EntityInterface $entity
     * @param array           $changeset
     * @param bool            $flush     if true the current unitOfWork is committed to the DB
     */
    public function logChangeset(EntityInterface $entity, array $changeset, $flush = false)
    {
        // changeset is [field => [oldValue, newValue]]
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
     * @param EntityInterface $entity
     * @param string          $field
     *
     * @return HistoryEntity[]
     */
    public function getHistory(EntityInterface $entity, $field = null)
    {
        $filter = $this->getHistoryFilter();
        $filter->byObject($entity);

        if ($field) {
            $filter->byField($field);

            return $filter->getQuery()->getOneOrNullResult();
        }

        $logs = $filter->getResult();
        foreach ($logs as $k => $log) {
            $logs[$log->getField()] = $log;
            unset($logs[$k]);
        }

        return $logs;
    }

    /**
     * Removes all log entries for the given entity.
     * Attention: Does not flush!
     *
     * @param EntityInterface $entity
     */
    public function purgeEntityHistory(EntityInterface $entity)
    {
        $filter = $this->getHistoryFilter();
        $filter->byObject($entity);
        $filter->delete();

        return $filter->getQuery()->execute();
    }

    /**
     * Retrieve a new filter instance to search for history entries.
     *
     * @param string $alias
     *
     * @return FieldHistoryFilter
     */
    public function getHistoryFilter($alias = 'h')
    {
        $qb = $this->getHistoryRepository()->createQueryBuilder($alias);

        return new FieldHistoryFilter($qb);
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
