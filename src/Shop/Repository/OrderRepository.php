<?php

namespace App\Shop\Repository;

use App\Account\Entity\CustomerUser;
use App\Shop\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findOneByOrderNumber(string $orderNumber): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.orderNumber = :number')
            ->setParameter('number', $orderNumber)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Order[]
     */
    public function findByCustomerUser(CustomerUser $customerUser): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.customerUser = :customerUser')
            ->setParameter('customerUser', $customerUser)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByOrderNumberForCustomer(
        string $orderNumber,
        CustomerUser $customerUser
    ): ?Order {
        return $this->createQueryBuilder('o')
            ->andWhere('o.orderNumber = :orderNumber')
            ->andWhere('o.customerUser = :customerUser')
            ->setParameter('orderNumber', $orderNumber)
            ->setParameter('customerUser', $customerUser)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}