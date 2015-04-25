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
     * @var integer
     */
    public $userId;

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

    public function getUserId()
    {
        return $this->userId;
    }
    public function setUserId($userId)
    {
        return $this->userId = $userId;
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
