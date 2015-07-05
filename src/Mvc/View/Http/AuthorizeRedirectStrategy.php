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
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Stdlib\ResponseInterface as ResponseInterface;
use Zend\View\Model\ViewModel;

/**
 * Listens on the dispatchEvent to check if the authorization failed, if yes redirect
 * the logged out user to the login form or show the 403 page if the user is logged in.
 * Allows to redirect the user back to his source page after login.
 */
class AuthorizeRedirectStrategy implements
    ListenerAggregateInterface,
    ServiceLocatorAwareInterface
{
    use ListenerAggregateTrait;
    use ServiceLocatorAwareTrait;

    /**
     * @var string
     */
    protected $template = 'error/403';

    /**
     * Number of seconds a user may be inactive before being logged out an the next hit.
     *
     * @var int
     */
    protected $ttl = 1800; // 30*60

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH,
            [$this, 'onDispatch'],
            5000
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'onDispatchError'],
            -5000
        );
    }

    /**
     * Handles redirects in case the activity timeout is reached.
     *
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onDispatch(MvcEvent $event)
    {
        // redirect to login is not necessary for console...
        if (! $event->getRequest() instanceof HttpRequest) {
            return;
        }

        $provider = $this->getServiceLocator()->get('BjyAuthorize\Provider\Identity\ProviderInterface');
        $identityRoles = $provider->getIdentityRoles();

        // no logged in user -> nothing to log out
        if (!in_array($provider->getAuthenticatedRole(), $identityRoles)) {
            return;
        }

        $now = time();
        $session = new \Zend\Session\Container(__CLASS__);
        if (isset($session['activityTimeout']) && $session['activityTimeout'] < $now) {
            $manager = $this->getServiceLocator()->get('UserManager');
            $manager->logout();

            $translator = $this->getServiceLocator()
                    ->get('Zend\I18n\Translator\TranslatorInterface');
            $message = $translator->translate([
                'message.user.activityTimeout',
                floor($this->getTtl() / 60)
            ]);

            $cm = $this->getServiceLocator()->get('ControllerPluginManager');
            $messenger = $cm->get('flashMessenger');
            $messenger->addErrorMessage($message);

            // @todo the helper returns a viewmodel containing a script-redirect
            // when the request is a XHR. This viewmodel is ignored by the event
            // trigger, the user is then redirected with error 500 to the login page
            // when he has no privilege to access the called page because is his
            // logged out now. But our activity-timeout message is not displayed.
            // also: we should differentiate between XHR that only expect JSON etc
            // and those that support the script-redirect
            $helper = $cm->get('loginRedirector');
            return $helper->gotoLogin();
        }

        // @todo XHR requests that are triggered periodically also keep the user
        // logged in, how can we differentiate between automatic hits and user
        // induced actions?

        // logged in but no timeout -> new session, set new timeout
        // logged in but timeout in the future -> refresh timeout
        $session['activityTimeout']  = $now + $this->getTtl();
    }

    /**
     * Handles redirects in case of dispatch errors caused by unauthorized access
     *
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onDispatchError(MvcEvent $event)
    {
        $result   = $event->getResult();
        $response = $event->getResponse();
        $routeMatch = $event->getRouteMatch();

        // Do nothing if the result is a response object or not valid
        if ($result instanceof ResponseInterface || !$routeMatch
            || ($response && ! $response instanceof HttpResponse)
        ) {
            return;
        }

        // Common view variables
        $viewVariables = [
            'error'      => $event->getParam('error'),
           'identity'   => $event->getParam('identity'),
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

        $provider = $this->getServiceLocator()->get('BjyAuthorize\Provider\Identity\ProviderInterface');
        $identityRoles = $provider->getIdentityRoles();

        if (in_array($provider->getAuthenticatedRole(), $identityRoles)) {
            // there is an identity -> the user is logged in but still not authorized
            // -> show error
            $model = new ViewModel($viewVariables);
            $model->setTemplate($this->getTemplate());
            $event->getViewModel()->addChild($model);

            $response->setStatusCode(403);
            $event->setResponse($response);

            return;
        }

        // no identity -> not logged in -> redirect to login page
        $cm = $this->getServiceLocator()->get('ControllerPluginManager');
        $messenger = $cm->get('flashMessenger');
        $messenger->addErrorMessage('message.user.loginRequired');

        $helper = $cm->get('loginRedirector');
        $result = $helper->gotoLogin();

        // Helper returns JsonModel for XHR and a response with the location header
        // for "normal" requests
        if ($result instanceof ViewModel) {
            $event->setViewModel($result);
        }
        else {
            $event->setResponse($result);
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
     * $return int
     * @todo konfigurierbar machen. Die Strategy ist servicelocator-aware weil nicht alle
     * dependencies injected werden sollen (nur in den allerwenigsten Fällen brauchen wir
     * tatsächlich den LoginRedirector und den Messenger, die Strategy wird aber bei jedem
     * Pagehit geladen). Daher hier auch die TTL aus dem SM holen.
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Sets the number seconds a user may be inactive before being logged out.
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = (int)$ttl;
    }
}
