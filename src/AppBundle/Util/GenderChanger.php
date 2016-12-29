<?php

namespace AppBundle\Util;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\GenderHistoryItem;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\GenderType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * Class GenderChanger
 * @package AppBundle\Util
 */
class GenderChanger
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /** @var Connection */
    private $connection;

    const MAX_TIME_INTERVAL = 14;

    /**
     * GenderChanger constructor.
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->connection = $manager->getConnection();
    }

    /**
     * @param Animal $animal
     * @return bool
     */
    public function hasDirectChildRelationshipCheck(Animal $animal)
    {
        return $animal->getChildren()->count() > 0 ? true : false;
    }

  /**
   * Changes the gender of a given animal to the given target gender based on
   * passed Entity type (Neuter, Ewe, Ram).
   *
   * If the target entity is the same as the current animal it will not apply changes to the animal
   * but return the given animal directly.
   *
   * @param Animal $animal
   * @param mixed Ram | Ewe | Neuter $targetEntityClass
   * @return Animal
   * @throws \Doctrine\DBAL\DBALException
   */
    public function changeToGender(Animal $animal, $targetEntityClass)
    {
        $targetEntity = $targetEntityClass::getClassName($targetEntityClass);

        //If animal has same (gender) type as target entity, breakout method
        if($animal instanceof $targetEntity) {
            return $animal;
        }

        $targetGender = str_replace(Constant::ENTITY_BASE_PATH, "", $targetEntity);

        switch ($targetGender){
            case AnimalObjectType::Neuter:
                //Do additional checks to see if we allow a gender change
                $requestValidation = $this->validateGenderChangeRequest($animal, AnimalObjectType::NEUTER);

                if ($requestValidation instanceof JsonResponse) {
                    return $requestValidation;
                } elseif (!$requestValidation){
                    return $animal;
                }

                //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .AnimalObjectType::Neuter ." WHERE id = " .$animal->getId();
                $this->connection->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO " .AnimalObjectType::Neuter ." (id, object_type) VALUES ( " . $animal->getId() .", 'Neuter')";
                $this->connection->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Neuter', gender = 'NEUTER' WHERE id = " .$animal->getId();
                $this->connection->exec($updateQuery);
                break;
            case AnimalObjectType::Ewe:
                //Do additional checks to see if we allow a gender change
                $requestValidation = $this->validateGenderChangeRequest($animal, AnimalObjectType::NEUTER);

                if ($requestValidation instanceof JsonResponse) {
                    return $requestValidation;
                } elseif (!$requestValidation){
                    return $animal;
                }

                 //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .AnimalObjectType::Ewe ." WHERE id = " .$animal->getId();
                $this->connection->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO " .AnimalObjectType::Ewe ." (id, object_type) VALUES ( " . $animal->getId() .", 'Ewe')";
                $this->connection->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Ewe', gender = 'EWE' WHERE id = " .$animal->getId();
                $this->connection->exec($updateQuery);
                break;
            case AnimalObjectType::Ram:
                //Do additional checks to see if we allow a gender change
                $requestValidation = $this->validateGenderChangeRequest($animal, AnimalObjectType::NEUTER);

                if ($requestValidation instanceof JsonResponse) {
                    return $requestValidation;
                } elseif (!$requestValidation){
                    return $animal;
                }

                //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .AnimalObjectType::Ram ." WHERE id = " .$animal->getId();
                $this->connection->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO" .AnimalObjectType::Ram ." (id, object_type) VALUES ( " . $animal->getId() .", 'Ram')";
                $this->connection->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Ram', gender = 'RAM' WHERE id = " .$animal->getId();
                $this->connection->exec($updateQuery);
                break;
        }

        //Re-retrieve animal
        $animal = $this->manager
          ->getRepository(Constant::ANIMAL_REPOSITORY)
          ->findOneBy(array ('ulnCountryCode' => $animal->getUlnCountryCode(), 'ulnNumber' => $animal->getUlnNumber()));

        //Add & persist gender change to history
        $genderHistoryItem = new GenderHistoryItem($animal->getObjectType(), $targetGender);
        $genderHistoryItem->setAnimal($animal);
        $animal->addGenderHistoryItem($genderHistoryItem);

        $this->manager->persist($animal);
        $this->manager->persist($genderHistoryItem);
        $this->manager->flush();

        return $animal;
    }

    /**
     * A gender change implicates changing history, it will have direct affect on other
     * parts of the system. For example, if an animal has been initially registered as an Ewe that
     * has given birth, changing gender of that animal would implicate that the birth could never have occured.
     * Above and numerous situations could be invalid after altering the gender, and ultimately changing the history.
     * Therefore additional validations need te be done before allowing the history alteration / gender change.
     *
     * @param Animal $animal
     * @param  $targetEntity
     * @return mixed JsonResponse|bool
     */
    function validateGenderChangeRequest(Animal $animal, $targetEntity)
    {
        /*
            - dekkingen checken*
            - geboorte checken*
            - als het kinderen heeft*

            - geboorte melding kind - mannetje maar moet vrouwtje worden en vice versa -
              dier staat al op de stal lijst en wordt paardagen na geboorte gender change aangeroepen,
              dan moet je de geboorte melding geslacht ook aanpassen.

            - moeder naar vader
            - vader naar moeder
         */
        $statusCode = 403;
        
        //Check if target entity is of type Neuter, disallow for now
        if($targetEntity == AnimalObjectType::Neuter && $animal->getGender() != GenderType::NEUTER) {
            return new JsonResponse(
              array(
                Constant::RESULT_NAMESPACE => array (
                  'code' => $statusCode,
                  "message" =>  $animal->getUln() . " has a known gender, therefore changing gender to a Neuter is not allowed.",
                )
              ), $statusCode);
        }
        
        //Check if animal has matings
        if($animal->getLocation()) {
            if($animal->getGender() == GenderType::FEMALE){
                if($animal->getLocation()->getMatings()->count() > 0) {
                    return new JsonResponse(
                      array(
                        Constant::RESULT_NAMESPACE => array (
                          'code' => $statusCode,
                          "message" =>  $animal->getUln() . " has registered matings, therefore changing gender is not allowed.",
                        )
                      ), $statusCode);
                }
            }
        }

        //Check if animal has registered births
        if ($animal->getBirths()->count() > 0) {
            if($animal->getGender() == GenderType::FEMALE){
                return new JsonResponse(
                  array(
                    Constant::RESULT_NAMESPACE => array (
                      'code' => $statusCode,
                      "message" =>  $animal->getUln() . " has registered births, therefore changing gender is not allowed.",
                    )
                  ), $statusCode);
            }
        }

        //Check if animal has registered children
        if ($this->hasDirectChildRelationshipCheck($animal)) {
            if ($animal->getGender() == GenderType::FEMALE || $animal->getGender() == GenderType::MALE) {
                return new JsonResponse(
                  array (
                    Constant::RESULT_NAMESPACE => array (
                      'code' => $statusCode,
                      "message" => $animal->getUln() . " has registered children, therefore changing gender is not allowed.",
                    )
                  ), $statusCode);
            }
        }

        //Check if birth registration is within a time span of MAX_TIME_INTERVAL from now,
        //then, and only then, an alteration of gender for this animal is allowed
        $dateInterval = $animal->getDateOfBirth()->diff(new \DateTime());

        if(!$dateInterval->y == 0
           && !$dateInterval->m == 0
           && !$dateInterval->days < self::MAX_TIME_INTERVAL) {
            return new JsonResponse(
              array (
                Constant::RESULT_NAMESPACE => array (
                  'code' => $statusCode,
                  "message" => $animal->getUln() . " has a registered birth that is longer then "
                    .self::MAX_TIME_INTERVAL ." days ago, from now, therefore changing gender is not allowed.",
                )
              ), $statusCode);
        }
        
        return true;
    }
}