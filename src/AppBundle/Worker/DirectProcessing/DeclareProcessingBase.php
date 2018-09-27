<?php


namespace AppBundle\Worker\DirectProcessing;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\Location;
use AppBundle\Service\ControllerServiceBase;

class DeclareProcessingBase extends ControllerServiceBase
{
    /**
     * @param Animal $animal
     * @param Location $location
     * @param \DateTime $endDate
     */
    protected function closeLastOpenAnimalResidence(Animal $animal, Location $location, $endDate)
    {
        if (!$endDate) {
            return;
        }

        $animalResidence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastOpenResidenceOnLocation($location, $animal);
        if ($animalResidence && !$animalResidence->getEndDate()) {
            $animalResidence->setEndDate($endDate);
            $animalResidence->setIsPending(false);
            $this->getManager()->persist($animalResidence);
        }
    }
}