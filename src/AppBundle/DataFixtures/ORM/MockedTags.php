<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Enumerator\AnimalType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedTags implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface  {

  /**
   * @var ContainerInterface
   */
  private $container;

  /**
   * @var ArrayCollection
   */
  static private $mockedTags;

  /**
   * Sets the container.
   *
   * @param ContainerInterface|null $container A ContainerInterface instance or null
   */
  /**
   * @param ContainerInterface|null $container
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

    self::$mockedTags = new ArrayCollection();

    for($i = 0; $i < 100; $i++) {

      $randomAnimalOrderNumber =  (string)rand(99, 999) .(string)rand(99, 999);
      $randomUln = (string)rand(99,999) . (string)rand(99, 999);

      //Mocked tags
      $tag = new Tag();
      $tag->setTagStatus('unassigned');
      $tag->setOrderDate(new \DateTime());
      $tag->setAnimalOrderNumber($randomAnimalOrderNumber);
      $tag->setUlnNumber($randomUln);
      $tag->setUlnCountryCode("NL");
      $tag->setOwner(MockedClient::getMockedClient());
      self::$mockedTags->add($tag);

      //Persist mocked data
      $manager->persist($tag);


//      MockedClient::getMockedClient()->addTag($tag);
//      $manager->persist(MockedClient::getMockedClient());
//      $manager->flush();
    }
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
    return 2;
  }

  /**
   * @return ArrayCollection
   */
  public static function getMockedTags()
  {
    return self::$mockedTags;
  }

  /**
   * @return Tag|null
   */
  public static function getOneUnassignedTag()
  {
    foreach(self::getMockedTags() as $mockedTag) {
      if($mockedTag->getTagStatus() == Constant::UNASSIGNED_NAMESPACE) {
        return $mockedTag;
      }
    }
    return null;
  }

}