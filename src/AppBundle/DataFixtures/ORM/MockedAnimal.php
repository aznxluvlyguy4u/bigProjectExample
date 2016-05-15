<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Enumerator\AnimalType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedAnimal implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface  {

  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var Ram
   */
  static private $mockedRamWithParents;

  /**
   * @var Ram
   */
  static private $mockedParentRam;

  /**
   * @var Ewe
   */
  static private $mockedParentEwe;

  /**
   * @var Ram
   */
  static private $mockedNewBornRam;

  /**
   * @var Ram
   */
  static private $mockedNewBornEwe;

  /**
   * @var Ewe
   */
  static private $MockedAnotherEwe;

  /**
   * @var array
   */
  static private $tag = array();

  /**
   * Sets the container.
   *
   * @param ContainerInterface|null $container A ContainerInterface instance or null
   */
  /**
   * @param ContainerInterface|null $container
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  /**
   * Load data fixtures with the passed EntityManager
   *
   * @param ObjectManager $manager
   */
  public function load(ObjectManager $manager) {

    //Get mocked person
    $personRepository = $manager->getRepository(Constant::CLIENT_REPOSITORY);
    $persons = $personRepository->findAll();
    $companies = null;
    $company = 0;

    if(sizeof($persons) > 0) {
      $person = $persons[0];
      $companies = $person->getCompanies();
      if(sizeof($companies) > 0){
        $company = $companies->get(0);
      }
    }

    //Get persons company location to add animals to.
    $location = $company->getLocations()->get(0);

    //Get mocked tags, assign it to mocked animals
    self::setTagStatusAssignedAndAddToTagArray(6);

    self::$mockedParentRam = new Ram();
    self::$mockedParentRam->setIsAlive(true);
    self::$mockedParentRam->setAnimalType(AnimalType::sheep);
    self::$mockedParentRam->setDateOfBirth(new \DateTime('2001-01-01'));
    self::$mockedParentRam->setPedigreeNumber("35645");
    self::$mockedParentRam->setPedigreeCountryCode("NL");
    self::$mockedParentRam->setAssignedTag(self::$tag[1]);
    self::$mockedParentRam->setLocation($location);

    self::$mockedParentEwe = new Ewe();
    self::$mockedParentEwe->setIsAlive(true);
    self::$mockedParentEwe->setAnimalType(AnimalType::sheep);
    self::$mockedParentEwe->setDateOfBirth(new \DateTime('2002-02-02'));
    self::$mockedParentEwe->setPedigreeNumber("79164");
    self::$mockedParentEwe->setPedigreeCountryCode("NL");
    self::$mockedParentEwe->setAssignedTag(self::$tag[2]);
    self::$mockedParentEwe->setLocation($location);

    self::$MockedAnotherEwe = new Ewe();
    self::$MockedAnotherEwe->setIsAlive(true);
    self::$MockedAnotherEwe->setAnimalType(AnimalType::sheep);
    self::$MockedAnotherEwe->setDateOfBirth(new \DateTime('2003-03-03'));
    self::$MockedAnotherEwe->setPedigreeNumber("52350");
    self::$MockedAnotherEwe->setPedigreeCountryCode("NL");
    self::$MockedAnotherEwe->setAssignedTag(self::$tag[3]);
    self::$MockedAnotherEwe->setLocation($location);

    self::$mockedRamWithParents = new Ram();
    self::$mockedRamWithParents->setIsAlive(true);
    self::$mockedRamWithParents->setPedigreeNumber("12345");
    self::$mockedRamWithParents->setPedigreeCountryCode("NL");
    self::$mockedRamWithParents->setDateOfBirth(new \DateTime('2004-04-04'));
    self::$mockedRamWithParents->setAnimalType(AnimalType::sheep);
    self::$mockedRamWithParents->setParentFather(self::$mockedParentRam);
    self::$mockedRamWithParents->setParentMother(self::$mockedParentEwe);
    self::$mockedRamWithParents->setAssignedTag(self::$tag[4]);
    self::$mockedRamWithParents->setLocation($location);

    self::$mockedNewBornRam = new Ram();
    self::$mockedNewBornRam->setIsAlive(true);
    self::$mockedNewBornRam->setAnimalType(AnimalType::sheep);
    self::$mockedNewBornRam->setDateOfBirth(new \DateTime('2005-05-05'));
    self::$mockedNewBornRam->setPedigreeNumber("65454");
    self::$mockedNewBornRam->setPedigreeCountryCode("NL");
    self::$mockedNewBornRam->setParentFather(self::$mockedParentRam);
    self::$mockedNewBornRam->setParentMother(self::$mockedParentEwe);
    self::$mockedNewBornRam->setAssignedTag(self::$tag[5]);
    self::$mockedNewBornRam->setLocation($location);

    self::$mockedNewBornEwe = new Ewe();
    self::$mockedNewBornEwe->setIsAlive(true);
    self::$mockedNewBornEwe->setAnimalType(AnimalType::sheep);
    self::$mockedNewBornEwe->setDateOfBirth(new \DateTime('2005-05-05'));
    self::$mockedNewBornEwe->setPedigreeNumber("80902");
    self::$mockedNewBornEwe->setPedigreeCountryCode("NL");
    self::$mockedNewBornEwe->setParentFather(self::$mockedParentRam);
    self::$mockedNewBornEwe->setParentMother(self::$mockedParentEwe);
    self::$mockedNewBornEwe->setAssignedTag(self::$tag[6]);
    self::$mockedNewBornEwe->setLocation($location);

    //Persist mocked data
    $manager->persist(self::$mockedRamWithParents);
    $manager->persist(self::$mockedNewBornRam);
    $manager->persist(self::$mockedNewBornEwe);
    $manager->persist(self::$MockedAnotherEwe);
    $manager->flush();

  }

  /**
   *  The order in which fixtures will be loaded, the lower the number,
   *  the sooner that this fixture is loaded
   *
   * @return int
   */
  public function getOrder()
  {
    return 3;
  }

  /**
   * @return Ram
   */
  public static function getMockedRamWithParents()
  {
    return self::$mockedRamWithParents;
  }

  /**
   * @return Ram
   */
  public static function getMockedParentRam()
  {
    return self::$mockedParentRam;
  }

  /**
   * @return Ewe
   */
  public static function getMockedParentEwe()
  {
    return self::$mockedParentEwe;
  }

  /**
   * @return Ram
   */
  public static function getMockedNewBornRam()
  {
    return self::$mockedNewBornRam;
  }

  /**
   * @return Ram
   */
  public static function getMockedNewBornEwe()
  {
    return self::$mockedNewBornEwe;
  }

  /**
   * @return Ewe
   */
  public static function getMockedAnotherEwe()
  {
      return self::$MockedAnotherEwe;
  }

  private static function setTagStatusAssignedAndAddToTagArray($tagCount)
  {
    $mockedTags = MockedTags::getMockedTags();
    $partSize = sizeof($mockedTags)/$tagCount;

    for($i = 1; $i<=$tagCount; $i++) {
      $tag = $mockedTags->get(rand(($i-1)*$partSize,$i*$partSize-2));
      $tag->setTagStatus(Constant::ASSIGNED_NAMESPACE);
      self::$tag[$i] = $tag;
    }
  }
}