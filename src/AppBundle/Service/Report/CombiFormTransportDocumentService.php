<?php

namespace AppBundle\Service\Report;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\ReasonOfDepartType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\NullChecker;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CombiFormTransportDocumentService extends ReportServiceBase
{

    const TITLE = 'combi_form_vki_transport_document';
    const FILENAME = self::TITLE;
    const TWIG_FILE = 'Report/combi_form_transport_document.html.twig';
    const MAX_AGE = 30;
    const OTHER_ANIMALS_PER_PAGE = 40;
    const ANIMAL_LIMIT = 20;

    /**
     * @inheritDoc
     * @throws Exception
     */
    function getReport($transportDate, $exportUBN, ?Location $location = null)
    {

        $PDFData = $this->getPDFData($transportDate, $exportUBN, $location);
        $this->filename = $this->translate(self::FILENAME);

        if ($PDFData instanceof JsonResponse) {
            return $PDFData;
        }

        return $this->getPdfReportBase(self::TWIG_FILE, $PDFData, false);
    }

    /**
     * @param $transportDate
     * @param $exportUbn
     * @param Location|null $location
     * @return JsonResponse|array
     * @throws Exception
     */
    private function getPDFData($transportDate, $exportUbn, ?Location $location)
    {
        /** @var Location $exportLocation */
        $exportLocation = $this->em->getRepository(Location::class)
            ->findOneBy(['ubn' => $exportUbn]);

        $result = [
            'animals' => [],
            'can_be_exported' => false,
            'transport_date' => $transportDate,
            ReportLabel::IMAGES_DIRECTORY => $this->getImagesDirectory()
        ];

        /** @var Animal $animal */
        foreach ($location->getAnimals() as $animal) {

            /** @var DeclareDepart $declareDepart */
            $declareDepart = $animal->getDepartures()->last();

            if (!$declareDepart instanceof DeclareDepart) {
                continue;
            }

            if (
                $declareDepart->getDepartDate()->format('d-m-Y') !== $transportDate ||
                $declareDepart->getRequestState() === RequestStateType::FINISHED ||
                $declareDepart->getRequestState() === RequestStateType::FINISHED_WITH_WARNING ||
                $declareDepart->getRequestState() === RequestStateType::IMPORTED ||
                $declareDepart->getReasonOfDepart() !== ReasonOfDepartType::BREEDING_FARM ||
                $declareDepart->getReasonOfDepart() !== ReasonOfDepartType::RENT
            ) {
                continue;
            }

            $diff = date_diff($animal->getDateOfBirth(), new \DateTime());

            if ($diff->days < self::MAX_AGE) {
                if ($animal->getLocationOfBirth() === $location) {
                    $result['can_be_exported'] = true;
                }
            }

            $result['animals'][] = $animal;
        }

        $totalAnimalCount = count($result['animals']);

        if ($totalAnimalCount === 0) {
            throw new BadRequestHttpException();
        }

        if ($totalAnimalCount >= self::ANIMAL_LIMIT) {
            $firstAnimals = array_slice($result['animals'], 0, self::ANIMAL_LIMIT);

            if ($totalAnimalCount === self::ANIMAL_LIMIT) {
                $totalAnimalCount = $totalAnimalCount+1;
            }

            $otherAnimals = array_slice($result['animals'], self::ANIMAL_LIMIT, $totalAnimalCount-self::ANIMAL_LIMIT);
            $result['total_other_animals'] = count($otherAnimals);
            $result['total_animal_pages'] = ceil(count($otherAnimals)/self::OTHER_ANIMALS_PER_PAGE);

            $result['paginated_animals'] = array_chunk($otherAnimals, self::OTHER_ANIMALS_PER_PAGE, true);

            $chunkedAnimals = array_chunk($firstAnimals, self::ANIMAL_LIMIT/2, true);
        } else {
            $result['total_other_animals'] = 0;

            if ($totalAnimalCount === 1) {
                $size = $totalAnimalCount;
            } else {
                $size = $totalAnimalCount/2;
            }

            $chunkedAnimals = array_chunk($result['animals'], $size, true);
        }

        $result['animals_left'] = $chunkedAnimals[0];
        if (count($chunkedAnimals) > 1) {
            $result['animals_right'] = $chunkedAnimals[1];
        } else {
            $result['animals_right'] = [];
        }

        $result['location'] = $location;
        $result['export_location'] = $exportLocation;

        return $result;
    }
}