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
     * @param Location $location
     * @param $messageNumber
     * @return DeclareDepartResponse|null
     */
    public function getDepartResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedDepartures = $this->_em->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->getDepartures($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedDepartures, $messageNumber);
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public function getDeparturesWithLastHistoryResponses(Location $location)
    {
        $retrievedDepartures = $this->_em->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->getDepartures($location);

        $results = new ArrayCollection();

        foreach($retrievedDepartures as $depart) {

            $isHistoryRequestStateType = $depart->getRequestState() == RequestStateType::OPEN ||
                $depart->getRequestState() == RequestStateType::REVOKING ||
                $depart->getRequestState() == RequestStateType::REVOKED ||
                $depart->getRequestState() == RequestStateType::FINISHED ||
                $depart->getRequestState() == RequestStateType::FINISHED_WITH_WARNING;

            if($isHistoryRequestStateType) {
                $results->add(DeclareDepartResponseOutput::createHistoryResponse($depart));
            }
        }

        return $results;
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getDeparturesWithLastErrorResponses(Location $location)
    {
        $retrievedDepartures = $this->_em->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->getDepartures($location);

        $results = array();

        foreach($retrievedDepartures as $depart) {
            if($depart->getRequestState() == RequestStateType::FAILED) {

                $lastResponse = Utils::returnLastResponse($depart->getResponses());
                if($lastResponse != false) {
                    $results[] = DeclareDepartResponseOutput::createErrorResponse($depart);
                }
            }
        }

        return $results;
    }

}