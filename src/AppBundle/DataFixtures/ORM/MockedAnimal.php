<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
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
    self::$mockedParentRam = new Ram();
    self::$mockedParentRam->setUlnCountryCode("NL");
    self::$mockedParentRam->setUlnNumber("11111111");
    self::$mockedParentRam->setAnimalType(AnimalType::sheep);
    self::$mockedParentRam->setDateOfBirth(new \DateTime());

    self::$mockedParentEwe = new Ewe();
    self::$mockedParentEwe->setUlnCountryCode("NL");
    self::$mockedParentEwe->setUlnNumber("222222222");
    self::$mockedParentEwe->setAnimalType(AnimalType::sheep);
    self::$mockedParentEwe->setDateOfBirth(new \DateTime());

    self::$mockedRamWithParents = new Ram();
    self::$mockedRamWithParents->setUlnCountryCode("UK");
    self::$mockedRamWithParents->setUlnNumber("333333333");
    self::$mockedRamWithParents->setPedigreeNumber("12345");
    self::$mockedRamWithParents->setPedigreeCountryCode("NL");
    self::$mockedRamWithParents->setAnimalType(AnimalType::sheep);
    self::$mockedRamWithParents->setDateOfBirth(new \DateTime());
    self::$mockedRamWithParents->setParentFather(self::$mockedParentRam);
    self::$mockedRamWithParents->setParentMother(self::$mockedParentEwe);

    //Persist mocked data
    $manager->persist(self::$mockedRamWithParents);
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
    return 2;
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
  
}