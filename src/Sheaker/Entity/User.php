<?php

namespace Sheaker\Entity;

class User
{
    /**
     * User id in database.
     *
     * @var integer
     */
    public $id;

    /**
     * User custom id to log in gym.
     * Could be differente if user want a custom identification
     *
     * @var integer
     */
    public $customId;

    /**
     * First Name.
     *
     * @var string
     */
    public $firstName;

    /**
     * Last Name.
     *
     * @var string
     */
    public $lastName;

    /**
     * Password.
     *
     * @var integer
     */
    public $password;

    /**
     * Phone number.
     *
     * @var string
     */
    public $phone;

    /**
     * Email.
     *
     * @var string
     */
    public $mail;

    /**
     * Access.
     *
     * @var integer
     */
    public $userLevel;

    /**
     * When the user entity was born.
     *
     * @var string
     */
    public $birthdate;

    /**
     * First line Street Address.
     *
     * @var string
     */
    public $addressStreet1;

    /**
     * Second line Street Address.
     *
     * @var string
     */
    public $addressStreet2;

    /**
     * Name of the City.
     *
     * @var string
     */
    public $city;

    /**
     * Zip code.
     *
     * @var integer
     */
    public $zip;

    /**
     * What kind of person is the user entity.
     *
     * @var integer
     */
    public $gender;

    /**
     * Photo path.
     *
     * @var String
     */
    public $photo;

    /**
     * Sponsor id.
     *
     * @var integer
     */
    public $sponsor;

    /**
     * Comment on the user.
     *
     * @var String
     */
    public $comment;

    /**
     * Number of failed login for this user.
     *
     * @var integer
     */
    public $failedLogins;

    /**
     * When the user entity was last seen.
     *
     * @var string
     */
    public $lastSeen;

    /**
     * The last IP of the user.
     *
     * @var String
     */
    public $lastIP;

    /**
     * When the user entity was created.
     *
     * @var string
     */
    public $subscriptionDate;

    /**
     * The active membership id.
     *
     * @var integer
     */
    public $activeMembershipId;

    /**
     * Array of all his payments.
     *
     * @var array
     */
    public $payments;

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        return $this->id = $id;
    }

    public function getCustomId()
    {
        return $this->customId;
    }
    public function setCustomId($customId)
    {
        return $this->customId = $customId;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getPassword()
    {
        return $this->password;
    }
    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPhone()
    {
        return $this->phone;
    }
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    public function getMail()
    {
        return $this->mail;
    }
    public function setMail($mail)
    {
        $this->mail = $mail;
    }

    public function getUserLevel()
    {
        return $this->userLevel;
    }
    public function setUserLevel($userLevel)
    {
        $this->userLevel = $userLevel;
    }

    public function getBirthdate()
    {
        return $this->birthdate;
    }
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    public function getAddressStreet1()
    {
        return $this->addressStreet1;
    }
    public function setAddressStreet1($addressStreet1)
    {
        $this->addressStreet1 = $addressStreet1;
    }

    public function getAddressStreet2()
    {
        return $this->addressStreet2;
    }
    public function setAddressStreet2($addressStreet2)
    {
        $this->addressStreet2 = $addressStreet2;
    }

    public function getCity()
    {
        return $this->city;
    }
    public function setCity($city)
    {
        $this->city = $city;
    }

    public function getZip()
    {
        return $this->zip;
    }
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    public function getGender()
    {
        return $this->gender;
    }
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    public function getPhoto()
    {
        return $this->photo;
    }
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    public function getSponsor()
    {
        return $this->sponsor;
    }
    public function setSponsor($sponsor)
    {
        $this->sponsor = $sponsor;
    }

    public function getComment()
    {
        return $this->comment;
    }
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getFailedLogins()
    {
        return $this->failedLogins;
    }
    public function setFailedLogins($failedLogins)
    {
        $this->failedLogins = $failedLogins;
    }

    public function getLastSeen()
    {
        return $this->lastSeen;
    }
    public function setLastSeen($lastSeen)
    {
        $this->lastSeen = $lastSeen;
    }

    public function getLastIP()
    {
        return $this->lastIP;
    }
    public function setLastIP($lastIP)
    {
        $this->lastIP = $lastIP;
    }

    public function getSubscriptionDate()
    {
        return $this->subscriptionDate;
    }
    public function setSubscriptionDate($subscriptionDate)
    {
        $this->subscriptionDate = $subscriptionDate;
    }

    public function getActiveMembershipId()
    {
        return $this->activeMembershipId;
    }
    public function setActiveMembershipId($activeMembershipId)
    {
        $this->activeMembershipId = $activeMembershipId;
    }

    public function getPayments()
    {
        return $this->payments;
    }
    public function setPayments($payments)
    {
        $this->payments = $payments;
    }
}
