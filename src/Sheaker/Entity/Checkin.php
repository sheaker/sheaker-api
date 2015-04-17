<?php

namespace Sheaker\Entity;

class Checkin
{
    /**
     * Client id in database.
     *
     * @var integer
     */
    public $id;

    /**
     * User.
     *
     * @var \Sheaker\Entity\User
     */
    public $user;

    /**
     * When the checkin entity was created.
     *
     * @var string
     */
    public $createdAt;

    public function getId()
    {
        return $this->id;
    }
    public function setId($id)
    {
        return $this->id = $id;
    }

    public function getUser()
    {
        return $this->user;
    }
    public function setUser($user)
    {
        return $this->user = $user;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
