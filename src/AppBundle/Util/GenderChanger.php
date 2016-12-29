<?php

namespace AppBundle\Util;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\GenderHistoryItem;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Enumerator\AnimalObjectType;
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

                //Animal was found, do additional checks to see if we allow a gender change
                if(count($animal->getBirths()) > 0) {
                    $statusCode = 403;
                    return new JsonResponse(
                      array(
                        Constant::RESULT_NAMESPACE => array (
                          'code' => $statusCode,
                          "message" => "Changing the gender of an Ewe which has given birth to a Ram is not allowed for ULN: " . $animal->getUln(),
                        )
                      ), $statusCode);
                }

                /**
                 *

                 - dekkingen checken
                 - geboorte checken
                 - als het kinderen heeft

                 - geboorte melding kind - mannetje maar moet vrouwtje worden en vice versa -
                   dier staat al op de stal lijst en wordt paardagen na geboorte gender change aangeroepen,
                    dan moet je de geboorte melding geslacht ook aanpassen.


                - moeder naar vader
                - vader naar moeder


                 */

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
        $genderHistoryItem = new GenderHistoryItem($animal->getAnimalObjectType(), $targetGender);
        $genderHistoryItem->setAnimal($animal);
        $animal->addGenderHistoryItem($genderHistoryItem);

        $this->manager->persist($animal);
        $this->manager->persist($genderHistoryItem);
        $this->manager->flush();

        return $animal;
    }
}