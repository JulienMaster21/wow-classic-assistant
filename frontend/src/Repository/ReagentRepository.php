<?php

namespace App\Repository;

use App\Entity\Reagent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Reagent|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reagent|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reagent[]    findAll()
 * @method Reagent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReagentRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {

        parent::__construct($registry, Reagent::class);
    }

    /**
     * @param $id
     * @return array|mixed[]
     * @throws DBALException
     */
    public function findOneBuyPrices($id) {

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->executeQuery("SELECT reagent_vendor.vendor_id, reagent_vendor.buy_price FROM reagent " .
                                                "INNER JOIN reagent_vendor ON reagent.id = reagent_vendor.reagent_id " .
                                                "WHERE reagent.id = ?", [$id]);
        $buyPrices = $stmt->fetchAll();
        return $buyPrices;
    }
}
