<?php

namespace Sheaker\Repository;

use Doctrine\DBAL\Connection;
use Sheaker\Entity\Checkin;

/**
 * Checkin repository
 */
class CheckinRepository implements RepositoryInterface
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * @var \Sheaker\Repository\UserRepository
     */
    protected $userRepository;

    public function __construct(Connection $db, $userRepository)
    {
        $this->db = $db;
        $this->userRepository = $userRepository;
    }

    /**
     * Saves the checkin to the database.
     *
     * @param \Sheaker\Entity\Checkin $checkin
     */
    public function save($checkin)
    {
        $checkinData = array(
            'user_id' => $checkin->getUser()->getId(),
        );

        $this->db->insert('users_checkin', $checkinData);
    }

    /**
     * Returns a checkin matching the supplied id.
     *
     * @param integer $id
     *
     * @return \Sheaker\Entity\Checkin|false An entity object if found, false otherwise.
     */
    public function find($id)
    {
        $checkinData = $this->db->fetchAssoc('
            SELECT *
            FROM users_checkin uc
            WHERE id = ?', array($id));
        return $checkinData ? $this->buildCheckin($checkinData) : FALSE;
    }

    /**
     * Returns a collection of checkin.
     *
     * @param integer $limit
     *   The number of checkin to return.
     * @param integer $offset
     *   The number of checkin to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of checkin, keyed by ckeckin id.
     */
    public function findAll($limit = 0, $offset = 0, $orderBy = array(), $conditions = array())
    {
        return $this->getCheckin($conditions, $limit, $offset, $orderBy);
    }

    /**
     * Returns a collection of checkin.
     *
     * @param integer $limit
     *   The number of checkin to return.
     * @param integer $offset
     *   The number of checkin to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of checkin.
     */
    public function getCheckin($conditions, $limit = 0, $offset = 0, $orderBy = array())
    {
        // Provide a default orderBy.
        if (!$orderBy) {
            $orderBy = array('user_id' => 'ASC');
        }

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('users_checkin', 'uc');
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }
        $queryBuilder->orderBy('uc.' . key($orderBy), current($orderBy));
        $parameters = array();
        foreach ($conditions as $key => $value) {
            $parameters[':' . $key] = $value;
            $where = $queryBuilder->expr()->eq('uc.' . $key, ':' . $key);
            $queryBuilder->andWhere($where);
        }
        $queryBuilder->setParameters($parameters);
        $statement = $queryBuilder->execute();
        $checkinData = $statement->fetchAll();

        $checkins = [];
        foreach ($checkinData as $checkin) {
            array_push($checkins, $this->buildCheckin($checkin));
        }
        return $checkins;
    }

    /**
     * Instantiates a checkin entity and sets its properties using db data.
     *
     * @param array $checkinData
     *   The array of db data.
     *
     * @return \Sheaker\Entity\Checkin
     */
    protected function buildCheckin($checkinData)
    {
        $user = $this->userRepository->findById($checkinData['user_id']);

        $checkin = new Checkin();
        $checkin->setId($checkinData['id']);
        $checkin->setUser($user);
        $checkin->setCreatedAt(date('c', strtotime($checkinData['created_at'])));
        return $checkin;
    }
}
