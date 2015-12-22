<?php

namespace Sheaker\Entity;

class Payment
{
    /**
     * Payment id.
     *
     * @var integer
     */
    public $id;

    /**
     * User id.
     *
     * @var integer
     */
    public $user_id;

    /**
     * Number of days of the subscription.
     *
     * @var integer
     */
    public $days;

    /**
     * First day of subscription.
     *
     * @var string
     */
    public $start_date;

    /**
     * Last day of subscription.
     *
     * @var string
     */
    public $end_date;

    /**
     * Special Comment.
     *
     * @var string
     */
    public $comment;

    /**
     * Price of the subscription.
     *
     * @var integer
     */
    public $price;

    /**
     * Payment method.
     *
     * @var integer
     */
    public $method;

    /**
     * When the payment entity was created.
     *
     * @var string
     */
    public $created_at;

    /**
     * When the payent entity was deleted.
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

    public function getUserId()
    {
        return $this->user_id;
    }
    public function setUserId($userId)
    {
        return $this->user_id = $userId;
    }

    public function getDays()
    {
        return $this->days;
    }
    public function setDays($days)
    {
        $this->days = $days;
    }

    public function getStartDate()
    {
        return $this->start_date;
    }
    public function setStartDate($startDate)
    {
        $this->start_date = $startDate;
    }

    public function getEndDate()
    {
        return $this->end_date;
    }
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;
    }

    public function getComment()
    {
        return $this->comment;
    }
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getPrice()
    {
        return $this->price;
    }
    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getMethod()
    {
        return $this->method;
    }
    public function setMethod($method)
    {
        $this->method = $method;
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
