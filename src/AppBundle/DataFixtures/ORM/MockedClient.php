<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\LocationHealth;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Setting\DataFixtureSetting;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
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
   * @var Client
   */
  static private $mockedClientTwo;

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
   * Load data fixtures with the passed ObjectManager
   *
   * @param ObjectManager $manager
   */
  public function load(ObjectManager $manager) {

    if(!DataFixtureSetting::USE_MOCKED_CLIENT) {
      return null;
    }

    $encoder = $this->container->get('security.password_encoder');

    $ubn = '1674459';

    //Create mocked data
    self::$mockedClient = new Client();
    self::$mockedClient->setFirstName("Bart");
    self::$mockedClient->setLastName("de Boer");
    self::$mockedClient->setEmailAddress("bart@deboer.com");
    self::$mockedClient->setRelationNumberKeeper("203719934");  //NOTE! Echte RelationNumberKeeper nodig voor successvolle IenR melding!
    self::$mockedClient->setUsername($ubn);
    self::$mockedClient->setPassword($encoder->encodePassword(self::$mockedClient, "blauwetexelaar"));
    self::$mockedClient->setCellphoneNumber("+31698765432");

    //For easy testing, keep the AccessToken the same for each run
    self::$mockedClient->setAccessToken("81a7a57abacc29ea18fcd270978c76055148a094");

    $netherlandsName = 'Netherlands';
    $netherlands = $manager->getRepository(Country::class)->getCountryByName($netherlandsName);
    
    self::$mockedLocationAddress = new LocationAddress();
    self::$mockedLocationAddress->setAddressNumber("1");
    self::$mockedLocationAddress->setCity("Den Haag");
    self::$mockedLocationAddress->setPostalCode("1111AZ");
    self::$mockedLocationAddress->setState("ZH");
    self::$mockedLocationAddress->setStreetName("Boederij");
    self::$mockedLocationAddress->setCountryDetails($netherlands);

    self::$mockedBillingAddress = new BillingAddress();
    self::$mockedBillingAddress->setAddressNumber("2");
    self::$mockedBillingAddress->setCity("Den Haag");
    self::$mockedBillingAddress->setPostalCode("2222GG");
    self::$mockedBillingAddress->setState("ZH");
    self::$mockedBillingAddress->setStreetName("Raamweg");
    self::$mockedBillingAddress->setCountryDetails($netherlands);

    self::$mockedCompanyAddress = new CompanyAddress();
    self::$mockedCompanyAddress->setAddressNumber("3");
    self::$mockedCompanyAddress->setCity("Rotterdam");
    self::$mockedCompanyAddress->setPostalCode("3333XX");
    self::$mockedCompanyAddress->setState("ZH");
    self::$mockedCompanyAddress->setStreetName("Papierengeldweg");
    self::$mockedCompanyAddress->setCountryDetails($netherlands);

    self::$mockedCompany = new Company();
    self::$mockedCompany->setAddress(self::$mockedCompanyAddress);
    self::$mockedCompany->setBillingAddress(self::$mockedBillingAddress);
    self::$mockedCompany->setCompanyName("Boederij de weiland");
    self::$mockedCompany->setOwner(self::$mockedClient);
    self::$mockedCompany->setCompanyRelationNumber("111111");
    self::$mockedCompany->setChamberOfCommerceNumber("222222");
    self::$mockedCompany->setVatNumber("333333");
    self::$mockedCompany->setTelephoneNumber("+313131313131");

    $LocationHealth = new LocationHealth();
    $LocationHealth->setMaediVisnaStatus(MaediVisnaStatus::FREE_2_YEAR);
    $LocationHealth->setMaediVisnaEndDate(new \DateTime('2016-08-04'));
    $LocationHealth->setScrapieStatus(ScrapieStatus::RESISTANT);
    $LocationHealth->setScrapieEndDate(new \DateTime('2016-10-04'));
    $LocationHealth->setCheckDate(new \DateTime('2016-07-04'));

    self::$mockedLocation = new Location();
    self::$mockedLocation->setAddress(self::$mockedLocationAddress);
    self::$mockedLocation->setCompany(self::$mockedCompany);
    self::$mockedLocation->addHealth($LocationHealth);

    self::$mockedLocation->setUbn($ubn); //NOTE! Echte UBN nodig voor successvolle IenR melding!

    self::$mockedCompany->addLocation(self::$mockedLocation);
    self::$mockedClient->addCompany(self::$mockedCompany);


    if(DataFixtureSetting::USE_MOCKED_CLIENT_TWO) {
      $ubnTwo = '6182575'; //real ubn number
      $relationNumberKeeper = '444555666';

      //Create mocked data ClientTwo
      self::$mockedClientTwo = new Client();
      self::$mockedClientTwo->setFirstName("Sarah");
      self::$mockedClientTwo->setLastName("de Schapenherder");
      self::$mockedClientTwo->setEmailAddress("sarah@deboer.com");
      self::$mockedClientTwo->setRelationNumberKeeper($relationNumberKeeper);
      self::$mockedClientTwo->setUsername($ubnTwo);
      self::$mockedClientTwo->setPassword($encoder->encodePassword(self::$mockedClientTwo, "super-password"));

      $mockedLocationAddress = new LocationAddress();
      $mockedLocationAddress->setAddressNumber("56");
      $mockedLocationAddress->setCity("Sweetlake City");
      $mockedLocationAddress->setPostalCode("2222ZZ");
      $mockedLocationAddress->setState("ZH");
      $mockedLocationAddress->setStreetName("Streetroadlane");
      $mockedLocationAddress->setCountryDetails($netherlands);

      $mockedBillingAddress = new BillingAddress();
      $mockedBillingAddress->setAddressNumber("26556");
      $mockedBillingAddress->setCity("Den Haag");
      $mockedBillingAddress->setPostalCode("2222PP");
      $mockedBillingAddress->setState("ZH");
      $mockedBillingAddress->setStreetName("Raamweg");
      $mockedBillingAddress->setCountryDetails($netherlands);

      $mockedCompanyAddress = new CompanyAddress();
      $mockedCompanyAddress->setAddressNumber("3");
      $mockedCompanyAddress->setCity("Zoetermeer");
      $mockedCompanyAddress->setPostalCode("3333XX");
      $mockedCompanyAddress->setState("ZH");
      $mockedCompanyAddress->setStreetName("Het Geertje");
      $mockedCompanyAddress->setCountryDetails($netherlands);

      $mockedCompany = new Company();
      $mockedCompany->setAddress($mockedCompanyAddress);
      $mockedCompany->setBillingAddress($mockedBillingAddress);
      $mockedCompany->setCompanyName("Animal Farm");
      $mockedCompany->setOwner(self::$mockedClientTwo);

      $LocationHealthTwo = new LocationHealth();
      $LocationHealthTwo->setMaediVisnaStatus(MaediVisnaStatus::FREE_1_YEAR);
      $LocationHealthTwo->setMaediVisnaEndDate(new \DateTime('2016-11-04'));
      $LocationHealthTwo->setScrapieStatus(ScrapieStatus::RESISTANT);
      $LocationHealthTwo->setScrapieEndDate(new \DateTime('2016-12-04'));
      $LocationHealthTwo->setCheckDate(new \DateTime('2016-12-14'));

      $mockedLocation = new Location();
      $mockedLocation->setAddress($mockedLocationAddress);
      $mockedLocation->setCompany($mockedCompany);
      $mockedLocation->setUbn($ubnTwo);

      $mockedLocation->addHealth($LocationHealthTwo);

      $mockedCompany->addLocation($mockedLocation);
      self::$mockedClientTwo->addCompany($mockedCompany);

      $manager->persist(self::$mockedClientTwo);
      $manager->persist(self::$mockedClientTwo->getCompanies()->last()->getLocations()->last()->getHealths()->last());
    }
    
    //persist mocked data
    $manager->persist(self::$mockedClient);
    $manager->persist(self::$mockedClient->getCompanies()->last()->getLocations()->last()->getHealths()->last());
    $manager->flush();

    echo "\r\n" .'client accestoken: ' . self::$mockedClient->getAccessToken() . "\r\n\r\n";
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
   * @return Client
   */
  public static function getMockedClientTwo()
  {
    return self::$mockedClientTwo;
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