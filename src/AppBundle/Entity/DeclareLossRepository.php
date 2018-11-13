<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
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


    /**
     * @param ArrayCollection $content
     * @param Location $location
     * @param bool $includeSecondaryValues
     * @return ArrayCollection
     */
    public function findByDeclareInput(ArrayCollection $content, Location $location,
                                       bool $includeSecondaryValues = false)
    {
        $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
        $reasonOfLoss = $content->get(JsonInputConstant::REASON_OF_LOSS);
        $ubnProcessor = $content->get(JsonInputConstant::UBN_PROCESSOR);
        $ubn = $location->getUbn();

        $dateOfDeath = RequestUtil::getDateTimeFromContent($content, JsonInputConstant::DATE_OF_DEATH);

        $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $animalArray);

        $searchValues = [
            'dateOfDeath' => $dateOfDeath,
            'ulnCountryCode' => $ulnCountryCode,
            'ulnNumber' => $ulnNumber,
            'ubn' => $ubn,
        ];

        if ($includeSecondaryValues) {
            $searchValues['ubnDestructor'] = $ubnProcessor;
            $searchValues['reasonOfLoss'] = $reasonOfLoss;
        }

        $losses = $this->findBy($searchValues);

        if (is_array($losses)) {
            return new ArrayCollection($losses);
        }

        return new ArrayCollection();
    }
}