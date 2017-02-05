<?php

namespace ChasseBundle\Repository;

use Doctrine\ORM\EntityRepository;
use ChasseBundle\Entity\Job;

/**
 * InterviewRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InterviewRepository extends EntityRepository
{
    public function getCountUsers() { //function that count number of distinct entry in the table user (how many user hav at least answered to one job)
         $qb = $this->createQueryBuilder('i')
            ->select('count(DISTINCT i.user)')
            ->getQuery();

             return $qb->getSingleScalarResult();
    }

    public function getCountJobs() { //function that count the number of distinct entry for 'name' in the table (how many jobs has been answered)
        $qb = $this->createQueryBuilder('i')
            ->select('count(DISTINCT i.job)')
            ->getQuery();

        return $qb->getSingleScalarResult();
    }

    public function getCountDomains()
    { //function that count number of distinct entry for' domain' in the table (how many domains has been answered)
        $qb = $this->createQueryBuilder('i')
            ->select('count(DISTINCT j.domain)')
            ->innerJoin('i.job', 'j')
            ->getQuery();

        return $qb->getSingleScalarResult();
    }

    public function get20jobs() { // function that returns the 20 most asnwered jobs
        $qb = $this->createQueryBuilder('i')
            ->select('i.id as id', 'j.name as name', 'count(i.id) as total')
            //->innerJoin( 'i', 'Job', 'j', 'j.id = i.job')
            ->innerJoin( 'i.job', 'j')
            ->groupBy('i.job')
            ->orderBy('total', 'DESC')
            ->setMaxResults(20)
            ->getQuery();

        return $qb->getResult();

    }

    public function get20jobsByF() { // function that returns the 20 most asnwered jobs
        $qb = $this->createQueryBuilder('i')
            ->select('i.id as id', 'j.name as name', 'count(i.id) as total')
            ->innerJoin( 'i.job', 'j')
            ->innerJoin('i.user', 'u')
            ->where('u.gender = :data')
            ->setParameter('data', 'F')
            ->groupBy('i.job')
            ->orderBy('total', 'DESC')
            ->setMaxResults(20)
            ->getQuery();

        return $qb->getResult();

    }

    public function get20domains() { //  function that returns the 20 most asnwered domains
        $qb = $this->createQueryBuilder('i')
            ->select('i', 'j.domain as domain', 'count(i.id) as total')
            ->innerJoin( 'i.job', 'j')
            ->groupBy('j.domain')
            ->orderBy('total', 'DESC')
            ->setMaxResults(20)
            ->getQuery();

        return $qb->getResult();

    }

    public function checkVote($id){
        $qb = $this->createQueryBuilder('i')
            ->select('count(i)')
            ->innerJoin('i.user', 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery();

        return $qb->getSingleScalarResult();

    }

    public function getJobsDone($user)
    {
        $qb = $this->createQueryBuilder('i')
            ->select('j.id', 'j.name')
            ->where('u.id = :user')
            ->innerJoin('i.user', 'u')
            ->innerJoin('i.job', 'j')
            ->setParameter('user', $user)
            ->getQuery();
        return $qb->getResult();
    }

    public function getSelectedUsers() {
        $qb = $this->createQueryBuilder('i')
            ->select('u.firstname, u.lastname, u.email')
            ->innerJoin('i.user', 'u')
            ->groupBy('u.username')
            ->having('count(u.username) >= 3')
            ->getQuery();

        return $qb->getScalarResult();
    }



}
