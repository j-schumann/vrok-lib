<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\ORM\EntityManager;
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
class UserManager implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;
    use ServiceLocatorAwareTrait;

    const EVENT_CREATE_GROUP_POST    = 'createGroup.post';
    const EVENT_CREATE_USER          = 'createUser';
    const EVENT_CREATE_USER_POST     = 'createUser.post';
    const EVENT_GET_POST_LOGIN_ROUTE = 'getPostLoginRoute';
    const EVENT_LOGOUT               = 'logout';

    /**
     * Do not only trigger under the identifier \Vrok\Service\UserManager but also
     * use the short name used as serviceManager alias.
     *
     * @var string
     */
    protected $eventIdentifier = 'UserManager';

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

        // the username defaults to the email address,
        // the InputFilter requires the field to be set
        if (empty($data['username'])) {
            $data['username'] = $data['email'];
        }

        // the displayName defaults to the username
        if (empty($data['displayName'])) {
            $data['displayName'] = $data['username'];
        }

        // set a default password for the InputFilter to pass, necessary when a random
        // password should be set
        if (empty($data['password'])) {
            // don't use "empty" etc as we are not sure if the random password
            // is really set afterwards, maybe no DB transaction is used
            $data['password'] = uniqid().microtime(true);
        }

        $data['id'] = '';

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
     * Marks an User as deleted by setting the deletedAt date and removing his
     * identification information.
     *
     * @param \Vrok\Entity\User $user
     */
    public function softDeleteUser(UserEntity $user)
    {
        // @todo NULL erlauben? Oder andere Domain bestimmen
        $user->setEmail('deleted_'.$user->getId().'@deleted.com');
        $user->setUsername('deleted_'.$user->getId());
        $user->setDisplayName('deleted user');

        $user->setIsActive(false);
        $user->removePassword();
        $user->setDeletedAt(new \DateTime());

        // @todo event für softdelete für Aufräumarbeiten?
        $fh = $this->getServiceLocator()->get('Vrok\Service\FieldHistory');
        $fh->purgeEntityHistory($user);

        $ms = $this->getServiceLocator()->get('Vrok\Service\Meta');
        $ms->clearObjectMeta($user);

        $this->getEntityManager()->flush();
    }

    /**
     * Creates a new Group from the given form data.
     *
     * @param array $formData
     * @return GroupEntity
     */
    public function createGroup(array $formData)
    {
        $em = $this->getEntityManager();

        $groupRepository = $em->getRepository('Vrok\Entity\Group');
        $group = $groupRepository->updateInstance(new GroupEntity(), $formData);
        $em->flush();
        $this->getEventManager()->trigger(self::EVENT_CREATE_GROUP_POST, $group);

        return $group;
    }

    /**
     * Retrieve the currently logged in user (if any).
     *
     * @return \Vrok\Entity\User
     */
    public function getCurrentUser()
    {
        $authService = $this->getServiceLocator()->get('AuthenticationService');
        return $authService->getIdentity();
    }

    /**
     * Checks if the current user is allowed to access the given resource (and has
     * the given privilege).
     * Mimics BjyAuthorize\Controller\Plugin\IsAllowed
     *
     * @param mixed $resource
     * @param string $privilege
     * @return bool
     */
    public function isAllowed($resource, $privilege = null)
    {
        $authorizeService =
                $this->getServiceLocator()->get('BjyAuthorize\Service\Authorize');
        return $authorizeService->isAllowed($resource, $privilege);
    }

    /**
     * Tries to find a user whos username or email equals the given identity.
     *
     * @param string $identity
     * @return UserEntity   or null if none found
     */
    public function getUserByIdentity($identity)
    {
        $repository = $this->getUserRepository();
        $user = $repository->findOneBy(array('username' => $identity));
        if ($user) {
            return $user;
        }

        return $repository->findOneBy(array('email' => $identity));
    }

    /**
     * Tries to login the given user.
     * Returns the user object on success, else the array with the error message(s).
     *
     * @param string $username
     * @param string $password
     * @return User|array
     */
    public function login($username, $password)
    {
        $validator = $this->getAuthValidator();
        if ($validator->isValid($password, ['username' => $username])) {
            return $this->getCurrentUser();
        }

        return $validator->getMessages();
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
        $authService->clearIdentity();
        $this->getEventManager()->trigger(self::EVENT_LOGOUT, $user);

        // when using destroy() we could not set any messenger notifications afterwarss
        \Zend\Session\Container::getDefaultManager()->getStorage()->clear();
        \Zend\Session\Container::getDefaultManager()->regenerateId();

        return true;
    }

    /**
     * Sets a new random password for the given user and sends it in an email.
     *
     * @param UserEntity $user
     */
    public function sendRandomPassword(UserEntity $user)
    {
        $password = $user->setRandomPassword();

        $emailService = $this->getServiceLocator()->get('Vrok\Service\Email');
        $mail = $emailService->createMail();
        $mail->setSubject('mail.user.randomPassword.subject');

        $viewHelperManager = $this->getServiceLocator()->get('viewhelpermanager');
        $urlHelper = $viewHelperManager->get('noAliasUrl');
        $fullUrlHelper = $viewHelperManager->get('FullUrl');
        $url = $urlHelper('account/login');

        $mail->setHtmlBody(array('mail.user.randomPassword.body', array(
            'displayName' => $user->getDisplayName(),
            'username'    => $user->getUsername(),
            'password'    => $password,
            'loginUrl'    => $fullUrlHelper('https').$url,
        )));

        $mail->addTo($user->getEmail(), $user->getDisplayName());
        $emailService->sendMail($mail);

        // push the random password to the database only after sending the mail,
        // maybe there was an exception or other error...
        $this->getEntityManager()->flush();
    }

    /**
     * Returns the default authentication service.
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    public function getAuthService()
    {
        return $this->getServiceLocator()->get('AuthenticationService');
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
        $validator->setTranslator($this->getServiceLocator()->get('translator'));
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
     * Retrieve the route to which the user should be redirected after login.
     * Defaults to his account.
     * This is only for the default routes, e.g. for different user groups, when
     * he requested a specific page prior login the loginRedirector helper will
     * redirect to that page instead of the default route.
     *
     * @return string
     * @triggers getPostLoginRoute
     */
    public function getPostLoginRoute()
    {
        // @todo konfigurierbar machen
        $route = 'account';

        $user = $this->getCurrentUser();
        if (!$user) {
            return $route;
        }

        $results = $this->getEventManager()->trigger(
            self::EVENT_GET_POST_LOGIN_ROUTE,
            $user
        );

        return is_string($results->last())
            ? $results->last()
            : $route;
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
     * Retrieve a new user filter instance.
     *
     * @param string $alias     the alias for the user record
     * @return \Vrok\Entity\Filter\UserFilter
     */
    public function getUserFilter($alias = 'u')
    {
        $qb = $this->getUserRepository()->createQueryBuilder($alias);
        $filter = new \Vrok\Entity\Filter\UserFilter($qb);
        return $filter;
    }

    /**
     * Retrieve the entity manager.
     *
     * @return EntityManager
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
     * Retrieve the group repository instance.
     *
     * @return \Vrok\Entity\GroupRepository
     */
    public function getGroupRepository()
    {
        return $this->getEntityManager()->getRepository('Vrok\Entity\Group');
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
