<?php

namespace Sheaker\Repository;

use Doctrine\DBAL\Connection;
use Sheaker\Entity\Payment;

/**
 * Payment repository
 */
class PaymentRepository implements RepositoryInterface
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
     * Saves the payment to the database.
     *
     * @param \Sheaker\Entity\Payment $payment
     */
    public function save($payment)
    {
        $paymentData = array(
            'user_id'    => $payment->getUser()->getId(),
            'days'       => $payment->getDays(),
            'start_date' => $payment->getStartDate(),
            'end_date'   => $payment->getEndDate(),
            'comment'    => $payment->getComment(),
            'price'      => $payment->getPrice(),
            'method'     => $payment->getMethod(),
        );

        $this->db->insert('users_payments', $paymentData);
    }

    /**
     * Returns a payment matching the supplied id.
     *
     * @param integer $id
     *
     * @return \Sheaker\Entity\Payment|false An entity object if found, false otherwise.
     */
    public function find($id)
    {
        $paymentData = $this->db->fetchAssoc('
            SELECT *
            FROM users_payments up
            WHERE id = ?', array($id));
        return $paymentData ? $this->buildPayment($paymentData) : FALSE;
    }

    /**
     * Returns a collection of payments.
     *
     * @param integer $limit
     *   The number of payments to return.
     * @param integer $offset
     *   The number of payments to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of payments, keyed by payment id.
     */
    public function findAll($limit = 0, $offset = 0, $orderBy = array())
    {
        return $this->getPayments(array(), $limit, $offset, $orderBy);
    }

    /**
     * Returns a collection of payments.
     *
     * @param integer $userId
     *   The user Id
     * @param integer $limit
     *   The number of payments to return.
     * @param integer $offset
     *   The number of payments to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of payments, keyed by payment id.
     */
    public function findAllByUser($userId, $limit = 0, $offset = 0, $orderBy = array())
    {
        $conditions = array(
            'user_id' => $userId
        );

        return $this->getPayments($conditions, $limit, $offset, $orderBy);
    }

    public function delete($id)
    {
    }

    public function getCount()
    {
    }

    /**
     * Returns a collection of payments.
     *
     * @param integer $limit
     *   The number of payments to return.
     * @param integer $offset
     *   The number of payments to skip.
     * @param array $orderBy
     *   Optionally, the order by info, in the $column => $direction format.
     *
     * @return array A collection of payments.
     */
    public function getPayments($conditions, $limit = 0, $offset = 0, $orderBy = array())
    {
        // Provide a default orderBy.
        if (!$orderBy) {
            $orderBy = array('user_id' => 'ASC');
        }

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->select('up.*')
            ->from('users_payments', 'up');
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }
        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }
        $queryBuilder->orderBy('up.' . key($orderBy), current($orderBy));
        $parameters = array();
        foreach ($conditions as $key => $value) {
            $parameters[':' . $key] = $value;
            $where = $queryBuilder->expr()->eq('up.' . $key, ':' . $key);
            $queryBuilder->andWhere($where);
        }
        $queryBuilder->setParameters($parameters);
        $statement = $queryBuilder->execute();
        $paymentsData = $statement->fetchAll();

        $payments = [];
        foreach ($paymentsData as $paymentData) {
            array_push($payments, $this->buildPayment($paymentData));
        }
        return $payments;
    }

    /**
     * Instantiates a payment entity and sets its properties using db data.
     *
     * @param array $paymentData
     *   The array of db data.
     *
     * @return \Sheaker\Entity\Payment
     */
    protected function buildPayment($paymentData)
    {
        //$user = $this->userRepository->findById($paymentData['user_id']);

        $payment = new Payment();
        $payment->setId($paymentData['id']);
        $payment->setUser($paymentData['user_id']);
        $payment->setDays($paymentData['days']);
        $payment->setStartDate(date('c', strtotime($paymentData['start_date'])));
        $payment->setEndDate(date('c', strtotime($paymentData['end_date'])));
        $payment->setComment($paymentData['comment']);
        $payment->setPrice($paymentData['price']);
        $payment->setMethod($paymentData['method']);
        $payment->setPaymentDate(date('c', strtotime($paymentData['created_at'])));
        return $payment;
    }
}
