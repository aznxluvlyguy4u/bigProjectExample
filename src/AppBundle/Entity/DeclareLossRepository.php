<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareLossRepository
 * @package AppBundle\Entity
 */
class DeclareLossRepository extends BaseRepository {

    /**
     * @param DeclareLoss $declareLossUpdate
     * @param Client $client
     * @param $id
     * @return DeclareLoss|null
     */
    public function updateDeclareLossMessage($declareLossUpdate, Client $client, $id) {

        $declareLoss = $this->getLossByRequestId($client, $id);

        if($declareLoss == null) {
            return null;

        } else {
            if ($declareLossUpdate->getAnimal() != null) {
                $declareLoss->setAnimal($declareLossUpdate->getAnimal());
            }

            if ($declareLossUpdate->getDateOfDeath() != null) {
                $declareLoss->setDateOfDeath($declareLossUpdate->getDateOfDeath());
            }

            if ($declareLossUpdate->getReasonOfLoss() != null) {
                $declareLoss->setReasonOfLoss($declareLossUpdate->getReasonOfLoss());
            }
        }

        return $declareLoss;
    }

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
     * @return DeclareLoss|null
     */
    public function getLossByRequestId(Client $client, $requestId)
    {
        $losses = $this->getLosses($client);

        return $this->getRequestByRequestId($losses, $requestId);
    }

}