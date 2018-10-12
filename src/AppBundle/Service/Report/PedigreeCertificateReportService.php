<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;

class PedigreeCertificateReportService extends ReportServiceBase
{
    const TITLE = 'pedigree certificates report';
    const TWIG_FILE = 'Report/pedigree_certificates2.html.twig';
    const TWIG_FILE_BETA = 'Report/pedigree_certificates_beta.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    /** @var PedigreeCertificates */
    private $pedigreeCertificatesGenerator;

    /**
     * @required
     *
     * @param PedigreeCertificates $pedigreeCertificatesGenerator
     */
    public function setPedigreeCertificatesGenerator(PedigreeCertificates $pedigreeCertificatesGenerator)
    {
        $this->pedigreeCertificatesGenerator = $pedigreeCertificatesGenerator;
    }

    /**
     * @param Person $actionBy
     * @param Location|null $selectedLocation
     * @param $fileType
     * @param ArrayCollection $content
     * @param $locale
     * @return JsonResponse
     */
    public function getReport(Person $actionBy, $selectedLocation, $fileType, ArrayCollection $content, $locale)
    {
        $client = null;
        $location = null;
        if(!AdminValidator::isAdmin($actionBy, AccessLevelType::ADMIN)) {
            $location = $selectedLocation;
            $client = $actionBy;
        }

        $company = $selectedLocation ? $selectedLocation->getCompany() : null;
        $this->ulnValidator->pedigreeCertificateUlnsInputValidation($content, $actionBy, $company);

        $this->filename = $this->translate(self::FILENAME);
        $this->folderName = self::FOLDER_NAME;

        $this->setLocale($locale);

        $this->pedigreeCertificatesGenerator->generate($actionBy, $content, $client, $location);

//dump($this->pedigreeCertificatesGenerator->getReports());die();
        if ($fileType === FileType::CSV) {
            return $this->getCsvReport();
        }

        return $this->getPdfReport();
    }

    /**
     * @return JsonResponse
     */
    private function getPdfReport()
    {
        //Or use... $this->getCurrentEnvironment() == Environment::PROD;
        $twigFile = ReportAPIController::IS_USE_PROD_VERSION_OUTPUT ? self::TWIG_FILE : self::TWIG_FILE_BETA;
        $data = $this->pedigreeCertificatesGenerator->getReports();
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
	        'margin-right'  => 4,
	        'margin-bottom' => 3,
	        'margin-left'   => 4,
        ];

        return $this->getPdfReportBase($twigFile, $data, false, $additionalData, $customPdfOptions);
    }


    /**
     * @return JsonResponse
     */
    private function getCsvReport()
    {
        $keysToIgnore = [
            ReportLabel::STARS_OUTPUT,
            //ignore the following two keys, so the columns always match in the csv
            'litterSize',
            'litterCount',
        ];

        $customKeysToTranslate = [
            'pedigree' => 'stn'
        ];

        $csvData = $this->convertNestedArraySetsToSqlResultFormat($this->pedigreeCertificatesGenerator->getReports(), $keysToIgnore, $customKeysToTranslate);

        return $this->generateFile($this->filename,
            $csvData,self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }


}