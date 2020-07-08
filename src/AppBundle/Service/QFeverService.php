<?php


namespace AppBundle\Service;


use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\QFeverDescription;
use AppBundle\Enumerator\QFeverType;
use Doctrine\ORM\EntityManagerInterface;

class QFeverService
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    public function isQFeverDescription(string $treatmentDescription): bool
    {
        return in_array($treatmentDescription, QFeverDescription::getConstants());
    }


    public function getFlagType(string $treatmentDescription, int $animalType = AnimalType::sheep): string
    {
        switch ($treatmentDescription) {
            case QFeverDescription::BASIC_VACCINATION_FIRST:
                $vaccinationTypeLetter = QFeverType::BASIC_VACCINATION_FIRST;
                break;
            case QFeverDescription::BASIC_VACCINATION_SECOND:
                $vaccinationTypeLetter = QFeverType::BASIC_VACCINATION_SECOND;
                break;
            case QFeverDescription::REPEATED_VACCINATION:
                $vaccinationTypeLetter = QFeverType::REPEATED_VACCINATION;
                break;
            default:
                throw new \Exception('Invalid QFever treatment description: '.$treatmentDescription);
        }

        switch ($animalType) {
            case AnimalType::sheep: $animalLetter = 'S'; break;
            case AnimalType::goat: $animalLetter = 'G'; break;
            default: throw new \Exception('Invalid animalType letter: '.$animalType);
        }

        $currentYear = date('Y');
        $lastTwoLettersOfCurrentYear = substr($currentYear, 2,2);

        return 'Q_'.$vaccinationTypeLetter.'_'.$animalLetter.'_'.$lastTwoLettersOfCurrentYear;
    }



}
