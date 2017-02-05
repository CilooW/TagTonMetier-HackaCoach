<?php

namespace ChasseBundle\Controller;

use ChasseBundle\ChasseBundle;
use ChasseBundle\Entity\Answer;
use ChasseBundle\Entity\Interview;
use ChasseBundle\Entity\InterviewAnswer;
use ChasseBundle\Entity\Job;
use ChasseBundle\Repository\JobRepository;
use ChasseBundle\Repository\AnswerRepository;
use ChasseBundle\Repository\InterviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ChasseBundle\Entity\User;


/**
 * Interview controller.
 *
 */
class InterviewController extends Controller implements OpeningController
{
    /**
     * Ajax request to display jobs once domain is chosen.
     * @param Request $request
     * @param $domain
     * @return JsonResponse
     */
    public function jobchooseAction(Request $request, $domain)
    {
        if ($request->isXmlHttpRequest()){

            /**
             * Get list of jobs in selected domain then treat if for use in array_diff
             * @var $repository JobRepository
             */
            $joblist = $this->getDoctrine()->getRepository('ChasseBundle:Job')->getJobsName($domain);
            $joblist_simplified=[];
            foreach($joblist as $value){
                $joblist_simplified[$value['id']]=$value['name'];
            }

            /**
             * Get list of jobs already answered by user then treat if for use in array_diff
             * @var $repository2 InterviewRepository
             */
            $user = $this->getUser();
            $jobdone = $this->getDoctrine()->getRepository('ChasseBundle:Interview')->getJobsDone($user);
            $jobdone_simplified = [];
            foreach($jobdone as $value){
                $jobdone_simplified[$value['id']]=$value['name'];
            }

            /**
             * Remove jobs already answered from lists of jobs in selected domain
             */
            $jobavailables = array_diff($joblist_simplified, $jobdone_simplified);

            $data = [];
            foreach($jobavailables as $key=>$value){
                $data[] = ['id'=>$key, 'name'=>$value];
            }

            return new JsonResponse(array("data" => json_encode($data)));
        } else {
            throw new HttpException('500', 'Invalid call');
        }
    }

    /**
     * Handling autocomplete request
     * @param Request $request
     * @param $word
     * @return JsonResponse
     */
    public function jobsearchAction(Request $request, $word)
    {
        if ($request->isXmlHttpRequest()){
            $data = $this->getDoctrine()->getRepository('ChasseBundle:Answer')->searchWords($word);
            return new JsonResponse(array("data" => json_encode($data)));
        } else {
            throw new HttpException('500', 'Invalid call');
        }
    }

    /**
     * Handling noidea button
     * @param Request $request
     * @param $jobid
     * @return JsonResponse
     */
    public function searchhelpAction(Request $request, $jobid)
    {
        if ($request->isXmlHttpRequest()){
            /* Get job domain */
            $domain =  $this->getDoctrine()->getRepository('ChasseBundle:Job')->find($jobid)->getDomain();
            /* Query for list of suggested word for that domain */
            $data = $this->getDoctrine()->getRepository('ChasseBundle:Answer')->searchRecommend($domain);
            return new JsonResponse(array("data" => json_encode($data)));
        } else {
            throw new HttpException('500', 'Invalid call');
        }
    }

    /**
     * Lists all domains.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function jobselectAction(Request $request){
        /* Get list of all domains */
        $domains = $this->getDoctrine()->getRepository('ChasseBundle:Job')->getDomains();
        $dom = [];

        /* Format list before sending it in formtype */
        foreach($domains as $value){
            $dom[$value['domain']]=$value['domain'];
        }
        $job = new Job();

        $form = $this->createForm('ChasseBundle\Form\JobType', $job, array('domains' => $dom));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $jobchos = $job->getName();
            $jobchosen = $this->getDoctrine()
                ->getRepository('ChasseBundle:Job')->find($jobchos);
            return $this->redirectToRoute('interview_new', array('job' => $jobchosen));

        }

        return $this->render('interview/jobselect.html.twig', array(
            'job' => $job,
            'form' => $form->createView(),
        ));
    }

    /**
     * Create form for user to select his keyword for the chosen job.
     *
     */
    public function newAction(Request $request, $job)
    {
        $interview = new Interview();
        /* Get user logged and job chosen before */
        $user = $this->getUser();
        $jobchosen = $this->getDoctrine()->getRepository('ChasseBundle:Job')->find($job);
        $jobname = $jobchosen->getName();

        /* Generate form and set data for user and job */
        $form = $this->createForm('ChasseBundle\Form\InterviewType', $interview);
        $form->get('user')->setData($user);
        $form->get('job')->setData($jobchosen);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($interview);
            $em->flush();

            return $this->redirectToRoute('votevalid');
        }

        return $this->render('interview/new.html.twig', array(
            'interview' => $interview,
            'form' => $form->createView(),
            'jobname' => $jobname,
        ));
    }

    public function voteValidAction()
    {
        $user = $this->getUser()->getId();

        $repository = $this->getDoctrine()->getRepository('ChasseBundle:User');
        $satisf = $repository->checkSatisf($user);

        if ($satisf != 0){

            $repository = $this->getDoctrine()->getRepository('ChasseBundle:Interview');
            $vote = $repository->checkVote($user);

            return $this->render('interview/votevalid.html.twig', array(
                'vote' => $vote));
        }
        else {
            return $this->redirectToRoute('user_edit', array(
                'id' => $user));
        }
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     */
    public function satisfactionAction(Request $request, User $user)
    {
        $repository = $this->getDoctrine()->getRepository('ChasseBundle:Interview');
        $vote = $repository->checkVote($user);

        $editForm = $this->createForm('ChasseBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('votevalid');
        }

        return $this->render('interview/satisfaction.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'vote' => $vote,
        ));
    }

    public function hackatonAction()
    {
        $em = $this->getDoctrine()->getManager();

        $answers = $em->getRepository('ChasseBundle:Answer')->findBy(array('word'=> 'Créativité')); //array avec toutes les réponses

        $jobs = array();
        /** @var Answer $answer */
        foreach ($answers as $answer) {
            $interviews = $answer->getInterviews();
            /** @var Interview $interview */
            foreach ($interviews as $interview) {
                $jobname = $interview->getJob()->getName();
                if(key_exists($jobname, $jobs)) {
                    $jobs[$jobname] += 1;
                }
                else {
                    $jobs[$jobname] = 1;
                }
            }
        }

        return $this->render('interview/hackaton.html.twig', array(
            'jobs' => $jobs,
        ));

    }

    public function dataAction()
    {
        // Récupération du path absolute et traitement pour récupérer le path vers input.csv
        $appPath = $this->get('kernel')->getRootDir();
        $appPath = explode("/",$appPath);
        array_pop($appPath);
        $dataPath = implode("/", $appPath). "/data.tsv";
        // Insertion de chaque ligne du fichier data dans un tableau
        $dataInArray = array();
        $inputcsv = fopen($dataPath, "r");
        while(!feof($inputcsv))
        {
            $line = fgets($inputcsv,1024);
            $dataInArray[] = $line;
        }
        fclose($inputcsv);
        var_dump($dataInArray);
        $arrayData = array();
        // Traitement de chaque ligne du tableau pour séparer l'utilisateur et la grille
        foreach ($dataInArray as $row) {
            $arrayRow = preg_split('/\s+/', $row);
            array_pop($arrayRow);
            if ($arrayRow) {
                var_dump($arrayRow);
                $key = $arrayRow[0];
                $value = $arrayRow[1];
                $arrayData[$key] = $value;
            }
        }
        var_dump($arrayData);
        $em = $this->getDoctrine()->getManager();
        // Insertion des données dans la bdd
        foreach ($arrayData as $key => $value){
            $grid = new InterviewAnswer();
            $grid->setInterviewId($key);
            $grid->setAnswerId($value);
            $em->persist($grid);
            unset($grid);
        }
        $em->flush();
        return $this->redirectToRoute('chasse_front');
    }
}
