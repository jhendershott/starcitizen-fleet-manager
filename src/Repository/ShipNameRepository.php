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

    /**
     * @return ShipName[]
     */
    public function findAllMappingsWithPatternAndProviderId(): array
    {
        $dql = <<<DQL
                SELECT s FROM App\Entity\ShipName s WHERE s.myHangarNamePattern IS NOT NULL AND s.providerId IS NOT NULL
            DQL;

        $query = $this->_em->createQuery($dql);
        $query->enableResultCache(300);

        return $query->getResult();
    }
}
