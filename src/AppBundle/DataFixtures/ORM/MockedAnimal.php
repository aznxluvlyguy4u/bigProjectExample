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
    $tags = MockedTags::getMockedTags();
    $tagListSize = sizeof($tags);

    self::$mockedParentRam = new Ram();
    self::$mockedParentRam->setIsAlive(true);
    self::$mockedParentRam->setAssignedTag($tags->get(rand(1,$tagListSize-1)));
    self::$mockedParentRam->setAnimalType(AnimalType::sheep);
    self::$mockedParentRam->setDateOfBirth(new \DateTime());
    $location->addAnimal(self::$mockedParentRam);

    self::$mockedParentEwe = new Ewe();
    self::$mockedParentEwe->setIsAlive(true);
    self::$mockedParentEwe->setAssignedTag($tags->get(rand(1,$tagListSize-1)));
    self::$mockedParentEwe->setAnimalType(AnimalType::sheep);
    self::$mockedParentEwe->setDateOfBirth(new \DateTime());
    $location->addAnimal(self::$mockedParentEwe);

    self::$mockedNewBornRam = new Ram();
    self::$mockedNewBornRam->setIsAlive(true);
    self::$mockedNewBornRam->setAssignedTag($tags->get(rand(1,$tagListSize-1)));
    self::$mockedNewBornRam->setAnimalType(AnimalType::sheep);
    self::$mockedNewBornRam->setDateOfBirth(new \DateTime());
    self::$mockedNewBornRam->setParentFather(self::$mockedParentRam);
    self::$mockedNewBornRam->setParentMother(self::$mockedParentEwe);
    $location->addAnimal(self::$mockedNewBornRam);

    self::$mockedRamWithParents = new Ram();
    self::$mockedRamWithParents->setIsAlive(true);

    $randomIndex = rand(1, $tagListSize-1);
    $tagToAssign = $tags->get($randomIndex);
    $tagToAssign->setAnimal(self::$mockedRamWithParents);
    self::$mockedRamWithParents->setAssignedTag($tagToAssign);

    self::$mockedRamWithParents->setPedigreeNumber("12345");
    self::$mockedRamWithParents->setPedigreeCountryCode("NL");
    self::$mockedRamWithParents->setAnimalType(AnimalType::sheep);
    self::$mockedRamWithParents->setDateOfBirth(new \DateTime());
    self::$mockedRamWithParents->setParentFather(self::$mockedParentRam);
    self::$mockedRamWithParents->setParentMother(self::$mockedParentEwe);
    $location->addAnimal(self::$mockedRamWithParents);

    //Persist mocked data
    $manager->persist(self::$mockedRamWithParents);
    $manager->persist(self::$mockedNewBornRam);
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
}