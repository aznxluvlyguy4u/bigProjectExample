<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\DataFixtures\ORM\MockedDeclareArrivalResponse;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
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
    public function getArrivalResponseByMessageNumber($messageNumber)
    {
        return $this->getEntityManager()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY)->findOneBy(array("messageNumber"=>$messageNumber));
    }

    public function getArrivalsWithLastHistoryResponses(Client $client)
    {
        $retrievedArrivals = $this->_em->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivals($client);

        $results = new ArrayCollection();

        foreach($retrievedArrivals as $arrival) {

            $isHistoryRequestStateType = $arrival->getRequestState() == RequestStateType::OPEN ||
                                         $arrival->getRequestState() == RequestStateType::REVOKING ||
                                         $arrival->getRequestState() == RequestStateType::FINISHED;

            if($isHistoryRequestStateType) {

                $res = array("request_id" => $arrival->getRequestId(),
                    "log_datum" => $arrival->getLogDate(),
                    "uln_country_code" => $arrival->getUlnCountryCode(),
                    "uln_number" => $arrival->getUlnNumber(),
                    "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
                    "pedigree_number" => $arrival->getPedigreeNumber(),
                    "arrival_date" => $arrival->getArrivalDate(),
                    "is_import_animal" => $arrival->getIsImportAnimal(),
                    "ubn_previous_owner" => $arrival->getUbnPreviousOwner(),
                    "request_state" => $arrival->getRequestState()
                );

                $results->add($res);
            }
        }

        return $results;
    }

    public function getArrivalsWithLastErrorResponses(Client $client)
    {
        $retrievedArrivals = $this->_em->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivals($client);

        $results = new ArrayCollection();

        foreach($retrievedArrivals as $arrival) {

            if($arrival->getRequestState() == RequestStateType::FAILED) {
                $lastResponse = $arrival->getResponses()->last();

                $res = array("request_id" => $arrival->getRequestId(),
                    "log_datum" => $arrival->getLogDate(),
                    "uln_country_code" => $arrival->getUlnCountryCode(),
                    "uln_number" => $arrival->getUlnNumber(),
                    "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
                    "pedigree_number" => $arrival->getPedigreeNumber(),
                    "ubn_previous_owner" => $arrival->getUbnPreviousOwner(),
                    "is_import_animal" => $arrival->getIsImportAnimal(),
                    "request_state" => $arrival->getRequestState(),
                    "error_code" => $lastResponse->getErrorCode(),
                    "error_message" => $lastResponse->getErrorMessage()
                );

                $results->add($res);
            }
        }

        return $results;
    }


}