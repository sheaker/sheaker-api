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
     * User.
     *
     * @var \Sheaker\Entity\User
     */
    public $user;

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
    public $startDate;

    /**
     * Last day of subscription.
     *
     * @var string
     */
    public $endDate;

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
    public $paymentMethod;

    /**
     * When the user entity was created.
     *
     * @var string
     */
    public $paymentDate;

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
        return $this->startDate;
    }
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
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

    public function getPaymentDate()
    {
        return $this->paymentDate;
    }
    public function setPaymentDate($paymentDate)
    {
        $this->paymentDate = $paymentDate;
    }
}
