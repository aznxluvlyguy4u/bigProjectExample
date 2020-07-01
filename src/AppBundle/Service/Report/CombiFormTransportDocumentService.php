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

    const TITLE = 'combi_formulier_vki__transport_document';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const TWIG_FILE = 'Report/combi_form_transport_document.html.twig';
    const MAX_AGE = 30;

    /**
     * @inheritDoc
     * @throws Exception
     */
    function getReport(Request $request)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);

        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        $PDFData = $this->getPDFData($content->toArray(), $location);

        if ($PDFData instanceof JsonResponse) {
            return $PDFData;
        }

        $PDFData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();
        $PDFData['transport_date'] = $content['transport_date'];

        return $this->getPdfReportBase(self::TWIG_FILE, $PDFData);
    }

    private function setFileNameValues()
    {
        $this->filename = $this->translate(self::FILENAME);
        $this->extension = FileType::PDF;
    }

    /**
     * @param array $requestContent
     * @param Location|null $location
     * @return JsonResponse|array
     * @throws Exception
     */
    private function getPDFData(array $requestContent, ?Location $location)
    {
        /** @var Location $exportLocation */
        $exportLocation = $this->em->getRepository(Location::class)
            ->findOneBy(['ubn' => $requestContent['export_ubn']]);

        $result = [
            'animals' => [],
            'can_be_exported' => false
        ];

        /** @var Animal $animal */
        foreach ($location->getAnimals() as $animal) {

            /** @var DeclareDepart $declareDepart */
            $declareDepart = $animal->getDepartures()->last();

            if (!$declareDepart instanceof DeclareDepart) {
                continue;
            }

            if (
                $declareDepart->getDepartDate()->format('d-m-Y') !== $requestContent['transport_date'] ||
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

        if (count($result['animals']) === 0) {
            throw new BadRequestHttpException();
        }

        $result['location'] = $location;
        $result['export_location'] = $exportLocation;

        return $result;
    }
}