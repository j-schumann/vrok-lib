<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Searches the given text for the given string and surrounds every occurence
 * with a tag to highlight search patterns etc.
 */
class HighlightText extends AbstractHelper
{
    protected $openString  = '<mark>';
    protected $closeString = '</mark>';

    /**
     * Uses simple str_replace, make sure the pattern does not match any tags,
     * or the input text does not contain any HTML tags!
     *
     * @param string $subject the text within the pattern is searched
     * @param string $pattern the string which is to be highlighted
     * @param bool $caseSensitive wether the pattern case is kept or the original
     *
     * @return string
     */
    public function __invoke($subject, $pattern, $caseSensitive = false)
    {
        return $caseSensitive
            ? str_replace($pattern,
                    $this->openString.$pattern.$this->closeString, $subject)
            : preg_replace("/".preg_quote($pattern)."/i",
                    $this->openString."\$0".$this->closeString, $subject);
    }

    /**
     * (Re-)sets the HTML markup to begin the highlighted passage.
     *
     * @param string $openString
     */
    public function setOpenString($openString = '<mark>')
    {
        $this->openString = $openString;
    }

    /**
     * (Re-)sets the HTML markup to end the highlighted passage.
     *
     * @param string $closeString
     */
    public function setCloseString($closeString = '</mark>')
    {
        $this->closeString = $closeString;
    }
}
