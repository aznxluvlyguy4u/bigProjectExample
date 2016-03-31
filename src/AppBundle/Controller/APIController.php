<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class APIController
 * @package AppBundle\Controller
 */
class APIController extends Controller
{
  /**
   * @var \JMS\Serializer\Serializer
   */
  private $serializer;

  /**
   * @var string
   */
  private $jsonNamespace  = 'json';

  /**
   * @var \AppBundle\Service\AWSQueueService
   */
  private $queueService;

  /**
   * @return \JMS\Serializer\Serializer
   */
  private function getSerializer()
  {
    if($this->serializer == null){
      $this->serializer = $this->get('jms_serializer');
    }

    return $this->serializer;
  }

  /**
   * @return \AppBundle\Service\AWSQueueService
   */
  protected function getQueueService(){
    if($this->queueService == null){
      $this->queueService = $this->get('app.aws.queueservice');
    }

    return $this->queueService;
  }

  /**
   * @param $object
   * @return mixed|string
   */
  protected function serializeToJSON($object)
  {
    return $this->getSerializer()->serialize($object, $this->jsonNamespace);
  }

  /**
   * @param $json
   * @param $entity
   * @return array|\JMS\Serializer\scalar|mixed|object
   */
  protected function deserializeToObject($json, $entity)
  {
    return $this->getSerializer()->deserialize($json, $entity, $this->jsonNamespace);
  }

  /**
   * Generate a psuedo random requestId of MAX length 20
   *
   * @return string
   */
  protected function getNewRequestId()
  {
    $maxLengthRequestId = 20;
    return join('', array_map(function($value) { return $value == 1 ? mt_rand(1, 9) :
      mt_rand(0, 9); }, range(1, $maxLengthRequestId)));
  }

  protected function getContentAsArray(Request $request){
    $content = $request->getContent();

    if(empty($content)){
      throw new BadRequestHttpException("Content is empty");
    }

    return new ArrayCollection(json_decode($content, true));
  }

  protected function getUserByToken(Request $request) {

    $user = $this->getDoctrine()->getRepository('AppBundle:Client')->findBy(['id' => 1]);

    if($user == null){


      $user = new Client();
      $user->setFirstName("Frank");
      $user->setLastName("de Boer");
      $user->setEmailAddress("frank@deboer.com");
      $user->setRelationNumberKeeper("9991111");

      $location = new Location();
      $location->setUbn("9999999");
      $user->addLocation($location);

      $user = $this->getDoctrine()->getRepository('AppBundle:Person')->persist($user);

    }

    return $user;
  }

}