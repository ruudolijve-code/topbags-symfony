<?php

namespace App\Catalog\Service;

use App\Catalog\Entity\ProductVariant;
use App\Catalog\Entity\StockMovement;
use Doctrine\ORM\EntityManagerInterface;

class StockMovementService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Log een voorraadmutatie
     */
    public function log(
        ProductVariant $variant,
        int $quantityChange,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
        bool $flush = false
    ): void {
        $movement = new StockMovement();

        $movement
            ->setVariant($variant)
            ->setQuantityChange($quantityChange)
            ->setType($type)
            ->setReferenceType($referenceType)
            ->setReferenceId($referenceId)
            ->setNote($note);

        $this->em->persist($movement);

        if ($flush) {
            $this->em->flush();
        }
    }
}