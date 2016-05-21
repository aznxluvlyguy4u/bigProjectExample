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

        return $this->getRequests($retrievedLosses, $state);
    }

    /**
     * @param Client $client
     * @param string $requestId
     * @return DeclareArrival|null
     */
    public function getLossByRequestId(Client $client, $requestId)
    {
        $losses = $this->getLosses($client);

        return $this->getRequestByRequestId($losses, $requestId);
    }

}