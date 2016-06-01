<?php

namespace AppBundle\Entity;
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
     * @param Client $client
     * @param $messageNumber
     * @return DeclareLossResponse|null
     */
    public function getLossResponseByMessageNumber(Client $client, $messageNumber)
    {
        $retrievedLosses = $this->_em->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->getLosses($client);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedLosses, $messageNumber);
    }

    public function getLossesWithLastHistoryResponses(Client $client)
    {
        $retrievedLosses = $this->_em->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->getLosses($client);

        $results = new ArrayCollection();

        foreach($retrievedLosses as $loss) {

            $isHistoryRequestStateType = $loss->getRequestState() == RequestStateType::OPEN ||
                $loss->getRequestState() == RequestStateType::REVOKING ||
                $loss->getRequestState() == RequestStateType::REVOKED ||
                $loss->getRequestState() == RequestStateType::FINISHED;

            if($isHistoryRequestStateType) {
                $results->add(DeclareLossResponseOutput::createHistoryResponse($loss));
            }
        }

        return $results;
    }

    public function getLossesWithLastErrorResponses(Client $client)
    {
        $retrievedLosses = $this->_em->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->getLosses($client);

        $results = new ArrayCollection();

        foreach($retrievedLosses as $loss) {
            if($loss->getRequestState() == RequestStateType::FAILED) {
                $results->add(DeclareLossResponseOutput::createErrorResponse($loss));
            }
        }

        return $results;
    }


}