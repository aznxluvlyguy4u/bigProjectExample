<?php

namespace AppBundle\Util;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\GenderHistoryItem;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Service\IRSerializer;
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
    private $conn;
    
    private $serializer;

    /**
     * GenderChanger constructor.
     * @param ObjectManager $manager
     * @param IRSerializer $serializer
     */
    public function __construct(ObjectManager $manager, IRSerializer $serializer)
    {
        $this->manager = $manager;
        $this->conn = $manager->getConnection();
        $this->serializer = $serializer;
    }

    /**
     * @param Animal $animal
     * @return bool
     */
    public function hasDirectChildRelationshipCheck($animal)
    {
        if($animal instanceof Ewe || $animal instanceof Ram || $animal instanceof Neuter) {
            return $animal->getChildren()->count() > 0;
        } else {
            return false;
        }
    }

    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateNeuterTypeByGender()
    {
        $sql = "SELECT type, gender, id, name
                FROM animal
                WHERE ((type = 'Ram' AND gender <> 'MALE') OR (type = 'Ewe' AND gender <> 'FEMALE') OR (type = 'Neuter' AND gender <> 'NEUTER')) AND type = 'Neuter'";
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $animalId = $result['id'];
            $newGender = $result['gender'];
            $type = $result['type'];
            $oldGender = self::getGenderByClassName($type);
            self::changeGenderBySql($this->manager, $animalId, $oldGender, $newGender);
        }
        return count($results);
    }

  /**
   * Changes the gender of a given animal to the given target gender based on
   * passed Entity type (Neuter, Ewe, Ram).
   *
   * If the target entity is the same as the current animal it will not apply changes to the animal
   * but return the given animal directly.
   *
   * @param Animal $animal
   * @param $targetEntityClass
   * @return Animal
   * @throws \Doctrine\DBAL\DBALException
   */
    public function changeToGender(Animal $animal, $targetEntityClass) {
        $targetEntity = $targetEntityClass::getClassName($targetEntityClass);

        //If animal has same (gender) type as target entity, breakout method
        if($animal instanceof $targetEntity) {
            return $animal;
        }

        $targetDeletionTable = null;

        if ($animal instanceof Ewe) {
            $targetDeletionTable = AnimalObjectType::Ewe;
        } elseif ($animal instanceof Ram) {
            $targetDeletionTable = AnimalObjectType::Ram;
        } else {
            $targetDeletionTable = AnimalObjectType::Neuter;
        }

        //Remove relationship from inheritance table
        $deleteQuery = "DELETE FROM "  .$targetDeletionTable ." WHERE id = " .$animal->getId();
        $this->conn->exec($deleteQuery);

        $targetGender = str_replace(Constant::ENTITY_BASE_PATH, "", $targetEntity);

        switch ($targetGender){
            case AnimalObjectType::Neuter:
                //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .AnimalObjectType::Neuter ." WHERE id = " .$animal->getId();
                $this->conn->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO" .AnimalObjectType::Neuter ." (id, object_type) VALUES ( " . $animal->getId() .", 'Neuter')";
                $this->conn->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Neuter', gender = 'NEUTER' WHERE id = " .$animal->getId();
                $this->conn->exec($updateQuery);
                break;
            case AnimalObjectType::Ewe:
                //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .AnimalObjectType::Ewe ." WHERE id = " .$animal->getId();
                $this->conn->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO" .AnimalObjectType::Ewe ." (id, object_type) VALUES ( " . $animal->getId() .", 'Ewe')";
                $this->conn->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Ewe', gender = 'EWE' WHERE id = " .$animal->getId();
                $this->conn->exec($updateQuery);
                break;
            case AnimalObjectType::Ram:
                //Remove relationship from current inheritance table
                $deleteQuery = "DELETE FROM "  .AnimalObjectType::Ram ." WHERE id = " .$animal->getId();
                $this->conn->exec($deleteQuery);

                // Create new inheritance in target inheritance table
                $insertQuery ="INSERT INTO" .AnimalObjectType::Ram ." (id, object_type) VALUES ( " . $animal->getId() .", 'Ram')";
                $this->conn->exec($insertQuery);

                //Update the discriminator type of the animal in parent Animal table
                $updateQuery = "UPDATE animal SET type = 'Ram', gender = 'RAM' WHERE id = " .$animal->getId();
                $this->conn->exec($updateQuery);
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