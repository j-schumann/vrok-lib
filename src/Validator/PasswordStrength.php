<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Validator;

use Vrok\Stdlib\PasswordStrength as StrengthCalculator;
use Zend\Validator\AbstractValidator;

/**
 * Checks the mathematical strength of the given password for enough entropy.
 * Does _not_ check for keyboard patterns or dictionary attacks.
 *
 * @see Vrok\Stdlib\PasswordStrength
 */
class PasswordStrength extends AbstractValidator
{
    const INVALID  = 'passwordInvalid';
    const TOO_WEAK = 'tooWeak';

    /**
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID  => 'Invalid type given. String expected',
        self::TOO_WEAK => 'The given password is too weak',
    ];

    /**
     * The minimum password strength required to be valid.
     *
     * @var float
     */
    protected $threshold = 20;

    /**
     * Sets validator options.
     *
     * @param int|array|\Traversable $options
     */
    public function __construct($options = [])
    {
        if (!is_array($options)) {
            $temp['threshold'] = array_shift(func_get_args());
            $options           = $temp;
        }

        parent::__construct($options);
    }

    /**
     * Returns the minimum password strength.
     *
     * @return float
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * Sets the minimum password strength.
     *
     * @param float $value
     *
     * @return self
     */
    public function setThreshold($value)
    {
        $this->threshold = (float) $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);

            return false;
        }

        $calc     = new StrengthCalculator();
        $strength = $calc->getStrength($value);
        if ($strength < $this->threshold) {
            $this->error(self::TOO_WEAK);

            return false;
        }

        return true;
    }
}
