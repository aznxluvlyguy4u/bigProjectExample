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

                $animal = $arrival->getAnimal();
                if($animal != null) {
                    $ulnCountryCode = $animal->getUlnCountryCode();
                    $ulnNumber = $animal->getUlnNumber();
                    $pedigreeCountryCode = $animal->getPedigreeCountryCode();
                    $pedigreeNumber = $animal->getPedigreeNumber();
                    $isImportAnimal = $animal->getIsImportAnimal();
                } else {
                    $ulnCountryCode = null;
                    $ulnNumber = null;
                    $pedigreeCountryCode = null;
                    $pedigreeNumber = null;
                    $isImportAnimal = null;
                }

                $res = array("request_id" => $arrival->getRequestId(),
                    "log_datum" => $arrival->getLogDate(),
                    "uln_country_code" => $ulnCountryCode,
                    "uln_number" => $ulnNumber,
                    "pedigree_country_code" => $pedigreeCountryCode,
                    "pedigree_number" => $pedigreeNumber,
                    "arrival_date" => $arrival->getArrivalDate(),
                    "is_import_animal" => $isImportAnimal,
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

                $animal = $arrival->getAnimal();
                if($animal != null) {
                    $ulnCountryCode = $animal->getUlnCountryCode();
                    $ulnNumber = $animal->getUlnNumber();
                    $pedigreeCountryCode = $animal->getPedigreeCountryCode();
                    $pedigreeNumber = $animal->getPedigreeNumber();
                    $isImportAnimal = $animal->getIsImportAnimal();
                } else {
                    $ulnCountryCode = null;
                    $ulnNumber = null;
                    $pedigreeCountryCode = null;
                    $pedigreeNumber = null;
                    $isImportAnimal = null;
                }

                $res = array("request_id" => $arrival->getRequestId(),
                    "log_datum" => $arrival->getLogDate(),
                    "uln_country_code" => $ulnCountryCode,
                    "uln_number" => $ulnNumber,
                    "pedigree_country_code" => $pedigreeCountryCode,
                    "pedigree_number" => $pedigreeNumber,
                    "ubn_previous_owner" => $arrival->getUbnPreviousOwner(),
                    "is_import_animal" => $isImportAnimal,
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