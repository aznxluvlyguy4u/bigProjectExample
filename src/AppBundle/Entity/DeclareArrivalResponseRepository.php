<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\DataFixtures\ORM\MockedDeclareArrivalResponse;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\DeclareArrivalResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\DeclareArrival;

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
    public function getArrivalResponseByMessageNumber(Client $client, $messageNumber)
    {
        $retrievedArrivals = $this->_em->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivals($client);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedArrivals, $messageNumber);
    }

    public function getArrivalsWithLastHistoryResponses(Client $client)
    {
        $retrievedArrivals = $this->_em->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivals($client);

        $results = new ArrayCollection();

        foreach($retrievedArrivals as $arrival) {

            $isHistoryRequestStateType = $arrival->getRequestState() == RequestStateType::OPEN ||
                                         $arrival->getRequestState() == RequestStateType::REVOKING ||
                                         $arrival->getRequestState() == RequestStateType::REVOKED ||
                                         $arrival->getRequestState() == RequestStateType::FINISHED;

            if($isHistoryRequestStateType) {
                $results->add(DeclareArrivalResponseOutput::createHistoryResponse($arrival));
            }
        }

        return $results;
    }

    public function getArrivalsWithLastErrorResponses(Client $client)
    {
        $retrievedArrivals = $this->_em->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivals($client);

        $results = array();

        foreach($retrievedArrivals as $arrival) {
            if($arrival->getRequestState() == RequestStateType::FAILED) {

                $lastResponse = Utils::returnLastResponse($arrival->getResponses());
                if($lastResponse != false) {
                    if($lastResponse->getIsRemovedByUser() != true) {
                        $results[] = DeclareArrivalResponseOutput::createErrorResponse($arrival);
                    }
                }
            }
        }

        return $results;
    }


}