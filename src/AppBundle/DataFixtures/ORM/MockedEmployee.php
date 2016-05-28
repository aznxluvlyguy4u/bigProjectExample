<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Employee;
use AppBundle\Setting\DataFixtureSetting;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedEmployee implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var Employee
   */
  static private $mockedEmployee;
  
  
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

    if(!DataFixtureSetting::USE_MOCKED_EMPLOYEE) {
      return null;
    }

    $encoder = $this->container->get('security.password_encoder');

    //Create mocked data
    self::$mockedEmployee = new Employee();
    self::$mockedEmployee->setFirstName("em");
    self::$mockedEmployee->setLastName("ployee");
    self::$mockedEmployee->setEmailAddress("boer@jongensvantechniek.nl");
    self::$mockedEmployee->setUsername("employeetestaccount");
    self::$mockedEmployee->setPassword($encoder->encodePassword(self::$mockedEmployee, "test"));
    self::$mockedEmployee->setCellphoneNumber("+31612345678");

    //For easy testing, keep the AccessToken the same for each run
    self::$mockedEmployee->setAccessToken("92a7a57abacc29ea18fcd270978c76055148a094");
    
    //persist mocked data
    $manager->persist(self::$mockedEmployee);
    $manager->flush();

    echo "\r\n" .'employee accestoken: ' . self::$mockedEmployee->getAccessToken() . "\r\n\r\n";
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
  public static function getMockedEmployee()
  {
    return self::$mockedEmployee;
  }


}