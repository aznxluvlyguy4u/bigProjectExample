<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareBirthResponseOutput;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareBirthResponseRepository
 * @package AppBundle\Entity
 */
class DeclareBirthResponseRepository extends BaseRepository {

    /**
     * @param Client $client
     * @param $messageNumber
     * @return DeclareBirthResponse|null
     */
    public function getBirthResponseByMessageNumber(Client $client, $messageNumber)
    {
        $retrievedBirths = $this->_em->getRepository(Constant::DECLARE_BIRTH_REPOSITORY)->getBirths($client);

        return $this->getResponseMessageFromRequestsByMessageNumber($retrievedBirths, $messageNumber);
    }

    public function getBirthsWithLastHistoryResponses(Client $client, $animalRepository)
    {
        $retrievedBirths = $this->_em->getRepository(Constant::DECLARE_BIRTH_REPOSITORY)->getBirths($client);

        $results = new ArrayCollection();

        foreach($retrievedBirths as $birth) {

            $isHistoryRequestStateType = $birth->getRequestState() == RequestStateType::OPEN ||
                $birth->getRequestState() == RequestStateType::REVOKING ||
                $birth->getRequestState() == RequestStateType::REVOKED ||
                $birth->getRequestState() == RequestStateType::FINISHED;

            if($isHistoryRequestStateType) {
                $results->add(DeclareBirthResponseOutput::createHistoryResponse($birth, $animalRepository));
            }
        }

        return $results;
    }

    public function getBirthsWithLastErrorResponses(Client $client, $animalRepository)
    {
        $retrievedBirths = $this->_em->getRepository(Constant::DECLARE_BIRTH_REPOSITORY)->getBirths($client);

        $results = array();

        foreach($retrievedBirths as $birth) {
            if($birth->getRequestState() == RequestStateType::FAILED) {

                $lastResponse = Utils::returnLastResponse($birth->getResponses());
                if($lastResponse != false) {
                    if($lastResponse->getIsRemovedByUser() != true) {
                        $results[] = DeclareBirthResponseOutput::createErrorResponse($birth, $animalRepository);                    }
                }
            }
        }

        return $results;
    }

}