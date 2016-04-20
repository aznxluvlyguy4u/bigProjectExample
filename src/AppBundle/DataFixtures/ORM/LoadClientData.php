<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;

/**
 * Class LoadClientData
 * @package AppBundle\DataFixtures\ORM
 *
 * @see <a href="http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html">
 *     DoctrineFixturesBundle</a>
 */
class LoadClientData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $mockClient = new Client();
        $mockClient->setFirstName("Bart");
        $mockClient->setLastName("de Boer");
        $mockClient->setEmailAddress("bart@deboer.com");
        $mockClient->setRelationNumberKeeper("77777444");

        $location = new Location();
        $location->setUbn("6666888");
        $mockClient->addLocation($location);

        $manager->persist($mockClient);
        $manager->flush();
    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 1;
    }

}



