<?php

namespace App\Repository;

use App\Entity\PlayableClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PlayableClass|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlayableClass|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlayableClass[]    findAll()
 * @method PlayableClass[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayableClassRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {

        parent::__construct($registry, PlayableClass::class);
    }
}
