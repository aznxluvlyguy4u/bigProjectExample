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
  static public $mockedClient;

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
    self::$mockedClient->setRelationNumberKeeper("77777444");
    self::$mockedClient->setUsername("Bartje");
    self::$mockedClient->setPassword($encoder->encodePassword(self::$mockedClient, "blauwetexelaar"));

    $locationAddress = new LocationAddress();
    $locationAddress->setAddressNumber("1");
    $locationAddress->setCity("Den Haag");
    $locationAddress->setPostalCode("1111AZ");
    $locationAddress->setState("ZH");
    $locationAddress->setStreetName("Boederij");
    $locationAddress->setCountry("Nederland");

    $billingAddress = new BillingAddress();
    $billingAddress->setAddressNumber("2");
    $billingAddress->setCity("Den Haag");
    $billingAddress->setPostalCode("2222GG");
    $billingAddress->setState("ZH");
    $billingAddress->setStreetName("Raamweg");
    $billingAddress->setCountry("Nederland");

    $companyAddress = new CompanyAddress();
    $companyAddress->setAddressNumber("3");
    $companyAddress->setCity("Rotterdam");
    $companyAddress->setPostalCode("3333XX");
    $companyAddress->setState("ZH");
    $companyAddress->setStreetName("Papierengeldweg");
    $companyAddress->setCountry("Nederland");

    $company = new Company();
    $company->setAddress($companyAddress);
    $company->setBillingAddress($billingAddress);
    $company->setCompanyName("Boederij de weiland");
    $company->setOwner(self::$mockedClient);

    $location = new Location();
    $location->setAddress($locationAddress);
    $location->setCompany($company);
    $location->setUbn("98989898");

    $company->addLocation($location);
    self::$mockedClient->addCompany($company);

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
}