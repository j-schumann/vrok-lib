<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\BjyAuthorize;

use BjyAuthorize\Exception\UnAuthorizedException;
use BjyAuthorize\Guard\Controller as OriginalGuard;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;

/**
 * Overwritten to directly return the DISPATCH_ERROR result like the
 * Mvc\RouteListener does.
 */
class ControllerGuard extends OriginalGuard
{
    /**
     * Event callback to be triggered on dispatch, causes application error triggering
     * in case of failed authorization check
     *
     * @param MvcEvent $event
     *
     * @return null|Zend\Router\RouteMatch
     */
    public function onDispatch(MvcEvent $event)
    {
        /* @var $service \BjyAuthorize\Service\Authorize */
        $service    = $this->serviceLocator->get('BjyAuthorize\Service\Authorize');
        $match      = $event->getRouteMatch();
        $controller = $match->getParam('controller');
        $action     = $match->getParam('action');
        $request    = $event->getRequest();
        $method     = $request instanceof HttpRequest ? strtolower($request->getMethod()) : null;

        $authorized = $service->isAllowed($this->getResourceName($controller))
            || $service->isAllowed($this->getResourceName($controller, $action))
            || ($method && $service->isAllowed($this->getResourceName($controller, $method)));

        if ($authorized) {
            return;
        }

        $event->setError(static::ERROR);
        $event->setParam('identity', $service->getIdentity());
        $event->setParam('controller', $controller);
        $event->setParam('action', $action);

        $errorMessage = sprintf("You are not authorized to access %s:%s", $controller, $action);
        $event->setParam('exception', new UnAuthorizedException($errorMessage));

        /* @var $app \Zend\Mvc\ApplicationInterface */
        $app = $event->getTarget();
        $results = $app->getEventManager()->trigger(MvcEvent::EVENT_DISPATCH_ERROR, $event);
        if (count($results)) {
            return $results->last();
        }

        return $event->getParams();
    }
}
