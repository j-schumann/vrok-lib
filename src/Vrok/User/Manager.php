<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\User;

use Doctrine\Common\Persistence\ObjectManager;
use Vrok\Entity\Group as GroupEntity;
use Vrok\Entity\User as UserEntity;
use Zend\Authentication\Validator\Authentication as AuthValidator;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Contains processes for creating and managing Attendant objects and their
 * associated actions.
 */
class Manager implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;
    use ServiceLocatorAwareTrait;

    const EVENT_CREATE_USER      = 'createUser';
    const EVENT_CREATE_USER_POST = 'createUser.post';

    /**
     * Name of the route where an user may be inspected/edited.
     *
     * @var string
     */
    protected $userAdminRoute = 'user/edit';

    /**
     * Name of the route where ajax requests with search pattern to search for users
     * should be sent.
     *
     * @var string
     */
    protected $userSearchRoute = 'user/search';


    /**
     * Creates a new UserEntity instance and sets the provided fields.
     *
     * @param array $data
     * @return UserEntity|array     the new user instance or an array of errors
     */
    public function createUser($data)
    {
        $repository = $this->getUserRepository();

        $filter = $repository->getInputFilter();
        $filter->setData($data);
        if (!$filter->isValid()) {
            return $filter->getMessages();
        }

        $user = new UserEntity();
        $this->getEventManager()->trigger(
            self::EVENT_CREATE_USER,
            $this,
            array(
                'user' => $user,
                'data' => $data,
            )
        );

        $repository->updateInstance($user, $data);
        $this->getEntityManager()->flush();
        $this->getEventManager()->trigger(self::EVENT_CREATE_USER_POST, $user);

        return $user;
    }

    /**
     * Creates a new Group from the given form data.
     *
     * @param array $formData
     * @return GroupEntity
     */
    public function createGroup(array $formData)
    {
        $objectManager = $this->getEntityManager();

        $group = new GroupEntity();
        $groupRepository = $objectManager->getRepository('Ellie\Entity\Group');
        $groupRepository->updateInstance($group, $formData);
        $objectManager->flush();

        return $group;
    }

    /**
     * Logs the current user out.
     *
     * @return boolean
     */
    public function logout()
    {
        $authService = $this->getAuthService();
        if (!$authService->hasIdentity()) {
            return false;
        }
        $user = $authService->getIdentity();
        // @todo
        // user->logout (logging)
        // event?
        $authService->clearIdentity();
        return true;
    }

    /**
     * Returns the default authentication service.
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    public function getAuthService()
    {
        return $this->getServiceLocator()->get('zfcuser_auth_service');
    }

    /**
     * Returns the default authentication adapter.
     *
     * @return \VrokAuthentication\Adapter\Doctrine
     */
    public function getAuthAdapter()
    {
        return $this->getServiceLocator()
                ->get('Vrok\Authentication\Adapter\Doctrine');
    }

    /**
     * Returns a preconfigured auth validator instance.
     *
     * @return AuthValidator
     */
    public function getAuthValidator()
    {
        $validator = new AuthValidator();
        $validator->setIdentity('username');
        $validator->setCredential('password');
        $validator->setAdapter($this->getAuthAdapter());
        $validator->setService($this->getAuthService());
        $validator->setMessages(array(
            // we do not differ between not allowed because of invalid password or
            // because the user is not active/validated or the identity was not found
            // to not give information about existing users / registered emails
            AuthValidator::CREDENTIAL_INVALID => 'validate.authentication.failed',
            AuthValidator::IDENTITY_NOT_FOUND => 'validate.authentication.failed',
            AuthValidator::UNCATEGORIZED      => 'validate.authentication.uncategorizedFailure',
            AuthValidator::GENERAL            => 'validate.authentication.failed',
        ));
        return $validator;
    }

    /**
     * Retrieve the route where search requests for user can be sent via AJAX.
     *
     * @return string
     */
    public function getUserSearchRoute()
    {
        return $this->userSearchRoute;
    }

    /**
     * Sets the route where search requests for user can be sent via AJAX.
     *
     * @param string $route
     * @return self
     */
    public function setUserSearchRoute($route)
    {
        $this->userSearchRoute = $route;
        return $this;
    }

    /**
     * Retrieve the URL where search requests for user can be sent via AJAX.
     *
     * @return string
     */
    public function getUserSearchUrl()
    {
        $url = $this->getServiceLocator()->get('ControllerPluginManager')->get('url');
        return $url->fromRoute($this->getUserSearchRoute());
    }

    /**
     * Retrieve the route where an user may be inspected/edited.
     *
     * @return string
     */
    public function getUserAdminRoute()
    {
        return $this->userAdminRoute;
    }

    /**
     * Sets the route where an user may be inspected/edited.
     * The route must support the "id" parameter for the user ID.
     *
     * @param string $route
     * @return self
     */
    public function setUserAdminRoute($route)
    {
        $this->userAdminRoute = $route;
        return $this;
    }

    /**
     * Retrieve the URL where an user may be inspected/edited.
     *
     * @param int $userId
     * @return string
     */
    public function getUserAdminUrl($userId)
    {
        $url = $this->getServiceLocator()->get('ControllerPluginManager')->get('url');
        return $url->fromRoute($this->getUserAdminRoute(), array('id' => $userId));
    }

    /**
     * Retrieve the entity manager.
     *
     * @return ObjectManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    }

    /**
     * Retrieve the user repository instance.
     *
     * @return \Vrok\Entity\UserRepository
     */
    public function getUserRepository()
    {
        return $this->getEntityManager()->getRepository('Vrok\Entity\User');
    }

    /**
     * Sets multiple config options at once.
     *
     * @todo validate $config
     * @param array $config
     */
    public function setConfig($config)
    {
        if (!empty($config['admin_route'])) {
            $this->setUserAdminRoute($config['admin_route']);
        }
        if (!empty($config['search_route'])) {
            $this->setUserAdminRoute($config['search_route']);
        }
    }
}
