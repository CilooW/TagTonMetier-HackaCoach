<?php

namespace ChasseBundle\Controller;

use ChasseBundle\Entity\Answer;
use ChasseBundle\Entity\Interview;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;


class FrontController extends Controller
{
    public function howtoAction()
    {
        // somehow create a Response object, like by rendering a template
        $response = $this->render('Front/howto.html.twig', []);

        // cache for 3600 seconds
        $response->setSharedMaxAge(2629000);

        return $response;
    }

    public function legalmentionAction()
    {
        $response = $this->render('Front/legalmention.html.twig', array());

        $response->setSharedMaxAge(2629000);

        return $response;
    }

    public function learnmoreAction()
    {
        return $this->render('Front/learnmore.html.twig', array(// ...
        ));
    }

    public function countdownAction()
    {
        return $this->render('Front/countdown.html.twig', array(// ...
        ));
    }

    public function finishedAction(){
        $response = $this->render('Front/end.html.twig', array(// ...
        ));

        $response->setSharedMaxAge(2629000);

        return $response;
    }

    public function searchjobsAction(Request $request)
    {

        /* Generate form and set data for keyword */
        $form = $this->createForm('ChasseBundle\Form\AnswerType');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $wordchosen = $form->getData()['word'];
            $words = array();
            foreach ($wordchosen as $word) {
                $words[]= $word->getWord();
            }
            var_dump($words);
            $em = $this->getDoctrine()->getManager();
            $answers = $em->getRepository('ChasseBundle:Answer')->findBy(array('word' => $words)); //array avec toutes les réponses
            //var_dump($answers);
            $jobs = array();

            /** @var Answer $answer */
            foreach ($answers as $answer) {
                $interviews = $answer->getInterviews();
                /** @var Interview $interview */
                foreach ($interviews as $interview) {
                    $jobname = $interview->getJob()->getName();
                    if (key_exists($jobname, $jobs)) {
                        $jobs[$jobname] += 1;
                    } else {
                        $jobs[$jobname] = 1;
                    }
                }

            }

            return $this->render('interview/hackaton.html.twig', array(
                'jobs' => $jobs,
            ));
        }
        return $this->render('Front/searchjobs.html.twig', array(
            'form' => $form->createView(),
        ));


/*
            //récupérer le mot de l'utilisateur
            $wordchos = $answer->getWord();
            $wordchosen = $this->getDoctrine()
                ->getRepository('ChasseBundle:Answer')->find($wordchos);
            // return $this->redirectToRoute('hackaton', array('word' => $wordchosen));

            $response = $this->redirectToRoute('hackaton', array(
                'word'  => $wordchosen,
            ));

            return $response;
        } else {
            return $this->render('Front/searchjobs.html.twig', array(
                'answer' => $answer,
                'form' => $form->createView(),
            ));
        }
*/


    }


    /**
     * Handling autocomplete request
     * @param Request $request
     * @param $word
     * @return JsonResponse
     */
    public function wordsearchAction(Request $request, $word)
    {
        if ($request->isXmlHttpRequest()){
            $data = $this->getDoctrine()->getRepository('ChasseBundle:Answer')->searchWords($word);
            return new JsonResponse(array("data" => json_encode($data)));
        } else {
            throw new HttpException('500', 'Invalid call');
        }
    }
}
