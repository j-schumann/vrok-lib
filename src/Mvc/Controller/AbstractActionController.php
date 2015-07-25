<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mvc\Controller;

use Zend\Mvc\Controller\AbstractActionController as ZendController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * Extend the default controller class with often used helper functions,
 * escpecially to handle Ajax/JSON requests.
 */
abstract class AbstractActionController extends ZendController
{
    const MESSAGE_PARAM_MISSING = 'message.controller.paramMissing';
    const MESSAGE_PARAM_INVALID = 'message.controller.paramInvalid';

    /**
     * Creates a new ViewModel with the flashmessenger preset and additional
     * variables if provided.
     *
     * @param array $variables
     *
     * @return ViewModel
     */
    public function createViewModel(array $variables = [])
    {
        // we want to inject the flashMessenger instance into the view
        // as the view helper would create a new instance (and old messages
        // would not be shown if we added new messages in the current action)
        $variables['flashMessenger'] = $this->flashMessenger();

        return new ViewModel($variables);
    }

    /**
     * Tries to load an entity with the given class using the identifier
     * provided in the given parameter name.
     *
     * @param string $entityClass
     * @param string $param       parameter name to use
     * @param string $field       field name to query with the parameter value
     *
     * @return mixed Doctrine Entity or an array containing the error message
     */
    public function getEntityFromParam($entityClass, $param = 'id', $field = 'id')
    {
        $value = $this->params($param);
        if (!$value) {
            return [self::MESSAGE_PARAM_MISSING, $param];
        }

        return $this->getEntity($entityClass, $value, $field)
            ?: [self::MESSAGE_PARAM_INVALID, $param];
    }

    /**
     * Tries to load an entity with the given class using the identifier
     * provided in the given parameter name.
     *
     * @param string $entityClass
     * @param string $param       parameter name to use
     * @param string $field       field name to query with the parameter value
     *
     * @return mixed Doctrine Entity or an array containing the error message
     */
    public function getEntityFromQuery($entityClass, $param = 'id', $field = 'id')
    {
        $value = $this->params()->fromQuery($param);
        if (!$value) {
            return [self::MESSAGE_PARAM_MISSING, $param];
        }

        return $this->getEntity($entityClass, $value, $field)
            ?: [self::MESSAGE_PARAM_INVALID, $param];
    }

    /**
     * Shortcut function to retrieve a Doctrine entity by a simple query.
     * Returns only one entity, even if more are found!
     *
     * @param string $entityClass the entity class
     * @param mixed  $value       the value for which should be queried
     * @param string $field       (optional) the column in which we want to search for
     *                            the value, defaults to "id"
     *
     * @return mixed the entity or null if none found
     */
    public function getEntity($entityClass, $value, $field = 'id')
    {
        $em         = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $repository = $em->getRepository($entityClass);

        $entity = $repository->findOneBy([$field => $value]);

        return $entity;
    }

    /**
     * Renders the given partial using the model data provided.
     *
     * @param string $partial
     * @param mixed  $model   (optional) ViewModel or array with data
     * @param string $module  (optional) name of the module where the partial resides
     *
     * @return string
     */
    public function renderPartial($partial, $model = [], $module = null)
    {
        $partialHelper = $this->getServiceLocator()->get('viewhelpermanager')
                ->get('partial');

        return $module
                ? $partialHelper($partial, $module, $model)
                : $partialHelper($partial, $model);
    }

    /**
     * Returns the JSON response with the given partial and javascript.
     *
     * @param string $partial
     * @param mixed  $model      (optional) ViewModel or array with data
     * @param string $javascript (optional) javascript to be executed after
     *                           the response loaded
     * @param string $module     (optional) name of the module where the
     *                           partial resides
     *
     * @return JsonModel
     */
    public function getAjaxResponse($partial, $model = [],
            $javascript = null, $module = null)
    {
        $html = $this->renderPartial($partial, $model, $module);

        $data = ['html' => $html];
        if ($javascript) {
            $data['script'] = $javascript;
        }

        return $this->getJsonModel($data);
    }

    /**
     * Returns the JSON response to show an error message and redirect the user
     * to the given URL (or home).
     *
     * @param string $message
     * @param string $url
     *
     * @return JsonModel
     */
    public function getErrorResponse($message = null, $url = '/')
    {
        $translator = $this->getServiceLocator()->get('translator');
        $message    = $translator->translate($message ?: 'message.invalidAjaxRequest');

        $data = [
            'script' => "alert('$message'); window.location.href='$url';",
        ];

        return $this->getJsonModel($data);
    }

    /**
     * Returns the response to redirect the user to the given URL (or home).
     *
     * @param string $url
     *
     * @return JsonModel
     */
    public function getRedirectResponse($url = '/')
    {
        $data = [
            'script' => "window.location.href='$url';",
        ];

        return $this->getJsonModel($data);
    }

    /**
     * Returns the given data as JSON to the client.
     * Supports JSONP when the callback is given in a paramenter named "callback".
     *
     * @param mixed $data
     *
     * @return JsonModel
     */
    public function getJsonModel($data)
    {
        $json = new JsonModel($data);
        if ($this->request->getQuery('callback')) {
            $json->setJsonpCallback($this->request->getQuery('callback'));
        }

        return $json;
    }
}
