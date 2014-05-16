<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

// required in the using class:
//use Doctrine\ORM\Mapping as ORM;

trait AddressInformation
{
    /**
     * @var string
     * @ORM\Column(type="string", length=70, nullable=true)
     */
    protected $street;

    /**
     * @var string
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    protected $houseNumber;

    /**
     * @var string
     * @ORM\Column(type="string", length=70, nullable=true)
     */
    protected $addressInfo;

    /**
     * @var string
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    protected $postalCode;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $city;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $district;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $country;

    /**
     * Returns the street name.
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Returns the house number.
     *
     * @return string
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * Returns additional address details.
     *
     * @return string
     */
    public function getAddressInfo()
    {
        return $this->addressInfo;
    }

    /**
     * Returns the postal code.
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Returns the city name.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Returns the district name.
     *
     * @return string
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Returns the country name.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the street name.
     *
     * @param string $street
     * @return self
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    /**
     * Sets the house number.
     *
     * @param string $houseNumber
     * @return self
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = $houseNumber;
        return $this;
    }

    /**
     * Sets the additional address details.
     *
     * @param string $addressInfo
     * @return self
     */
    public function setAddressInfo($addressInfo)
    {
        $this->addressInfo = $addressInfo;
        return $this;
    }

    /**
     * Sets the postal code.
     *
     * @param string $postalCode
     * @return self
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * Sets the city name.
     *
     * @param string $city
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Sets the district name.
     *
     * @param string $district
     * @return self
     */
    public function setDistrict($district)
    {
        $this->district = $district;
        return $this;
    }

    /**
     * Sets the country name.
     *
     * @param string $country
     * @return self
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }
}
