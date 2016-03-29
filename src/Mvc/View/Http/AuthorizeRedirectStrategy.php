<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mvc\View\Http;

use BjyAuthorize\Exception\UnAuthorizedException;
use BjyAuthorize\Guard\Controller;
use BjyAuthorize\Guard\Route;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ResponseInterface as ResponseInterface;
use Zend\View\Model\ViewModel;

/**
 * Listens on the dispatchErrorEvent to check if the authorization failed, if yes
 * redirect the logged out user to the login form or show the 403 page if the
 * user is logged in.
 * Allows to redirect the user back to his source page after login.
 *
 * Listens to the routeEvent to log the user out if the activitityTimeout is
 * reached (independently from the session timeout).
 *
 * This strategy uses the serviceLocator as the dependencies (urlHelper,
 * flashMessenger, config) are only used in rare cases, we don't want to
 * fetch/instantiate those on every single page hit.
 *
 * dependencies: BjyAuthorize\Provider\Identity\ProviderInterface, UserManager,
 * config, Zend\I18n\Translator\TranslatorInterface, ControllerPluginManager
 */
class AuthorizeRedirectStrategy implements
    ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    const DEFAULT_TTL = 1800; // 30*60

    /**
     * @var string
     */
    protected $template = 'error/403';

    /**
     * Number of seconds a user may be inactive before being logged out on the
     * next hit.
     *
     * @var int
     */
    protected $ttl = null;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = null;

    /**
     * Class constructor - stores the ServiceLocator instance.
     * We inject the locator directly as not all services are lazy loaded
     * but some are only used in rare cases.
     * @todo lazyload all required services and include them in the factory
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
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'onRoute'],
            5000 // to run *before* BjyAuthorize, so the user is already logged
                 // out when the permissions are checked
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'onDispatch'],
            -5000 // to run *after* the RouteNotFoundStrategy
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'onDispatchError'],
            -5000 // to run (almost) last (assetManager has lower prio)
        );
    }

    /**
     * Deactivate the layout for XHR requests that triggered a 404.
     *
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onDispatch(MvcEvent $event)
    {
        $response = $event->getResponse();
        if (! $response instanceof HttpResponse
            || $response->getStatusCode() != 404
        ) {
            // Only handle 404 responses
            return;
        }

        if ($event->getRequest()->isXmlHttpRequest()) {
            $vm = $event->getViewModel();
            $firstChild = $vm->getChildren()[0];
            $firstChild->setTerminal(true);
            $event->setViewModel($firstChild);
        }
    }

    /**
     * Handles redirects in case the activity timeout is reached.
     *
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onRoute(MvcEvent $event)
    {
        // redirect to login is not necessary for console...
        if (! $event->getRequest() instanceof HttpRequest) {
            return;
        }

        $provider      = $this->getServiceLocator()->get('BjyAuthorize\Provider\Identity\ProviderInterface');
        $identityRoles = $provider->getIdentityRoles();

        // no logged in user -> nothing to log out
        if (!in_array($provider->getAuthenticatedRole(), $identityRoles)) {
            return;
        }

        $now     = time();
        $session = new \Zend\Session\Container(__CLASS__);
        if (isset($session['activityTimeout']) && $session['activityTimeout'] < $now) {
            $manager = $this->getServiceLocator()->get('Vrok\Service\UserManager');
            $manager->logout();

            $translator = $this->getServiceLocator()
                    ->get('Zend\I18n\Translator\TranslatorInterface');
            $message = $translator->translate([
                'message.user.activityTimeout',
                floor($this->getTtl() / 60),
            ]);

            $cm        = $this->getServiceLocator()->get('ControllerPluginManager');
            $messenger = $cm->get('flashMessenger');
            $messenger->addInfoMessage($message);

            // let the dispatch finish. If the requested resource is
            // accessible to logged out users it is fine, else the
            // onDispatchError will handle it.
            return;
        }

        // @todo XHR requests that are triggered periodically also keep the user
        // logged in, how can we differentiate between automatic hits and user
        // induced actions?

        // logged in but no timeout -> new session, set new timeout
        // logged in but timeout in the future -> refresh timeout
        $session['activityTimeout'] = $now + $this->getTtl();
    }

    /**
     * Handles redirects in case of dispatch errors caused by unauthorized access.
     *
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onDispatchError(MvcEvent $event)
    {
        $result     = $event->getResult();
        $response   = $event->getResponse();
        $routeMatch = $event->getRouteMatch();

        // Do nothing if the result is a response object or not valid
        if ($result instanceof ResponseInterface || !$routeMatch
            || ($response && !$response instanceof HttpResponse)
        ) {
            return;
        }

        // Common view variables
        $viewVariables = [
            'error'    => $event->getParam('error'),
            'identity' => $event->getParam('identity'),
        ];

        switch ($event->getError()) {
            case Controller::ERROR:
                $viewVariables['controller'] = $event->getParam('controller');
                $viewVariables['action']     = $event->getParam('action');
                break;

            case Route::ERROR:
                $viewVariables['route'] = $event->getParam('route');
                break;

            case Application::ERROR_EXCEPTION:
                if (!($event->getParam('exception') instanceof UnAuthorizedException)) {
                    return;
                }

                $viewVariables['reason'] = $event->getParam('exception')->getMessage();
                $viewVariables['error']  = 'error-unauthorized';
                break;

            default:
                /*
                 * do nothing if there is no error in the event or the error
                 * does not match one of our predefined errors (we don't want
                 * our 403 template to handle other types of errors)
                 */

                return;
        }

        if (!$response) {
            $response = new HttpResponse();
        }

        $provider      = $this->getServiceLocator()->get('BjyAuthorize\Provider\Identity\ProviderInterface');
        $identityRoles = $provider->getIdentityRoles();

        if (in_array($provider->getAuthenticatedRole(), $identityRoles)) {
            // there is an identity -> the user is logged in but still not authorized
            // -> show error
            $model = new ViewModel($viewVariables);
            $model->setTemplate($this->getTemplate());
            $event->getViewModel()->addChild($model);

            $response->setStatusCode(403);
            $event->setResponse($response);

            return $response;
        }

        // no identity -> not logged in -> redirect to login page
        $cm        = $this->getServiceLocator()->get('ControllerPluginManager');
        $messenger = $cm->get('flashMessenger');
        $messenger->addErrorMessage('message.user.loginRequired');

        $helper = $cm->get('loginRedirector');
        $redirect = $helper->gotoLogin();

        // prevent further listeners, we already decided to return a 403,
        // no need to check for assets etc.
        $event->stopPropagation();

        // @todo how can we differentiate between XHR that only expect JSON etc
        // and those that support the script-redirect for vrok-lib.js?

        // Helper returns JsonModel for XHR and a response with the location
        // header for "normal" requests
        if ($redirect instanceof ViewModel) {
            $event->setViewModel($redirect);
            $response->setStatusCode(403); // else we would see an error 500
        } else {
            $event->setResponse($redirect);
            return $redirect; // return directly to prevent view rendering
        }
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = (string) $template;
    }

    /**
     * Returns the number seconds a user may be inactive before being logged out.
     *
     * The TTL is only used when a user is logged in so we fetch it only when
     * required and not set instead of injecting on every page hit via factory.
     *
     * $return int
     */
    public function getTtl()
    {
        if (!$this->ttl) {
            $config = $this->getServiceLocator()->get('Config');
            $this->ttl = isset($config['user_manager']['activity_timeout'])
                ? (int)$config['user_manager']['activity_timeout']
                : self::DEFAULT_TTL;
        }

        return $this->ttl;
    }

    /**
     * Sets the number seconds a user may be inactive before being logged out.
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = (int) $ttl;
    }
}
