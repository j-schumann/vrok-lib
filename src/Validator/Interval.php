<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Validator;

use DateInterval;
use Zend\Validator\AbstractValidator;

/**
 * Checks if the value is a valid ISO 8601 duration specification.
 *
 * @see \Vrok\Form\Element\Interval
 * @see \Vrok\Filter\Interval
 */
class Interval extends AbstractValidator
{
    const INVALID_INTERVAL = 'intervalInvalid';
    const INVALID_AMOUNT   = 'amountInvalid';

    /**
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID_INTERVAL => 'Value is no valid ISO 8601 duration specification',
        self::INVALID_AMOUNT   => 'The interval must be given as positive integer',
    ];

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        // it is not our responsibility to check for empty/required
        if (empty($value)) {
            return true;
        }

        if (is_string($value)) {
            try {
                new DateInterval($value);
            } catch (Exception $e) {
                $this->error(self::INVALID_INTERVAL);
                return false;
            }

            return true;
        }

        // we want to present a more meaningful error message to the user when
        // using our Interval form element
        if (isset($value['amount'])) {
            $amount = $value['amount'];
            if (!is_numeric($amount) || (int)$amount != $amount || $amount <= 0) {
                $this->error(self::INVALID_AMOUNT);
                return false;
            }
        }

        $this->error(self::INVALID_INTERVAL);
        return false;
    }
}
