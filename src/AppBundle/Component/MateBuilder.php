<?php

namespace AppBundle\Component;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class MateBuilder
 * @package AppBundle\Component
 */
class MateBuilder extends NsfoBaseBuilder
{
    /**
     * @param ObjectManager $manager
     * @param ArrayCollection $content
     * @param Client $client
     * @param Person $loggedInUser
     * @param Location $location
     * @return \AppBundle\Entity\DeclareNsfoBase|Mate
     */
    public static function post(ObjectManager $manager, ArrayCollection $content, Client $client, Person $loggedInUser, Location $location)
    {
        $mate = new Mate();
        $mate = self::postBase($client, $loggedInUser, $location, $mate);
        $mate = self::setMateValues($manager, $content, $location, $mate);

        return $mate;
    }


    /**
     * @param ObjectManager $manager
     * @param ArrayCollection $content
     * @param Location $location
     * @param Mate $mate
     * @return Mate
     */
    private static function setMateValues(ObjectManager $manager, ArrayCollection $content, Location $location, Mate $mate)
    {
        /* Set the RequestState to OPEN since it needs to be approved by the third party */
        $mate->setRequestState(RequestStateType::OPEN);

        /* Set non-Animal values */
        
        $startDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::START_DATE, $content);
        $endDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::END_DATE, $content);
        $ki = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::KI, $content);
        $pmsg = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::PMSG, $content);

        $mate->setStartDate($startDate);
        $mate->setEndDate($endDate);
        $mate->setKi($ki);
        $mate->setPmsg($pmsg);

        $mate->setLocation($location);

        /* Set Animal values */

        $eweArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWE, $content);
        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $manager->getRepository(Animal::class);

        /** @var Ewe $ewe */
        $ewe = $animalRepository->findAnimalByAnimalArray($eweArray);
        $mate->setStudEwe($ewe);

        $ram = $animalRepository->findAnimalByAnimalArray($ramArray);

        if($ram instanceof Animal) {
            //Get uln from animal, incase a pedigreeCode was given in the json instead of a uln
            $mate->setRamUlnCountryCode($ram->getUlnCountryCode());
            $mate->setRamUlnNumber($ram->getUlnNumber());

        } else {
            //Animal does not exist in database, so input must have been a uln
            $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $ramArray);
            $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $ramArray);
            $mate->setRamUlnCountryCode($ulnCountryCode);
            $mate->setRamUlnNumber($ulnNumber);
        }

        //Only set the ram on Mate if it actually is a Ram entity
        if($ram instanceof Ram) {
            $mate->setStudRam($ram);
        }
        
        return $mate;
    }


    /**
     * @param Mate $mate
     * @param Person $loggedInUser
     * @return Mate
     */
    public static function approveMateDeclaration(Mate $mate, Person $loggedInUser)
    {
        $mate->setIsApprovedByThirdParty(true);
        $mate->setRequestState(RequestStateType::FINISHED);
        $mate = self::respondToMateDeclaration($mate, $loggedInUser);
        return $mate;
    }


    /**
     * @param Mate $mate
     * @param Person $loggedInUser
     * @return Mate
     */
    public static function rejectMateDeclaration(Mate $mate, Person $loggedInUser)
    {
        $mate->setIsApprovedByThirdParty(false);
        $mate->setRequestState(RequestStateType::REJECTED);
        $mate = self::respondToMateDeclaration($mate, $loggedInUser);
        return $mate;
    }


    private static function respondToMateDeclaration(Mate $mate, Person $loggedInUser)
    {
        $mate->setApprovedBy($loggedInUser);
        $mate->setApprovalDate(new \DateTime('now'));
        return $mate;
    }
}