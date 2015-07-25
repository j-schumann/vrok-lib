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
use Vrok\Entity\Validation;
use Vrok\Stdlib\PasswordStrength;
use Zend\Authentication\Validator\Authentication as AuthValidator;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Contains processes for creating and managing Attendant objects and their
 * associated actions.
 */
class UserManager implements
    EventManagerAwareInterface,
    ListenerAggregateInterface,
    ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;
    use ListenerAggregateTrait;
    use ServiceLocatorAwareTrait;

    const EVENT_CREATE_GROUP_POST       = 'createGroup.post';
    const EVENT_CREATE_USER             = 'createUser';
    const EVENT_CREATE_USER_POST        = 'createUser.post';
    const EVENT_GET_POST_LOGIN_ROUTE    = 'getPostLoginRoute';
    const EVENT_LOGOUT                  = 'logout';
    const EVENT_DELETE_ACCOUNT_PRE      = 'deleteAccount.pre';
    const EVENT_DELETE_ACCOUNT_POST     = 'deleteAccount.post';
    const EVENT_USER_VALIDATED          = 'userValidated';
    const EVENT_USER_VALIDATION_EXPIRED = 'userValidationExpired';

    const MSG_USER_VALIDATED = 'message.user.validationSuccessful';

    const VALIDATION_USER  = 'validateUser';
    const VALIDATION_EMAIL = 'confirmEmail';

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
     * Thresholds above which a password receives the according rating.
     *
     * @var array
     */
    protected $passwordStrengthThresholds = [
        'weak'  => 15,
        'ok'    => 20,
        'good'  => 25,
        'great' => 30,
    ];

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents = $events->getSharedManager();

        $sharedEvents->attach(
            'ValidationManager',
            \Vrok\Service\ValidationManager::EVENT_VALIDATION_SUCCESSFUL,
            [$this, 'onValidationSuccessful']
        );
        $sharedEvents->attach(
            'ValidationManager',
            \Vrok\Service\ValidationManager::EVENT_VALIDATION_EXPIRED,
            [$this, 'onValidationExpired']
        );
    }

    /**
     * Called when a validation was successfully confirmed.
     *
     * @triggers userValidated
     * @param EventInterface $e
     */
    public function onValidationSuccessful(EventInterface $e)
    {
        $validation = $e->getTarget();
        /* @var $validation Validation */
        if ($validation->getType() !== self::VALIDATION_USER) {
            return;
        }

        $user = $validation->getReference($this->getEntityManager());
        if (!$user) {
            return;
        }
        /* @var $user UserEntity */

        // don't change the "activated" flag as it has a different meaning
        // (e.g admin activation required or other requirements)
        $user->setIsValidated(true);

        // persist the change just in case there is no listener or something fails
        $this->getEntityManager()->flush();

        $this->getEventManager()->trigger(
            self::EVENT_USER_VALIDATED,
            $user
        );

        $this->getServiceLocator()->get('ControllerPluginManager')
                ->get('flashMessenger')->addSuccessMessage(self::MSG_USER_VALIDATED);

        // tell the ValidationController to redirect to the confirmation page
        return $this->getServiceLocator()->get('ControllerPluginManager')
                ->get('redirect')->toRoute('account/login');
    }

    /**
     * Called when a validation expired.
     *
     * @triggers userValidationExpired
     * @param EventInterface $e
     */
    public function onValidationExpired(EventInterface $e)
    {
        $validation = $e->getTarget();
        if ($validation->getType() !== self::VALIDATION_USER) {
            return;
        }

        // failsave, do not delete if meanwhile validated (e.g. validation
        // was not deleted or validated by admin)
        $user = $validation->getReference($this->getEntityManager());
        if (!$user || $user->getIsValidated()) {
            return;
        }
        /* @var $user UserEntity */

        // expiration implies deletion, no additional event needed
        $this->getEventManager()->trigger(
            self::EVENT_USER_VALIDATION_EXPIRED,
            $user
        );

        $this->getEntityManager()->remove($user);
        // flushed by the ValidationManager
    }

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

        // set a default password for the InputFilter to pass, necessary when a
        // random password should be set
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
            [
                'user' => $user,
                'data' => $data,
            ]
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
     * Called when the currently logged in user wants to delete his account.
     *
     * @return \Zend\EventManager\ResponseCollection
     */
    public function deleteAccount()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            throw new Exception\RuntimeException('Not logged in!');
        }

        // ask if someone wants to prevent the deletion
        $results = $this->getEventManager()->trigger(
            self::EVENT_DELETE_ACCOUNT_PRE,
            $user
        );

        // if the event is stopped the deletion is prohibited, return the
        // results, they should contain messages explaining the reasons.
        if ($results->stopped()) {
            return $results;
        }

        $data = $this->getUserRepository()->getInstanceData($user);

        $this->softDeleteUser($user);
        $this->logout();
        // allow cleanup actions and messages
        $results = $this->getEventManager()->trigger(
            self::EVENT_DELETE_ACCOUNT_POST,
            $user,
            ['data' => $data]
        );

        return $results;
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
        $user = $repository->findOneBy(['username' => $identity]);
        if ($user) {
            return $user;
        }

        return $repository->findOneBy(['email' => $identity]);
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
        if (!$validator->isValid($password, ['username' => $username])) {
            return $validator->getMessages();
        }

        $now = new \DateTime();
        $user = $this->getCurrentUser();
        $user->setLastLogin($now);
        $user->setLastSession($now);
        $this->getEntityManager()->flush();

        return $user;
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
        $urlHelper = $viewHelperManager->get('url');
        $fullUrlHelper = $viewHelperManager->get('FullUrl');
        $url = $urlHelper('account/login');

        $mail->setHtmlBody(['mail.user.randomPassword.body', [
            'displayName' => $user->getDisplayName(),
            'username'    => $user->getUsername(),
            'password'    => $password,
            'loginUrl'    => $fullUrlHelper('https').$url,
        ]]);

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
        $validator->setMessages([
            AuthValidator::CREDENTIAL_INVALID => 'validate.authentication.failed',
            AuthValidator::IDENTITY_NOT_FOUND => 'validate.authentication.failed',
            AuthValidator::UNCATEGORIZED      => 'validate.authentication.uncategorizedFailure',
            AuthValidator::GENERAL            => 'validate.authentication.failed',
        ]);
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
        $config = $this->getServiceLocator()->get('config');
        $route = empty($config['user_manager']['post_login_route'])
            ? 'account'
            : $config['user_manager']['post_login_route'];

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
        return $url->fromRoute($this->getUserAdminRoute(), ['id' => $userId]);
    }

    /**
     * Sets the thresholds to use when rating passwords.
     *
     * @param array $thresholds
     */
    public function setPasswordStrengthThresholds(array $thresholds)
    {
        $this->passwordStrengthThresholds
                = array_merge($this->passwordStrengthThresholds, $thresholds);
    }

    /**
     * Retrieve the current password strength thresholds.
     *
     * @return array
     */
    public function getPasswordStrengthThresholds()
    {
        return $this->passwordStrengthThresholds;
    }

    /**
     * Calculates the password strength and returns it together with a rating
     * between BAD and GREAT and a translation message for this rating.
     *
     * @param string $password
     * @return array    [strength => float, rating => string, ratingText => string]
     * @see Vrok\Stdlib\PasswordStrength
     */
    public function ratePassword($password)
    {
        $calc = new PasswordStrength();
        $calc->setThresholds($this->getPasswordStrengthThresholds());

        $strength = $calc->getStrength($password);
        $rating = $calc->getRating($strength);
        $ratingText = 'message.passwordRating.'.$rating;

        return [
            'strength'   => $strength,
            'rating'     => $rating,
            'ratingText' => $ratingText,
        ];
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

        if (!empty($config['password_strength_thresholds'])) {
            $this->setPasswordStrengthThresholds($config['password_strength_thresholds']);
        }
    }
}
