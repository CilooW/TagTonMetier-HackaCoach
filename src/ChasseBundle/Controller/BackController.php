<?php

namespace ChasseBundle\Controller;

use ChasseBundle\ChasseBundle;
use ChasseBundle\Entity\User;
use ChasseBundle\Repository\UserRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;


class BackController extends Controller
{
    public function statsAction()
    {
        //nb of registered users
        $userManager = $this->container->get('fos_user.user_manager');
        $users = count($userManager->findUsers());

        //nb of user who answered at least one job
        $activeUsers = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->getCountUsers();

        //nb of aswered jobs
        $nbjobs = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->getCountJobs();

        //nb of answered domains
        $nbDomains = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->getCountDomains();

        //  20 most answered jobs
        $mostAnsweredJobs = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobs();

        // 20 most answered domains
        $mostAnsweredDomains = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20domains();

        // selected persons
        $selectedPersons = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->getSelectedUsers();
        $eligibleuser = count($selectedPersons);


        // 20 most answered words
        $words = $this->getDoctrine()->getRepository('ChasseBundle:Answer')->mostUsed();


        return $this->render('Back/stats.html.twig', array(
            "totalusers"    =>      $users,
            "activeusers"   =>      $activeUsers,
            "nbjobs"        =>      $nbjobs,
            "nbdomains"     =>      $nbDomains,
            "mostAnsweredJobs" =>   $mostAnsweredJobs,
            "mostAnsweredDomains" => $mostAnsweredDomains,
            "words" => $words,
            "eligibleuser" => $eligibleuser,
        ));
    }

    public function newStatsAction() {
        //  20 most answered jobs by gender F
        $jobsByF = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByF();

        //  20 most answered jobs by gender H
        $jobsByH = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByH();

        //  20 most answered jobs by Collégien
        $jobsByCol = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByCol();

        //  20 most answered jobs by Lycéen
        $jobsByLyc = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByLyc();

        //  20 most answered jobs by Etudiant
        $jobsByEtu = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByEtu();

        //  20 most answered jobs by Parent
        $jobsByPar = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByPar();

        //  20 most answered jobs by Demandeur d'emploi
        $jobsByDem = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByDem();

        //  20 most answered jobs by Adulte en réorientation
        $jobsByAdu = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByAdu();

        //  20 most answered jobs by Professionnel de l'orientation et de la formation
        $jobsByPro = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByPro();

        //  20 most answered jobs by Salarié
        $jobsBySal = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsBySal();

        //  20 most answered jobs by Autre
        $jobsByAut = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->get20jobsByAut();

        return $this->render('Back/newstats.html.twig', array(
            "jobsByF"    =>      $jobsByF,
            "jobsByH"    =>      $jobsByH,
            "jobsByCol"  =>      $jobsByCol,
            "jobsByLyc"  =>      $jobsByLyc,
            "jobsByEtu"  =>      $jobsByEtu,
            "jobsByPar"  =>      $jobsByPar,
            "jobsByDem"  =>      $jobsByDem,
            "jobsByAdu"  =>      $jobsByAdu,
            "jobsByPro"  =>      $jobsByPro,
            "jobsBySal"  =>      $jobsBySal,
            "jobsByAut"  =>      $jobsByAut,
        ));
    }

    public function userStatsAction ($page = 1) {
        //paginator
        $start = ($page-1) * UserRepository::MAX_RESULT;
        $subscribers = $this->getDoctrine()->getRepository('ChasseBundle:User')->getSubscribers($start);
        $total = count($subscribers);
        $maxPage = ceil($total/UserRepository::MAX_RESULT);

        //nb of registered users
        $userManager = $this->container->get('fos_user.user_manager');
        $users = count($userManager->findUsers());

        //list of email who subscribed newsletter
        //$subscribers = $this->getDoctrine()->getRepository('ChasseBundle:User')->getSubscribers();

        //nb of male/female users
        $genders = $this->getDoctrine()->getRepository('ChasseBundle:User')->countGender();

        //classment of the most registered status among users (student, employee, etc.)
        $statuses = $this->getDoctrine()->getRepository('ChasseBundle:User')->getMostRegStatus();

        //classment by age category
        $agecategory = [];
        $agecategory[] = ["-16 ans", $this->getDoctrine()->getRepository('ChasseBundle:User')->getAgeCategories(0,16)];
        $agecategory[] = ["17-20 ans", $this->getDoctrine()->getRepository('ChasseBundle:User')->getAgeCategories(17,20)];
        $agecategory[] = ["21-25 ans", $this->getDoctrine()->getRepository('ChasseBundle:User')->getAgeCategories(21,25)];
        $agecategory[] = ["26-35 ans", $this->getDoctrine()->getRepository('ChasseBundle:User')->getAgeCategories(26,35)];
        $agecategory[] = ["36-45 ans", $this->getDoctrine()->getRepository('ChasseBundle:User')->getAgeCategories(36,45)];
        $agecategory[] = ["46+ ans", $this->getDoctrine()->getRepository('ChasseBundle:User')->getAgeCategories(46, 100)];

        //classment of most registered departments
        $departments = $this->getDoctrine()->getRepository('ChasseBundle:User')->getMostRegDepartment();

        return $this->render('Back/userstats.html.twig', array(
            "genders" => $genders,
            "statuses" => $statuses,
            "agecategory" => $agecategory,
            "departments" => $departments,
            "subscribers" => $subscribers,
            'page'        => $page,
            'maxPage'     => $maxPage,
            'total'       => $total,
            "totalusers"  => $users,
        ));
    }

    //ACTION TO GENERATE A CSV FILE FROM USERS
    public function generateCsvAction() {

            $em = $this->getDoctrine()->getEntityManager();

            $results = $em->getRepository('ChasseBundle:User')->getSubscribers(1, false);
            $date = new DateTime();
            $strdate = $date->format('d-m-Y');
            $filename = 'csv/inscrits-newsletter-'.$strdate.'.csv';
            $handle = fopen($filename, 'w+');
            $header = array();
            fputcsv($handle, ['firstname', 'lastname', 'email'], ';');

            foreach ($results as $user) {
            fputcsv($handle,[$user->getFirstname(),$user->getLastname(), $user->getEmail()],';');
            }

            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            return new Response($content, 200, array(
                'Content-Type' => 'application/force-download',
                'Content-Disposition' => 'attachment; filename="inscrits-newsletter-26-01-2017.csv"'
            ));
    }

    public function winnerAction() {

        // selected persons
        $selectedPersons = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->getSelectedUsers();

        //get the winner
        $randArrayInt = random_int(0, count($selectedPersons)-1);
        $winner = $selectedPersons[$randArrayInt];


        return $this->render('Back/winner.html.twig', array(
            "winner"          =>    $winner,
        ));
    }
}
