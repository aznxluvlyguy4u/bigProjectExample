<?php

namespace AppBundle\Component;

use AppBundle\Service\Decrappifier;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Client as Client;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class RequestMessageBuilder
 * @package AppBundle\Component
 */
class RequestMessageBuilder
{
    const decrappify = true;
    const dontDecrappify = false;

    /**
     * @var ArrivalMessageBuilder
     */
    private $arrivalMessageBuilder;

    /**
     * @var Decrappifier
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct($em, $serializer)
    {
        $this->entityManager = $em;
        $this->serializer = $serializer;

        $this->arrivalMessageBuilder = new ArrivalMessageBuilder($em);
    }

    public function build($messageClassNameSpace, ArrayCollection $contentArray, Person $person)
    {
        $messageObject = null;
        $message = null;
        switch($messageClassNameSpace) {
            case 'DeclareArrival':
                $frontEndMessageObject = $this->serializer->denormalizeToObject($messageClassNameSpace, $contentArray, $this::decrappify);
                $messageObject = $this->arrivalMessageBuilder->buildMessage($frontEndMessageObject, $person);
                break;

            case " ";
                break;

            default:
                break;
        }

        return $messageObject;
    }
}