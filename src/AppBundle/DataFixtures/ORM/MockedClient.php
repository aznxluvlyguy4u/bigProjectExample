<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Company;
use AppBundle\Entity\Client;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedClient implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var Client
   */
  static private $mockedClient;

  /**
   * @var LocationAddress
   */
  static private $mockedLocationAddress;

  /**
   * @var BillingAddress
   */
  static private $mockedBillingAddress;

  /**
   * @var CompanyAddress
   */
  static private $mockedCompanyAddress;

  /**
   * @var Company
   */
  static private $mockedCompany;

  /**
   * @var Location
   */
  static private $mockedLocation;
  
  
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
    $encoder = $this->container->get('security.password_encoder');

    //Create mocked data
    self::$mockedClient = new Client();
    self::$mockedClient->setFirstName("Bart");
    self::$mockedClient->setLastName("de Boer");
    self::$mockedClient->setEmailAddress("bart@deboer.com");
    self::$mockedClient->setRelationNumberKeeper("203719934");
    self::$mockedClient->setUsername("Bartje");
    self::$mockedClient->setPassword($encoder->encodePassword(self::$mockedClient, "blauwetexelaar"));

    self::$mockedLocationAddress = new LocationAddress();
    self::$mockedLocationAddress->setAddressNumber("1");
    self::$mockedLocationAddress->setCity("Den Haag");
    self::$mockedLocationAddress->setPostalCode("1111AZ");
    self::$mockedLocationAddress->setState("ZH");
    self::$mockedLocationAddress->setStreetName("Boederij");
    self::$mockedLocationAddress->setCountry("Nederland");

    self::$mockedBillingAddress = new BillingAddress();
    self::$mockedBillingAddress->setAddressNumber("2");
    self::$mockedBillingAddress->setCity("Den Haag");
    self::$mockedBillingAddress->setPostalCode("2222GG");
    self::$mockedBillingAddress->setState("ZH");
    self::$mockedBillingAddress->setStreetName("Raamweg");
    self::$mockedBillingAddress->setCountry("Nederland");

    self::$mockedCompanyAddress = new CompanyAddress();
    self::$mockedCompanyAddress->setAddressNumber("3");
    self::$mockedCompanyAddress->setCity("Rotterdam");
    self::$mockedCompanyAddress->setPostalCode("3333XX");
    self::$mockedCompanyAddress->setState("ZH");
    self::$mockedCompanyAddress->setStreetName("Papierengeldweg");
    self::$mockedCompanyAddress->setCountry("Nederland");

    self::$mockedCompany = new Company();
    self::$mockedCompany->setAddress(self::$mockedCompanyAddress);
    self::$mockedCompany->setBillingAddress(self::$mockedBillingAddress);
    self::$mockedCompany->setCompanyName("Boederij de weiland");
    self::$mockedCompany->setOwner(self::$mockedClient);

    self::$mockedLocation = new Location();
    self::$mockedLocation->setAddress(self::$mockedLocationAddress);
    self::$mockedLocation->setCompany(self::$mockedCompany);
    self::$mockedLocation->setUbn("1674459");

    self::$mockedCompany->addLocation(self::$mockedLocation);
    self::$mockedClient->addCompany(self::$mockedCompany);

    //persist mocked data
    $manager->persist(self::$mockedClient);
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
   * @return Client
   */
  public static function getMockedClient()
  {
    return self::$mockedClient;
  }

  /**
   * @return LocationAddress
   */
  public static function getMockedLocationAddress()
  {
    return self::$mockedLocationAddress;
  }

  /**
   * @return BillingAddress
   */
  public static function getMockedBillingAddress()
  {
    return self::$mockedBillingAddress;
  }

  /**
   * @return CompanyAddress
   */
  public static function getMockedCompanyAddress()
  {
    return self::$mockedCompanyAddress;
  }

  /**
   * @return Company
   */
  public static function getMockedCompany()
  {
    return self::$mockedCompany;
  }

  /**
   * @return Location
   */
  public static function getMockedLocation()
  {
    return self::$mockedLocation;
  }


}