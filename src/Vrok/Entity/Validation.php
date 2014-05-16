<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Ellie\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Validation object for confirming entries, actions etc via a token sent by
 * email.
 *
 * @ORM\Entity
 * @ORM\Table(name="validations")
 * @ORM\Entity(repositoryClass="Vrok\Entity\ValidationRepository")
 */
class Validation
{
    use \Vrok\Doctrine\Traits\AutoincrementId;
    use \Vrok\Doctrine\Traits\CreationDate;

// <editor-fold defaultstate="collapsed" desc="count">
    /**
     * @var integer
     * @ORM\Column(type="string", options={"default" = 0})
     */
    protected $count = 0;

    /**
     * Returns how often this validation was re-requested.
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Sets how often this validation was re-requested.
     *
     * @param integer $value
     * @return self
     */
    public function setAttendanceCount($value)
    {
        $this->count = $value;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="content">
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $content;

    /**
     * Returns the content to be validated (e.g. new email address).
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the content to be validated (e.g. new email address).
     *
     * @param string $value
     * @return self
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="token">
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $token;

    /**
     * Returns the validation token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the validation token.
     *
     * @param string $value
     * @return self
     */
    public function setToken($value)
    {
        $this->token = $value;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactCity">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $contactCity;

    /**
     * Returns the contact city name.
     *
     * @return string
     */
    public function getContactCity()
    {
        return $this->contactCity;
    }

    /**
     * Sets the contact city name.
     *
     * @param string $city
     * @return self
     */
    public function setContactCity($city)
    {
        $this->contactCity = $city;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactCountry">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $contactCountry;

    /**
     * Returns the contact country name.
     *
     * @return string
     */
    public function getContactCountry()
    {
        return $this->contactCountry;
    }

    /**
     * Sets the contact country name.
     *
     * @param string $country
     * @return self
     */
    public function setContactCountry($country)
    {
        $this->contactCountry = $country;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactDistrict">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $contactDistrict;

    /**
     * Returns the contact district name.
     *
     * @return string
     */
    public function getContactDistrict()
    {
        return $this->contactDistrict;
    }

    /**
     * Sets the contact district name.
     *
     * @param string $district
     * @return self
     */
    public function setContactDistrict($district)
    {
        $this->contactDistrict = $district;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactEmail">
    /**
     * @var string
     * @ORM\Column(type="string", length=70, nullable=true)
     */
    protected $contactEmail;

    /**
     * Returns the contact email
     *
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * Sets the contact email.
     *
     * @param string $email
     * @return self
     */
    public function setContactEmail($email)
    {
        $this->contactEmail = $email;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactFax">
    /**
     * @var string
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $contactFax;

    /**
     * Returns the contact fax number.
     *
     * @return string
     */
    public function getContactFax()
    {
        return $this->contactFax;
    }

    /**
     * Sets the contact fax number.
     *
     * @param string $fax
     * @return self
     */
    public function setContactFax($fax)
    {
        $this->contactFax = $fax;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactFirstName">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $contactFirstName;

    /**
     * Returns the contact first name.
     *
     * @return string
     */
    public function getContactFirstName()
    {
        return $this->contactFirstName;
    }

    /**
     * Sets the contact first name.
     *
     * @param string $firstName
     * @return self
     */
    public function setContactFirstName($firstName)
    {
        $this->contactFirstName = $firstName;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactHouseNumber">
    /**
     * @var string
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    protected $contactHouseNumber;

    /**
     * Returns the contact house number.
     *
     * @return string
     */
    public function getContactHouseNumber()
    {
        return $this->contactHouseNumber;
    }

    /**
     * Sets the contact house number.
     *
     * @param string $houseNumber
     * @return self
     */
    public function setContactHouseNumber($houseNumber)
    {
        $this->contactHouseNumber = $houseNumber;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactLastName">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $contactLastName;

    /**
     * Returns the contact last name.
     *
     * @return string
     */
    public function getContactLastName()
    {
        return $this->contactLastName;
    }

    /**
     * Sets the contact last name.
     *
     * @param string $lastName
     * @return self
     */
    public function setContactLastName($lastName)
    {
        $this->contactLastName = $lastName;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactPhone">
    /**
     * @var string
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $contactPhone;

    /**
     * Returns the contact phone number.
     *
     * @return string
     */
    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    /**
     * Sets the contact phone number.
     *
     * @param string $phone
     * @return self
     */
    public function setContactPhone($phone)
    {
        $this->contactPhone = $phone;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactPosition">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $contactPosition;

    /**
     * Returns the contacts position within the organization.
     *
     * @return integer
     */
    public function getContactPosition()
    {
        return $this->contactPosition;
    }

    /**
     * Sets the contacts position within the organization.
     *
     * @param string $contactPosition
     * @return self
     */
    public function setContactPosition($contactPosition)
    {
        $this->contactPosition = $contactPosition;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactPostalCode">
    /**
     * @var string
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    protected $contactPostalCode;

    /**
     * Returns the contact postal code.
     *
     * @return string
     */
    public function getContactPostalCode()
    {
        return $this->contactPostalCode;
    }

    /**
     * Sets the contact postal code.
     *
     * @param string $postalCode
     * @return self
     */
    public function setContactPostalCode($postalCode)
    {
        $this->contactPostalCode = $postalCode;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactRessort">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $contactRessort;

    /**
     * Returns the contacts ressort of responsibility.
     *
     * @return string
     */
    public function getContactRessort()
    {
        return $this->contactRessort;
    }

    /**
     * Sets the contacts ressort of responsibility.
     *
     * @param string $contactRessort
     * @return self
     */
    public function setContactRessort($contactRessort)
    {
        $this->contactRessort = $contactRessort;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contacts">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $contacts;

    /**
     * Returns the contacts with this organization.
     *
     * @return array
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * Sets the additional contacts with the organization.
     *
     * @param array $contacts
     * @return self
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactStreet">
    /**
     * @var string
     * @ORM\Column(type="string", length=70, nullable=true)
     */
    protected $contactStreet;

    /**
     * Returns the contact street name.
     *
     * @return string
     */
    public function getContactStreet()
    {
        return $this->contactStreet;
    }

    /**
     * Sets the contact street name.
     *
     * @param string $street
     * @return self
     */
    public function setContactStreet($street)
    {
        $this->contactStreet = $street;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactTitle">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $contactTitle;

    /**
     * Returns the contact title.
     *
     * @return string
     */
    public function getContactTitle()
    {
        return $this->contactTitle;
    }

    /**
     * Sets the contact title.
     *
     * @param string $title
     * @return self
     */
    public function setContactTitle($title)
    {
        $this->contactTitle = $title;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="contactUnit">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $contactUnit;

    /**
     * Returns the contact organization unit.
     *
     * @return string
     */
    public function getContactUnit()
    {
        return $this->contactUnit;
    }

    /**
     * Sets the contact organization unit.
     *
     * @param string $unit
     * @return self
     */
    public function setContactUnit($unit)
    {
        $this->contactUnit = $unit;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="desiredAgreement">
    const AGREEMENT_GENERAL = 'general';
    const AGREEMENT_SINGLE = 'single';

    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $desiredAgreement;

    /**
     * Returns the desired agreement with ellievant.
     *
     * @return array
     */
    public function getDesiredAgreement()
    {
        return $this->desiredAgreement;
    }

    /**
     * Sets the desired agreement with ellievant.
     *
     * @param array $desiredAgreement
     * @return self
     */
    public function setDesiredAgreement($desiredAgreement)
    {
        $this->desiredAgreement = $desiredAgreement;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="division">
    /**
     * @var array
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $division;

    /**
     * Returns the organizations division addressed with this request.
     *
     * @return array
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * Sets wether this request is meant for a local/regional/... division
     * and which.
     *
     * @param array $division
     * @return self
     */
    public function setDivision($division)
    {
        $this->division = $division;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="email">
    /**
     * @var string
     * @ORM\Column(type="string", length=70)
     */
    protected $email;

    /**
     * Returns the email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the email.
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="employeeCount">
    /**
     * @var integer
     * @ORM\Column(type="string", nullable=true)
     */
    protected $employeeCount;

    /**
     * Returns the employee count.
     *
     * @return integer
     */
    public function getEmployeeCount()
    {
        return $this->employeeCount;
    }

    /**
     * Sets the employee count addressed with this request.
     *
     * @param integer $employeeCount
     * @return self
     */
    public function setEmployeeCount($employeeCount)
    {
        $this->employeeCount = $employeeCount;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="employeeSupportMeasures">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $employeeSupportMeasures;

    /**
     * Returns the organizations employee support measures.
     *
     * @return array
     */
    public function getEmployeeSupportMeasures()
    {
        return $this->employeeSupportMeasures;
    }

    /**
     * Sets the organizations employee support measures.
     *
     * @param array $employeeSupportMeasures
     * @return self
     */
    public function setEmployeeSupportMeasures($employeeSupportMeasures)
    {
        $this->employeeSupportMeasures = $employeeSupportMeasures;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="employeeRetentionMeasures">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $employeeRetentionMeasures;

    /**
     * Returns the organizations employee retention measures.
     *
     * @return array
     */
    public function getEmployeeRetentionMeasures()
    {
        return $this->employeeRetentionMeasures;
    }

    /**
     * Sets the organizations employee retention measures.
     *
     * @param array $employeeRetentionMeasures
     * @return self
     */
    public function setEmployeeRetentionMeasures($employeeRetentionMeasures)
    {
        $this->employeeRetentionMeasures = $employeeRetentionMeasures;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="fax">
    /**
     * @var string
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $fax;

    /**
     * Returns the fax number.
     *
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Sets the fax number.
     *
     * @param string $fax
     * @return self
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="firstContactDate">
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $firstContactDate;

    /**
     * Returns the date of the first contact with this organization.
     *
     * @return \DateTime
     */
    public function getFirstContactDate()
    {
        return $this->firstContactDate;
    }

    /**
     * Sets the date of the first contact with the organization.
     *
     * @param \DateTime $firstContactDate
     * @return self
     */
    public function setFirstContactDate(\DateTime $firstContactDate)
    {
        $this->firstContactDate = $firstContactDate;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="firstContactPerson">
    /**
     * @var integer
     * @ORM\Column(type="string", nullable=true)
     */
    protected $firstContactPerson;

        /**
     * Returns the name of the person which first contacted this organization
     *
     * @return string
     */
    public function getFirstContactPerson()
    {
        return $this->firstContactPerson;
    }

    /**
     * Sets the name of the person which first contacted the organization.
     *
     * @param string $firstContactPerson
     * @return self
     */
    public function setFirstContactPerson($firstContactPerson)
    {
        $this->firstContactPerson = $firstContactPerson;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="informationEvent">
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $informationEvent;

    /**
     * Returns wether there is interest in an information event.
     *
     * @return bool
     */
    public function getInformationEvent()
    {
        return $this->informationEvent;
    }

    /**
     * Sets wether there is interest in an information event.
     *
     * @param bool $informationEvent
     * @return self
     */
    public function setInformationEvent($informationEvent)
    {
        $this->informationEvent = $informationEvent;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="ipAddress">
    /**
     * IPV6 = 32 characters + 5 colons
     *
     * @var string
     * @ORM\Column(type="string", length=39, nullable=true)
     */
    protected $ipAddress;

    /**
     * Returns the IP address from which the questionaire was created.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Sets the IP address from which the questionare was submitted.
     *
     * @param string $ipAddress
     * @return self
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="isDivision">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $isDivision = false;

    /**
     * Returns wether the request is for a subdivision.
     *
     * @return bool
     */
    public function getIsDivision()
    {
        return $this->isDivision;
    }

    /**
     * Sets wether the request is for a subdivision.
     *
     * @param bool $isDivision
     * @return self
     */
    public function setIsDivision($isDivision)
    {
        $this->isDivision = (bool) $isDivision;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="level">
    const LEVEL_BRANCH   = 'branch';
    const LEVEL_NATION   = 'nation';
    const LEVEL_STATE    = 'state';
    const LEVEL_REGION   = 'region';
    const LEVEL_LOCALITY = 'locality';

    /**
     * @var array
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $level;

    /**
     * Returns the organizations level addressed with this request.
     *
     * @return array
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Sets wether this request is meant for a local/regional/... level
     * and which.
     *
     * @param array $level
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="levelName">
    /**
     * @var array
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $levelName;

    /**
     * Returns the level name.
     *
     * @return array
     */
    public function getLevelName()
    {
        return $this->levelName;
    }

    /**
     * Sets the level name.
     *
     * @param array $levelName
     * @return self
     */
    public function setLevelName($levelName)
    {
        $this->levelName = $levelName;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="name">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * Returns the organizations name.
     *
     * @return integer
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the organizations name.
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="nationalities">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $nationalities;

    /**
     * Returns the most frequent nationalities of the employees.
     *
     * @return array
     */
    public function getNationalities()
    {
        return $this->nationalities;
    }

    /**
     * Sets the most frequent nationalities of the employees.
     *
     * @param array $nationalities
     * @return self
     */
    public function setNationalities($nationalities)
    {
        $this->nationalities = $nationalities;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="notes">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $notes;

    /**
     * Returns the internal notes of ellie staff on that organization.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Sets the internal notes of the ellie staff on the organization.
     *
     * @param array $notes
     * @return self
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="phone">
    /**
     * @var string
     * @ORM\Column(type="string", length=30)
     */
    protected $phone;

    /**
     * Returns the phone number.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Sets the phone number.
     *
     * @param string $phone
     * @return self
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="position">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $position;

    /**
     * Returns the position within the organization.
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets the position within the organization.
     *
     * @param string $position
     * @return self
     */
    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="preferredLanguages">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $preferredLanguages;

    /**
     * Returns the preferred languages spoken by the employees.
     *
     * @return array
     */
    public function getPreferredLanguages()
    {
        return $this->preferredLanguages;
    }

    /**
     * Sets the preferred languages spoken by the employees.
     *
     * @param array $preferredLanguages
     * @return self
     */
    public function setPreferredLanguages($preferredLanguages)
    {
        $this->preferredLanguages = $preferredLanguages;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="ressort">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $ressort;

    /**
     * Returns the ressort of responsibility.
     *
     * @return string
     */
    public function getRessort()
    {
        return $this->ressort;
    }

    /**
     * Sets the ressort of responsibility.
     *
     * @param string $ressort
     * @return self
     */
    public function setRessort($ressort)
    {
        $this->ressort = $ressort;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="questions">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $questions;

    /**
     * Returns the questions to ellievant.
     *
     * @return array
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * Sets the questions to ellievant.
     *
     * @param array $questions
     * @return self
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="specialLeave">
    /**
     * @var array
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $specialLeave;

    /**
     * Returns the organizations special leave arrangements for parents.
     *
     * @return array
     */
    public function getSpecialLeave()
    {
        return $this->specialLeave;
    }

    /**
     * Sets the organizations arrangements for special leave for parents.
     *
     * @param array $specialLeave
     * @return self
     */
    public function setSpecialLeave($specialLeave)
    {
        $this->specialLeave = $specialLeave;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="unit">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $unit;

    /**
     * Returns the organization unit.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Sets the organization unit.
     *
     * @param string $unit
     * @return self
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="useAdditionalContact">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $useAdditionalContact = false;

    /**
     * Returns wether the contact address differs from the current address.
     *
     * @return bool
     */
    public function getUseAdditionalContact()
    {
        return $this->useAdditionalContact;
    }

    /**
     * Sets wether the the contact address differs from the current address.
     *
     * @param bool $useAdditionalContact
     * @return self
     */
    public function setUseAdditionalContact($useAdditionalContact)
    {
        $this->useAdditionalContact = (bool) $useAdditionalContact;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="web">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $web;

    /**
     * Returns the organizations website.
     *
     * @return integer
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * Sets the organizations website.
     *
     * @param string $web
     * @return self
     */
    public function setWeb($web)
    {
        $this->web = $web;
        return $this;
    }
// </editor-fold>
}
