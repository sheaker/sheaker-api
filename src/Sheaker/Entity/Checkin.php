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
    public $user_id;

    /**
     * When the checkin entity was created.
     *
     * @var string
     */
    public $created_at;

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
        return $this->user_id;
    }
    public function setUserId($userId)
    {
        return $this->user_id = $userId;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    }
}
