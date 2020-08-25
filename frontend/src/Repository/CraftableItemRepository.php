<?php

namespace App\Repository;

use App\Entity\CraftableItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CraftableItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CraftableItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CraftableItem[]    findAll()
 * @method CraftableItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CraftableItemRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {

        parent::__construct($registry, CraftableItem::class);
    }
}
