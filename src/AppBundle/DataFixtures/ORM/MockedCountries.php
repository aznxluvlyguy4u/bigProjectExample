<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Setting\DataFixtureSetting;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use AppBundle\Entity\Country;

class MockedCountries implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

  const LANDCODE_NAMESPACE = 'code';
  const LANDNAME_NAMESPACE = 'name';
  const UNKNOWN_NAMESPACE = "Unknown";
  const COUNTRIES_JSON_FILENAME = 'countries_v1.json';
  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var array
   */
  static private $mockedCountries;

  /**
   * Sets the container.
   *
   * @param ContainerInterface|null $container A ContainerInterface instance or null
   */
  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  /**
   * Load data fixtures with the passed ObjectManager
   *
   * @param ObjectManager $manager
   */
  public function load(ObjectManager $manager)
  {
    if(!DataFixtureSetting::USE_MOCKED_COUNTRIES) {
      return null;
    }

    $countries = null;

    //Read in json file
    $finder = new Finder();
    $finder->files()->in(__DIR__);

    $finder->files()->name($this::COUNTRIES_JSON_FILENAME);

    //Decode json to array
    foreach ($finder as $file) {
      $contents = $file->getContents();
      $countries = json_decode($contents, true);
    }

    //Persist country item in array
    foreach($countries as $countryItem) {
      $country = new Country();

      if (empty($countryItem[Constant::CONTINENT_NAMESPACE])) {
        $country->setContinent($this::UNKNOWN_NAMESPACE);
      }
      else {
        $country->setContinent($countryItem[Constant::CONTINENT_NAMESPACE]);
      }
      $country->setCode($countryItem[$this::LANDCODE_NAMESPACE]);
      $country->setName($countryItem[$this::LANDNAME_NAMESPACE]);

      //persist mocked data
      $manager->persist($country);

    }

    $manager->flush();

    //Set countries list as mockedList
    self::$mockedCountries = $countries;
  }


  /**
   * @return array
   */
  public static function getMockedCountriesList()
  {
    return self::$mockedCountries;
  }

  /**
   * Get the order of this fixture
   *
   * @return integer
   */
  /**
   *  The order in which fixtures will be loaded, the lower the number,
   *  the sooner that this fixture is loaded
   *
   * @return int
   */
  public function getOrder()
  {
    return 1;
  }
}