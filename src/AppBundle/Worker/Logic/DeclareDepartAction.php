<?php

namespace AppBundle\Worker\Logic;

use AppBundle\Entity\AnimalResidenceRepository;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\DoctrineUtil;
use \Doctrine\Common\Persistence\ObjectManager;
use \AppBundle\Entity\AnimalResidence;
use \AppBundle\Entity\DeclareDepart;
use \AppBundle\Entity\DeclareDepartResponse;
use \AppBundle\Entity\DeclareDepartRepository;
use \AppBundle\Entity\LocationRepository;
use \AppBundle\Constant\JsonInputConstant;
use \AppBundle\Util\TimeUtil;
use \AppBundle\Util\IRUtil;
use \AppBundle\Enumerator\InternalWorkerResponse;
use \AppBundle\Enumerator\AnimalTransferStatus;

class DeclareDepartAction
{    
    /** @var ObjectManager */
    private $em;

    /** @var DeclareDepartRepository */
    private $departRepository;

    /** @var LocationRepository */
    private $locationRepository;

    /** @var AnimalResidenceRepository */
    private $animalResidenceRepository;

    /** @var boolean */
    private $isSkipProcessedDeclares;

    public function __construct(ObjectManager $em, $isSkipProcessedDeclares = true)
    {
        $this->em = $em;
        $this->departRepository = $em->getRepository(DeclareDepart::class);
        $this->locationRepository = $em->getRepository(Location::class);
        $this->animalResidenceRepository = $em->getRepository(AnimalResidence::class);

        $this->isSkipProcessedDeclares = $isSkipProcessedDeclares;
    }


    /**
     * Processes a DeclareDepartResponse array
     * 
     * @param array $departResponseArray
     * @param bool $isFlushAtEnd
     * @return string
     */
    public function save($departResponseArray, $isFlushAtEnd = true)
    {
        $requestId = $departResponseArray[JsonInputConstant::REQUEST_ID];

        /** @var DeclareDepart $depart */
        $depart = $this->departRepository->findOneByRequestId($requestId);
        if ($depart != null) {

            if($this->isSkipProcessedDeclares) {
                if(IRUtil::isProcessedRequest($depart->getRequestState())) {
                    return InternalWorkerResponse::ALREADY_FINISHED;
                }
            }

            // Set basic values
            $departResponse = $this->createDeclareDepartResponseFromArray($departResponseArray);
            $departResponse->setUlnCountryCode($depart->getUlnCountryCode());
            $departResponse->setUlnNumber($depart->getUlnNumber());
            $departResponse->setDepartDate($depart->getDepartDate());
            $departResponse->setLogDate($depart->getLogDate());
            $departResponse->setReasonOfDepart($depart->getReasonOfDepart());
            $departResponse->setUbnNewOwner($depart->getUbnNewOwner());
            $departResponse->setActionBy($depart->getActionBy());
            $departResponse->setIsDepartedAnimal($depart->getIsDepartedAnimal());

            $errorKindIndicator = $departResponseArray[JsonInputConstant::ERROR_KIND_INDICATOR];
            $successIndicator = $departResponseArray[JsonInputConstant::SUCCESS_INDICATOR];
            $requestState = IRUtil::getRequestState($successIndicator, $errorKindIndicator, $depart->getRequestState());

            //Note that only Animals already in the liveStock of the Client can by departed
            //So the null check for animal is already during the request
            $animal = $depart->getAnimal();

            $currentLocation = $depart->getLocation();
            $newLocation = $this->locationRepository->findOneByActiveUbn($depart->getUbnNewOwner());
            
            //Note that there is already a null check in Utils::setResidenceToPending(...)
            /** @var AnimalResidence $currentResidence */
            $currentResidence = $this->animalResidenceRepository->getLastOpenResidenceOnLocation($currentLocation, $animal);

            if(IRUtil::isSuccessResponse($successIndicator)) {
                //Request Succeeded
                $internalWorkerResponse = InternalWorkerResponse::SUCCESS_RESPONSE;
                $depart->setRequestState($requestState);

                $animal->setIsExportAnimal(false);
                $animal->setLocation(null);
                $animal->setTransferState(AnimalTransferStatus::TRANSFERRED);
                $currentLocation->removeAnimal($animal);
                $this->em->persist($currentLocation);

                if($currentResidence != null) {
                    $currentResidence->setIsPending(false);
                    $currentResidence->setEndDate($depart->getDepartDate());
                    $this->em->persist($currentResidence);
                }

                //Find new residence in case the DeclareArrival has already been done
                $newResidence = $this->animalResidenceRepository->getLastOpenResidenceOnLocation($newLocation, $animal);

                //DeclareArrival is done and succeeded, finalize transaction
                if($newResidence != null) {
                    $animal->setLocation($newResidence->getLocation());
                    $animal->setTransferState(null);
                    $newResidence->setIsPending(false);
                    $this->em->persist($newResidence);
                }

            } else {
                //Request Failed
                $internalWorkerResponse = InternalWorkerResponse::FAILED_RESPONSE;
                $depart->setRequestState($requestState);

                //Reset animal state before the request
                $animal->setTransferState(null);

                //Reset AnimalResidence pending state
                if($currentResidence != null) {
                    if($currentResidence->getEndDate() == null && $currentResidence->getIsPending() == true) {
                        $currentResidence->setIsPending(false);
                        $this->em->persist($currentResidence);
                    }
                }
            }

            $departResponse->setDeclareDepartRequestMessage($depart);
            $depart->addResponse($departResponse);

            $this->em->persist($departResponse);
            $this->em->persist($depart);
            $this->em->persist($animal);

        } else {
            $missingDeclares[] = $requestId;
            $internalWorkerResponse = InternalWorkerResponse::MISSING_DECLARE;
        }
        if($isFlushAtEnd) {
            DoctrineUtil::flushClearAndGarbageCollect($this->em);
        }
        
        return $internalWorkerResponse;
    }


    /**
     * @param array $departResponseArray
     * @return DeclareDepartResponse
     */
    private function createDeclareDepartResponseFromArray($departResponseArray)
    {
        $departResponse = new DeclareDepartResponse();

        $requestId = $departResponseArray[JsonInputConstant::REQUEST_ID];
        $messageNumber = $departResponseArray[JsonInputConstant::MESSAGE_NUMBER];
        $logDate = TimeUtil::getDateTimeFromAwsSqsTimestamp($departResponseArray[JsonInputConstant::LOG_DATE]);
        $errorCode = $departResponseArray[JsonInputConstant::ERROR_CODE];
        $errorMessage = $departResponseArray[JsonInputConstant::ERROR_MESSAGE];
        $errorKindIndicator = $departResponseArray[JsonInputConstant::ERROR_KIND_INDICATOR];
        $successIndicator = $departResponseArray[JsonInputConstant::SUCCESS_INDICATOR];
        $departDate = TimeUtil::getDateTimeFromAwsSqsTimestamp($departResponseArray[JsonInputConstant::DEPART_DATE]);
        $ulnNumber = $departResponseArray[JsonInputConstant::ULN_NUMBER];
        $ulnCountryCode = $departResponseArray[JsonInputConstant::ULN_COUNTRY_CODE];
        $pedigreeCountryCode = $departResponseArray[JsonInputConstant::PEDIGREE_COUNTRY_CODE];
        $pedigreeNumber = $departResponseArray[JsonInputConstant::PEDIGREE_NUMBER];
        $ubnNewOwner = $departResponseArray[JsonInputConstant::UBN_NEW_OWNER];
        $reasonOfDeparture = $departResponseArray[JsonInputConstant::REASON_OF_DEPARTURE];
        $isExportAnimal = $departResponseArray[JsonInputConstant::IS_EXPORT_ANIMAL];
        $isRemovedByUser = $departResponseArray[JsonInputConstant::IS_REMOVED_BY_USER];

        $departResponse->setRequestId($requestId);
        $departResponse->setMessageNumber($messageNumber);
        $departResponse->setLogDate($logDate);
        $departResponse->setErrorCode($errorCode);
        $departResponse->setErrorMessage($errorMessage);
        $departResponse->setErrorKindIndicator($errorKindIndicator);
        $departResponse->setSuccessIndicator($successIndicator);
        $departResponse->setDepartDate($departDate);
        $departResponse->setUlnCountryCode($ulnCountryCode);
        $departResponse->setUlnNumber($ulnNumber);
        $departResponse->setPedigreeCountryCode($pedigreeCountryCode);
        $departResponse->setPedigreeNumber($pedigreeNumber);
        $departResponse->setUbnNewOwner($ubnNewOwner);
        $departResponse->setReasonOfDepart($reasonOfDeparture);
        $departResponse->setIsExportAnimal($isExportAnimal);
        $departResponse->setIsRemovedByUser($isRemovedByUser);

        return $departResponse;
    }
}