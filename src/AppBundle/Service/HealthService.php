<?php

namespace AppBundle\Service;


use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthQueue;
use AppBundle\Entity\LocationHealthQueueRepository;
use AppBundle\Entity\LocationHealthRepository;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\Finder;
use AppBundle\Util\HealthChecker;
use AppBundle\Util\LocationHealthUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class HealthService
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * HealthService constructor.
     * @param $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @param DeclareArrival|DeclareImport $declareInBase
     */
    public function updateLocationHealth($declareInBase)
    {
        $location = $declareInBase->getLocation();

        $locationHealthMessages = $declareInBase->getLocation()->getHealthMessages(); //ordered ascending by checkDate
        $baseKey = Finder::findLocationHealthMessageArrayKey($declareInBase); //found anti-chronologically by checkDate
        $messageCount = $locationHealthMessages->count();

        //update locationHealth chronologically
        $isDeclareInBase = true;
        $this->updateLocationHealthByArrivalOrImport($location, $declareInBase, $isDeclareInBase);

        if($baseKey < $messageCount) {
            $isDeclareInBase = false;
            for($i = $baseKey+1; $i < $messageCount; $i++) {
                $declareIn = $locationHealthMessages->get($i)->getRequest();
                $this->updateLocationHealthByArrivalOrImport($location, $declareIn, $isDeclareInBase);
            }
        }

    }

    /**
     * @param Location $location
     * @param DeclareArrival|DeclareImport $declareIn
     * @param boolean $isDeclareBaseIn
     */
    private function updateLocationHealthByArrivalOrImport(Location $location, $declareIn, $isDeclareBaseIn)
    {
        $em = $this->entityManager;

        if($declareIn instanceof DeclareArrival) {
            LocationHealthUpdater::updateByGivenUbnOfOrigin($em, $location, $declareIn, $isDeclareBaseIn);

        } else if ($declareIn instanceof DeclareImport) {
            LocationHealthUpdater::updateWithoutOriginHealthData($em, $location, $declareIn, $isDeclareBaseIn);

        } else {
            //do nothing
        }
    }
}