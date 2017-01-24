<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use DateTime;
use Doctrine\ORM\EntityManager;
use Vrok\Entity\Filter\LoginKeyFilter;
use Vrok\Entity\Filter\UserFilter;
use Vrok\Entity\Group as GroupEntity;
use Vrok\Entity\LoginKey;
use Vrok\Entity\User as UserEntity;
use Vrok\Entity\Validation;
use Vrok\Stdlib\DateInterval;
use Vrok\Stdlib\PasswordStrength;
use Zend\Authentication\Validator\Authentication as AuthValidator;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Contains processes for creating and managing Attendant objects and their
 * associated actions.
 *
 * dependencies: ControllerPluginManager, EntityManager, EventManager, Meta,
 * FieldHistory, BjyAuthorize\Service\Authorize, Email,ViewHelperManager
 * Zend\Authentication\AuthenticationService, Vrok\Authentication\Adapter\Doctrine,
 * Vrok\Authentication\Adapter\Cookie, MvcTranslator, config
 */
class UserManager implements
    EventManagerAwareInterface,
    ListenerAggregateInterface
{
    use EventManagerAwareTrait;
    use ListenerAggregateTrait;

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

    const VALIDATION_USER     = 'validateUser';
    const VALIDATION_EMAIL    = 'confirmEmail';
    const VALIDATION_PASSWORD = 'confirmPasswordRequest';

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
     * The domain which is used for setcookie() when setting the authCookie.
     *
     * @var string
     */
    protected $cookieDomain = '';

    /**
     * Time in seconds a loginKey is valid.
     *
     * @var int
     */
    protected $loginKeyTimeout = 2592000; // 30*24*60*60

    /**
     * Wether the user can be logged in using his authToken cookie or not.
     *
     * @var bool
     */
    protected $cookieLoginEnabled = false;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = null;

    /**
     * Class constructor - stores the ServiceLocator instance.
     * @todo Wir verwenden hier den ServiceLocator statt die vielen dependencies
     * einzeln zu injizieren, wir bräuchten für jede noch mindestens einen setter,
     * Damit brauchen wir auch vorerst nicht sämtliche dependencies lazy-loaden,
     * der UserManager wird ja durch attach() bei jedem page hit instanziiert,
     * wir würden also zumindest sämtliche proxies instantiieren müssen.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Retrieve the stored service manager instance.
     *
     * @return ServiceLocatorInterface
     */
    private function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents = $events->getSharedManager();

        $sharedEvents->attach(
            'ValidationManager',
            \Vrok\Service\ValidationManager::EVENT_VALIDATION_SUCCESSFUL,
            [$this, 'onUserValidationSuccessful'],
            $priority
        );
        $sharedEvents->attach(
            'ValidationManager',
            \Vrok\Service\ValidationManager::EVENT_VALIDATION_SUCCESSFUL,
            [$this, 'onPasswordValidationSuccessful'],
            $priority
        );

        $sharedEvents->attach(
            'ValidationManager',
            \Vrok\Service\ValidationManager::EVENT_VALIDATION_EXPIRED,
            [$this, 'onValidationExpired'],
            $priority
        );
    }

    /**
     * Called when a validation was successfully confirmed.
     *
     * @triggers userValidated
     *
     * @param EventInterface $e
     *
     * @return Zend\Http\Response
     */
    public function onUserValidationSuccessful(EventInterface $e)
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
        $e->stopPropagation(true);
        return $this->getServiceLocator()->get('ControllerPluginManager')
                ->get('redirect')->toRoute('account/login');
    }

    /**
     * Called when a validation was successfully confirmed.
     *
     * @param EventInterface $e
     *
     * @return Zend\Http\Response
     */
    public function onPasswordValidationSuccessful(EventInterface $e)
    {
        $validation = $e->getTarget();
        /* @var $validation Validation */
        if ($validation->getType() !== self::VALIDATION_PASSWORD) {
            return;
        }

        $user = $validation->getReference($this->getEntityManager());
        if (!$user) {
            return;
        }

        $container = new \Zend\Session\Container(__CLASS__);
        $container['passwordRequestIdentity'] = $user->getUsername();

        // tell the ValidationController to redirect to the password form
        $e->stopPropagation(true);
        return $this->getServiceLocator()->get('ControllerPluginManager')
                ->get('redirect')->toRoute('account/reset-password');
    }

    /**
     * Called when a validation expired.
     *
     * @triggers userValidationExpired
     *
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
     *
     * @return UserEntity|array the new user instance or an array of errors
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
        $user->setDeletedAt(new DateTime());

        $this->clearUserLoginKeys($user);

        // @todo event für softdelete für Aufräumarbeiten?
        $fh = $this->getServiceLocator()->get(FieldHistory::class);
        $fh->purgeEntityHistory($user);

        $ms = $this->getServiceLocator()->get(Meta::class);
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
        $preDelete = $this->getEventManager()->trigger(
            self::EVENT_DELETE_ACCOUNT_PRE,
            $user
        );

        // if the event is stopped the deletion is prohibited, return the
        // results, they should contain messages explaining the reasons.
        if ($preDelete->stopped()) {
            return $preDelete;
        }

        $data = $this->getUserRepository()->getInstanceData($user);

        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $this->softDeleteUser($user);
            $this->logout();

            // allow cleanup actions and messages
            $results = $this->getEventManager()->trigger(
                self::EVENT_DELETE_ACCOUNT_POST,
                $user,
                ['data' => $data]
            );

            $em->getConnection()->commit();
        } catch (Exception $e) {
            $em->getConnection()->rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Creates a new Group from the given form data.
     *
     * @param array $formData
     *
     * @return GroupEntity
     */
    public function createGroup(array $formData)
    {
        $em = $this->getEntityManager();

        $groupRepository = $em->getRepository('Vrok\Entity\Group');
        $group           = $groupRepository->updateInstance(new GroupEntity(), $formData);
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
        return $this->getAuthService()->getIdentity();
    }

    /**
     * Checks if the current user is allowed to access the given resource (and has
     * the given privilege).
     * Mimics BjyAuthorize\Controller\Plugin\IsAllowed.
     *
     * @param mixed  $resource
     * @param string $privilege
     *
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
     *
     * @return UserEntity or null if none found
     */
    public function getUserByIdentity($identity)
    {
        $repository = $this->getUserRepository();
        $user       = $repository->findOneBy(['username' => $identity]);
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
     * @param bool $rememberMe  wether or not the user stays logged in using the authToken
     *
     * @return User|array
     */
    public function login($username, $password, $rememberMe = false)
    {
        $validator = $this->getAuthValidator();
        if (!$validator->isValid($password, ['username' => $username])) {
            return $validator->getMessages();
        }

        $now  = new DateTime();
        $user = $this->getCurrentUser();
        $user->setLastLogin($now);
        $user->setLastSession($now);
        $this->getEntityManager()->flush();

        if ($rememberMe && $this->cookieLoginEnabled) {
            $this->setAuthCookie($user);
        }

        return $user;
    }

    /**
     * Logs the current user out.
     *
     * @param bool $global  if true the users loginkeys will be deleted, logging
     *     him out on other devices too
     *
     * @return bool
     * @todo how can active sessions on other devices be logged out too?
     */
    public function logout($global = false)
    {
        $authService = $this->getAuthService();
        if (!$authService->hasIdentity()) {
            return false;
        }

        $user = $authService->getIdentity();
        $authService->clearIdentity();

        if ($this->cookieLoginEnabled) {
            $this->deleteAuthCookie();

            if ($global) {
                $this->clearUserLoginKeys($user);
            }
        }

        $this->getEventManager()->trigger(self::EVENT_LOGOUT, $user);

        // when using destroy() we could not set any messenger notifications afterwarss
        \Zend\Session\Container::getDefaultManager()->getStorage()->clear();
        \Zend\Session\Container::getDefaultManager()->regenerateId();

        return true;
    }

    /**
     * Checks if a user is already logged in, if not if the auth cookie is set
     * and valid. Tries to login the user specified in the token.
     *
     * @return UserEntity|null
     */
    public function checkAuthCookie()
    {
        if (!$this->cookieLoginEnabled || $this->getCurrentUser()) {
            return;
        }

        $credential = $this->getAuthCookie();
        if (!$credential) {
            return;
        }

        $adapter = $this->getCookieAuthAdapter();
        $adapter->getIdentity($credential[0]);
        $adapter->setCredential($credential);

        $service = $this->getAuthService();
        $result = $service->authenticate($adapter);
        if ($result->getCode() != \Zend\Authentication\Result::SUCCESS) {
            // @todo log failed auth attempt for temp bans
            $this->deleteAuthCookie();

            return;
        }

        $now  = new DateTime();
        $user = $this->getCurrentUser();
        $user->setLastSession($now);
        $this->getEntityManager()->flush();

        // update cookies, e.g. to get new future key
        $this->setAuthCookie($user);

        return $user;
    }

    /**
     * Retrieve the users current loginKeys.
     *
     * @param UserEntity $user
     * @return LoginKey[]
     */
    public function getUserLoginKeys(UserEntity $user)
    {
        // purge all expired keys in the table
        $this->purgeLoginKeys();

        $keys = $this->getLoginKeyFilter()
            ->byUser($user)
            ->getResult();

        // user has two not expired keys -> nothing else to do
        if (count($keys) > 1) {
            return $keys;
        }

        $em = $this->getEntityManager();
        $current = null;
        $interval = new DateInterval('PT'.$this->loginKeyTimeout.'S');

        // no keys -> generate new current key
        if (count($keys) == 0) {
            $now = new DateTime();
            $current = new LoginKey();
            $current->setExpirationDate($now->add($interval));
            $current->setToken(\Vrok\Stdlib\Random::getRandomToken(64));
            $current->setUser($user);
            $em->persist($current);
        }
        // remaining entry is the current key
        else {
            $current = $keys[0];
        }

        // now generate a new future key which expires after the current key
        $expiration = clone $current->getExpirationDate();
        $future = new LoginKey();
        $future->setExpirationDate($expiration->add($interval));
        $future->setToken(\Vrok\Stdlib\Random::getRandomToken(64));
        $future->setUser($user);
        $em->persist($future);

        $em->flush();
        return [$current, $future];
    }

    /**
     * Parses the current visitors auth cookie.
     *
     * @return array    [0 => id, 1 => currentKey, 2 => futureKey]
     *                  or null if cookie is invalid
     */
    protected function getAuthCookie()
    {
        if(empty($_COOKIE['authToken'])) {
            return null;
        }

        $values = explode('|', $_COOKIE['authToken']);
        if (count($values) != 3) {
            // delete corrupt cookie;
            $this->deleteAuthCookie();
            return null;
        }

        return $values;
    }

    /**
     * Sets the users auth cookie with current and future login key.
     *
     * @param UserEntity $user
     */
    protected function setAuthCookie(UserEntity $user)
    {
        if(headers_sent()) {
            return;
        }

        // delete the auth cookie if the user has an old password, force re-login
        if (password_needs_rehash($user->getPassword(), PASSWORD_DEFAULT)) {
            $this->deleteAuthCookie();
            return;
        }

        // cookie expires in twice the timeout for one key
        $expires = time() + $this->loginKeyTimeout * 2;

        // build cookie content from the users current keys
        $keys = $this->getUserLoginKeys($user);
        $content = implode('|', [
            $user->getId(),
            $keys[0]->getToken(),
            $keys[1]->getToken()
        ]);

        setcookie('authToken', $content, $expires, '/', $this->cookieDomain, false, true);
    }

    /**
     * Deletes the users auth cookie
     */
    protected function deleteAuthCookie()
    {
        if(headers_sent()) {
            return;
        }

        unset($_COOKIE['authToken']);
        setcookie('authToken', '', 0, '/', $this->cookieDomain, false, true);
    }

    /**
     * Removes all loginKeys for the given user.
     *
     * @param \Vrok\Service\User $user
     */
    public function clearUserLoginKeys(UserEntity $user)
    {
        $this->getLoginKeyFilter()
            ->byUser($user)
            ->delete()->getQuery()->execute();
    }

    /**
     * Removes all expired loginKeys from the database.
     */
    public function purgeLoginKeys()
    {
        $this->getLoginKeyFilter()
            ->areExpired()
            ->delete()->getQuery()->execute();
    }

    /**
     * Sets a new random password for the given user and sends it in an email.
     *
     * @param UserEntity $user
     */
    public function sendRandomPassword(UserEntity $user)
    {
        $password = $user->setRandomPassword();

        $emailService = $this->getServiceLocator()->get(Email::class);
        $mail         = $emailService->createMail();
        $mail->setSubject('mail.user.randomPassword.subject');

        $viewHelperManager = $this->getServiceLocator()->get('ViewHelperManager');
        $urlHelper         = $viewHelperManager->get('url');
        $fullUrlHelper     = $viewHelperManager->get('fullUrl');
        $url               = $urlHelper('account/login');

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
        return $this->getServiceLocator()
                ->get('Zend\Authentication\AuthenticationService');
    }

    /**
     * Returns the default authentication adapter.
     *
     * @return \Vrok\Authentication\Adapter\Doctrine
     */
    public function getAuthAdapter()
    {
        return $this->getServiceLocator()
                ->get(\Vrok\Authentication\Adapter\Doctrine::class);
    }

    /**
     * Returns the default authentication adapter.
     *
     * @return \Vrok\Authentication\Adapter\Cookie
     */
    public function getCookieAuthAdapter()
    {
        return $this->getServiceLocator()
                ->get(\Vrok\Authentication\Adapter\Cookie::class);
    }

    /**
     * Returns a preconfigured auth validator instance.
     *
     * @todo als factory umsetzen
     * @return AuthValidator
     */
    public function getAuthValidator()
    {
        $validator = new AuthValidator();
        $validator->setTranslator($this->getServiceLocator()->get('MvcTranslator'));
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
        $route  = empty($config['user_manager']['post_login_route'])
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
     *
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
     *
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
     *
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
     * Sets the loginKey timeout.
     *
     * @param int $value
     */
    public function setLoginKeyTimeout($value)
    {
        $this->loginKeyTimeout = (int) $value;
    }

    /**
     * Retrieve the current loginKey timeout.
     *
     * @return int
     */
    public function getLoginKeyTimeout()
    {
        return $this->loginKeyTimeout;
    }

    /**
     * Sets the cookie domain.
     *
     * @param string $value
     */
    public function setCookieDomain($value)
    {
        $this->cookieDomain = (string) $value;
    }

    /**
     * Retrieve the cookie domain.
     *
     * @return string
     */
    public function getCookieDomain()
    {
        return $this->cookieDomain;
    }

    /**
     * Sets wether login via cookie is enabled or not.
     *
     * @param bool $value
     */
    public function setCookieLoginEnabled($value)
    {
        $this->cookieLoginEnabled = (bool) $value;
    }

    /**
     * Returns true if login via cookie is enabled, else false.
     *
     * @return bool
     */
    public function getCookieLoginEnabled()
    {
        return $this->cookieLoginEnabled;
    }

    /**
     * Calculates the password strength and returns it together with a rating
     * between BAD and GREAT and a translation message for this rating.
     *
     * @param string $password
     *
     * @return array [strength => float, rating => string, ratingText => string]
     *
     * @see Vrok\Stdlib\PasswordStrength
     */
    public function ratePassword($password)
    {
        $calc = new PasswordStrength();
        $calc->setThresholds($this->getPasswordStrengthThresholds());

        $strength   = $calc->getStrength($password);
        $rating     = $calc->getRating($strength);
        $ratingText = 'message.passwordRating.'.$rating;

        return [
            'strength'   => $strength,
            'rating'     => $rating,
            'ratingText' => $ratingText,
        ];
    }

    /**
     * Retrieve a new loginKey filter instance.
     *
     * @param string $alias the alias for the loginKey record
     *
     * @return LoginKeyFilter
     */
    public function getLoginKeyFilter($alias = 'l')
    {
        $qb     = $this->getLoginKeyRepository()->createQueryBuilder($alias);
        $filter = new LoginKeyFilter($qb);

        return $filter;
    }

    /**
     * Retrieve a new user filter instance.
     *
     * @param string $alias the alias for the user record
     *
     * @return UserFilter
     */
    public function getUserFilter($alias = 'u')
    {
        $qb     = $this->getUserRepository()->createQueryBuilder($alias);
        $filter = new UserFilter($qb);

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
     * Retrieve the group repository instance.
     *
     * @return \Vrok\Entity\GroupRepository
     */
    public function getGroupRepository()
    {
        return $this->getEntityManager()->getRepository(GroupEntity::class);
    }

    /**
     * Retrieve the loginKey repository instance.
     *
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getLoginKeyRepository()
    {
        return $this->getEntityManager()->getRepository(LoginKey::class);
    }

    /**
     * Retrieve the user repository instance.
     *
     * @return \Vrok\Entity\UserRepository
     */
    public function getUserRepository()
    {
        return $this->getEntityManager()->getRepository(UserEntity::class);
    }

    /**
     * Sets multiple config options at once.
     *
     * @todo validate $config
     *
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
        if (!empty($config['loginkey_timeout'])) {
            $this->setLoginKeyTimeout($config['loginkey_timeout']);
        }
        if (!empty($config['cookie_domain'])) {
            $this->setCookieDomain($config['cookie_domain']);
        }
        if (!empty($config['cookielogin_enabled'])) {
            $this->setCookieLoginEnabled($config['cookielogin_enabled']);
        }
        if (!empty($config['password_strength_thresholds'])) {
            $this->setPasswordStrengthThresholds($config['password_strength_thresholds']);
        }
    }
}
