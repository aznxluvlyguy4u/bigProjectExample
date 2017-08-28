<?php

namespace AppBundle\Tests\Controller;


use AppBundle\Component\ArrivalMessageBuilder;
use AppBundle\Component\ImportMessageBuilder;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalRepository;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareImportRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthQueue;
use AppBundle\Entity\LocationHealthQueueRepository;
use AppBundle\Entity\RamRepository;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Service\HealthUpdaterService;
use AppBundle\Service\IRSerializer;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client as RequestClient;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ram;

/**
 * Class LocationHealthQueueUpdateTest
 * @package AppBundle\Tests\Controller
 * @group location-health
 */
class LocationHealthQueueUpdateTest extends WebTestCase
{
    const MESSAGE_COUNT = 3;

    /** @var RequestClient */
    private $client;

    /** @var IRSerializer */
    static private $serializer;

    /** @var DoctrineBundle */
    static private $doctrine;

    /** @var HealthUpdaterService */
    static private $healthService;

    /** @var ObjectManager */
    static private $em;

    /** @var ArrivalMessageBuilder */
    static private $arrivalMessageBuilder;

    /** @var ImportMessageBuilder */
    static private $importMessageBuilder;

    /** @var LocationHealthQueueRepository */
    static private $locationHealthQueueRepository;

    /** @var RamRepository */
    static private $ramRepository;

    /** @var DeclareArrivalRepository */
    static private $arrivalRepository;

    /** @var DeclareImportRepository */
    static private $importRepository;

    /** @var Client */
    static private $mockedClient;

    /** @var Location */
    static private $location;

//    /**
//     * Runs before class setup
//     */
//    public static function setUpBeforeClass()
//    {
//        //start the symfony kernel
//        $kernel = static::createKernel();
//        $kernel->boot();
//
//        static::$kernel = static::createKernel();
//        static::$kernel->boot();
//
//        //Get the DI container
//        $container = $kernel->getContainer();
//
//        //Get service classes
//        self::$serializer = $container->get('app.serializer.ir');
//        self::$doctrine = $container->get('doctrine');
//        self::$healthService = $container->get('app.health.updater');
//        self::$em = self::$doctrine->getManager();
//
//        self::$arrivalMessageBuilder = new ArrivalMessageBuilder(self::$em);
//        self::$importMessageBuilder = new ImportMessageBuilder(self::$em);
//
//        self::$locationHealthQueueRepository = self::$doctrine->getRepository('AppBundle:LocationHealthQueue');
//        self::$ramRepository = self::$doctrine->getRepository('AppBundle:Ram');
//        self::$arrivalRepository = self::$doctrine->getRepository('AppBundle:DeclareArrival');
//        self::$importRepository = self::$doctrine->getRepository('AppBundle:DeclareImport');
//    }
//
//    /**
//     * Runs on each testcase
//     */
//    public function setUp()
//    {
//        $this->client = parent::createClient();
//
//        //Load fixture class
//        $fixtures = array(
//            'AppBundle\DataFixtures\ORM\DataFixturesRealClients'
//            );
//        $this->loadFixtures($fixtures);
//
//        //Get mocked Client
//        self::$mockedClient = self::$em->getRepository('AppBundle:Client')->findAll()[0];
//        self::$location = self::$mockedClient->getCompanies()->get(0)->getLocations()->get(0);
//
//        $ram = new Ram();
//        $ram->setIsAlive(true);
//        $ram->setAnimalType(AnimalType::sheep);
//        $ram->setDateOfBirth(new \DateTime('2001-01-01'));
//        $ram->setPedigreeNumber("35645");
//        $ram->setPedigreeCountryCode("NL");
//        self::$em->persist($ram);
//
//        for($i = 1; $i <= LocationHealthQueueUpdateTest::MESSAGE_COUNT; $i++) {
//            $arrival = self::$arrivalMessageBuilder->buildMessage(new DeclareArrival(), self::$mockedClient, self::$location);
//            $arrival->setUlnNumber($i);
//            $arrival->setUlnCountryCode('NL');
//            $arrival->setAnimalType(AnimalType::sheep);
//            $arrival->setAnimal(self::$ramRepository->find($i));
//            $arrival->setAnimalObjectType('Neuter');
//            $arrival->setArrivalDate(new \DateTime('now'));
//            $arrival->setUbnPreviousOwner('1010336');
//            $arrival->setIsArrivedFromOtherNsfoClient(false);
//            $arrival->setIsImportAnimal(false);
//
//            $import = self::$importMessageBuilder->buildMessage(new DeclareImport(), self::$mockedClient, self::$location);
//            $import->setUlnNumber($i+3);
//            $import->setUlnCountryCode('NL');
//            $import->setAnimalType(AnimalType::sheep);
//            $import->setAnimal(self::$ramRepository->find($i+4));
//            $import->setAnimalObjectType('Neuter');
//            $import->setImportDate(new \DateTime('now'));
//            $import->setAnimalCountryOrigin('SPACE');
//            $arrival->setIsImportAnimal(true);
//
//            $locationHealthQueue = new LocationHealthQueue();
//            $locationHealthQueue->addArrival($arrival);
//            $locationHealthQueue->addImport($import);
//            $arrival->setLocationHealthQueue($locationHealthQueue);
//            $import->setLocationHealthQueue($locationHealthQueue);
//
//            self::$em->persist($arrival);
//            self::$em->persist($import);
//
//            self::$locationHealthQueueRepository->persist($locationHealthQueue);
//        }
//        self::$em->flush();
//    }
//
//    /**
//     * @group utils
//     * Test create new revoke declaration
//     */
//    public function testUpdateLocationHealthQueue()
//    {
//        //Validate setup is correct
//        $queues = self::$locationHealthQueueRepository->findAll();
//
//        $arrivalCount = 0; $importCount = 0;
//        foreach($queues as $queue) {
//            $arrivalCount += $queue->getArrivals()->count();
//            $importCount += $queue->getImports()->count();
//        }
//
//        $this->assertEquals(LocationHealthQueueUpdateTest::MESSAGE_COUNT, sizeof($queues));
//        $this->assertEquals(LocationHealthQueueUpdateTest::MESSAGE_COUNT, $arrivalCount);
//        $this->assertEquals(LocationHealthQueueUpdateTest::MESSAGE_COUNT, $importCount);
//
//        //Update LocationHealthQueue with a new DeclareArrival
//        $i = LocationHealthQueueUpdateTest::MESSAGE_COUNT*2+1;
//
//        $arrival = self::$arrivalMessageBuilder->buildMessage(new DeclareArrival(), self::$mockedClient, self::$location);
//        $arrival->setUlnNumber($i);
//        $arrival->setUlnCountryCode('NL');
//        $arrival->setAnimalType(AnimalType::sheep);
//        $arrival->setAnimal(self::$ramRepository->find($i));
//        $arrival->setAnimalObjectType('Neuter');
//        $arrival->setArrivalDate(new \DateTime('now'));
//        $arrival->setUbnPreviousOwner('1010336');
//        $arrival->setIsArrivedFromOtherNsfoClient(false);
//        $arrival->setIsImportAnimal(false);
//        self::$em->persist($arrival);
//        self::$em->flush();
//
//        self::$healthService->updateLocationHealthQueue($arrival); //persist and flush is already included in the function
//
//        //Validate
//        $queues = self::$locationHealthQueueRepository->findAll();
//        $arrivalCount = $queues[0]->getArrivals()->count();
//        $importCount = $queues[0]->getImports()->count();
//
//        $this->assertEquals(1, sizeof($queues));
//        $this->assertEquals(LocationHealthQueueUpdateTest::MESSAGE_COUNT+1, $arrivalCount);
//        $this->assertEquals(LocationHealthQueueUpdateTest::MESSAGE_COUNT, $importCount);
//    }
//
//    public function tearDown() {
//        parent::tearDown();
//    }
//
//    /*
//     * Runs after all testcases ran and teardown
//     */
//    public static function tearDownAfterClass()
//    {
//
//    }
}