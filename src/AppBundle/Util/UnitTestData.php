<?php


namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

class UnitTestData
{
    const ULN_COUNTRY_CODE = 'XZ';
    const UBN_OF_BIRTH = '1674459';
    const BREEDER_NUMBER = '00756';
    const PEDIGREE_REGISTER = 'TSNH';
    const BREED_TYPE = BreedType::PURE_BRED;
    const BREED_CODE = 'TE100';

    const RAM_ULN_NUMBER = '999999999998';
    const EWE_ULN_NUMBER = '999999999997';
    const TAG_ULN_NUMBER = '999999999996';

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
        return self::createTestAnimal($em, new Ewe(), self::RAM_ULN_NUMBER, $location);
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

        $animal->setLocation($location);
        $animal->setIsAlive(true);

        $em->persist($animal);
        $em->flush();

        return $animal;
    }


    /**
     * @param Location $location
     * @param string $ulnNumber
     * @param string $tagStatus
     * @return Tag
     */
    public static function createTag(Location $location,
                                     $ulnNumber = self::TAG_ULN_NUMBER,
                                     $tagStatus = TagStateType::UNASSIGNED)
    {
        $animalOrderNumber = StringUtil::getLast5CharactersFromString($ulnNumber);

        $tag = new Tag();
        $tag->setUlnCountryCode(self::ULN_COUNTRY_CODE);
        $tag->setUlnNumber($ulnNumber);
        $tag->setAnimalOrderNumber($animalOrderNumber);
        $tag->setTagStatus($tagStatus);
        $tag->setOrderDate(new \DateTime());

        $tag->setLocation($location);
        $tag->setOwner($location->getOwner());

        return $tag;
    }
}