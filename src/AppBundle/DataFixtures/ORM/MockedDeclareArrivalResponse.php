<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Constant\Constant;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\DeclareArrivalResponse;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MockedDeclareArrivalResponse implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DeclareArrivalResponse
     */
    static private $mockedArrivalSuccessResponse;

    /**
     * @var DeclareArrivalResponse
     */
    static private $mockedArrivalFailedResponse;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if(MockedDeclareArrival::$hasCascadePersistenceIssueBeenFixed) {
            //Create mocked data
            self::$mockedArrivalSuccessResponse = new DeclareArrivalResponse();

            $id1 = uniqid(mt_rand(0,999999));
            $messageNumberFailedResponse = uniqid(mt_rand(0,99));
            $messageNumberSuccessResponse = uniqid(mt_rand(0,99));
            $mockedArrival = MockedDeclareArrival::getMockedArrival();

            self::$mockedArrivalSuccessResponse->setDeclareArrivalRequestMessage($mockedArrival);
            self::$mockedArrivalSuccessResponse->setLogDate(new \DateTime('2023-03-22'));
            self::$mockedArrivalSuccessResponse->setRequestId($id1);
            self::$mockedArrivalSuccessResponse->setMessageId($id1);
            self::$mockedArrivalSuccessResponse->setMessageNumber($messageNumberSuccessResponse);
            self::$mockedArrivalSuccessResponse->setErrorCode(null);
            self::$mockedArrivalSuccessResponse->setErrorMessage(null);
            self::$mockedArrivalSuccessResponse->setErrorKindIndicator(null);
            self::$mockedArrivalSuccessResponse->setSuccessIndicator("J");

            self::$mockedArrivalFailedResponse = new DeclareArrivalResponse();

            $id2 = uniqid(mt_rand(0,999999));
            $mockedArrival = MockedDeclareArrival::getMockedArrival();

            self::$mockedArrivalFailedResponse->setDeclareArrivalRequestMessage($mockedArrival);
            self::$mockedArrivalFailedResponse->setLogDate(new \DateTime('2023-03-21'));
            self::$mockedArrivalFailedResponse->setRequestId($id2);
            self::$mockedArrivalFailedResponse->setMessageId($id2);
            self::$mockedArrivalFailedResponse->setMessageNumber($messageNumberFailedResponse);
            self::$mockedArrivalFailedResponse->setErrorCode("IRD-00363");
            self::$mockedArrivalFailedResponse->setErrorMessage("Er zijn geen dieren gevonden bij het opgegeven werknummer");
            self::$mockedArrivalFailedResponse->setErrorKindIndicator("F");
            self::$mockedArrivalFailedResponse->setSuccessIndicator("N");
            self::$mockedArrivalFailedResponse->setIsRemovedByUser(false);

            //Persist mocked data
            $manager->persist(self::$mockedArrivalSuccessResponse);
            $manager->persist(self::$mockedArrivalFailedResponse);
            $manager->flush();

        } else {
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
            $arrivalRepository = $entityManager->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);

            $messageNumberFailedResponse = uniqid(mt_rand(0,99));
            $messageNumberSuccessResponse = uniqid(mt_rand(0,99));

            //It is necessary to retrieve the DeclareArrival entity from the Repository first
            $declareArrival = $arrivalRepository->findAll()['0'];

            //Create and persist failed Response
            self::$mockedArrivalFailedResponse = new DeclareArrivalResponse();
            self::$mockedArrivalFailedResponse->setDeclareArrivalRequestMessage($declareArrival);
            self::$mockedArrivalFailedResponse->setLogDate(new \DateTime('2023-03-21'));
            self::$mockedArrivalFailedResponse->setRequestId($declareArrival->getRequestId());
            self::$mockedArrivalFailedResponse->setMessageId($declareArrival->getMessageId());
            self::$mockedArrivalFailedResponse->setMessageNumber($messageNumberFailedResponse);
            self::$mockedArrivalFailedResponse->setArrivalDate($declareArrival->getArrivalDate());
            self::$mockedArrivalFailedResponse->setUbnPreviousOwner($declareArrival->getUbnPreviousOwner());
            self::$mockedArrivalFailedResponse->setErrorCode("IRD-00363");
            self::$mockedArrivalFailedResponse->setErrorMessage("Er zijn geen dieren gevonden bij het opgegeven werknummer");
            self::$mockedArrivalFailedResponse->setErrorKindIndicator("F");
            self::$mockedArrivalFailedResponse->setSuccessIndicator("N");
            self::$mockedArrivalFailedResponse->setIsRemovedByUser(false);

            //Create and persist successful Response
            self::$mockedArrivalSuccessResponse = new DeclareArrivalResponse();
            self::$mockedArrivalSuccessResponse->setDeclareArrivalRequestMessage($declareArrival);
            self::$mockedArrivalSuccessResponse->setLogDate(new \DateTime('2023-03-22'));
            self::$mockedArrivalSuccessResponse->setRequestId($declareArrival->getRequestId());
            self::$mockedArrivalSuccessResponse->setMessageId($declareArrival->getMessageId());
            self::$mockedArrivalSuccessResponse->setMessageNumber($messageNumberSuccessResponse);
            self::$mockedArrivalSuccessResponse->setArrivalDate($declareArrival->getArrivalDate());
            self::$mockedArrivalSuccessResponse->setUbnPreviousOwner($declareArrival->getUbnPreviousOwner());
            self::$mockedArrivalSuccessResponse->setErrorCode(null);
            self::$mockedArrivalSuccessResponse->setErrorMessage(null);
            self::$mockedArrivalSuccessResponse->setErrorKindIndicator(null);
            self::$mockedArrivalSuccessResponse->setSuccessIndicator("J");
            self::$mockedArrivalSuccessResponse->setIsRemovedByUser(false);

            //Persist mocked data
            $manager->persist(self::$mockedArrivalSuccessResponse);
            $manager->persist(self::$mockedArrivalFailedResponse);
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
        return 6;
    }

    /**
     * @return DeclareArrivalResponse
     */
    public static function getMockedArrivalSuccessResponse()
    {
        return self::$mockedArrivalSuccessResponse;
    }

    /**
     * @return DeclareArrivalResponse
     */
    public static function getMockedArrivalFailedResponse()
    {
        return self::$mockedArrivalFailedResponse;
    }


}