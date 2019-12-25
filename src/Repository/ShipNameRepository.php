<?php

namespace App\Repository;

use App\Entity\ShipName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShipNameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShipName::class);
    }

    public function findAllShipNames(): array
    {
        $dql = <<<DQL
                SELECT s.myHangarName, s.shipMatrixName FROM App\Entity\ShipName s INDEX BY s.myHangarName
            DQL;

        $query = $this->_em->createQuery($dql);
        $query->enableResultCache(30);

        return $query->getArrayResult();
    }
}