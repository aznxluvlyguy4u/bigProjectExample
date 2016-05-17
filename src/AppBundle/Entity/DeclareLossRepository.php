<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareLossRepository
 * @package AppBundle\Entity
 */
class DeclareLossRepository extends BaseRepository {

    /**
     * @param Client $client
     * @param string $state
     * @return ArrayCollection
     */
    public function getLosses(Client $client, $state = null)
    {
        $location = $client->getCompanies()->get(0)->getLocations()->get(0);
        $retrievedLosses = $location->getLosses();

        if($state == null) {
            $declareLosses = $retrievedLosses;

        } else {
            $declareLosses = new ArrayCollection();
            foreach($retrievedLosses as $retrievedLoss) {
                if($retrievedLoss->getRequestState() == $state) {
                    $declareLosses->add($retrievedLoss);
                }
            }
        }

        return $declareLosses;
    }

    /**
     * @param Client $client
     * @param string $requestId
     * @return DeclareLoss|null
     */
    public function getLossesById(Client $client, $requestId)
    {
        $losses = $this->getLosses($client);

        foreach($losses as $loss) {
            $foundRequestId = $loss->getRequestId($requestId);
            if($foundRequestId == $requestId) {
                return $loss;
            }
        }

        return null;
    }
}