<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

/**
 * Allows to calculate the strength of a password with a variant of a NIST
 * proposal developed by Thomas Hruska,
 *
 * @link http://en.wikipedia.org/wiki/Password_strength#NIST_Special_Publication_800-63
 * @link http://cubicspot.blogspot.de/2011/11/how-to-calculate-password-strength.html
 * @link http://cubicspot.blogspot.de/2012/01/how-to-calculate-password-strength-part.html
 * @link http://cubicspot.blogspot.de/2012/06/how-to-calculate-password-strength-part.html
 */
class PasswordStrength
{
    const RATING_BAD   = 'bad';
    const RATING_WEAK  = 'weak';
    const RATING_OK    = 'ok';
    const RATING_GOOD  = 'good';
    const RATING_GREAT = 'great';

    /**
     * Thresholds above which a password is rated OK/GOOD etc.
     *
     * @var array
     */
    protected $thresholds = array(
        self::RATING_WEAK  => 15,
        self::RATING_OK    => 20,
        self::RATING_GOOD  => 25,
        self::RATING_GREAT => 30,
    );

    /**
     * Returns the current threshold settings.
     *
     * @return array
     */
    public function getThresholds()
    {
        return $this->thresholds;
    }

    /**
     * Allows to set the threshold values for each rating.
     *
     * @param array $thresholds
     */
    public function setThresholds(array $thresholds)
    {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
    }

    /**
     * Converts the given strength value into a human readable rating.
     *
     * @param float $strength
     * @return string
     */
    public function getRating($strength)
    {
        if ($strength >= $this->thresholds[self::RATING_GREAT]) {
            return self::RATING_GREAT;
        }

        if ($strength >= $this->thresholds[self::RATING_GOOD]) {
            return self::RATING_GOOD;
        }

        if ($strength >= $this->thresholds[self::RATING_OK]) {
            return self::RATING_OK;
        }

        if ($strength >= $this->thresholds[self::RATING_WEAK]) {
            return self::RATING_WEAK;
        }

        return self::RATING_BAD;
    }

    /**
     * Calculates the password strength using an entropy method.
     * Returns a numeric value where higher = better, starting with -6 for an
     * empty string. Gives a bonus for passphrases consisting of 4 or more
     * words separated with a space.
     *
     * @param string $password
     * @return float
     */
    public function getStrength($password)
    {
        $y = strlen($password);

        // Variant on NIST rules to reduce long sequences of repeated characters
        $result = 0;
        $mult = [];
        for ($i = 0; $i < $y; $i++) {
            $code = ord($password[$i]);

            if (!isset($mult[$code])) {
                $mult[$code] = 1;
            }

            if ($i > 19) {
                $result += $mult[$code];
            } elseif ($i > 7) {
                $result += $mult[$code] * 1.5;
            } elseif ($i > 0) {
                $result += $mult[$code] * 2;
            } else {
                $result += 4;
            }

            $mult[$code] *= 0.75;
        }

        // NIST password strength rules allow up to 6 extra bits for mixed case
        // and non-alphabetic characters
        $lower = preg_match('/[a-z]/', $password);
        $upper = preg_match('/[A-Z]/', $password);
        $numeric = preg_match('/\d/', $password);
        $space = preg_match('/ /', $password);
        $other = !preg_match('/^[A-Za-z0-9 ]*$/', $password);

        $extrabits = 0;
        if ($upper) {
            $extrabits += 1;
        }
        if ($lower && $upper) {
            $extrabits += 1;
        }
        if ($numeric) {
            $extrabits += 1;
        }
        if ($other) {
            $extrabits += 2;
        } elseif($space) {
            $extrabits += 1;
        }

        // malus if only special characters or only numeric
        if (!$lower && !$upper) {
            $extrabits -= 2;

            if (!$other && !$space) {
                $extrabits -= 4;
            }
        }

        // bonus if pw consists of 4 or more separate words
        if (count(explode(" ", preg_replace('/\s+/', " ", $password))) > 3) {
            $extrabits++;
        }

        return $result + $extrabits;
    }

    /**
     * Returns the rating for the given password.
     *
     * @param string $password
     * @return string
     */
    public function ratePassword($password)
    {
        return $this->getRating($this->getStrength($password));
    }
}
