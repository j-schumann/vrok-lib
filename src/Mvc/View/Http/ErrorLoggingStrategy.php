<?php

/*
 *  @copyright   (c) 2014-2016, Vrok
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mvc\View\Http;

use Throwable;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ResponseInterface as Response;

class ErrorLoggingStrategy extends AbstractListenerAggregate
{
    /**
     * Folder to log the errors to.
     *
     * @var string
     */
    protected $logDir = '';

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = -3000)
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'logException'],
            $priority
        );
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_RENDER_ERROR,
            [$this, 'logException'],
            $priority
        );
    }

    /**
     * Set the logging folder.
     *
     * @param  string $logDir
     * @return self
     */
    public function setLogDir(string $logDir)
    {
        $this->logDir = $logDir;
        return $this;
    }

    /**
     * Log the exception/throwable if any.
     *
     * @param  MvcEvent $e
     * @return void
     *
     * @todo viel Code vom StdLib\ErrorHandler dupliziert -> neue ToolClass
     */
    public function logException(MvcEvent $e)
    {
        // Do nothing if no error in the event
        $error = $e->getError();
        if (empty($error)) {
            return;
        }

        $result = $e->getResult();
        if ($result instanceof Response) {
            return $result;
        }

        if ($error === Application::ERROR_ROUTER_NO_MATCH) {
            return;
        }

        $message = $error;
        if (isset($_SERVER['REQUEST_URI'])) {
            $message .= 'URI: '.$_SERVER['REQUEST_URI']."\n";
        }

        $exception = $e->getParam('exception');
        if ($exception instanceof Throwable) {
            $message = "\n".get_class($exception).' in '.$exception->getFile().':'
                    .$exception->getLine().' - '.$exception->getMessage()."\n";
            if (isset($_SERVER['REQUEST_URI'])) {
                $message .= 'URI: '.$_SERVER['REQUEST_URI']."\n";
            }
            $message .= $exception->getTraceAsString();
        }

        $environment = ! empty($_SERVER['SERVER_ADDR'])
            ? $_SERVER['SERVER_ADDR']
            : php_sapi_name();
        $month    = date('Y-m');
        $filename = ($this->logDir ? $this->logDir.DIRECTORY_SEPARATOR : '')
            .$environment.'-'.$month.'.log';

        $handle = fopen($filename, 'a+');
        if ($handle) {
            $time = date('Y-m-d H:i:s');
            fwrite($handle, $time.': '.$message."\n");
            fclose($handle);
        }
    }
}
