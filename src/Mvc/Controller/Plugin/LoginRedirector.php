<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mvc\Controller\Plugin;

use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container as SessionContainer;
use Zend\View\Helper\Url as UrlHelper;
use Zend\View\Model\JsonModel;

/**
 * Allows to redirect the user to the login page and then redirect back to the
 * source page.
 */
class LoginRedirector extends AbstractPlugin
{
    /**
     * Route to the login form.
     *
     * @var string
     */
    protected $loginRoute = 'account/login';

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * @var SessionContainer
     */
    protected $session = null;

    /**
     * @var UrlHelper
     */
    protected $urlHelper = null;

    /**
     * Retrieve the session storage.
     *
     * @return SessionContainer
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = new SessionContainer(__CLASS__);
            $this->session->setExpirationHops(2);
        }

        return $this->session;
    }

    /**
     * Redirect the user to the login page.
     * Result may be directly returned from the controller.
     *
     * @param string $route     (optional) alternative login route
     * @return Response|JsonModel
     */
    public function gotoLogin($route = null)
    {
        $helper = $this->urlHelper;
        $url    = $helper($route ?: $this->loginRoute);

        /*
         * Detection of XHR is based an the X_REQUESTED_WITH header, any AJAX
         * requests without that header (e.g. D3.js or cross-domain requests)
         * cannot be detected this way and will be handled as normal requests.
         *
         * We cannot redirect back after login for failed XHRs as they are no
         * normal page, so we simply return the message&redirect without setting
         * the returnAfterLogin URL.
         */
        if ($this->request->isXmlHttpRequest()) {
            // this "script" result is only supported by Vrok.Tools.processResponse
            // in vrok-lib.js
            return new JsonModel([
                'message' => 'Not allowed, please log in!',
                'script'  => "window.location.href='$url';",
            ]);
        }

        /*
         * store the complete URI including GET params to allow redirection back
         * to this page after the login. POST is ignored and must be repeated.
         */
        $session                     = $this->getSession();
        $session['returnAfterLogin'] = [
            'uri' => $this->request->getUriString(),
        ];

        $response = new Response();
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode(302);

        return $response;
    }

    /**
     * Should be called by a controller after a successful login to redirect the user
     * to his originally requested page if there is any in the session. Else redirects
     * him to the (given) default route.
     *
     * @param string $defaultRoute
     *
     * @return Response
     */
    public function goBack($defaultRoute = 'account')
    {
        $session  = $this->getSession();
        $response = new Response();
        $response->setStatusCode(302);

        if (empty($session['returnAfterLogin'])) {
            $helper = $this->urlHelper;
            $url    = $helper($defaultRoute);
            $response->getHeaders()->addHeaderLine('Location', $url);
        } else {
            $response->getHeaders()->addHeaderLine('Location', $session['returnAfterLogin']);
            $session->exchangeArray([]);
        }

        return $response;
    }

    /**
     * Injected by the factory.
     *
     * @param UrlHelper $urlHelper
     *
     * @return self
     */
    public function setUrlHelper(UrlHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;

        return $this;
    }

    /**
     * Injected by the factory.
     *
     * @param Request $request
     *
     * @return self
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
