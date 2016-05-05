<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use AppBundle\Entity\Country;

class MockedCountries implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

  /**
   * @var ContainerInterface
   */
  private $container;


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
   * Load data fixtures with the passed EntityManager
   *
   * @param ObjectManager $manager
   */
  public function load(ObjectManager $manager) {

    $countries = null;

    //Read in json file
    $finder = new Finder();
    $finder->files()->in(__DIR__);

    $finder->files()->name('countries_v1.json');

    //Decode json to array
    foreach ($finder as $file) {
      $contents = $file->getContents();
      $countries = json_decode($contents, true);
    }

    //Persist country item in array
    foreach($countries as $countryItem) {
      $country = new Country();

      if (empty($countryItem['continent'])) {
        $country->setContinent('Unknown');
      }
      else {
        $country->setContinent($countryItem['continent']);
      }
      $country->setCode($countryItem['code']);
      $country->setName($countryItem['name']);

      $manager->persist($country);

    }

    //persist mocked data
    $manager->flush();
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
    return 3;
  }
}