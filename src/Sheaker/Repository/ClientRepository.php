<?php

namespace Sheaker\Repository;

use Doctrine\DBAL\Connection;
use Sheaker\Entity\Client;

/**
 * Client repository
 */
class ClientRepository implements RepositoryInterface
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Saves the client to the database.
     *
     * @param \Sheaker\Entity\Client $client
     */
    public function save($client)
    {
        $clientData = array(
            'name'        => $client->getName(),
            'subdomain'   => $client->getSubdomain(),
            'secret_key'  => $client->getSecretKey(),
            'created_at'  => $client->getCreatedAt()
        );

        if ($client->getId()) {
            $this->db->update('clients', $clientData, array('id' => $client->getId()));
        } else {
            $this->db->insert('clients', $clientData);
            $client->setId($this->db->lastInsertId());
        }
    }

    /**
     * Returns a client matching the supplied Id.
     *
     * @param integer $id
     *
     * @return \Sheaker\Entity\Client|false An entity object if found, false otherwise.
     */
    public function find($id)
    {
        $clientData = $this->db->fetchAssoc('
            SELECT *
            FROM clients c
            WHERE c.id = ?', array($id));
        return $clientData ? $this->buildClient($clientData) : FALSE;
    }

    /**
     * Returns a client matching the supplied subdomain.
     *
     * @param string $subdomain
     *
     * @return \Sheaker\Entity\Client|false An entity object if found, false otherwise.
     */
    public function findBySubdomain($subdomain)
    {
        $clientData = $this->db->fetchAssoc('
            SELECT *
            FROM clients c
            WHERE c.subdomain = ?', array($subdomain));
        return $clientData ? $this->buildClient($clientData) : FALSE;
    }

    /**
     * Returns a collection of clients.
     *
     * @param integer $limit
     *   The number of users to return.
     * @param integer $offset
     *   The number of users to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of clients, keyed by client id.
     */
    public function findAll($limit = 0, $offset = 0, $orderBy = array())
    {
        return $this->getClients(array(), $limit, $offset, $orderBy);
    }

    public function delete($id)
    {
    }

    public function getCount()
    {
    }

    /**
     * Returns a collection of clients.
     *
     * @param integer $limit
     *   The number of users to return.
     * @param integer $offset
     *   The number of users to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of clients, keyed by client id.
     */
    public function getClients($conditions, $limit = 0, $offset = 0, $orderBy = array())
    {
        // Provide a default orderBy.
        if (!$orderBy) {
            $orderBy = array('id' => 'ASC');
        }

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('c.*')
            ->from('clients', 'c');
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }
        $queryBuilder->orderBy('c.' . key($orderBy), current($orderBy));
        $parameters = array();
        foreach ($conditions as $key => $value) {
            $parameters[':' . $key] = $value;
            $where = $queryBuilder->expr()->eq('c.' . $key, ':' . $key);
            $queryBuilder->andWhere($where);
        }
        $queryBuilder->setParameters($parameters);
        $statement = $queryBuilder->execute();
        $clientsData = $statement->fetchAll();

        $clients = array();
        foreach ($clientsData as $clientData) {
            $clientId = $clientData['id'];
            $clients[$clientId] = $this->buildClient($clientData);
        }

        return $clients;
    }

    /**
     * Instantiates a client entity and sets its properties using db data.
     *
     * @param array $clientData
     *   The array of db data.
     *
     * @return \Sheaker\Entity\Client
     */
    protected function buildClient($clientData)
    {
        $client = new Client();
        $client->setId($clientData['id']);
        $client->setName($clientData['name']);
        $client->setSubdomain($clientData['subdomain']);
        $client->setSecretKey($clientData['secret_key']);
        $client->setCreatedAt($clientData['created_at']);

        return $client;
    }
}
