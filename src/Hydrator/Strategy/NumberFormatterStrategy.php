<?php

/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Hydrator\Strategy;

use Zend\Hydrator\Strategy\StrategyInterface;
use Zend\I18n\Filter\NumberParse;
use Zend\I18n\View\Helper\NumberFormat;

/**
 * Display the stored value as localized number if possible.
 * We inject both helper to allow setting a custom locale to override the
 * default/current locale.
 */
class NumberFormatterStrategy implements StrategyInterface
{
    /**
     * @var NumberFormat
     */
    protected $formatter;

    /**
     * @var NumberParse
     */
    protected $parser;

    /**
     * Constructor
     *
     * @param NumberFormat $formatter
     */
    public function __construct(NumberFormat $formatter, NumberParse $parser)
    {
        $this->formatter = $formatter;
        $this->parser = $parser;
    }

    /**
     * Localizes the numeric value.
     *
     * @param mixed $value
     *
     * @return mixed|string
     */
    public function extract($value)
    {
        $f = $this->formatter;
        return $f($value);
    }

    /**
     * Normalizes the numeric value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function hydrate($value)
    {
        $result = $this->parser->filter($value);

        // re-use the formatters decimals here.
        // For cases where the database allows more decimals but we want to
        // restrict them anyways
        if (is_int($this->formatter->getDecimals())) {
            $result = round($result, $this->formatter->getDecimals());
        }

        return $result;
    }
}
