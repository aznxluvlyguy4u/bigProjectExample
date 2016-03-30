<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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

}