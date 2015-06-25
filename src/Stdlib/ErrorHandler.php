<?php
/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     http://customlicense CustomLicense
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

use Exception;

/**
 * Registers itself as error/shutdown handlers and logs the errors to the file
 * given.
 */
class ErrorHandler
{
    /**
     * List of error names corresponding to the error level.
     *
     * @var array
     */
    public $errorLevels = array(
        0                   => 'Uncaught Exception',
        E_ERROR             => 'E_ERROR',
        E_WARNING           => 'E_WARNING',
        E_PARSE             => 'E_PARSE',
        E_NOTICE            => 'E_NOTICE',
        E_CORE_ERROR        => 'E_CORE_ERROR',
        E_CORE_WARNING      => 'E_CORE_WARNING',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_USER_WARNING      => 'E_USER_WARNING',
        E_USER_NOTICE       => 'E_USER_NOTICE',
        E_STRICT            => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED        => 'E_DEPRECATED',
        E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
        E_ALL               => 'E_ALL'
    );

    /**
     * Path to the folder where the log file(s) should be stored.
     *
     * @var string
     */
    protected $logfolder = '';

    /**
     * The URL to which the user should be redirected on errors.
     *
     * @var string
     */
    protected $redirectUrl = '';

    /**
     * Registers the handlers.
     *
     * @param string $logfolder
     * @param string $redirectUrl
     */
    public function __construct($logfolder, $redirectUrl = '/error/')
    {
        $this->logfolder = $logfolder;
        $this->redirectUrl = $redirectUrl;

        set_error_handler(array($this, 'errorHandler'), E_ALL);
        set_exception_handler(array($this, 'exceptionHandler'));
        register_shutdown_function(array($this, 'shutdownHandler'));
    }

    /**
     * Custom error handling function to allow logging of PHP errors in the
     * applications log file instead of the syslog or apache log file.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param mixed $errcontext
     * @return boolean
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        // if we are not on the command line and want to see errors continue
        // with the default error handling
        $display = ini_get('display_errors');
        if (php_sapi_name() !== 'cli' && ($display === 'on' || $display == 1)) {
            return false;
        }

        // by default the application will be stopped if not changed later on,
        // e.g. for deprecated warnings
        $return = false;

        switch($errno) {
            case E_WARNING:
            case E_NOTICE:
            case E_STRICT:
            case E_RECOVERABLE_ERROR:
            case E_DEPRECATED:
                $return = true;
                break;
        }

        $e = $errcontext instanceof Exception
            ? $errcontext
            : new Exception(''); // empty Exception just for the backtrace

        $errname = $this->errorLevels[$errno];
        $message = "$errname in $errfile:$errline - $errstr\n";
        if (isset($_SERVER['REQUEST_URI'])) {
            $message .= 'URI: '.$_SERVER['REQUEST_URI']."\n";
        }
        $message .= $e->getTraceAsString();

        $this->logMessage($message);

        // return is set to true -> resume with normal error handling,
        if ($return ) {
            return false;
        }

        $this->redirect();
    }

    /**
     * Proxies to the default error handler with the exception details
     *
     * @param Exception $e
     */
    public function exceptionHandler(Exception $e, $nested = false)
    {
        if ($e->getPrevious() instanceof Exception) {
            $this->exceptionHandler($e->getPrevious(), true);
        }

        $this->errorHandler(
            0, // custom errno
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e // errcontext
        );

        if ($nested) {
            return;
        }

        // because the exception handler cannot "return false" to resume the
        // default handling we have to display the error ourselves
        $display = ini_get('display_errors');
        if ($display === 'on' || $display == 1) {
            echo "Uncaught exception '".get_class($e)."' with message '"
                .$e->getMessage()."' in ".$e->getFile().":".$e->getLine()
                ." Stack trace: ".$e->getTraceAsString();
        }
    }

    /**
     * Called on each script shutdown, checks for fatal errors and logs them.
     */
    public function shutdownHandler()
    {
        // do nothing if there was no error
        $err = error_get_last();
        if (!$err || $err['type'] != E_ERROR) {
            return;
        }

        // if we are not on the command line and display_errors is enabled
        // -> do nothing
        $display = ini_get('display_errors');
        if (php_sapi_name() !== 'cli' && ($display === 'on' || $display == 1)) {
            return;
        }

        // there is no backtrace available in the shutdown_handler so we can
        // only get file and line number and eventually the URL
        $message = 'Shutdown on E_ERROR in '.$err['file'].':'.$err['line'].' - '
            .$err['message'];
        if (isset($_SERVER['REQUEST_URI'])) {
            $message .= ' - URI: '.$_SERVER['REQUEST_URI'];
        }
        $this->logMessage($message);

        $this->redirect();
    }

    /**
     * Logs the given message to the logfile.
     * Prepends the timestamp and appends a linebreak.
     *
     * @param string $message
     */
    protected function logMessage($message)
    {
        $environment = isset($_SERVER['SERVER_ADDR'])
            ? $_SERVER['SERVER_ADDR']
            : php_sapi_name();
        $month = date('Y-m');
        $filename = $this->logfolder.DIRECTORY_SEPARATOR
                .$environment.'-'.$month.'.log';

        $handle = fopen($filename, 'a+');
        if ($handle) {
            $time = date("Y-m-d H:i:s");
            fwrite($handle, $time.': '.$message."\n");
            fclose($handle);
        }
    }

    /**
     * Redirects the user to the error page.
     */
    public function redirect()
    {
        // no redirects on the commandline
        if (php_sapi_name() === 'cli') {
            return;
        }

        // prevent redirect loops
        if (!empty($_SERVER['REQUEST_URI'])
            && $_SERVER['REQUEST_URI'] === $this->redirectUrl
        ) {
            return;
        }

        if (!headers_sent()) {
            header('Location: '.$this->redirectUrl);
        }
        else {
            echo '<script type="text/javascript">window.location =  "'
                .$this->redirectUrl.'";</script>';
        }
    }
}
