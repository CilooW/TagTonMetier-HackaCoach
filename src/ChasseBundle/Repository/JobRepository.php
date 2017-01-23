<?php

namespace ChasseBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * JobRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class JobRepository extends EntityRepository
{
    public function getJobsName($domain)
    {
        $qb= $this->createQueryBuilder('j')
            ->select(array('j.name', 'j.id'))
            ->where('j.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery();
        return $qb->getResult();
    }


    public function getDomains(){
        $qb= $this->createQueryBuilder('j')
            ->select('j.domain')
            ->distinct('true')
            ->getQuery();
        return $qb->getResult();

    }
}