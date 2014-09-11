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

    const EVENT_VALIDATION_EXPIRED    = 'validationExpired';
    const EVENT_VALIDATION_FAILED     = 'validationFailed';
    const EVENT_VALIDATION_SUCCESSFUL = 'validationSuccessful';

    /**
     * Name of the route where an user may confirm a validation.
     * "/params" is appended to this route name when a validation object is provided
     * (fixes the display of an additional "/" on the end of the URL when no route
     * parameters were set)
     *
     * @var string
     */
    protected $confirmationRoute = 'validation/confirm';

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
        if (!$validation) {
            $this->triggerFail();
            return false;
        }

        // trigger the failure event before removing the validation so the listeners
        // can receive the object
        if ($this->isExpiredValidation($validation)) {
            $this->triggerFail($validation);

            // @todo should the validation be removed immediately or is this better done
            // by the cron job to reduce page load time?
            $this->removeIfExpired($validation);
            return false;
        }

        if ($validation->getToken() != $token) {
            $this->triggerFail($validation);
            return false;
        }

        $results = $this->getEventManager()->trigger(
            self::EVENT_VALIDATION_SUCCESSFUL,
            $validation
        );

        $repository->remove($validation);
        $this->getServiceLocator()->get('Doctrine\ORM\EntityManager')->flush();

        // return the event result, the controller action returns it again if it is
        // an instance of Zend\Http\Response to allow redirects
        return $results->last();
    }

    /**
     * Outsource for confirmValidation, adds a flash message and triggers the
     * validationFailed event to allow logging etc.
     *
     * @triggers validationFailed
     * @param ValidationEntity $validation
     */
    protected function triggerFail(ValidationEntity $validation = null)
    {
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
    }

    /**
     * Retrieve the URL where the given validation can be confirmed.
     * If no validation is given the base URL to the validation form is returned.
     *
     * @param ValidationEntity $validation
     * @return string
     */
    public function getConfirmationUrl(ValidationEntity $validation = null)
    {
        $url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
        if (!$validation) {
            return $url($this->confirmationRoute);
        }

        return $url($this->confirmationRoute.'/params', array(
            'id'    => $validation ? $validation->getId() : null,
            'token' => $validation ? $validation->getToken() : null,
        ));
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
     * @triggers validationExpired
     * @return int  the number of expired & deleted validations
     */
    public function purgeValidations()
    {
        $validations = $this->getValidationRepository()->findAll();
        $count = 0;

        foreach($validations as $validation) {
            if ($this->removeIfExpired($validation)) {
                $count++;
            }
        }
        $log = $this->getServiceLocator()->get('ZendLog');
        $log->debug('purgeValidations finished '.  date('Y-m-d H:i:s'));

        return $count;
    }

    /**
     * Triggers the validationExpired event and removes the validation from the database
     * if it is expired.
     *
     * @triggers validationExpired
     * @param ValidationEntity $validation
     * @return boolean  true if the validation is expired and was removed, else false
     */
    protected function removeIfExpired(ValidationEntity $validation)
    {
        if (!$this->isExpiredValidation($validation)) {
            return false;
        }

        $this->getEventManager()->trigger(
            self::EVENT_VALIDATION_EXPIRED,
            $validation
        );

        // remove the validation regardless of the event result and flush the EM,
        // there were probably more cleanups through the event listeners so we want
        // to commit them now
        $this->getValidationRepository()->remove($validation);
        $this->getServiceLocator()->get('Doctrine\ORM\EntityManager')->flush();

        return true;
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
        $expirationDate->add(new \DateInterval('PT'.$timeout.'S'));
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
