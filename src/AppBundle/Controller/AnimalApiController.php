<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1")
 */
class AnimalApiController extends Controller
{
    /**
     * @Route("/animal")
     * @Method("GET")
     */
    public function getIndexAction(Request $request)
    {
        $ss = $this->get('jms_serializer');

        $data = $this->getDoctrine()->getRepository('AppBundle:Animal')->findAll();

        $data = $ss->serialize($data, 'json');

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/animal/{id}")
     * @ParamConverter("animal", class="AppBundle:Animal")
     * @Method("GET")
     */
    public function getAnimalAction(Request $request, $animal)
    {
        $ss = $this->get('jms_serializer');
        $data = $ss->serialize($animal, 'json');

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/animal")
     * @Method("POST")
     */
    public function postAnimalAction(Request $request)
    {
        $ss = $this->get('jms_serializer');
        $vs = $this->get('validator');
        $eps = $this->get('api.error_parsing');
        $em = $this->getDoctrine()->getManager();

        $animal = $ss->deserialize($request->getContent(), 'AppBundle\Entity\Animal', 'json');
        $errors = $vs->validate($animal);
        $json_errors = $eps->parse($errors);

        if ($json_errors) {
            return new JsonResponse($json_errors);
        }

        $em->persist($animal);
        $em->flush();

        $animal = $ss->serialize($animal, 'json');

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($animal);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
