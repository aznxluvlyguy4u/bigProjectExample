<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/contacts")
 */
class ContactAPIController extends APIController {


  /**
   *
   * Create a Client
   *
   * @Route("")
   * @Method("POST")
   */
  public function postContactEmail(Request $request) {

    $content = $this->getContentAsArray($request);
    
    //Content format
    $email = $content->get('email');
    $category = $content->get('category');
    $mood = $content->get('mood');
    $message = $content->get('message');

    return new JsonResponse($content, 200);
  }



}