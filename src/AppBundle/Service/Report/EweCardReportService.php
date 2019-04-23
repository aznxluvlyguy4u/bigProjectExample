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

class EweCardReportService extends ReportServiceBase
{
    const TITLE = 'ewe cards report';
    const TWIG_FILE = 'Report/ewe_cards.html.twig';
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
    public function getReport($actionBy, Location $selectedLocation, ArrayCollection $content)
    {
        dump($content);die();

        $client = null;
        $location = null;
//        if(!AdminValidator::isAdmin($actionBy, AccessLevelType::ADMIN)) {
//            $location = $selectedLocation;
//            $client = $actionBy;
//        }

//        $company = $selectedLocation ? $selectedLocation->getCompany() : null;
//        $this->ulnValidator->pedigreeCertificateUlnsInputValidation($content, $actionBy, $company);

        $this->filename = $this->translate(self::FILENAME);
        $this->folderName = self::FOLDER_NAME;


        $this->pedigreeCertificatesGenerator->generate($actionBy, $content, $client, $selectedLocation);
        $data = $this->pedigreeCertificatesGenerator->getReports();
        dump($data);die();


        return $this->getPdfReport();
    }

    /**
     * @return JsonResponse
     */
    private function getPdfReport()
    {
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
            'margin-right'  => 3,
            'margin-bottom' => 3,
            'margin-left'   => 3,
        ];

        return $this->getPdfReportBase(self::TWIG_FILE, $data, false,
            $customPdfOptions, $additionalData);
    }
}
