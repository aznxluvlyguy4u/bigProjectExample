<?php

namespace AppBundle\Util;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\GenderHistoryItem;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

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

    const MAX_MONTH_INTERVAL = 6;

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
        if ($animal instanceof Ram || $animal instanceof Ewe) {
            return $animal->getChildren()->count() > 0 ? true : false;
        }
        return false;
    }

  /**
   * Changes the gender of a given animal to the given target gender based on
   * passed Entity type (Neuter, Ewe, Ram).
   *
   * If the target entity is the same as the current animal it will not apply changes to the animal
   * but return the given animal directly.
   *
   * @param Ram | Ewe | Neuter $animal
   * @param Ram | Ewe | Neuter $targetEntityClass
   * @param Person $user
   * @return Animal
   * @throws \Doctrine\DBAL\DBALException
   */
    public function changeToGender($animal, $targetEntityClass, $user = null)
    {
        $targetEntity = $targetEntityClass::getClassName($targetEntityClass);
        $sourceEntity = $animal->getObjectType();

        //If animal has same (gender) type as target entity, breakout method
        if($animal instanceof $targetEntity) {
            return $animal;
        }

        $targetGender = str_replace(Constant::ENTITY_BASE_PATH, "", $targetEntity);

        switch ($targetGender){
            case AnimalObjectType::Neuter:
                //Do additional checks to see if we allow a gender change
                $requestValidation = $this->validateGenderChangeRequest($animal, AnimalObjectType::NEUTER, $user);

                if ($requestValidation instanceof JsonResponse) {
                    return $requestValidation;
                }

                //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .$sourceEntity ." WHERE id = " .$animal->getId();
                $this->connection->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO " .AnimalObjectType::Neuter ." (id, object_type) VALUES ( " . $animal->getId() .", 'Neuter')";
                $this->connection->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Neuter', gender = '" . GenderType::NEUTER . "' WHERE id = " .$animal->getId();
                $this->connection->exec($updateQuery);
                break;
            case AnimalObjectType::Ewe:
                //Do additional checks to see if we allow a gender change
                $requestValidation = $this->validateGenderChangeRequest($animal, AnimalObjectType::EWE, $user);

                if ($requestValidation instanceof JsonResponse) {
                    return $requestValidation;
                }

                 //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .$sourceEntity ." WHERE id = " .$animal->getId();
                $this->connection->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO " .AnimalObjectType::Ewe ." (id, object_type) VALUES ( " . $animal->getId() .", 'Ewe')";
                $this->connection->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Ewe', gender = '" . GenderType::FEMALE . "' WHERE id = " .$animal->getId();
                $this->connection->exec($updateQuery);
                break;
            case AnimalObjectType::Ram:
                //Do additional checks to see if we allow a gender change
                $requestValidation = $this->validateGenderChangeRequest($animal, AnimalObjectType::RAM, $user);

                if ($requestValidation instanceof JsonResponse) {
                    return $requestValidation;
                }

                //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .$sourceEntity ." WHERE id = " .$animal->getId();
                $this->connection->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO " .AnimalObjectType::Ram ." (id, object_type) VALUES ( " . $animal->getId() .", 'Ram')";
                $this->connection->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Ram',  gender = '" . GenderType::MALE . "' WHERE id = " .$animal->getId();
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
     * @param Ram | Ewe | Neuter $animal
     * @param  $targetEntity
     * @param Person $user
     * @return mixed JsonResponse|bool
     */
    function validateGenderChangeRequest($animal, $targetEntity, $user = null)
    {
        $statusCode = 403;

        if($user instanceof Person) {
            if(!$animal->getOwner() || $user->getId() !== $animal->getOwner()->getId()) {
                if (!AdminValidator::isAdmin($user, AccessLevelType::ADMIN)) {
                    return new JsonResponse(
                        array(
                            Constant::RESULT_NAMESPACE => array (
                                'code' => $statusCode,
                                "message" =>  'U heeft geen toegang tot het dier met ULN ' . $animal->getUln() . ".",
                            )
                        ), $statusCode);
                }
            }
        }
        //Check if target entity is of type Neuter, disallow for now
        if($targetEntity == AnimalObjectType::Neuter && $animal->getGender() != GenderType::NEUTER) {
            return new JsonResponse(
              array(
                Constant::RESULT_NAMESPACE => array (
                  'code' => $statusCode,
//                  "message" =>  $animal->getUln() . " has a known gender, therefore changing gender to a Neuter is not allowed.",
                  "message" =>  $animal->getUln() . " heeft reeds een bekend geslacht, zodoende is het niet geoorloofd om het geslacht van het dier te wijzigen.",
                )
              ), $statusCode);
        }

        //check if animal is registered in a mating
        $matings = $this->manager
          ->getRepository(Constant::MATE_REPOSITORY)->getMatingsByStudIds($animal);
          
        if ($matings->count() > 0) {
            return new JsonResponse(
              array(
                Constant::RESULT_NAMESPACE => array (
                  'code' => $statusCode,
//                  "message" =>  $animal->getUln() . " has registered matings, therefore changing gender is not allowed.",
                  "message" =>  $animal->getUln() . " heeft geregistreerde dekkingsmeldingen, zodoende is het niet geoorloofd om het geslacht van het dier te wijzigen.",
                )
              ), $statusCode);
        }

        //Check if animal is part of a registered litter

        if($animal->getObjectType() != AnimalObjectType::Neuter) {
            if ($animal->getLitters()->count() > 0) {
                return new JsonResponse(
                  array(
                    Constant::RESULT_NAMESPACE => array (
                      'code' => $statusCode,
//                      "message" =>  $animal->getUln() . " is part of a registered litter, therefore changing gender is not allowed.",
                      "message" =>  $animal->getUln() . " heeft geregistreerde worpen, zodoende is het niet geoorloofd om het geslacht van het dier te wijzigen.",
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
//                      "message" => $animal->getUln() . " has registered children, therefore changing gender is not allowed.",
                      "message" =>  $animal->getUln() . " heeft geregistreerde kinderen, zodoende is het niet geoorloofd om het geslacht van het dier te wijzigen.",
                    )
                  ), $statusCode);
            }
        }

        //Ignore dateOfBirth for validation check if it is null
        if($animal->getDateOfBirth() == null) {
            return $animal;
        }

        //Check if birth registration is within a time span of MAX_TIME_INTERVAL from now,
        //then, and only then, an alteration of gender for this animal is allowed
        $dateInterval = $animal->getDateOfBirth()->diff(new \DateTime());

        if($dateInterval->y > 0 || $dateInterval->m >= self::MAX_MONTH_INTERVAL) {
            //Allow gender change for animals beyond MAX_TIME_INTERVAL
            // if current gender is of type NEUTER
            if($animal instanceof Neuter) {
                return $animal;
            }

            //Allow gender change for animals beyond MAX_TIME_INTERVAL
            // if user is an admin with a high enough accessLevel
            if($user instanceof Person) {
                //For backwards PHP compatibility (versions below 5.6.0 not supporting Constant expressions)
                //the AccessLevelType::ADMIN is not set in a separate constant of this class.
                if(AdminValidator::isAdmin($user, AccessLevelType::ADMIN)) {
                    return $animal;
                }
            }

            return new JsonResponse(
                array (
                    Constant::RESULT_NAMESPACE => array (
                        'code' => $statusCode,
//                  "message" => $animal->getUln() . " has a registered birth that is longer then "
//                    .self::MAX_MONTH_INTERVAL ." months ago, from now, therefore changing gender is not allowed.",
                        "message" => $animal->getUln() . " heeft een geregistreerde geboortedatum dat langer dan "
                            .self::MAX_MONTH_INTERVAL ." maanden geleden is, zodoende is het niet geoorloofd om het geslacht van het dier te wijzigen. Dit moet worden goedgekeurd door een administrator.",
                    )
                ), $statusCode);
        }

        return $animal;
    }
}