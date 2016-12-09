<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\GenderHistoryItem;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\GenderType;
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

    /**
     * GenderChanger constructor.
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->conn = $manager->getConnection();
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
    public function updateNeuterTypeByGender()
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
     * @param Animal $animal
     * @return Ewe|null
     */
    public function makeFemale($animal)
    {
        if($animal instanceof Ewe) {
            return $animal;

        } elseif ($animal instanceof Ram || $animal instanceof Neuter) {
            
            $ewe = new Ewe();
            $ewe->duplicateValuesAndTransferRelationships($animal);

            $previousGender = StringUtil::getGenderFullyWritten($animal->getGender());
            $newGender = GenderType::FEMALE;
            $genderHistoryItem = new GenderHistoryItem($previousGender, $newGender);
            $genderHistoryItem->setAnimal($ewe);
            $ewe->addGenderHistoryItem($genderHistoryItem);

            $this->manager->persist($ewe);
            $this->manager->persist($genderHistoryItem);
            $this->manager->remove($animal);
            $this->manager->flush();
            
            return $ewe;

        } else {
            return null;
        }
    }


    /**
     * @param Animal $animal
     * @return Ram|null
     */
    public function makeMale($animal)
    {
        if($animal instanceof Ram) {
            return $animal;

        } elseif ($animal instanceof Ewe || $animal instanceof Neuter) {

            $ram = new Ram();
            $ram->duplicateValuesAndTransferRelationships($animal);

            $previousGender = StringUtil::getGenderFullyWritten($animal->getGender());
            $newGender = GenderType::MALE;
            $genderHistoryItem = new GenderHistoryItem($previousGender, $newGender);
            $genderHistoryItem->setAnimal($ram);
            $ram->addGenderHistoryItem($genderHistoryItem);

            $this->manager->persist($ram);
            $this->manager->persist($genderHistoryItem);
            $this->manager->remove($animal);
            $this->manager->flush();
            
            return $ram;

        } else {
            return null;
        }
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @param string $gender
     */
    public static function changeGenderOfNeuter(ObjectManager $em, $animalId, $gender)
    {
        if(is_int($animalId) && is_string($gender)) {
            switch ($gender) {
                case GenderType::MALE:      self::changeNeuterToMaleBySql($em, $animalId);      break;
                case GenderType::FEMALE:    self::changeNeuterToFemaleBySql($em, $animalId);    break;
                default: break;
            }
        }
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     */
    public static function changeNeuterToFemaleBySql(ObjectManager $em, $animalId)
    {
        $sql = "UPDATE animal SET type='Ewe', gender = '".GenderType::FEMALE."' WHERE id = ". $animalId;
        $em->getConnection()->exec($sql);

        $sql = "SELECT id FROM ewe WHERE id = ". $animalId;
        $resultEwe = $em->getConnection()->query($sql)->fetch();

        if($resultEwe['id'] == '' || $resultEwe['id'] == null) {
            $sql = "INSERT INTO ewe VALUES (" . $animalId . ", 'Ewe')";
            $em->getConnection()->exec($sql);
        }

        $sql = "SELECT id FROM neuter WHERE id = ". $animalId;
        $resultNeuter = $em->getConnection()->query($sql)->fetch();

        if($resultNeuter['id'] != '' || $resultNeuter['id'] != null) {
            $sql = "DELETE FROM neuter WHERE id = " . $animalId;
            $em->getConnection()->exec($sql);
        }
    }

    /**
     * @param ObjectManager $em
     * @param int $animalId
     */
    public static function changeNeuterToMaleBySql(ObjectManager $em, $animalId)
    {
        $sql = "UPDATE animal SET type='Ram', gender = '".GenderType::MALE."' WHERE id = ". $animalId;
        $em->getConnection()->exec($sql);

        $sql = "SELECT id FROM ram WHERE id = ". $animalId;
        $resultRam = $em->getConnection()->query($sql)->fetch();

        if($resultRam['id'] == '' || $resultRam['id'] == null) {
            $sql = "INSERT INTO ram VALUES (" . $animalId . ", 'Ram')";
            $em->getConnection()->exec($sql);
        }

        $sql = "SELECT id FROM neuter WHERE id = ". $animalId;
        $resultNeuter = $em->getConnection()->query($sql)->fetch();

        if($resultNeuter['id'] != '' || $resultNeuter['id'] != null) {
            $sql = "DELETE FROM neuter WHERE id = " . $animalId;
            $em->getConnection()->exec($sql);
        }
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     */
    public static function changeMaleToFemaleBySql(ObjectManager $em, $animalId)
    {
        $sql = "UPDATE animal SET type='Ewe', gender = '".GenderType::FEMALE."' WHERE id = ". $animalId;
        $em->getConnection()->exec($sql);

        $sql = "SELECT id FROM ewe WHERE id = ". $animalId;
        $resultEwe = $em->getConnection()->query($sql)->fetch();

        if($resultEwe['id'] == '' || $resultEwe['id'] == null) {
            $sql = "INSERT INTO ewe VALUES (" . $animalId . ", 'Ewe')";
            $em->getConnection()->exec($sql);
        }

        $sql = "SELECT id FROM ram WHERE id = ". $animalId;
        $resultNeuter = $em->getConnection()->query($sql)->fetch();

        if($resultNeuter['id'] != '' || $resultNeuter['id'] != null) {
            $sql = "DELETE FROM ram WHERE id = " . $animalId;
            $em->getConnection()->exec($sql);
        }
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     */
    public static function changeFemaleToMaleBySql(ObjectManager $em, $animalId)
    {
        $sql = "UPDATE animal SET type='Ram', gender = '".GenderType::MALE."' WHERE id = ". $animalId;
        $em->getConnection()->exec($sql);

        $sql = "SELECT id FROM ram WHERE id = ". $animalId;
        $resultRam = $em->getConnection()->query($sql)->fetch();

        if($resultRam['id'] == '' || $resultRam['id'] == null) {
            $sql = "INSERT INTO ram VALUES (" . $animalId . ", 'Ram')";
            $em->getConnection()->exec($sql);
        }

        $sql = "SELECT id FROM ewe WHERE id = ". $animalId;
        $resultNeuter = $em->getConnection()->query($sql)->fetch();

        if($resultNeuter['id'] != '' || $resultNeuter['id'] != null) {
            $sql = "DELETE FROM ewe WHERE id = " . $animalId;
            $em->getConnection()->exec($sql);
        }
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @param string $oldGender
     * @param string $newGender
     */
    public static function changeGenderBySql(ObjectManager $em, $animalId, $oldGender, $newGender) {
        if($oldGender == GenderType::NEUTER && $newGender == GenderType::FEMALE) {
            self::changeNeuterToFemaleBySql($em, $animalId);
        } elseif ($oldGender == GenderType::NEUTER && $newGender == GenderType::MALE) {
            self::changeNeuterToMaleBySql($em, $animalId);
        } else if($oldGender == GenderType::MALE && $newGender == GenderType::FEMALE) {
            self::changeMaleToFemaleBySql($em, $animalId);
        } else if($oldGender == GenderType::FEMALE && $newGender == GenderType::MALE) {
            self::changeFemaleToMaleBySql($em, $animalId);
        }
    }


    /**
     * @param string $gender
     * @return string
     */
    public static function getClassNameByGender($gender)
    {
        switch ($gender)
        {
            case GenderType::FEMALE: return 'Ewe';
            case GenderType::MALE: return 'Ram';
            case GenderType::NEUTER: return 'Neuter';
            default: return 'Neuter';
        }
    }


    public static function getGenderByClassName($type)
    {
        switch ($type)
        {
            case 'Ewe': return GenderType::FEMALE;
            case 'Ram': return GenderType::MALE;
            case 'Neuter': return GenderType::NEUTER;
            default: return null;
        }
    }
}