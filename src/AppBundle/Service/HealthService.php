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
     * @param DeclareArrival|DeclareImport $declareIn
     * @return null|ArrayCollection
     */
    public function updateLocationHealth($declareIn)
    {
        $location = $declareIn->getLocation();
        $em = $this->entityManager;

        if($declareIn instanceof DeclareArrival) {
            $result = LocationHealthUpdater::updateByGivenUbnOfOrigin($em, $location, $declareIn);

        } else if ($declareIn instanceof DeclareImport) {
            $result = LocationHealthUpdater::updateWithoutOriginHealthData($em, $location, $declareIn);

        } else {
            return null;
        }
    }
}