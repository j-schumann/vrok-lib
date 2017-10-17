<?php

/**
 * @copyright   (c) 2017, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Escapes special chars in LaTeX
 */
class TexEscape extends AbstractHelper
{
    /**
     * Uses simple str_replace to escape LaTeX special chars.
     *
     * @param string $subject the text to escape
     *
     * @return string
     */
    public function __invoke(string $subject) : string
    {
        return str_replace(
            // backslash first to prevent double replace
            array('\\',             '&',  '%',  '$',  '#',  '_',  '{',  '}',  '~',               '^',                "\n",   "\r"),
            array('\textbackslash', '\&', '\%', '\$', '\#', '\_', '\{', '\}', '\textasciitilde', '\textasciicircum', '\\\\', ''),
            $subject
        );
    }
}
