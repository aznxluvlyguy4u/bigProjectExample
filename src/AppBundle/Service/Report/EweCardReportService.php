<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;

use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\ReportUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EweCardReportService extends ReportServiceBase
{
    const TITLE = 'ewe cards report';
    const TWIG_FILE = 'Report/ewe_cards.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'EWE_CARD';
    const DATE_RESULT_NULL_REPLACEMENT = "-";


    /**
     * @param $actionBy
     * @param Location $location
     * @param ArrayCollection $content
     * @return JsonResponse
     */
    public function getReport($actionBy, Location $location, ArrayCollection $content)
    {
        dump($content);die();

        $this->filename = $this->getEweCardReportFileName($uln);
        $this->folderName = self::FOLDER_NAME;
        $this->extension = self::defaultFileType();

        return $this->getPdfReport($uln);
    }

    /**
     * Per
     *
     * @param ArrayCollection $content
     * @return string
     */
    private function getUlnFromContent(ArrayCollection $content)
    {
        return "NL100153624597"; // TODO replace test data
    }

    /**
     * @param string $uln
     * @return JsonResponse
     */
    private function getPdfReport(string $uln)
    {
        $animal = $this->validateUln($uln);

        $data = [
            'animal' => $this->getAnimalData(),
            'production' => $this->getProductionData(),
            'offspring' => $this->getOffspringData(),
            'treatments' => $this->getTreatmentsData(),
        ];

        $additionalData = [
            'bootstrap_css' => FilesystemUtil::getAssetsDirectory($this->rootDir). '/bootstrap-3.3.7-dist/css/bootstrap.min.css',
            'bootstrap_js' => FilesystemUtil::getAssetsDirectory($this->rootDir). '/bootstrap-3.3.7-dist/js/bootstrap.min.js',
            'images_dir' => FilesystemUtil::getImagesDirectory($this->rootDir),
            'fonts_dir' => FilesystemUtil::getAssetsDirectory(($this->rootDir)). '/fonts'
        ];
        $customPdfOptions = [
            'orientation'=>'Portrait',
            'default-header'=>false,
            'page-size' => 'A4',
            'margin-top'    => 3,
            'margin-right'  => 3,
            'margin-bottom' => 3,
            'margin-left'   => 3,
        ];

        return $this->getPdfReportBase(self::TWIG_FILE, $data, false,
            $customPdfOptions, $additionalData);
    }


    public function validateUln($uln): Animal {
        $animal = $this->em->getRepository(Animal::class)->findByUlnOrPedigree($uln, true);
        if (!$animal) {
            throw new NotFoundHttpException(ucfirst($this->trans(TranslationKey::NO_ANIMAL_FOUND_FOR_GIVEN_ULN)));
        }
        return $animal;
    }

    public static function defaultFileType(): String {
        return FileType::PDF;
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
