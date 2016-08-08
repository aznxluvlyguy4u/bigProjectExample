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
     * @param Location $location
     * @param $id
     * @return DeclareLoss|null
     */
    public function updateDeclareLossMessage($declareLossUpdate, Location $location, $id) {

        $declareLoss = $this->getLossByRequestId($location, $id);

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
     * @param Location $location
     * @param string $state
     * @return ArrayCollection
     */
    public function getLosses(Location $location, $state = null)
    {
        $retrievedLosses = $location->getLosses();

        return $this->getRequests($retrievedLosses, $state);
    }

    /**
     * @param Location $location
     * @param string $requestId
     * @return DeclareLoss|null
     */
    public function getLossByRequestId(Location $location, $requestId)
    {
        $losses = $this->getLosses($location);

        return $this->getRequestByRequestId($losses, $requestId);
    }

}