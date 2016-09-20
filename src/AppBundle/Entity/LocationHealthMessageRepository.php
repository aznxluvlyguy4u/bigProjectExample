<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class LocationHealthMessageRepository
 * @package AppBundle\Entity
 */
class LocationHealthMessageRepository extends BaseRepository {

    /**
     * Find the previous entities in the history
     * 'previous' refers to the non-revoked LocationHealthMessage-DeclareArrival/DeclareImport and the related 
     * illnesses right before the given one.
     * 
     * @param DeclareArrival|DeclareImport $declareIn
     * @param Location $location
     * @return LocationHealthMessage|null
     */
    public function getPreviouslocationHealthMessage($declareIn, $location)
    {
        if($declareIn instanceof DeclareArrival) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->lt('arrivalDate', $declareIn->getArrivalDate()))
                ->andWhere(Criteria::expr()->eq('location', $location))
                ->orderBy(['arrivalDate' => Criteria::DESC])
                ->setMaxResults(1);
        } else { //DeclareImport
            $criteria = Criteria::create()
                ->where(Criteria::expr()->lt('arrivalDate', $declareIn->getImportDate()))
                ->andWhere(Criteria::expr()->eq('location', $location))
                ->orderBy(['arrivalDate' => Criteria::DESC])
                ->setMaxResults(1);
        }

        $previousHealthMessageResults = $this->getManager()->getRepository('AppBundle:LocationHealthMessage')
            ->matching($criteria);

        if($previousHealthMessageResults->count() > 0) {
            $previousHealthMessage = $previousHealthMessageResults->get(0);
        } else {
            $previousHealthMessage = null;
        }

        return $previousHealthMessage;
    }

}