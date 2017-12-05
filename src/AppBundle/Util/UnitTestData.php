<?php


namespace AppBundle\Util;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Service\CacheService;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class UnitTestData
{
    const UBN = '1674459';

    const ULN_NUMBER_LENGTH = 12;
    const ULN_NUMBER_KEYSPACE = '0123456789';

    const ULN_COUNTRY_CODE = 'XZ';
    const UBN_OF_BIRTH = '1674459';
    const BREEDER_NUMBER = '00756';
    const PEDIGREE_REGISTER = 'TSNH';
    const BREED_TYPE = BreedType::PURE_BRED;
    const BREED_CODE = 'TE100';

    const RAM_ULN_NUMBER = '999999999998';
    const EWE_ULN_NUMBER = '999999999997';
    const TAG_ULN_NUMBER = '999999999996';

    const TEST_ANIMAL_LABEL = 'TEST_ANIMAL';
    const TEST_TAG_LABEL = 'TEST_TAG';

    /**
     * @param string $testEmail
     * @param string $accessLevel
     * @return Employee
     */
    public static function getTestAdmin($testEmail, $accessLevel = AccessLevelType::ADMIN)
    {
        Validator::validateEmailAddress($testEmail, true);

        $admin = new Employee(
            $accessLevel,
            'JOHN',
            'DOE',
            $testEmail
            );
        return $admin;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @return Animal
     */
    public static function createTestRam(EntityManagerInterface $em, $location = null)
    {
        return self::createTestAnimal($em, new Ram(), self::RAM_ULN_NUMBER, $location);
    }


    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @return Animal
     */
    public static function createTestEwe(EntityManagerInterface $em, $location = null)
    {
        return self::createTestAnimal($em, new Ewe(), self::EWE_ULN_NUMBER, $location);
    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param string $ulnNumber
     * @param Location $location
     * @return Animal
     */
    private static function createTestAnimal(ObjectManager $em, Animal $animal, $ulnNumber, $location)
    {
        $animalOrderNumber = StringUtil::getLast5CharactersFromString($ulnNumber);

        $animal->setUlnCountryCode(self::ULN_COUNTRY_CODE);
        $animal->setUlnNumber($ulnNumber);
        $animal->setAnimalOrderNumber($animalOrderNumber);

        $animal->setPedigreeCountryCode(self::ULN_COUNTRY_CODE);
        $animal->setPedigreeNumber(self::BREEDER_NUMBER . '-' . $animalOrderNumber);

        $locationOfBirth = $em->getRepository(Location::class)->findOneByActiveUbn(self::UBN_OF_BIRTH);
        $animal->setLocationOfBirth($locationOfBirth);
        $animal->setUbnOfBirth(self::UBN_OF_BIRTH);
        $animal->setBreedCode(self::BREED_CODE);
        $animal->setBreedType(self::BREED_TYPE);

        $animal->setNickname(self::TEST_ANIMAL_LABEL);
        $animal->setName(self::TEST_ANIMAL_LABEL);

        $animal->setLocation($location);
        $animal->setIsAlive(true);

        $em->persist($animal);
        $em->flush();

        return $animal;
    }


    /**
     * @param Connection $conn
     * @param array|string $tableNames
     * @return int
     */
    public static function deleteTestAnimals(Connection $conn, $tableNames = [])
    {
        $testLabel = self::TEST_ANIMAL_LABEL;

        if (is_string($tableNames)) { $tableNames = [$tableNames]; }
        foreach ($tableNames as $tableName) {
            if (is_string($tableName)) {
                $sql = "DELETE FROM $tableName WHERE animal_id IN 
                (SELECT id FROM animal WHERE nickname = '$testLabel' AND name = '$testLabel')";
                SqlUtil::updateWithCount($conn, $sql);
            }
        }

        $sql = "DELETE FROM animal WHERE nickname = '$testLabel' OR name = '$testLabel'";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function deleteTestAnimalsWithDeclares(Connection $conn)
    {
        return UnitTestData::deleteTestAnimals($conn,
            [
                DeclareArrival::getTableName(),
                DeclareBirth::getTableName(),
                DeclareDepart::getTableName(),
                DeclareExport::getTableName(),
                DeclareImport::getTableName(),
                DeclareLoss::getTableName(),
                DeclareTagReplace::getTableName(),
                DeclareWeight::getTableName()
            ]);
    }


    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @param string $ulnNumber
     * @param string $tagStatus
     * @param \DateTime|string|null $orderDate
     * @return Tag
     */
    public static function createTag(EntityManagerInterface $em,
                                     Location $location,
                                     $ulnNumber = self::TAG_ULN_NUMBER,
                                     $tagStatus = TagStateType::UNASSIGNED,
                                     $orderDate = null
    )
    {
        $tag = $em->getRepository(Tag::class)->findOneBy(
            ['ulnCountryCode' => self::ULN_COUNTRY_CODE, 'ulnNumber' => $ulnNumber]);

        if ($tag) {
            if ($tag->getLocation() === $location &&
                $tag->getTagStatus() === $tagStatus &&
                $tag->getTagDescription() === self::TEST_TAG_LABEL
            ) {
                if ($location) {
                    if ($tag->getOwner() === $location->getOwner()) { return $tag; }
                } else {
                    if ($tag->getOwner() === null) { return $tag; }
                }
            }
            $tag->setLocation($location);
            $tag->setOwner($location->getOwner());
            $tag->setTagStatus($tagStatus);
            $em->persist($tag);
            $em->flush();
            return $tag;
        }

        $animalOrderNumber = StringUtil::getLast5CharactersFromString($ulnNumber);

        $tag = new Tag();
        $tag->setUlnCountryCode(self::ULN_COUNTRY_CODE);
        $tag->setUlnNumber($ulnNumber);
        $tag->setAnimalOrderNumber($animalOrderNumber);
        $tag->setTagStatus($tagStatus);

        if (is_string($orderDate)) {
            $orderDate = new \DateTime($orderDate);
        } elseif ($orderDate === null) {
            $orderDate = new \DateTime();
        }
        $tag->setOrderDate($orderDate);

        $tag->setLocation($location);
        $tag->setOwner($location->getOwner());

        $tag->setTagDescription(self::TEST_TAG_LABEL);

        $em->persist($tag);
        $em->flush();

        return $tag;
    }


    /**
     * @param EntityManagerInterface $em
     * @param string $emailAddress
     * @param string $firstName
     * @param string $lastName
     * @return VwaEmployee
     */
    public static function getOrCreateVwaEmployee(EntityManagerInterface $em, $emailAddress,
                                                  $firstName = 'Billy', $lastName = 'Bob')
    {
        $vwaEmployee = $em->getRepository(VwaEmployee::class)->findOneBy(['emailAddress' => $emailAddress]);

        if (!$vwaEmployee) {
            $vwaEmployee = new VwaEmployee();
            $vwaEmployee
                ->setEmailAddress($emailAddress)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setPassword('BLANK')
            ;
            $em->persist($vwaEmployee);
            $em->flush();
        }

        return $vwaEmployee;
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function deleteTestTags(Connection $conn)
    {
        $sql = "DELETE FROM tag WHERE tag_description = '".self::TEST_TAG_LABEL."'";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param ObjectManager|EntityManagerInterface $em
     * @param string $accessLevel
     * @return string
     */
    public static function getRandomAdminAccessTokenCode(EntityManagerInterface $em, $accessLevel = AccessLevelType::ADMIN)
    {
        $sql = "SELECT token.code as code 
                FROM employee 
                    INNER JOIN token ON employee.id = token.owner_id
                    INNER JOIN person ON employee.id = person.id
                WHERE token.type = 'ACCESS' 
                  AND person.is_active = TRUE
                  AND employee.access_level = '" . $accessLevel . "'
                  ";
        $tokenCodes = $em->getConnection()->query($sql)->fetchAll();

        //null check
        if (count($tokenCodes) == 0) {
            return null;
        }

        $choice = rand(1, count($tokenCodes) - 1);
        return $tokenCodes[$choice]['code'];
    }


    /**
     * @param EntityManagerInterface $em
     * @param Location|int $locationToSkip
     * @param int $minAliveAnimalsCount
     * @return Location
     */
    public static function getActiveTestLocation(EntityManagerInterface $em, $locationToSkip = null, $minAliveAnimalsCount = 30)
    {
        //Check if Default Location exists
        $location = $em->getRepository(Location::class)->findOneByActiveUbn(self::UBN);
        if ($location) {
            return $location;
        }

        return self::getRandomActiveLocation($em, $locationToSkip, $minAliveAnimalsCount);
    }


    /**
     * @param EntityManagerInterface $em
     * @param Location|int $locationToSkip
     * @param int $minAliveAnimalsCount
     * @return Location
     */
    public static function getRandomActiveLocation(EntityManagerInterface $em, $locationToSkip = null, $minAliveAnimalsCount = 30)
    {
        if ($locationToSkip instanceof Location) {
            $locationFilter = " AND location.id <> " . $locationToSkip->getId();
        } elseif (is_int($locationToSkip)) {
            $locationFilter = " AND location.id <> " . $locationToSkip;
        } else {
            $locationFilter = "";
        }

        $sql = "SELECT location.id as id 
                FROM (location 
                      INNER JOIN (
                                  SELECT location_id, COUNT(*) 
                                  FROM animal 
                                  WHERE animal.transfer_state IS NULL AND animal.is_alive = true 
                                  GROUP BY location_id HAVING COUNT(*) > " . $minAliveAnimalsCount . " 
                                  ) 
                                  lc ON location.id = lc.location_id
                      ) 
                      WHERE location.is_active = TRUE" . $locationFilter;

        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, Location::class);
    }


    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @return null|Ram
     */
    public static function getRandomRamFromLocation(EntityManagerInterface $em, Location $location)
    {
        return self::getRandomAnimalFromLocation($em, $location, 'Ram');
    }

    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @return null|Ewe
     */
    public static function getRandomEweFromLocation(EntityManagerInterface $em, Location $location)
    {
        return self::getRandomAnimalFromLocation($em, $location, 'Ewe');
    }

    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @param null $gender
     * @return null|Animal|Ram|Ewe|Neuter
     */
    public static function getRandomAnimalFromLocation(EntityManagerInterface $em, Location $location, $gender = null)
    {
        if ($gender == 'Ram') {
            $typeFilter = " AND a.type = 'Ram'";
            $clazz = Ram::class;
        } elseif ($gender == 'Ewe') {
            $typeFilter = " AND a.type = 'Ewe'";
            $clazz = Ewe::class;
        } elseif ($gender == 'Neuter') {
            $typeFilter = " AND a.type = 'Neuter'";
            $clazz = Neuter::class;
        } else {
            $typeFilter = null;
            $clazz = Animal::class;
        }

        $sql = "SELECT * FROM animal a WHERE a.location_id = " . $location->getId() . " AND is_alive = TRUE AND a.transfer_state IS NULL" . $typeFilter;
        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, $clazz);
    }

    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @return null|Tag
     */
    public static function getRandomUnassignedTag(EntityManagerInterface $em, Location $location)
    {
        $ownerId = $location->getCompany()->getOwner()->getId();
        $locationId = $location->getId();
        $sql = "SELECT * FROM tag t WHERE t.owner_id = " . $ownerId . " AND t.location_id = " . $locationId . " AND tag_status = 'UNASSIGNED'";
        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, Tag::class);
    }

    /**
     * @param EntityManagerInterface $em
     * @param $results
     * @param $clazz
     * @return null|object|mixed
     */
    public static function getRandomItemFromResults(EntityManagerInterface $em, $results, $clazz)
    {
        $resultsSize = count($results);
        //null check
        if ($resultsSize == 0) {
            return null;
        }

        $result = null;
        $maximumRetries = 10;

        for ($i = 0; $i < $maximumRetries; $i++) {
            $choice = rand(0, $resultsSize - 1);
            $result = $em->getRepository($clazz)->find($results[$choice]['id']);

            if ($result != null) {
                return $result;
            }
        }
        return null;
    }


    /**
     * @param EntityManagerInterface $em
     * @param CacheService $cacheService
     * @param Location $location
     * @param $totalAnimalCount
     * @param $gender
     * @return array
     */
    public static function getAnimalsUlnsBody(EntityManagerInterface $em, CacheService $cacheService,
                                              Location $location, $totalAnimalCount, $gender = null)
    {
        $animals = $em->getRepository(Animal::class)->getLiveStock($location, $cacheService, true, $gender);

        $result = [];
        $animalCount = 0;
        /** @var Animal $animal */
        foreach ($animals as $animal) {
            $animalCount++;
            $result[] = [
              JsonInputConstant::ULN_COUNTRY_CODE => $animal->getUlnCountryCode(),
              JsonInputConstant::ULN_NUMBER => $animal->getUlnNumber(),
            ];

            if (++$animalCount >= $totalAnimalCount) {
                break;
            }
        }
        return $result;
    }
}