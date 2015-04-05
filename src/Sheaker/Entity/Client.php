<?php

namespace Sheaker\Entity;

class Client
{
    /**
     * Client id in database.
     *
     * @var integer
     */
    public $id;

    /**
     * Client name.
     *
     * @var string
     */
    public $name;

    /**
     * subdomain name.
     *
     * @var string
     */
    public $subdomain;

    /**
     *Secret Key use to encrypt token.
     *
     * @var string
     */
    public $secretKey;

    /**
     * When the client entity was created.
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

    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        return $this->name = $name;
    }

    public function getSubdomain()
    {
        return $this->subdomain;
    }
    public function setSubdomain($subdomain)
    {
        return $this->subdomain = $subdomain;
    }

    public function getSecretKey()
    {
        return $this->secretKey;
    }
    public function setSecretKey($secretKey)
    {
        return $this->secretKey = $secretKey;
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
