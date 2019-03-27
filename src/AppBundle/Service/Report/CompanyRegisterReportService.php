<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\Locale;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Util\NullChecker;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;

class CompanyRegisterReportService extends ReportServiceBase
{
    const TITLE = 'company_register_report';
    const TWIG_FILE = 'Report/company_register_report.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const FILE_NAME_REPORT_TYPE = 'COMPANY_REGISTER';

    const MAX_MATE_AGE_IN_MONTHS = 6;


    /** @var ArrayCollection */
    private $content;
    /** @var array */
    private $ramData;
    /** @var array */
    private $ewesData;
    /** @var int */
    private $generationOfAscendants;

    /** @var InbreedingCoefficientReportData */
    private $reportResults;

    /**
     * @var Client
     */
    private $client;

    /** @var array */
    private static $customReportFilenames = [
        Locale::NL => 'inteeltcoeffient rapportage',
    ];

    /** @var array */
    private static $customReportFolderNames = [
        Locale::NL => 'inteeltcoeffient rapportage',
    ];


    /**
     * @param Person $person
     * @param Location $location
     * @param CompanyRegisterReportOptions $options
     * @return JsonResponse
     * @throws \Exception
     */
    public function getReport(Person $person, Location $location, CompanyRegisterReportOptions $options)
    {
        self::validateUser($person, $location);

        $this->filename = $this->trans(self::FILE_NAME_REPORT_TYPE).'_'.$location->getUbn();
        $this->folderName = self::FOLDER_NAME;

        if ($options->getFileType() === FileType::CSV) {
            return $this->getCsvReport($person, $location, $options);
        }

        return $this->getPdfReport($person, $location, $options);
    }


    private function setFileName()
    {
        switch ($this->translator->getLocale()) {
            case Locale::NL: $this->filename = self::$customReportFilenames[Locale::NL]; break;
            default: $this->filename = $this->translate(self::FILENAME);
        }

        $this->filename = StringUtil::replaceSpacesWithUnderscores($this->filename);
    }


    private function setFolderName()
    {
        switch ($this->translator->getLocale()) {
            case Locale::NL: $this->folderName = self::$customReportFolderNames[Locale::NL]; break;
            default: $this->folderName = $this->translate(self::FOLDER_NAME);
        }

        $this->folderName = StringUtil::replaceSpacesWithUnderscores($this->folderName);
    }



    private function retrieveAndValidateInput()
    {
        $primaryKeys = [JsonInputConstant::EWES, JsonInputConstant::RAM];
        foreach ($primaryKeys as $key) {
            if (!$this->content->containsKey($key)) {
                $this->inputErrors = "Key '" . $key . "' is missing";
            }
        }

        if (count($this->inputErrors) > 0) { return; }

        $ewesArray = $this->content->get(JsonInputConstant::EWES);
        $ramArray = $this->content->get(JsonInputConstant::RAM);

        $this->validateRamArray($ramArray);

        if(self::MAX_EWES_COUNT > 0 && count($ewesArray) > self::MAX_EWES_COUNT) {
            $this->inputErrors[] = $this->translateErrorMessages(self::EWES_COUNT_EXCEEDS_MAX);
            return;
        }


        $this->validateEwesArray($ewesArray);
        $this->validateGenerations();
    }


    private function validateGenerations()
    {
        $input = $this->content->get(JsonInputConstant::GENERATIONS);
        if (is_int($input) || ctype_digit($input)) {
            $this->generationOfAscendants = intval($input);
            if ($this->generationOfAscendants > self::MAX_GENERATION_OF_ASCENDANTS) {
                $this->inputErrors[] = $this->translateErrorMessages(self::MAX_GENERATIONS_LIMIT_EXCEEDED);
                return;
            }
        } else {
            $this->generationOfAscendants = self::GENERATION_OF_ASCENDANTS;
        }
    }



    /**
     * @param array $ramArray
     */
    private function validateRamArray($ramArray) {

        //First validate if uln or pedigree exists
        $containsUlnOrPedigree = NullChecker::arrayContainsUlnOrPedigree($ramArray);
        if(!$containsUlnOrPedigree) {
            $this->inputErrors[] = $this->translateErrorMessages(self::RAM_MISSING_INPUT);
            return;
        }

        //Then validate the uln if it exists
        $ulnString = NullChecker::getUlnStringFromArray($ramArray, null);
        if ($ulnString != null) {
            //ULN check

            $isUlnFormatValid = Validator::verifyUlnFormat($ulnString);
            if(!$isUlnFormatValid) {
                $this->inputErrors[] = $this->translateErrorMessages(self::RAM_ULN_FORMAT_INCORRECT) . ': '.$ulnString;
                return;
            }

            //If animal is in database, verify the gender

            $sql = "SELECT id, uln_country_code, uln_number, pedigree_country_code, pedigree_number, type
                    FROM animal WHERE ". SqlUtil::getUlnQueryFilter([$ramArray],'');
            $results = $this->conn->query($sql)->fetchAll();

            if (count($results) === 0) {
                $this->inputErrors[] = $this->translateErrorMessages(self::RAM_ULN_NOT_FOUND). ': '.$ulnString;
                return;
            }

            foreach ($results as $result) {
                if ($result['type'] === 'Ram') {
                    $this->ramData = $result;
                    return;
                }
            }

            $this->inputErrors[] = $this->translateErrorMessages(self::RAM_ULN_FOUND_BUT_NOT_MALE). ': '.$ulnString;
            return;

        } else {

            //Validate pedigree if it exists (by checking if animal is in the database or not)
            $pedigreeCodeExists = Validator::verifyPedigreeCodeInAnimalArray($this->em, $ramArray, false);
            if($pedigreeCodeExists) {
                //If animal is in database, verify the gender

                $pedigreeCountryCode = $ramArray[JsonInputConstant::PEDIGREE_COUNTRY_CODE];
                $pedigreeNumber = $ramArray[JsonInputConstant::PEDIGREE_NUMBER];
                $pedigreeCode = $pedigreeCountryCode . $pedigreeNumber;

                $sql = "SELECT id, uln_country_code, uln_number, pedigree_country_code, pedigree_number, type
                    FROM animal WHERE pedigree_country_code = '$pedigreeCountryCode' AND pedigree_number = '$pedigreeNumber'";
                $results = $this->conn->query($sql)->fetchAll();

                if (count($results) === 0) {
                    $this->inputErrors[] = $this->translateErrorMessages(self::RAM_PEDIGREE_NOT_FOUND) . ': ' . $pedigreeCode;
                    return;
                }

                foreach ($results as $result) {
                    if ($result['type'] === 'Ram') {
                        $this->ramData = $result;
                        return;
                    }
                }

                $this->inputErrors[] = $this->translateErrorMessages(self::RAM_PEDIGREE_FOUND_BUT_NOT_MALE). ': ' . $pedigreeCode;
                return;

            } else {
                $this->inputErrors[] = $this->translateErrorMessages(self::RAM_PEDIGREE_NOT_FOUND);
            }
        }
    }


    /**
     * @param array $ewesArray
     * @return bool
     */
    private function validateEwesArray($ewesArray)
    {

        $requestedEweUlnStrings = [];
        foreach ($ewesArray as $eweArray)
        {
            $ulnString = NullChecker::getUlnStringFromArray($eweArray, null);
            if($ulnString == null) {
                $this->inputErrors[] = $this->translateErrorMessages(self::EWE_MISSING_INPUT);
                return false;
            }


            $isUlnFormatValid = Validator::verifyUlnFormat($ulnString);
            if(!$isUlnFormatValid) {
                $this->inputErrors[] = $this->translateErrorMessages(self::EWE_ULN_FORMAT_INCORRECT) . ': '.$ulnString;
            }

            $requestedEweUlnStrings[$ulnString] = $ulnString;
        }

        if (count($this->inputErrors) > 0) {
            return;
        }


        $sql = "SELECT a.id, uln_country_code, uln_number, pedigree_country_code, pedigree_number, a.type, l.ubn, c.owner_id
                FROM animal a 
                  LEFT JOIN location l ON l.id = a.location_id
                  LEFT JOIN company c ON c.id = l.company_id
                WHERE ". SqlUtil::getUlnQueryFilter($ewesArray,'a.');
        $results = $this->conn->query($sql)->fetchAll();

        $this->ewesData = [];


        $foundUlns = [];
        $nonEweUlns = [];
        foreach ($results as $result)
        {
            $ulnString = $result[JsonInputConstant::ULN_COUNTRY_CODE] . $result[JsonInputConstant::ULN_NUMBER];
            $foundUlns[$ulnString] = $ulnString;

            if ($result['type'] === 'Ewe') {
                $this->ewesData[$ulnString] = $result;
            } else {
                $nonEweUlns[$ulnString] = $ulnString;
            }
        }


        //Check if any found uln only belong to non Ewes
        foreach ($nonEweUlns as $nonEweUln) {
            if (!key_exists($nonEweUln, $foundUlns)) {
                $this->inputErrors[] = $this->translateErrorMessages(self::EWE_FOUND_BUT_NOT_EWE). ': '.$nonEweUln;
            }
        }


        //Check for missing ewes
        foreach ($requestedEweUlnStrings as $requestedEweUlnString)
        {
            if (!key_exists($requestedEweUlnString, $foundUlns)) {
                $this->inputErrors[] = $this->translateErrorMessages(self::EWE_NO_ANIMAL_FOUND). ': '.$requestedEweUlnString;
            }
        }



        //Check ownership if not admin

        if ($this->client instanceof Client) {
            $clientId = $this->client->getId();
            foreach ($this->ewesData as $ulnString => $eweData)
                if ($clientId !== $eweData['owner_id']) {
                    $this->inputErrors[] = $this->translateErrorMessages(self::EWE_NOT_OF_CLIENT) . ': ' .$ulnString;
                }
        }

        return true;
    }

    /**
     * @return JsonResponse
     */
    private function getPdfReport(Person $person, Location $location, CompanyRegisterReportOptions $options)
    {
        $reportData = $this->reportResults->getData();
        $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

        return $this->getPdfReportBase(self::TWIG_FILE, $reportData, false);
    }

    /**
     * @return JsonResponse
     * @throws \Exception
     */
    private function getCsvReport(Person $person, Location $location, CompanyRegisterReportOptions $options)
    {
        return $this->generateFile($this->filename,
            $this->reportResults->getCsvData(),self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }
}
