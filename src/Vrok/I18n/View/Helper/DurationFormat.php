<?php
/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     http://customlicense CustomLicense
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\View\Helper;

use Zend\I18n\View\Helper\AbstractTranslatorHelper;

/**
 * Helper that constructs the full application url to use for links including
 * the schema + domain.
 */
class DurationFormat extends AbstractTranslatorHelper
{
    /**
     * Format a number of hours in human readable form
     *
     * @param  mixed $value
     * @param  string    $locale
     * @return string
     */
    public function __invoke($value, $locale = null)
    {
        // @todo support other inputs besides hours (minutes, seconds, ...)

        if (is_array($value)) {
            if (isset($value['hours'])) {
                $value[0] = $value['hours'];
            }
            if (isset($value['minutes'])) {
                $value[1] = $value['minutes'];
            }
        } elseif (is_null($value)) {
            $value = [0, 0];
        } elseif (is_numeric($value)) {
            $value = [floor($value), ($value - floor($value)) * 60];
        } else {
            // @todo custom exception interface
            throw new \InvalidArgumentException('Value can not be parsed as duration!');
        }

        $value[1] = str_pad($value[1], 2, '0', STR_PAD_LEFT);

        // @todo singular/plural translation
        // @todo support other duration displays like (Xd, Yh, Zmin o.ä)
        return $this->getTranslator()->translate(
            ['duration.hoursAndMinutes', $value],
            $this->getTranslatorTextDomain(),
            $locale
        );
    }
}
