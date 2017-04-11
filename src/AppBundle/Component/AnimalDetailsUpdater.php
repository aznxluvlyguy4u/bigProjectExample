<?php


namespace AppBundle\Component;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRepository;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\BreedType;
use AppBundle\Service\IRSerializer;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

class AnimalDetailsUpdater
{
    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param Collection $content
     * @return Animal
     */
    public static function update(ObjectManager $em, $animal, Collection $content)
    {
        if(!($animal instanceof Animal)){ return $animal; }

        //Keep track if any changes were made
        $anyValueWasUpdated = false;

        //Collar color & number
        if($content->containsKey('collar')) {
            $collar = $content->get('collar');
            $newCollarNumber = ArrayUtil::get('number',$collar);
            $newCollarColor = ArrayUtil::get('color',$collar);

            if($animal->getCollarNumber() != $newCollarNumber) {
                $animal->setCollarNumber($newCollarNumber);
                $anyValueWasUpdated = true;
            }

            if($animal->getCollarColor() != $newCollarColor) {
                $animal->setCollarColor($newCollarColor);
                $anyValueWasUpdated = true;
            }
        }

        //Only update animal in database if any values were actually updated
        if($anyValueWasUpdated) {
            $em->persist($animal);
            $em->flush();
        }

        return $animal;
    }


    /**
     * @param IRSerializer $serializer
     * @param Animal $animal
     * @param Collection $content
     * @return Animal
     */
    public static function updateAsAdmin(IRSerializer $serializer, $animal, Collection $content)
    {
        $updateString = '';

        $animalArray = $content->get(JsonInputConstant::ANIMAL);
        if($animalArray == null || !$animal instanceof Animal) {
            return null;
        }

        $em = $serializer->getManager();

        $animalArray = $content->get(JsonInputConstant::ANIMAL);
        $type = ArrayUtil::get(JsonInputConstant::TYPE, $animalArray);

        switch ($type) {
            case ucfirst(JsonInputConstant::EWE): $clazz = Ewe::class; break;
            case ucfirst(JsonInputConstant::RAM): $clazz = Ram::class; break;
            case ucfirst(JsonInputConstant::NEUTER): $clazz = Neuter::class; break;
            default: return null;
        }

//        $locationArray = ArrayUtil::get(JsonInputConstant::LOCATION, $animalArray);
//        unset($animalArray[JsonInputConstant::LOCATION]);
//        $updatedLocation = $locationArray != null ? $serializer->denormalizeToObject($locationArray, Location::class) : null;
        /** @var Animal $updatedAnimal */
        $updatedAnimal = $serializer->denormalizeToObject($animalArray, $clazz);

        if($updatedAnimal->getPedigreeCountryCode() != $animal->getPedigreeCountryCode() ||
           $updatedAnimal->getPedigreeNumber() != $animal->getPedigreeNumber()
        ) {
            $oldStn = $animal->getPedigreeCountryCode().$animal->getPedigreeNumber();
            $oldStn = $oldStn == '' ? 'LEEG' : $oldStn;
            $animal->setPedigreeCountryCode($updatedAnimal->getPedigreeCountryCode());
            $animal->setPedigreeNumber($updatedAnimal->getPedigreeNumber());
            $stn = $updatedAnimal->getPedigreeCountryCode().$updatedAnimal->getPedigreeNumber();
            $updateString = $updateString.'stn: '.$oldStn.' => '.$stn.', ';
        }

        if($updatedAnimal->getUlnCountryCode() != $animal->getUlnCountryCode() ||
            $updatedAnimal->getUlnNumber() != $animal->getUlnNumber()
        ) {
            $oldUln = $animal->getUlnCountryCode().$animal->getUlnNumber();
            $oldUln = $oldUln == '' ? 'LEEG' : $oldUln;
            $animal->setUlnCountryCode($updatedAnimal->getUlnCountryCode());
            $animal->setUlnNumber($updatedAnimal->getUlnNumber());
            $animal->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($updatedAnimal->getUlnNumber()));
            $uln = $updatedAnimal->getUlnCountryCode().$updatedAnimal->getUlnNumber();
            $updateString = $updateString.'uln: '.$oldUln.' => '.$uln.', ';
        }

        if($updatedAnimal->getNickname() != $animal->getNickname()) {
            $oldNickName = $animal->getNickname();
            $oldNickName = $oldNickName == '' ? 'LEEG' : $oldNickName;
            $animal->setNickname($updatedAnimal->getNickname());
            $updateString = $updateString.'nickname: '.$oldNickName.' => '.$updatedAnimal->getNickname().', ';
        }

        if($updatedAnimal->getCollarColor() != $animal->getCollarColor() ||
            $updatedAnimal->getCollarNumber() != $animal->getCollarNumber()
        ) {
            $oldCollar = $animal->getCollarColor().$animal->getCollarNumber();
            $oldCollar = $oldCollar == '' ? 'LEEG' : $oldCollar;
            $animal->setCollarColor($updatedAnimal->getCollarColor());
            $animal->setCollarNumber($updatedAnimal->getCollarNumber());
            $collar = $updatedAnimal->getCollarColor().$updatedAnimal->getCollarNumber();
            $updateString = $updateString.'halsband: '.$oldCollar.' => '.$collar.', ';
        }

        if($updatedAnimal->getBreedCode() != $animal->getBreedCode()) {
            $oldBreedCode = $animal->getBreedCode();
            $oldBreedCode = $oldBreedCode == '' ? 'LEEG' : $oldBreedCode;
            $animal->setBreedCode($updatedAnimal->getBreedCode());
            $updateString = $updateString.'rascode: '.$oldBreedCode.' => '.$updatedAnimal->getBreedCode().', ';
        }

        if($updatedAnimal->getScrapieGenotype() != $animal->getScrapieGenotype()) {
            $oldScrapieGenotype = $animal->getScrapieGenotype();
            $oldScrapieGenotype = $oldScrapieGenotype == '' ? 'LEEG' : $oldScrapieGenotype;
            $animal->setScrapieGenotype($updatedAnimal->getScrapieGenotype());
            $updateString = $updateString.'scrapieGenotype: '.$oldScrapieGenotype.' => '.$updatedAnimal->getScrapieGenotype().', ';
        }

        
        if(is_string($updatedAnimal->getDateOfBirth())) {
            $updatedDateOfBirth = new \DateTime($updatedAnimal->getDateOfBirth());
            $updatedAnimal->setDateOfBirth($updatedDateOfBirth);
        } elseif ($updatedAnimal->getDateOfBirth() == null) {
            $updatedDateOfBirth = null;
        } else {
            $updatedDateOfBirth = $updatedAnimal->getDateOfBirth();
        }
        
        if($updatedDateOfBirth != $animal->getDateOfBirth()) {
            $oldDateOfBirth = $animal->getDateOfBirthString();
            $oldDateOfBirth = $oldDateOfBirth == null ? 'LEEG' : $oldDateOfBirth;
            $animal->setDateOfBirth($updatedDateOfBirth);
            $updateString = $updateString.'geboorteDatum: '.$oldDateOfBirth.' => '. $animal->getDateOfBirthString().', ';
        }

        
        if(is_string($updatedAnimal->getDateOfDeath())) {
            $updatedDateOfDeath = new \DateTime($updatedAnimal->getDateOfDeath());
            $updatedAnimal->setDateOfDeath($updatedDateOfDeath);
        } elseif ($updatedAnimal->getDateOfDeath() == null) {
            $updatedDateOfDeath = null;
        } else {
            $updatedDateOfDeath = $updatedAnimal->getDateOfDeath();
        }

        if($updatedDateOfDeath != $animal->getDateOfDeath()) {
            $oldDateOfDeath = $animal->getDateOfDeathString();
            $oldDateOfDeath = $oldDateOfDeath == null ? 'LEEG' : $oldDateOfDeath;
            $animal->setDateOfDeath($updatedDateOfDeath);
            $updateString = $updateString.'sterfteDatum: '.$oldDateOfDeath.' => '. $animal->getDateOfDeathString().', ';
        }
        

        if($updatedAnimal->getIsAlive() != $animal->getIsAlive()) {
            $oldIsAlive = StringUtil::getBooleanAsString($animal->getIsAlive());
            $animal->setIsAlive($updatedAnimal->getIsAlive());
            $updateString = $updateString.'isLevendStatus: '. $oldIsAlive.' => '.StringUtil::getBooleanAsString($updatedAnimal->getIsAlive()).', ';
        }


        if($updatedAnimal->getNote() != $animal->getNote()) {
            $oldNote = $animal->getNote();
            $oldNote = $oldNote == '' ? 'LEEG' : $oldNote;
            $animal->setNote($updatedAnimal->getNote());
            $updateString = $updateString.'notitie: '.$oldNote.' => '.$updatedAnimal->getNote().', ';
        }

        $updatedBreedType = Translation::getEnglish($updatedAnimal->getBreedType());
        if($updatedBreedType != $animal->getBreedType()) {
            $oldBreedType = $updatedAnimal->getBreedType();
            $oldBreedType = $oldBreedType == '' ? 'LEEG' : $oldBreedType;
            $animal->setBreedType($updatedBreedType);
            $updateString = $updateString.'rasStatus: '.$oldBreedType.' => '.$updatedBreedType.', ';
        }


        if($updatedAnimal->getUbnOfBirth() != $animal->getUbnOfBirth()) {
            $oldUbnOfBirth = $animal->getUbnOfBirth();
            $oldUbnOfBirth = $oldUbnOfBirth == '' ? 'LEEG' : $oldUbnOfBirth;
            $animal->setUbnOfBirth($updatedAnimal->getUbnOfBirth());
            $updateString = $updateString.'fokkerUbn(alleen nummer): '.$oldUbnOfBirth.' => '.$updatedAnimal->getUbnOfBirth().', ';
        }


        $updatedLocationOfBirthUbn = null;
        if($updatedAnimal->getLocationOfBirth()) {
            if(is_array($updatedAnimal->getLocationOfBirth())) {
                /** @var Location $updatedLocationOfBirth */
                $updatedLocationOfBirth = $serializer->denormalizeToObject($updatedAnimal->getLocationOfBirth(), Location::class);
                $updatedLocationOfBirth->setLocationId(null);
                $updatedAnimal->setLocationOfBirth($updatedLocationOfBirth);
            }
            $updatedLocationOfBirthUbn = $updatedAnimal->getLocationOfBirth()->getUbn();
        }
        $locationOfBirthUbn = 'LEEG';
        if($animal->getLocationOfBirth()) {
            $locationOfBirthUbn = $animal->getLocationOfBirth()->getUbn();
        }
        if($updatedLocationOfBirthUbn == null && $locationOfBirthUbn != null) {
            $animal->setLocationOfBirth(null);
            $updateString = $updateString.'fokkerUbn(LOCATIE): LEEG, ';
        } elseif($updatedLocationOfBirthUbn != null && ($locationOfBirthUbn != $updatedLocationOfBirthUbn)) {
            /** @var LocationRepository $locationRepository */
            $locationRepository = $em->getRepository(Location::class);
            $locationOfBirth = $locationRepository->findOnePrioritizedByActiveUbn($updatedLocationOfBirthUbn);
            if($locationOfBirth) {
                $animal->setLocationOfBirth($locationOfBirth);
                $updateString = $updateString.'fokkerUbn(LOCATIE): '.$locationOfBirthUbn.' => '.$updatedLocationOfBirthUbn.', ';
            } else {
                $updateString = $updateString.'fokkerUbn(LOCATIE): '.$locationOfBirthUbn.' => '.$updatedLocationOfBirthUbn.'(bestaat niet in database) , ';
            }
        }


        $updatedPedigreeRegisterId = null;
        if($updatedAnimal->getPedigreeRegister()) {
            if(is_array($updatedAnimal->getPedigreeRegister())) {
                /** @var PedigreeRegister $updatedPedigreeRegister */
                $updatedPedigreeRegister = $serializer->denormalizeToObject($updatedAnimal->getPedigreeRegister(), PedigreeRegister::class);
                $updatedAnimal->setPedigreeRegister($updatedPedigreeRegister);
            }
            $updatedPedigreeRegisterId = $updatedAnimal->getPedigreeRegister()->getId();
        }
        $pedigreeRegisterId = null;
        $oldPedigreeRegisterAbbreviation = 'LEEG';
        if($animal->getPedigreeRegister()) {
            $pedigreeRegisterId = $animal->getPedigreeRegister()->getId();
            $oldPedigreeRegisterAbbreviation = $animal->getPedigreeRegister()->getAbbreviation();
        }
        if($updatedPedigreeRegisterId == null && $pedigreeRegisterId != null) {
            $animal->setPedigreeRegister(null);
            $updateString = $updateString.'stamboek: '.$oldPedigreeRegisterAbbreviation.' => LEEG, ';
        } elseif($updatedPedigreeRegisterId != null && ($pedigreeRegisterId != $updatedPedigreeRegisterId)) {
            /** @var PedigreeRegisterRepository $pedigreeRegisterRepository */
            $pedigreeRegisterRepository = $em->getRepository(PedigreeRegister::class);
            /** @var PedigreeRegister $pedigreeRegister */
            $pedigreeRegister = $pedigreeRegisterRepository->find($updatedPedigreeRegisterId);
            if($pedigreeRegister) {
                $animal->setPedigreeRegister($pedigreeRegister);
                $updateString = $updateString.'stamboek: '.$oldPedigreeRegisterAbbreviation.' => '.$pedigreeRegister->getAbbreviation().', ';
            } else {
                $updateString = $updateString.'stamboek: met id '.$oldPedigreeRegisterAbbreviation.' => '.$updatedPedigreeRegisterId.' niet in database, ';
            }
        }

        $updateString = rtrim($updateString, ', ');


        $em->persist($animal);
        $em->flush();

        return $animal;
    }
}