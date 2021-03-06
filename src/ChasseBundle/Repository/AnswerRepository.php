<?php

namespace ChasseBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * AnswerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnswerRepository extends EntityRepository
{
    /* Autocompolete query */
    public function searchWords($word){
        $word = $word."%";
        $qb= $this->createQueryBuilder('a')
            ->select('a.word, a.id')
            ->where('a.word LIKE :word')
            ->groupBy('a.word')
            ->setParameter('word', $word)
            ->getQuery();
        return $qb->getResult();

    }

    /* Query for noidead button */
    public function searchRecommend($domain){
        $qb= $this->createQueryBuilder('a')
            ->select('a.word, a.id')
            ->where('a.domain LIKE :domain')
            ->setParameter('domain', $domain)
            ->getQuery();
        return $qb->getResult();

    }

    /* Query to get the 20 most used word in surveys*/
    public function mostUsed(){
        $qb= $this->createQueryBuilder('a')
            ->select('a.word, count(i) as nb')
            ->groupBy('a.word')
            ->innerJoin('a.interviews','i')
            ->orderBy('nb', 'DESC')
            ->setMaxResults(20)
            ->getQuery();
        return $qb->getResult();

    }


}

