<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\LocationHealthStatus;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Setting\DataFixtureSetting;
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

class DataFixturesRealClients implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface {

  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var Client
   */
  static private $janVanRijnsbergen;

  /**
   * @var Client
   */
  static private $reinardEverts;

  /**
   * @var Client
   */
  static private $nsfoTestAccount;

  /**
   * @var Client
   */
  static private $andreVanDenOuden;

  /**
   * @var Client
   */
  static private $henkVerheul;

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
   * Load data fixtures of real Clients
   *
   * @param ObjectManager $manager
   */
  public function load(ObjectManager $manager) {

    if(!DataFixtureSetting::USE_REAL_CLIENT_DATA) {
      return null;
    }

    $encoder = $this->container->get('security.password_encoder');

    $ubnJanVanRijnsbergen = '1593729';
    $ubnReinardEverts = '1674459';
    $ubnNsfoTestAccount = '1111111'; //TODO insert real data

    $emailJanVanRijnsbergen = 'jan@nsfo.nl';
    $emailReinardEverts = 'dierenarts@nsfo.nl';
    $emailNsfoTestAccount = 'kantoor@nsfo.nl';

    $passwordJanVanRijnsbergen = '12345';
    $passwordReinardEverts = '12345';
    $passwordNsfoTestAccount = '12345';

    $relationNumberKeeperJanVanRijnsbergen = '60181397'; //testdata TODO insert real data
    $relationNumberKeeperReinardEverts = '203719934'; //NOTE! Echte RelationNumberKeeper nodig voor successvolle IenR melding!
    $relationNumberKeeperNsfoTestAccount = '222222222'; //testdata TODO insert real data


    //Persoonsgegevens JVR
    self::$janVanRijnsbergen = new Client();
    self::$janVanRijnsbergen->setFirstName("Jan");
    self::$janVanRijnsbergen->setLastName("van Rijnsbergen");
    self::$janVanRijnsbergen->setEmailAddress($emailJanVanRijnsbergen);
    self::$janVanRijnsbergen->setRelationNumberKeeper($relationNumberKeeperJanVanRijnsbergen);
    self::$janVanRijnsbergen->setUsername('J.G. van Rijnsbergen');
    self::$janVanRijnsbergen->setPassword($encoder->encodePassword(self::$janVanRijnsbergen, $passwordJanVanRijnsbergen));
    self::$janVanRijnsbergen->setCellphoneNumber("");

    $janVanRijnsbergenLocationAddress = new LocationAddress();
    $janVanRijnsbergenLocationAddress->setAddressNumber("1");
    $janVanRijnsbergenLocationAddress->setCity("Stad A");
    $janVanRijnsbergenLocationAddress->setPostalCode("1111XX");
    $janVanRijnsbergenLocationAddress->setState("ZH");
    $janVanRijnsbergenLocationAddress->setStreetName("Straat A");
    $janVanRijnsbergenLocationAddress->setCountry("Nederland");

    $janVanRijnsbergenBillingAddress = new BillingAddress();
    $janVanRijnsbergenBillingAddress->setAddressNumber("2");
    $janVanRijnsbergenBillingAddress->setCity("Stad B");
    $janVanRijnsbergenBillingAddress->setPostalCode("2222XX");
    $janVanRijnsbergenBillingAddress->setState("ZH");
    $janVanRijnsbergenBillingAddress->setStreetName("Straat B");
    $janVanRijnsbergenBillingAddress->setCountry("Nederland");

    $janVanRijnsbergenCompanyAddress = new CompanyAddress();
    $janVanRijnsbergenCompanyAddress->setAddressNumber("3");
    $janVanRijnsbergenCompanyAddress->setCity("Stad C");
    $janVanRijnsbergenCompanyAddress->setPostalCode("3333XX");
    $janVanRijnsbergenCompanyAddress->setState("ZH");
    $janVanRijnsbergenCompanyAddress->setStreetName("Straat C");
    $janVanRijnsbergenCompanyAddress->setCountry("Nederland");

    $janVanRijnsbergenCompany = new Company();
    $janVanRijnsbergenCompany->setAddress($janVanRijnsbergenCompanyAddress);
    $janVanRijnsbergenCompany->setBillingAddress($janVanRijnsbergenBillingAddress);
    $janVanRijnsbergenCompany->setCompanyName("Naam van Bedrijf A");
    $janVanRijnsbergenCompany->setOwner(self::$janVanRijnsbergen);
    $janVanRijnsbergenCompany->setCompanyRelationNumber("111111");
    $janVanRijnsbergenCompany->setChamberOfCommerceNumber("222222");
    $janVanRijnsbergenCompany->setVatNumber("333333");
    $janVanRijnsbergenCompany->setTelephoneNumber("0313131313");
    $janVanRijnsbergenCompany->setAnimalHealthSubscription(true);
    $janVanRijnsbergenCompany->setIsActive(true);

    $janVanRijnsbergenLocationHealth = new LocationHealth();
    $janVanRijnsbergenScrapie = new Scrapie(ScrapieStatus::FREE, new \DateTime('2017-08-04'));
    $janVanRijnsbergenMaediVisna = new MaediVisna(MaediVisnaStatus::FREE, new \DateTime('2017-01-03'));
    $janVanRijnsbergenScrapie->setCheckDate(new \DateTime('2016-06-01'));
    $janVanRijnsbergenMaediVisna->setCheckDate(new \DateTime('2016-06-01'));
    $janVanRijnsbergenLocationHealth->setCurrentMaediVisnaStatus($janVanRijnsbergenMaediVisna->getStatus());
    $janVanRijnsbergenLocationHealth->setCurrentMaediVisnaEndDate($janVanRijnsbergenMaediVisna->getEndDate());
    $janVanRijnsbergenLocationHealth->setCurrentScrapieStatus($janVanRijnsbergenScrapie->getStatus());
    $janVanRijnsbergenLocationHealth->setCurrentScrapieEndDate($janVanRijnsbergenScrapie->getEndDate());
    $janVanRijnsbergenScrapie->setLocationHealth($janVanRijnsbergenLocationHealth);
    $janVanRijnsbergenMaediVisna->setLocationHealth($janVanRijnsbergenLocationHealth);
    $janVanRijnsbergenLocationHealth->addScrapie($janVanRijnsbergenScrapie);
    $janVanRijnsbergenLocationHealth->addMaediVisna($janVanRijnsbergenMaediVisna);

    $janVanRijnsbergenLocation = new Location();
    $janVanRijnsbergenLocation->setIsActive(true);
    $janVanRijnsbergenLocation->setAddress($janVanRijnsbergenLocationAddress);
    $janVanRijnsbergenLocation->setCompany($janVanRijnsbergenCompany);
    $janVanRijnsbergenLocation->setLocationHealth($janVanRijnsbergenLocationHealth);

    $janVanRijnsbergenLocation->setUbn($ubnJanVanRijnsbergen); //NOTE! Echte UBN nodig voor successvolle IenR melding!

    $janVanRijnsbergenCompany->addLocation($janVanRijnsbergenLocation);
    self::$janVanRijnsbergen->addCompany($janVanRijnsbergenCompany);
    
    //
    
    self::$reinardEverts = new Client();
    self::$reinardEverts->setFirstName("Reinard");
    self::$reinardEverts->setLastName("Everts");
    self::$reinardEverts->setEmailAddress($emailReinardEverts);
    self::$reinardEverts->setRelationNumberKeeper($relationNumberKeeperReinardEverts);
    self::$reinardEverts->setUsername('R. Everts');
    self::$reinardEverts->setPassword($encoder->encodePassword(self::$reinardEverts, $passwordReinardEverts));
    self::$reinardEverts->setCellphoneNumber("");

    $reinardEvertsLocationAddress = new LocationAddress();
    $reinardEvertsLocationAddress->setAddressNumber("1");
    $reinardEvertsLocationAddress->setCity("Stad A");
    $reinardEvertsLocationAddress->setPostalCode("1111XX");
    $reinardEvertsLocationAddress->setState("ZH");
    $reinardEvertsLocationAddress->setStreetName("Straat A");
    $reinardEvertsLocationAddress->setCountry("Nederland");

    $reinardEvertsBillingAddress = new BillingAddress();
    $reinardEvertsBillingAddress->setAddressNumber("2");
    $reinardEvertsBillingAddress->setCity("Stad B");
    $reinardEvertsBillingAddress->setPostalCode("2222XX");
    $reinardEvertsBillingAddress->setState("ZH");
    $reinardEvertsBillingAddress->setStreetName("Straat B");
    $reinardEvertsBillingAddress->setCountry("Nederland");

    $reinardEvertsCompanyAddress = new CompanyAddress();
    $reinardEvertsCompanyAddress->setAddressNumber("3");
    $reinardEvertsCompanyAddress->setCity("Stad C");
    $reinardEvertsCompanyAddress->setPostalCode("3333XX");
    $reinardEvertsCompanyAddress->setState("ZH");
    $reinardEvertsCompanyAddress->setStreetName("Straat C");
    $reinardEvertsCompanyAddress->setCountry("Nederland");

    $reinardEvertsCompany = new Company();
    $reinardEvertsCompany->setAddress($reinardEvertsCompanyAddress);
    $reinardEvertsCompany->setBillingAddress($reinardEvertsBillingAddress);
    $reinardEvertsCompany->setCompanyName("Naam van Bedrijf A");
    $reinardEvertsCompany->setOwner(self::$reinardEverts);
    $reinardEvertsCompany->setCompanyRelationNumber("111111");
    $reinardEvertsCompany->setChamberOfCommerceNumber("222222");
    $reinardEvertsCompany->setVatNumber("333333");
    $reinardEvertsCompany->setTelephoneNumber("0313131315");
    $reinardEvertsCompany->setAnimalHealthSubscription(true);
    $reinardEvertsCompany->setIsActive(true);

    $reinardEvertsLocationHealth = new LocationHealth();
    $reinardEvertsScrapie = new Scrapie(ScrapieStatus::RESISTANT, new \DateTime('2016-10-04'));
    $reinardEvertsMaediVisna = new MaediVisna(MaediVisnaStatus::FREE_2_YEAR, new \DateTime('2016-08-04'));
    $reinardEvertsScrapie->setCheckDate(new \DateTime('2016-06-01'));
    $reinardEvertsMaediVisna->setCheckDate(new \DateTime('2016-06-01'));
    $reinardEvertsLocationHealth->setCurrentMaediVisnaEndDate($reinardEvertsMaediVisna->getEndDate());
    $reinardEvertsLocationHealth->setCurrentScrapieStatus($reinardEvertsScrapie->getStatus());
    $reinardEvertsLocationHealth->setCurrentMaediVisnaStatus($reinardEvertsMaediVisna->getStatus());
    $reinardEvertsLocationHealth->setCurrentScrapieEndDate($reinardEvertsScrapie->getEndDate());
    $reinardEvertsScrapie->setLocationHealth($reinardEvertsLocationHealth);
    $reinardEvertsMaediVisna->setLocationHealth($reinardEvertsLocationHealth);
    $reinardEvertsLocationHealth->addScrapie($reinardEvertsScrapie);
    $reinardEvertsLocationHealth->addMaediVisna($reinardEvertsMaediVisna);

    $reinardEvertsLocation = new Location();
    $reinardEvertsLocation->setIsActive(true);
    $reinardEvertsLocation->setAddress($reinardEvertsLocationAddress);
    $reinardEvertsLocation->setCompany($reinardEvertsCompany);
    $reinardEvertsLocation->setLocationHealth($reinardEvertsLocationHealth);

    $reinardEvertsLocation->setUbn($ubnReinardEverts); //NOTE! Echte UBN nodig voor successvolle IenR melding!

    $reinardEvertsCompany->addLocation($reinardEvertsLocation);
    self::$reinardEverts->addCompany($reinardEvertsCompany);
    
    //
        
    self::$nsfoTestAccount = new Client();
    self::$nsfoTestAccount->setFirstName("NSFO");
    self::$nsfoTestAccount->setLastName("Testaccount");
    self::$nsfoTestAccount->setEmailAddress($emailNsfoTestAccount);
    self::$nsfoTestAccount->setRelationNumberKeeper($relationNumberKeeperNsfoTestAccount);
    self::$nsfoTestAccount->setUsername('testaccount gebruiker');
    self::$nsfoTestAccount->setPassword($encoder->encodePassword(self::$nsfoTestAccount, $passwordNsfoTestAccount));
    self::$nsfoTestAccount->setCellphoneNumber("");

    $nsfoTestAccountLocationAddress = new LocationAddress();
    $nsfoTestAccountLocationAddress->setAddressNumber("1");
    $nsfoTestAccountLocationAddress->setCity("Stad A");
    $nsfoTestAccountLocationAddress->setPostalCode("1111XX");
    $nsfoTestAccountLocationAddress->setState("ZH");
    $nsfoTestAccountLocationAddress->setStreetName("Straat A");
    $nsfoTestAccountLocationAddress->setCountry("Nederland");

    $nsfoTestAccountBillingAddress = new BillingAddress();
    $nsfoTestAccountBillingAddress->setAddressNumber("2");
    $nsfoTestAccountBillingAddress->setCity("Stad B");
    $nsfoTestAccountBillingAddress->setPostalCode("2222XX");
    $nsfoTestAccountBillingAddress->setState("ZH");
    $nsfoTestAccountBillingAddress->setStreetName("Straat B");
    $nsfoTestAccountBillingAddress->setCountry("Nederland");

    $nsfoTestAccountCompanyAddress = new CompanyAddress();
    $nsfoTestAccountCompanyAddress->setAddressNumber("3");
    $nsfoTestAccountCompanyAddress->setCity("Stad C");
    $nsfoTestAccountCompanyAddress->setPostalCode("3333XX");
    $nsfoTestAccountCompanyAddress->setState("ZH");
    $nsfoTestAccountCompanyAddress->setStreetName("Straat C");
    $nsfoTestAccountCompanyAddress->setCountry("Nederland");

    $nsfoTestAccountCompany = new Company();
    $nsfoTestAccountCompany->setAddress($nsfoTestAccountCompanyAddress);
    $nsfoTestAccountCompany->setBillingAddress($nsfoTestAccountBillingAddress);
    $nsfoTestAccountCompany->setCompanyName("Naam van Bedrijf A");
    $nsfoTestAccountCompany->setOwner(self::$nsfoTestAccount);
    $nsfoTestAccountCompany->setCompanyRelationNumber("111111");
    $nsfoTestAccountCompany->setChamberOfCommerceNumber("222222");
    $nsfoTestAccountCompany->setVatNumber("333333");
    $nsfoTestAccountCompany->setTelephoneNumber("0313131313");
    $nsfoTestAccountCompany->setAnimalHealthSubscription(true);
    $nsfoTestAccountCompany->setIsActive(true);

    $nsfoTestAccountLocationHealth = new LocationHealth();
    $nsfoTestAccountScrapie = new Scrapie(ScrapieStatus::RESISTANT, new \DateTime('2016-10-04'));
    $nsfoTestAccountMaediVisna = new MaediVisna(MaediVisnaStatus::FREE_2_YEAR, new \DateTime('2016-08-04'));
    $nsfoTestAccountScrapie->setCheckDate(new \DateTime('2016-06-01'));
    $nsfoTestAccountMaediVisna->setCheckDate(new \DateTime('2016-06-01'));
    $nsfoTestAccountLocationHealth->setCurrentMaediVisnaStatus($nsfoTestAccountMaediVisna->getStatus());
    $nsfoTestAccountLocationHealth->setCurrentMaediVisnaEndDate($nsfoTestAccountMaediVisna->getEndDate());
    $nsfoTestAccountLocationHealth->setCurrentScrapieStatus($nsfoTestAccountScrapie->getStatus());
    $nsfoTestAccountLocationHealth->setCurrentScrapieEndDate($nsfoTestAccountScrapie->getEndDate());
    $nsfoTestAccountScrapie->setLocationHealth($nsfoTestAccountLocationHealth);
    $nsfoTestAccountMaediVisna->setLocationHealth($nsfoTestAccountLocationHealth);
    $nsfoTestAccountLocationHealth->addScrapie($nsfoTestAccountScrapie);
    $nsfoTestAccountLocationHealth->addMaediVisna($nsfoTestAccountMaediVisna);

    $nsfoTestAccountLocation = new Location();
    $nsfoTestAccountLocation->setIsActive(true);
    $nsfoTestAccountLocation->setAddress($nsfoTestAccountLocationAddress);
    $nsfoTestAccountLocation->setCompany($nsfoTestAccountCompany);
    $nsfoTestAccountLocation->setLocationHealth($nsfoTestAccountLocationHealth);

    $nsfoTestAccountLocation->setUbn($ubnNsfoTestAccount); //NOTE! Echte UBN nodig voor successvolle IenR melding!

    $nsfoTestAccountCompany->addLocation($nsfoTestAccountLocation);
    self::$nsfoTestAccount->addCompany($nsfoTestAccountCompany);
    
    //persist data
    $manager->persist(self::$janVanRijnsbergen);
    $manager->persist($janVanRijnsbergenLocationHealth);
    $manager->persist($janVanRijnsbergenScrapie);
    $manager->persist($janVanRijnsbergenMaediVisna);
    $manager->persist(self::$reinardEverts);
    $manager->persist($reinardEvertsLocationHealth);
    $manager->persist($reinardEvertsScrapie);
    $manager->persist($reinardEvertsMaediVisna);
    $manager->persist(self::$nsfoTestAccount);
    $manager->persist($nsfoTestAccountLocationHealth);
    $manager->persist($nsfoTestAccountScrapie);
    $manager->persist($nsfoTestAccountMaediVisna);
    $manager->flush();



    //THE DATA BELOW IS NOT USED AT THE MOMENT

    if(false) {
      $ubnAndreVanDenOuden = '2628260';
      $ubnHenkVerheul = '297394';

      $emailAndreVanDenOuden = 'kantoor@nsfo.nl';
      $emailHenkVerheul = 'kantoor@nsfo.nl';

      $passwordAndreVanDenOuden = '12345';
      $passwordHenkVerheul = '12345';

      $relationNumberKeeperAndreVanDenOuden = '222222222'; //testdata TODO insert real data
      $relationNumberKeeperHenkVerheul = '333333333'; //testdata TODO insert real data

      //Create mocked data
      self::$andreVanDenOuden = new Client();
      self::$andreVanDenOuden->setFirstName("Andre");
      self::$andreVanDenOuden->setLastName("van den Ouden");
      self::$andreVanDenOuden->setEmailAddress($emailAndreVanDenOuden);
      self::$andreVanDenOuden->setRelationNumberKeeper($relationNumberKeeperAndreVanDenOuden);
      self::$andreVanDenOuden->setUsername('A. van den Ouden');
      self::$andreVanDenOuden->setPassword($encoder->encodePassword(self::$andreVanDenOuden, $passwordAndreVanDenOuden));
      self::$andreVanDenOuden->setCellphoneNumber("");

      //Create mocked data
      self::$henkVerheul = new Client();
      self::$henkVerheul->setFirstName("Henk");
      self::$henkVerheul->setLastName("Verheul");
      self::$henkVerheul->setEmailAddress($emailHenkVerheul);
      self::$henkVerheul->setRelationNumberKeeper($relationNumberKeeperHenkVerheul);
      self::$henkVerheul->setUsername('H. Verheul');
      self::$henkVerheul->setPassword($encoder->encodePassword(self::$henkVerheul, $passwordHenkVerheul));
      self::$henkVerheul->setCellphoneNumber("");

      //persist data
      $manager->persist(self::$andreVanDenOuden);
      $manager->persist(self::$henkVerheul);
      $manager->flush();
    }    
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
  public static function getJanVanRijnsbergen()
  {
    return self::$janVanRijnsbergen;
  }

  /**
   * @return Client
   */
  public static function getReinardEverts()
  {
    return self::$reinardEverts;
  }

  /**
   * @return Client
   */
  public static function getNsfoTestAccount()
  {
    return self::$nsfoTestAccount;
  }

  /**
   * @return Client
   */
  public static function getAndreVanDenOuden()
  {
    return self::$andreVanDenOuden;
  }

  /**
   * @return Client
   */
  public static function getHenkVerheul()
  {
    return self::$henkVerheul;
  }



}