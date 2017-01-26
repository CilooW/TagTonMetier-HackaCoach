<?php

namespace ChasseBundle\Controller;

use ChasseBundle\Entity\Interview;
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
             * Check if existing in cache, else get list of jobs in selected domain
             * @var $repository JobRepository
             */
            $domaincache = $domain."jobc";

            $cacheDriver = new \Doctrine\Common\Cache\ApcuCache();
            if($cacheDriver->contains($domaincache))
            {
                $joblist_simplified = $cacheDriver->fetch($domaincache);
            }
            else {
                /* Get all jobs in chosen domain */
                $joblist = $this->getDoctrine()->getRepository('ChasseBundle:Job')->getJobsName($domain);
                /* Treatment for use in array_diff */
                $joblist_simplified=[];
                foreach($joblist as $value){
                    $joblist_simplified[$value['id']]=$value['name'];
                }
                /* Cache result for futur use */
                $cacheDriver->save($domaincache, $joblist_simplified, 2629000);
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
            /* Creating cache id */
            $wordcache = $word."wca";
            $cacheDriver = new \Doctrine\Common\Cache\ApcuCache();

            /* If word is only 3 letters long, checking if cache available */
            if(strlen($word)==3){
                if($cacheDriver->contains($wordcache))
                {
                    $data = $cacheDriver->fetch($wordcache);
                    return new JsonResponse(array("data" => json_encode($data)));
                }
            }

            /* If no cache yet, searching words for autocomplete and caching it if only 3 characters */
            $data = $this->getDoctrine()->getRepository('ChasseBundle:Answer')->searchWords($word);
            if(strlen($word)==3){
                $cacheDriver->save($wordcache, $data, 2629000);
            }

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
            /* Create id for cache and check if already existing, return it in that case */
            $jobcache = $jobid."noid";

            $cacheDriver = new \Doctrine\Common\Cache\ApcuCache();
            if($cacheDriver->contains($jobcache))
            {
                $entity = $cacheDriver->fetch($jobcache);
                return new JsonResponse(array("data" => json_encode($entity)));
            }

            /* Get job's domain and query for list of suggested word for that domain */
            $domain =  $this->getDoctrine()->getRepository('ChasseBundle:Job')->find($jobid)->getDomain();
            $data = $this->getDoctrine()->getRepository('ChasseBundle:Answer')->searchRecommend($domain);

            /* Cache result for futur use */
            $cacheDriver->save($jobcache, $data, 2629000);

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

        /* Create cache id then check if already existing (should always be the case aside from first usage else, get list of all domains*/
        $permcache = "All_doms";

        $cacheDriver = new \Doctrine\Common\Cache\ApcuCache();
        if($cacheDriver->contains($permcache)){
            $dom = $cacheDriver->fetch($permcache);
        }
        else {
            $domains = $this->getDoctrine()->getRepository('ChasseBundle:Job')->getDomains();

            /* Format list before sending it in formtype */
            $dom = [];
            foreach($domains as $value){
                $dom[$value['domain']]=$value['domain'];
            }
            /* Cache result for futur use */
            $cacheDriver->save($permcache, $dom, 2629000);

        }

        $job = new Job();

        $form = $this->createForm('ChasseBundle\Form\JobType', $job, array('domains' => $dom));
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $jobchos = $job->getName();
            $jobchosen = $this->getDoctrine()->getRepository('ChasseBundle:Job')->find($jobchos);
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
}
