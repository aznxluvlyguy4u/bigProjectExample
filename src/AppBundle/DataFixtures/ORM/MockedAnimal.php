<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Setting\DataFixtureSetting;
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

    if(!DataFixtureSetting::USE_MOCKED_ANIMALS) {
      return null;
    }

    //Get mocked person
    $personRepository = $manager->getRepository(Constant::CLIENT_REPOSITORY);
    $persons = $personRepository->findAll();
    $companies = null;
    $company = 0;

    $person = $personRepository->getByRelationNumberKeeper(MockedClient::getMockedClient()->getRelationNumberKeeper());

    $companies = $person->getCompanies();
    if(sizeof($companies) > 0){
      $company = $companies->get(0);
    }

    //Get persons company location to add animals to.
    $location = $company->getLocations()->get(0);

    $tagRepository = $this->container->get('doctrine.orm.entity_manager')->getRepository(Constant::TAG_REPOSITORY);

    $tags = $tagRepository->findAll();
    $lowestTagId = $tags['0']->getId();
    $numberOfTagsUsed = 6;

    $offset = $numberOfTagsUsed * rand(0, ( (sizeof($tags)-$numberOfTagsUsed) /$numberOfTagsUsed)  );

    self::$mockedParentRam = new Ram();
    self::$mockedParentRam->setIsAlive(true);
    self::$mockedParentRam->setAnimalType(AnimalType::sheep);
    self::$mockedParentRam->setDateOfBirth(new \DateTime('2001-01-01'));
    self::$mockedParentRam->setPedigreeNumber("35645");
    self::$mockedParentRam->setPedigreeCountryCode("NL");
    self::$mockedParentRam->setLocation($location);
    $location->addAnimal(self::$mockedParentRam);

    $tag1 = $tagRepository->findOneBy(array("id"=>$lowestTagId+$offset+0));
    self::$mockedParentRam->setAssignedTag($tag1);
    $manager->persist(self::$mockedParentRam);
    $manager->flush();

    self::$mockedParentEwe = new Ewe();
    self::$mockedParentEwe->setIsAlive(true);
    self::$mockedParentEwe->setAnimalType(AnimalType::sheep);
    self::$mockedParentEwe->setDateOfBirth(new \DateTime('2002-02-02'));
    self::$mockedParentEwe->setPedigreeNumber("79164");
    self::$mockedParentEwe->setPedigreeCountryCode("NL");
    self::$mockedParentEwe->setLocation($location);

    $tag2 = $tagRepository->findOneBy(array("id"=>$lowestTagId+$offset+1));
    self::$mockedParentEwe->setAssignedTag($tag2);
    $manager->persist(self::$mockedParentEwe);
    $manager->flush();


    self::$MockedAnotherEwe = new Ewe();
    self::$MockedAnotherEwe->setIsAlive(true);
    self::$MockedAnotherEwe->setAnimalType(AnimalType::sheep);
    self::$MockedAnotherEwe->setDateOfBirth(new \DateTime('2003-03-03'));
    self::$MockedAnotherEwe->setPedigreeNumber("52350");
    self::$MockedAnotherEwe->setPedigreeCountryCode("NL");
    self::$MockedAnotherEwe->setLocation($location);

    $tag3 = $tagRepository->findOneBy(array("id"=>$lowestTagId+$offset+2));
    self::$MockedAnotherEwe->setAssignedTag($tag3);
    $manager->persist(self::$MockedAnotherEwe);
    $manager->flush();


    self::$mockedRamWithParents = new Ram();
    self::$mockedRamWithParents->setIsAlive(true);
    self::$mockedRamWithParents->setPedigreeNumber("12345");
    self::$mockedRamWithParents->setPedigreeCountryCode("NL");
    self::$mockedRamWithParents->setDateOfBirth(new \DateTime('2004-04-04'));
    self::$mockedRamWithParents->setAnimalType(AnimalType::sheep);
    self::$mockedRamWithParents->setParentFather(self::$mockedParentRam);
    self::$mockedRamWithParents->setParentMother(self::$mockedParentEwe);
    self::$mockedRamWithParents->setLocation($location);

    $tag4 = $tagRepository->findOneBy(array("id"=>$lowestTagId+$offset+3));
    self::$mockedRamWithParents->setAssignedTag($tag4);
    $manager->persist(self::$mockedRamWithParents);
    $manager->flush();


    self::$mockedNewBornRam = new Ram();
    self::$mockedNewBornRam->setIsAlive(true);
    self::$mockedNewBornRam->setAnimalType(AnimalType::sheep);
    self::$mockedNewBornRam->setDateOfBirth(new \DateTime('2005-05-05'));
    self::$mockedNewBornRam->setPedigreeNumber("65454");
    self::$mockedNewBornRam->setPedigreeCountryCode("NL");
    self::$mockedNewBornRam->setParentFather(self::$mockedParentRam);
    self::$mockedNewBornRam->setParentMother(self::$mockedParentEwe);
    self::$mockedNewBornRam->setLocation($location);

    $tag5 = $tagRepository->findOneBy(array("id"=>$lowestTagId+$offset+4));
    self::$mockedNewBornRam->setAssignedTag($tag5);
    $manager->persist(self::$mockedNewBornRam);
    $manager->flush();


    self::$mockedNewBornEwe = new Ewe();
    self::$mockedNewBornEwe->setIsAlive(true);
    self::$mockedNewBornEwe->setAnimalType(AnimalType::sheep);
    self::$mockedNewBornEwe->setDateOfBirth(new \DateTime('2005-05-05'));
    self::$mockedNewBornEwe->setPedigreeNumber("80902");
    self::$mockedNewBornEwe->setPedigreeCountryCode("NL");
    self::$mockedNewBornEwe->setParentFather(self::$mockedParentRam);
    self::$mockedNewBornEwe->setParentMother(self::$mockedParentEwe);
    self::$mockedNewBornEwe->setLocation($location);

    $tag6 = $tagRepository->findOneBy(array("id"=>$lowestTagId+$offset+5));
    self::$mockedNewBornEwe->setAssignedTag($tag6);
    $manager->persist(self::$mockedNewBornEwe);
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
}