<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Province;
use AppBundle\Setting\DataFixtureSetting;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DataFixturesRealProvinces implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var array
   */
  static private $provinces;

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
  public function load(ObjectManager $manager)
  {
    if(!DataFixtureSetting::USE_REAL_PROVINCES) {
      return null;
    }

    $em = $this->container->get('doctrine.orm.entity_manager');

    $nl = $em->getRepository(Constant::COUNTRY_REPOSITORY)->findOneBy(array('code' => 'NL'));

    //From https://onzetaal.nl/taaladvies/advies/afkortingen-van-provincienamen
    $drenthe = new Province($nl, 'Drenthe',  'DR');
    $flevoland = new Province($nl, 'Flevoland',  'FL');
    $friesland = new Province($nl, 'Friesland',  'FR');
    $gelderland = new Province($nl, 'Gelderland',  'GD');
    $groningen = new Province($nl, 'Groningen',  'GR');
    $limburg = new Province($nl, 'Limburg',  'LB');
    $noordBrabant = new Province($nl, 'Noord-Brabant',  'NB');
    $noordHolland = new Province($nl, 'Noord-Holland',  'NH');
    $overijssel = new Province($nl, 'Overijssel',  'OV');
    $utrecht = new Province($nl, 'Utrecht',  'UT');
    $zuidHolland = new Province($nl, 'Zuid-Holland',  'ZH');
    $zeeland = new Province($nl, 'Zeeland',  'ZL');

    self::$provinces[] = $drenthe;
    self::$provinces[] = $flevoland;
    self::$provinces[] = $friesland;
    self::$provinces[] = $gelderland;
    self::$provinces[] = $groningen;
    self::$provinces[] = $limburg;
    self::$provinces[] = $noordBrabant;
    self::$provinces[] = $noordHolland;
    self::$provinces[] = $overijssel;
    self::$provinces[] = $utrecht;
    self::$provinces[] = $zuidHolland;
    self::$provinces[] = $zeeland;

    foreach(self::$provinces as $province) {
      $manager->persist($province);
    }
    $manager->flush();
  }


  /**
   * @return array
   */
  public static function getProvinces()
  {
    return self::$provinces;
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