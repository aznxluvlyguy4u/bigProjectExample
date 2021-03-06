<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DataFixturesEmployees implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var Employee
   */
  static private $employeeJVTRudolf;

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
   * Load data fixtures of employees
   *
   * @param ObjectManager $manager
   */
  public function load(ObjectManager $manager) {

    $encoder = $this->container->get('security.password_encoder');

    self::$employeeJVTRudolf = new Employee(AccessLevelType::SUPER_ADMIN);

    self::$employeeJVTRudolf->setFirstName('Rudolf');
    self::$employeeJVTRudolf->setLastName('Sneep');
    self::$employeeJVTRudolf->setEmailAddress('rudolf@jongensvantechniek.nl');

    $password = 'N93I92G9H09QN8C90EC0NHF';
    self::$employeeJVTRudolf->setPassword($encoder->encodePassword(self::$employeeJVTRudolf, $password));

      //persist data
      $manager->persist(self::$employeeJVTRudolf);
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
    return 1;
  }


  /**
   * @return Employee
   */
  public static function getEmployeeJVTRudolf()
  {
    return self::$employeeJVTRudolf;
  }



}