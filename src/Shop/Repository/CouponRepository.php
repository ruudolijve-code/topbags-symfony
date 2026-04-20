<?php

namespace App\Shop\Repository;

use App\Shop\Entity\Coupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    public function findOneByCode(string $code): ?Coupon
    {
        return $this->findOneBy([
            'code' => mb_strtoupper(trim($code)),
        ]);
    }
}