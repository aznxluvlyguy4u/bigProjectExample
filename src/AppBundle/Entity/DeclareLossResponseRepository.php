<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\DataFixtures\ORM\MockedDeclareArrivalResponse;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\DeclareArrivalResponseOutput;
use AppBundle\Output\DeclareLossResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\DeclareLoss;

/**
 * Class DeclareLossResponseRepository
 * @package AppBundle\Entity
 */
class DeclareLossResponseRepository extends BaseRepository {


    /**
     * @param Location $location
     * @param $messageNumber
     * @return DeclareLossResponse|null
     */
    public function getLossResponseByMessageNumber(Location $location, $messageNumber)
    {
        $retrievedLosses = $this->_em->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->getLosses($location);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedLosses, $messageNumber);
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public function getLossesWithLastHistoryResponses(Location $location)
    {
        $retrievedLosses = $this->_em->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->getLosses($location);

        $results = new ArrayCollection();

        foreach($retrievedLosses as $loss) {

            $isHistoryRequestStateType = $loss->getRequestState() == RequestStateType::OPEN ||
                $loss->getRequestState() == RequestStateType::REVOKING ||
                $loss->getRequestState() == RequestStateType::REVOKED ||
                $loss->getRequestState() == RequestStateType::FINISHED ||
                $loss->getRequestState() == RequestStateType::FINISHED_WITH_WARNING;

            if($isHistoryRequestStateType) {
                $results->add(DeclareLossResponseOutput::createHistoryResponse($loss));
            }
        }

        return $results;
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getLossesWithLastErrorResponses(Location $location)
    {
        $retrievedLosses = $this->_em->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->getLosses($location);

        $results = array();

        foreach($retrievedLosses as $loss) {
            if($loss->getRequestState() == RequestStateType::FAILED) {

                $lastResponse = Utils::returnLastResponse($loss->getResponses());
                if($lastResponse != false) {
                    if($lastResponse->getIsRemovedByUser() != true) {
                        $results[] = DeclareLossResponseOutput::createErrorResponse($loss);
                    }
                }
            }
        }

        return $results;
    }


}