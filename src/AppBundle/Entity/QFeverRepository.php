<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\QFeverDescription;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Service\QFeverService;
use Psr\Log\LoggerInterface;

/**
 * Class QFeverRepository
 * @package AppBundle\Entity
 */
class QFeverRepository  extends BaseRepository
{
    public function initializeRecords(LoggerInterface $logger)
    {
        /** @var TreatmentTypeRepository $typeRepository */
        $typeRepository = $this->getManager()->getRepository(TreatmentType::class);
        $typeRepository->initializeRecords($logger);

        $descriptions = [
            QFeverDescription::BASIC_VACCINATION_FIRST,
            QFeverDescription::BASIC_VACCINATION_SECOND,
            QFeverDescription::REPEATED_VACCINATION,
        ];
        $typeOption = TreatmentTypeOption::INDIVIDUAL;

        $animalTypes = [
            AnimalType::sheep,
            // AnimalType::goat, // TODO activate this later for the goat feature
        ];

        foreach ($animalTypes as $animalType) {
            foreach ($descriptions as $description) {
                $template = $this->findOneBy(['description' => $description]);
                if (!$template) {
                    $treatmentType = $typeRepository->findActiveOneByTypeAndDescription(
                        $typeOption, $description
                    );

                    $template = (new QFever())
                        ->setQFeverType(QFeverService::qFeverTypeLetter($description))
                        ->setDescription($description)
                        ->setTreatmentType($treatmentType)
                        ->setType($treatmentType->getType())
                        ->setIsEditable(false)
                        ->setAnimalType($animalType)
                    ;

                    $this->persist($template);
                    $this->flush();
                    if ($logger) {
                        $logger->notice('Created Q-Fever treatmentTemplate: '.$description.' of type '.$typeOption);
                    }
                }
            }
        }
    }

    public function findQFeverDescriptions()
    {
        $treatmentTemplates = $this->findAll();

        $output = [];

        /** @var TreatmentTemplate $treatmentTemplate */
        foreach ($treatmentTemplates as $treatmentTemplate) {
            if($treatmentTemplate instanceof QFever) {
                $output[] = $treatmentTemplate->getDescription();
            }
        }

        return $output;
    }
}
