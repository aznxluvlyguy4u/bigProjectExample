<?php

namespace AppBundle\Service\Report;

use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\NullChecker;
use AppBundle\Util\RequestUtil;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class CombiFormTransportDocumentService extends ReportServiceBase
{

    const TITLE = 'combi_formulier_vki__transport_document';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const TWIG_FILE = 'Report/combi_form_transport_document.html.twig';

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
     * @return array
     * @throws Exception
     */
    private function getPDFData(array $requestContent, ?Location $location)
    {
        /** @var Location $exportLocation */
        $exportLocation = $this->em->getRepository(Location::class)
            ->findOneBy(['ubn' => $requestContent['export_ubn']]);

        $result = [
            'location'        => $location,
            'export_location' => $exportLocation
        ];

        foreach ($requestContent['animals'] as $animalItem) {
            /** @var Animal $animal */
            $animal = $this->em->getRepository(Animal::class)
                ->findOneBy([
                    'ulnNumber' => $animalItem['uln_number'],
                    'ulnCountryCode' => $animalItem['uln_country_code']
                ]);

            $result['can_be_exported'] = false;

            $diff = date_diff($animal->getDateOfBirth(), new \DateTime());

            if ($diff->days < 30) {
                if ($animal->getLocationOfBirth() === $location) {
                    $result['can_be_exported'] = true;
                }
            }

            $result['animals'][] = $animal;
        }


        return $result;
    }


}