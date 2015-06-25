<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

/**
 * Used to store the geocoordinates of an object: longitude & latitude in degrees.
 */
trait GeolocationInformation
{
    /**
     * Checks if the entity has geocoordinates set.
     *
     * @return bool
     */
    public function hasGeolocation()
    {
        return $this->longitude && $this->latitude;
    }

    /**
     * Retrieve the geolocation as array.
     *
     * @return array
     */
    public function getGeolocation()
    {
        return array('lat' => $this->latitude, 'lon' => $this->longitude);
    }

// <editor-fold defaultstate="collapsed" desc="latitude">
    /**
     * @var string
     * @Doctrine\ORM\Mapping\Column(type="decimal", precision=13, scale=10, nullable=true)
     */
    protected $latitude;

    /**
     * Returns the latitude.
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Sets the latitude.
     *
     * @param string $latitude
     * @return self
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="longitude">
    /**
     * @var string
     * @Doctrine\ORM\Mapping\Column(type="decimal", precision=13, scale=10, nullable=true)
     */
    protected $longitude;

    /**
     * Returns the latitude.
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Sets the longitude.
     *
     * @param string $longitude
     * @return self
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }
// </editor-fold>
}
