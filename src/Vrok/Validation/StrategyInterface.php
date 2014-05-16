<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Validation;

interface StrategyInterface
{
    public function __construct(\Vrok\Entity\Validation $validation);
}
