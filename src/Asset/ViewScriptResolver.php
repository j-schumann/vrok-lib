<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Asset;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use AssetManager\Exception;
use AssetManager\Resolver\MimeResolverAwareInterface;
use AssetManager\Resolver\ResolverInterface;
use AssetManager\Service\MimeResolver;
use Zend\View\Renderer\PhpRenderer;

/**
 * This resolver allows to use view scripts within the viewManagers path as
 * assets. Returns ViewScriptAssets that will be rendered as any other template.
 */
class ViewScriptResolver implements ResolverInterface, MimeResolverAwareInterface
{
    /**
     * @var array
     */
    protected $map = [];

    /**
     * @var MimeResolver The mime resolver.
     */
    protected $mimeResolver;

    /**
     * @var PhpRenderer
     */
    protected $renderer;

    /**
     * Constructor.
     *
     * Instantiate and optionally populate map.
     *
     * @param PhpRenderer       $renderer
     * @param array|Traversable $map
     */
    public function __construct(PhpRenderer $renderer, $map = [])
    {
        $this->renderer = $renderer;
        $this->setMap($map);
    }

    /**
     * Set the mime resolver.
     *
     * @param MimeResolver $resolver
     */
    public function setMimeResolver(MimeResolver $resolver)
    {
        $this->mimeResolver = $resolver;
    }

    /**
     * Get the mime resolver.
     *
     * @return MimeResolver
     */
    public function getMimeResolver()
    {
        return $this->mimeResolver;
    }

    /**
     * Set (overwrite) map.
     *
     * Maps should be arrays or Traversable objects with name => path pairs
     *
     * @param array|Traversable $map
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setMap($map)
    {
        if (!is_array($map) && !$map instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects an array or Traversable, received "%s"',
                __METHOD__,
                (is_object($map) ? get_class($map) : gettype($map))
            ));
        }

        if ($map instanceof Traversable) {
            $map = ArrayUtils::iteratorToArray($map);
        }

        $this->map = $map;
    }

    /**
     * Retrieve the map.
     *
     * @return array
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($name)
    {
        if (!isset($this->map[$name])) {
            return;
        }

        $file  = $this->map[$name];
        $asset = new ViewScriptAsset($file, $this->renderer);

        $realFile        = $this->renderer->resolver($file);
        $asset->mimetype = $this->getMimeResolver()->getMimeType($realFile);

        return $asset;
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        return array_keys($this->map);
    }
}
