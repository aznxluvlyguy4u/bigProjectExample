<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareDepartResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareArrivalResponseRepository
 * @package AppBundle\Entity
 */
class DeclareDepartResponseRepository extends BaseRepository {

    /**
     * @param Client $client
     * @param $messageNumber
     * @return DeclareDepartResponse|null
     */
    public function getDepartResponseByMessageNumber(Client $client, $messageNumber)
    {
        $retrievedDepartures = $this->_em->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->getDepartures($client);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedDepartures, $messageNumber);
    }

    public function getDeparturesWithLastHistoryResponses(Client $client)
    {
        $retrievedDepartures = $this->_em->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->getDepartures($client);

        $results = new ArrayCollection();

        foreach($retrievedDepartures as $depart) {

            $isHistoryRequestStateType = $depart->getRequestState() == RequestStateType::OPEN ||
                $depart->getRequestState() == RequestStateType::REVOKING ||
                $depart->getRequestState() == RequestStateType::REVOKED ||
                $depart->getRequestState() == RequestStateType::FINISHED;

            if($isHistoryRequestStateType) {
                $results->add(DeclareDepartResponseOutput::createHistoryResponse($depart));
            }
        }

        return $results;
    }

    public function getDeparturesWithLastErrorResponses(Client $client)
    {
        $retrievedDepartures = $this->_em->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->getDepartures($client);

        $results = array();

        foreach($retrievedDepartures as $depart) {
            if($depart->getRequestState() == RequestStateType::FAILED) {

                $lastResponse = Utils::returnLastResponse($depart->getResponses());
                if($lastResponse != false) {
                    if($lastResponse->getIsRemovedByUser() != true) {
                        $results[] = DeclareDepartResponseOutput::createErrorResponse($depart);
                    }
                }
            }
        }

        return $results;
    }

}