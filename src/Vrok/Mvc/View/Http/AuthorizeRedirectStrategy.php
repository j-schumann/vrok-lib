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
class AuthorizeRedirectStrategy implements ListenerAggregateInterface, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * @var string
     */
    protected $template = 'error/403';

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), -5000);
    }

    /**
     * {@inheritDoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
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
        $viewVariables = array(
           'error'      => $event->getParam('error'),
           'identity'   => $event->getParam('identity'),
        );

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
        $messenger = $this->getServiceLocator()->get('ControllerPluginManager')
                    ->get('flashMessenger');
        $messenger->addErrorMessage('message.user.loginRequired');

        $helper = $this->getServiceLocator()->get('ControllerPluginManager')
                    ->get('loginRedirector');
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
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = (string) $template;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
