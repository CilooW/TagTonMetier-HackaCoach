<?php
/**
 * Created by PhpStorm.
 * User: ciloo
 * Date: 28/11/16
 * Time: 19:06
 */

namespace ChasseBundle\DataFixtures\ORM;


use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use ChasseBundle\Entity\Answer;

class LoadAnswerData implements FixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $answers = ["fort", "habile", "humain", "social", "concentration", "conscienscieux"];

        for ($i = 0; $i < count($answers)-1; $i++) {
            $newAnswer = new Answer();
            $newAnswer->setWord($answers [$i]);

            $manager->persist($newAnswer);
            $manager->flush();
            $manager->clear();

            }

    }
}