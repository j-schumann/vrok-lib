<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Validation;

use Vrok\Entity\Validation as ValidationEntity;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Manages validations and triggers events when a validation fails or succeeds.
 */
class Manager implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;
    use ServiceLocatorAwareTrait;

    const EVENT_VALIDATION_FAILED     = 'validationFailed';
    const EVENT_VALIDATION_SUCCESSFUL = 'validationSuccessful';

    /**
     * Do not only trigger under the identifier \Vrok\Validation\Manager but also
     * use the short name used as serviceManager alias.
     *
     * @var string
     */
    protected $eventIdentifier = 'ValidationManager';

    /**
     * List of timeouts in seconds until the validations of a type expire.
     *
     * @var int[]   array(type => timeout, ...)
     */
    protected $timeouts = array();

    /**
     * Creates a new validation of the given type for the given owner.
     *
     * @param string $type
     * @param object $owner
     * @return ValidationEntity
     */
    public function createValidation($type, $owner)
    {
        $validation = new ValidationEntity();
        $validation->setType($type);
        $validation->setRandomToken();

        $ownerService = $this->getServiceLocator()->get('OwnerService');
        $ownerService->setOwner($validation, $owner);

        $this->getValidationRepository()->persist($validation);
        return $validation;
    }

    /**
     * Tries to find the specified validation and triggers the confirmation event.
     * If the validation was not found or is expired false is returned, the
     * event handlers may have created log entries or set flash messages.
     * If the confirmation was successful the last event handler result is returned
     * which may be a Response containing a redirect.
     *
     * @triggers validationFailed
     * @triggers validationSuccessful
     * @param int $id
     * @param string $token
     * @return mixed    false|Response
     */
    public function confirmValidation($id, $token)
    {
        $repository = $this->getValidationRepository();
        $validation = $repository->find($id);

        // this will cause a failed validation event to be logged but this would
        // also be the case if the validation was already purged by the cron job
        if ($validation && $this->isExpiredValidation($validation)) {
            $repository->remove($validation);
            $validation = null;
        }

        if (!$validation || $validation->getToken() != $token) {
            $this->getServiceLocator()->get('ControllerPluginManager')
                    ->get('flashMessenger')
                    ->addErrorMessage('message.validation.noMatchingValidation');

            // allow logging and setting of flash messages but leave everything else
            // to the controller -> return false
            $this->getEventManager()->trigger(
                self::EVENT_VALIDATION_FAILED,
                $this,
                array('validation' => $validation,)
            );
            return false;
        }

        $results = $this->getEventManager()->trigger(
            self::EVENT_VALIDATION_SUCCESSFUL,
            $this,
            array('validation' => $validation,)
        );

        $repository->remove($validation);
        $this->getServiceLocator()->get('Doctrine\ORM\EntityManager')->flush();

        // return the event result, the controller action returns it again if it is
        // an instance of Zend\Http\Response to allow redirects
        return $results->last();
    }

    /**
     * Queries the database for all validations matching the given owner and/or the
     * given type.
     *
     * @param object $owner
     * @param string $type
     * @return array
     */
    public function getValidations($owner = null, $type = null)
    {
        $repository = $this->getValidationRepository();
        $qb = $repository->createQueryBuilder('v');

        if ($owner) {
            $ownerService = $this->getServiceLocator()->get('OwnerService');
            $ownerService->getByOwner($qb, $owner);
        }

        if ($type) {
            $qb->where($qb->expr()->eq('v.type', $type));
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Deletes all expired validations from the database.
     *
     * @return int  the number of expired & deleted validations
     */
    public function purgeValidations()
    {
        $repository = $this->getValidationRepository();
        $validations = $repository->findAll();
        $count = 0;

        foreach($validations as $validation) {
            if ($this->isExpiredValidation($validation)) {
                $repository->remove($validation);
                $count++;
            }
        }

        $this->getServiceLocator()->get('Doctrine\ORM\EntityManager')->flush();
        return $count;
    }

    /**
     * Returns true if the given validation is expired, else false.
     *
     * @param ValidationEntity $validation
     * @return boolean
     */
    public function isExpiredValidation(ValidationEntity $validation)
    {
        $timeout = $this->getTimeout($validation->getType());
        if (!$timeout) {
            return false;
        }

        $expirationDate = $validation->getCreatedAt();
        $expirationDate->add(new DateInterval('PT'.$timeout.'S'));
        $now = new \DateTime('now');

        return $expirationDate <= $now;
    }

    /**
     *
     * @return \Vrok\Entity\ValidationRepository
     */
    public function getValidationRepository()
    {
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        return $em->getRepository('Vrok\Entity\Validation');
    }

    /**
     * Retrieve the timeout in seconds configured for the given type or
     * null if no timeout was set (validation will never expire).
     *
     * @param string $type
     * @return int|null
     */
    public function getTimeout($type)
    {
        return isset($this->timeouts[$type])
            ? $this->timeouts[$type]
            : null;
    }


    /**
     * Sets the time in seconds validations of the given type are valid.
     *
     * @todo validate args
     * @param string $type
     * @param int $timeout
     */
    public function setTimeout($type, $timeout)
    {
        $this->timeouts[$type] = $timeout;
    }

    /**
     * Sets multiple timeouts at once.
     *
     * @todo use Zend Guard to check for array etc
     * @param array $timeouts
     */
    public function setTimeouts($timeouts)
    {
        foreach($timeouts as $type => $timeout) {
            $this->setTimeout($type, $timeout);
        }
    }
}
