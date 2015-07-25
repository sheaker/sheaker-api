<?php

namespace Sheaker\Repository;

use Doctrine\DBAL\Connection;
use Sheaker\Entity\User;

/**
 * User repository
 */
class UserRepository implements RepositoryInterface
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
     * Saves the user to the database.
     *
     * @param \Sheaker\Entity\User $user
     */
    public function save($user)
    {
        $userData = array(
            'custom_id'        => $user->getCustomId(),
            'first_name'       => $user->getFirstName(),
            'last_name'        => $user->getLastName(),
            'password'         => $user->getPassword(),
            'phone'            => $user->getphone(),
            'mail'             => $user->getMail(),
            'birthdate'        => $user->getBirthdate(),
            'address_street_1' => $user->getAddressStreet1(),
            'address_street_2' => $user->getAddressStreet2(),
            'city'             => $user->getCity(),
            'zip'              => $user->getZip(),
            'gender'           => $user->getGender(),
            'photo'            => $user->getPhoto(),
            'sponsor_id'       => $user->getSponsor(),
            'comment'          => $user->getComment(),
            'last_seen'        => $user->getLastSeen(),
            'last_ip'          => $user->getLastIP(),
            'failed_logins'    => $user->getFailedLogins()
        );

        if ($user->getId()) {
            $this->db->update('users', $userData, array('id' => $user->getId()));

            if ($user->getUserLevel()) {
                $this->db->delete('users_access', array('user_id' => $user->getId()));
                $this->db->insert('users_access', array('user_id' => $user->getId(), 'user_level' => $user->getUserLevel()));
            }
        } else {
            $this->db->insert('users', $userData);
            $user->setId($this->db->lastInsertId());

            if ($user->getUserLevel()) {
                $this->db->insert('users_access', array('user_id' => $user->getId(), 'user_level' => $user->getUserLevel()));
            }
        }
    }

    /**
     * Returns a user matching the supplied Id.
     *
     * @param integer $id
     *
     * @return \Sheaker\Entity\User|false An entity object if found, false otherwise.
     */
    public function find($id)
    {
        // Use findById()
    }

    /**
     * Returns a user matching the supplied id.
     * The id is most use db side
     *
     * @param integer $id
     *
     * @return \Sheaker\Entity\User|false An entity object if found, false otherwise.
     */
    public function findById($id)
    {
        $userData = $this->db->fetchAssoc('
            SELECT *
            FROM users u
            WHERE u.id = ?', array($id));
        return $userData ? $this->buildUser($userData) : FALSE;
    }

    /**
     * Returns a user matching the supplied custom Id.
     *
     * @param integer $customId
     *
     * @return \Sheaker\Entity\User|false An entity object if found, false otherwise.
     */
    public function findByCustomId($customId)
    {
        $userData = $this->db->fetchAssoc('
            SELECT *
            FROM users u
            WHERE u.custom_id = ?', array($customId));
        return $userData ? $this->buildUser($userData) : FALSE;
    }

    /**
     * Returns a collection of users.
     *
     * @param integer $limit
     *   The number of users to return.
     * @param integer $offset
     *   The number of users to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of users, keyed by user id.
     */
    public function findAll($limit = 0, $offset = 0, $orderBy = array(), $conditions = array())
    {
        return $this->getUsers($conditions, $limit, $offset, $orderBy);
    }

    /**
     * Deletes the entity.
     *
     * @param integer $id
     */
    public function delete($id)
    {
        $this->db->delete('users', [ 'id' => $id ]);
    }

    /**
     * Returns a collection of users.
     *
     * @param integer $limit
     *   The number of users to return.
     * @param integer $offset
     *   The number of users to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of users, keyed by user id.
     */
    public function getUsers($conditions, $limit = 0, $offset = 0, $orderBy = array())
    {
        // Provide a default orderBy.
        if (!$orderBy) {
            $orderBy = array('id' => 'ASC');
        }

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('users', 'u')
            ->leftJoin('u', 'users_access', 'ua', 'u.id = ua.user_id');
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }
        $queryBuilder->orderBy('u.' . key($orderBy), current($orderBy));
        $parameters = array();
        foreach ($conditions as $key => $value) {
            $parameters[':' . $key] = $value;
            $where = $queryBuilder->expr()->eq('u.' . $key, ':' . $key);
            $queryBuilder->andWhere($where);
        }
        $queryBuilder->setParameters($parameters);
        $statement = $queryBuilder->execute();
        $usersData = $statement->fetchAll();

        $users = array();
        foreach ($usersData as $userData) {
            $userId = $userData['id'];
            $users[$userId] = $this->buildUser($userData);
        }

        return $users;
    }

    /**
     * Instantiates a user entity and sets its properties using db data.
     *
     * @param array $userData
     *   The array of db data.
     *
     * @return \Sheaker\Entity\User
     */
    protected function buildUser($userData)
    {
        $user = new User();
        $user->setId($userData['id']);
        $user->setCustomId($userData['custom_id']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->setPhone($userData['phone']);
        $user->setMail($userData['mail']);
        $user->setBirthdate($userData['birthdate']);
        $user->setAddressStreet1($userData['address_street_1']);
        $user->setAddressStreet2($userData['address_street_2']);
        $user->setCity($userData['city']);
        $user->setZip($userData['zip']);
        $user->setGender($userData['gender']);
        $user->setPhoto($userData['photo']);
        $user->setSponsor($userData['sponsor_id']);
        $user->setComment($userData['comment']);
        $user->setFailedLogins($userData['failed_logins']);
        $user->setLastSeen(date('c', strtotime($userData['last_seen'])));
        $user->setLastIP($userData['last_ip']);
        $user->setCreatedAt(date('c', strtotime($userData['created_at'])));
        $user->setUserLevel($userData['user_level']);

        return $user;
    }
}
