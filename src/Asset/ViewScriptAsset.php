<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Asset;

use Assetic\Asset\BaseAsset;
use Assetic\Filter\FilterInterface;
use Zend\View\Renderer\PhpRenderer;

/**
 * Allows to use any view script as asset.
 * It will be rendered and used as asset file with the mimetype determined by
 * its file extension. It can then be filtered, e.g. minified or SCSS compiled.
 */
class ViewScriptAsset extends BaseAsset
{
    /**
     * @var string
     */
    protected $viewScript = null;

    /**
     * @var PhpRenderer
     */
    protected $renderer = null;

    /**
     * Constructor.
     *
     * @param string      $source   Path to the view script so it can be found
     *                              in the Viewmanagers path stack
     * @param PhpRenderer $renderer The view renderer
     * @param array       $filters  An array of filters
     */
    public function __construct($source, PhpRenderer $renderer, $filters = [])
    {
        $this->viewScript = $source;
        $this->renderer   = $renderer;
        parent::__construct($filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified()
    {
        $realFile = $this->renderer->resolver($this->viewScript);
        if (!is_file($realFile)) {
            throw new \RuntimeException(
                    sprintf('The source file "%s" does not exist.', $realFile));
        }

        return filemtime($realFile);
    }

    /**
     * {@inheritdoc}
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        $content = $this->renderer->render($this->viewScript);
        $this->doLoad($content, $additionalFilter);
    }
}
