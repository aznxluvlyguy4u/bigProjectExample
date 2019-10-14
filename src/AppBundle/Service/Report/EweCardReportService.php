<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\ReportUtil;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EweCardReportService extends ReportServiceBase
{
    const TITLE = 'ewe_card';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'EWE_CARD';
    const DATE_RESULT_NULL_REPLACEMENT = "-";

    const TWIG_FILE = 'Report/ewe_card.html.twig';

    /**
     * @param string $uln
     * @return JsonResponse
     */
    public function getReport(string $uln)
    {
        $this->filename = $this->getEweCardReportFileName($uln);
        $this->folderName = self::FOLDER_NAME;
        $this->extension = FileType::PDF;

        ReportUtil::validateFileType($this->extension, self::allowedFileTypes(), $this->translator);

        $animal = $this->validateUln($uln);

        $data = [
            'animal' => $this->getAnimalData(),
            'production' => $this->getProductionData(),
            'offspring' => $this->getOffspringData(),
            'treatments' => $this->getTreatmentsData(),
        ];

        return $this->getPdfReportBase(self::TWIG_FILE, $data, false);
    }

    public function validateUln($uln): Animal {
        $animal = $this->em->getRepository(Animal::class)->findByUlnOrPedigree($uln, true);
        if (!$animal) {
            throw new NotFoundHttpException(ucfirst($this->trans(TranslationKey::NO_ANIMAL_FOUND_FOR_GIVEN_ULN)));
        }
        return $animal;
    }

    public static function allowedFileTypes(): array {
        return [
            FileType::PDF
        ];
    }

    private function getEweCardReportFileName(string $uln): string {
        return ucfirst(ReportUtil::translateFileName($this->translator, self::FILE_NAME_REPORT_TYPE))
            . '_' . $uln;
    }

    private function getAnimalData(): array
    {
        return [];
    }

    private function getProductionData(): array
    {
        return [];
    }

    private function getOffspringData(): array
    {
        return [];
    }

    private function getTreatmentsData(): array
    {
        return [];
    }
}
