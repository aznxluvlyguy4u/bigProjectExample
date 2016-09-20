<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Processor;
use AppBundle\Setting\DataFixtureSetting;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DataFixturesRealProcessors implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

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
  static private $processors;

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
    if(!DataFixtureSetting::USE_REAL_PROCESSOR_DATA) {
      return null;
    }

    $processorRendac = new Processor('2486574', 'Rendac');
    $processorCVI = new Processor('2461025', 'CVI');
    $processorGD = new Processor('1803859', 'GD');

    $manager->persist($processorRendac);
    $manager->persist($processorCVI);
    $manager->persist($processorGD);
    $manager->flush();

    //Set countries list as mockedList
    self::$processors[] = $processorRendac;
    self::$processors[] = $processorCVI;
    self::$processors[] = $processorGD;
  }


  /**
   * @return array
   */
  public static function getProcessors()
  {
    return self::$processors;
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
}