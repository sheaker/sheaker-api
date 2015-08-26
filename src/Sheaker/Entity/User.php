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
     * First Name.
     *
     * @var string
     */
    public $first_name;

    /**
     * Last Name.
     *
     * @var string
     */
    public $last_name;

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
    public $user_level;

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
    public $address_street_1;

    /**
     * Second line Street Address.
     *
     * @var string
     */
    public $address_street_2;

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
    public $failed_logins;

    /**
     * When the user entity was last seen.
     *
     * @var string
     */
    public $last_seen;

    /**
     * The last IP of the user.
     *
     * @var String
     */
    public $last_ip;

    /**
     * When the user entity was created.
     *
     * @var string
     */
    public $created_at;

    /**
     * When the user entity was deleted.
     *
     * @var string
     */
    public $deleted_at;

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        return $this->id = $id;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }
    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;
    }

    public function getLastName()
    {
        return $this->last_name;
    }
    public function setLastName($lastName)
    {
        $this->last_name = $lastName;
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
        return $this->user_level;
    }
    public function setUserLevel($userLevel)
    {
        $this->user_level = $userLevel;
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
        return $this->address_street_1;
    }
    public function setAddressStreet1($addressStreet1)
    {
        $this->address_street_1 = $addressStreet1;
    }

    public function getAddressStreet2()
    {
        return $this->address_street_2;
    }
    public function setAddressStreet2($addressStreet2)
    {
        $this->address_street_2 = $addressStreet2;
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
        return $this->failed_logins;
    }
    public function setFailedLogins($failedLogins)
    {
        $this->failed_logins = $failedLogins;
    }

    public function getLastSeen()
    {
        return $this->last_seen;
    }
    public function setLastSeen($lastSeen)
    {
        $this->last_seen = $lastSeen;
    }

    public function getLastIP()
    {
        return $this->last_ip;
    }
    public function setLastIP($lastIP)
    {
        $this->last_ip = $lastIP;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    }

    public function getDeletedAt()
    {
        return $this->deleted_at;
    }
    public function setDeletedAt($deletedAt)
    {
        $this->deleted_at = $deletedAt;
    }
}
