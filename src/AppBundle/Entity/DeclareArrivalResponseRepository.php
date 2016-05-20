<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareArrivalResponseRepository
 * @package AppBundle\Entity
 */
class DeclareArrivalResponseRepository extends BaseRepository {


    /**
     * @param Client $client
     * @param $messageNumber
     * @return DeclareArrivalResponse|null
     */
    public function getArrivalResponseByMessageNumber($messageNumber)
    {
        return $this->getEntityManager()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY)->findOneBy(array("messageNumber"=>$messageNumber));
    }

    /**
     * @param Client $client
     * @param string $state
     * @return ArrayCollection
     */
    public function getArrivalResponses(Client $client, $state = null)
    {
        $location = $client->getCompanies()->get(0)->getLocations()->get(0);
        $retrievedArrivals = $location->getArrivals();

        $declareArrivalsResponse = new ArrayCollection();

        if($state == null) {
            foreach($retrievedArrivals as $arrival) {
                $responses = $arrival->getResponses();

                foreach($responses as $response) {
                    $declareArrivalsResponse->add($response);
                }
            }

        } else {
            foreach($retrievedArrivals as $arrival) {
                $responses = $arrival->getResponses();

                foreach($responses as $response) {
                    if($arrival->getRequestState() == $state) {
                        $declareArrivalsResponse->add($response);
                    }
                }
            }
        }

        return $declareArrivalsResponse;
    }

    /**
     * @param Client $client
     * @param string $requestId
     * @return DeclareArrivalResponse|null
     */
    public function getArrivalResponseById(Client $client, $requestId)
    {
        $arrivalResponsesResponse = $this->getArrivalsResponse($client);

        foreach($arrivalResponsesResponse as $arrivalResponse) {
            $foundRequestId = $arrivalResponse->getRequestId($requestId);
            if($foundRequestId == $requestId) {
                return $arrivalResponse;
            }
        }

        return null;
    }

}