<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\UlnValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class PedigreeCertificateReportService extends ReportServiceBase
{
    const TITLE = 'pedigree certificates report';
    const TWIG_FILE = 'Report/pedigree_certificates.html.twig';
    const TWIG_FILE_BETA = 'Report/pedigree_certificates_beta.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    /** @var PedigreeCertificates */
    private $reportResults;


    /**
     * @param Person $person
     * @param Location|null $selectedLocation
     * @param $fileType
     * @param ArrayCollection $content
     * @param $locale
     * @return JsonResponse
     */
    public function getReport(Person $person, $selectedLocation, $fileType, ArrayCollection $content, $locale)
    {
        $client = null;
        $location = null;
        if(!AdminValidator::isAdmin($person, AccessLevelType::ADMIN)) {
            $location = $selectedLocation;
            $client = $person;
        }

        //Validate if given ULNs are correct AND there should at least be one ULN given
        $ulnValidator = new UlnValidator($this->em, $content, true, null, $location);
        if(!$ulnValidator->getIsUlnSetValid()) {
            return $ulnValidator->createArrivalJsonErrorResponse();
        }

        $this->filename = $this->translate(self::FILENAME);
        $this->folderName = self::FOLDER_NAME;

        $this->setLocale($locale);

        $this->reportResults = new PedigreeCertificates($this->em, $content, $client, $location);

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
        return $this->getPdfReportBase($twigFile, $this->reportResults->getReports(), true);
    }


    /**
     * @return JsonResponse
     */
    private function getCsvReport()
    {
        $keysToIgnore = [
            'breederIndexStars',
            'mBreederIndexStars',
            'fBreederIndexStars',
            'extIndexStars',
            'vlIndexStars',
            'breederIndexNoAcc',
            'mBreederIndexNoAcc',
            'fBreederIndexNoAcc',
            'extIndexNoAcc',
            //ignore the following two keys, so the columns always match in the csv
            'litterSize',
            'litterCount',
        ];

        $customKeysToTranslate = [
            'pedigree' => 'stn'
        ];

        $csvData = $this->convertNestedArraySetsToSqlResultFormat($this->reportResults->getReports(), $keysToIgnore, $customKeysToTranslate);

        return $this->generateFile($this->filename,
            $csvData,self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }


}