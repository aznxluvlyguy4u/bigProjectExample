<?php

namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\Locale;
use AppBundle\Enumerator\PedigreeMasterKey;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\PedigreeUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InbreedingCoefficientReportService extends ReportServiceBase
{
    const GENERATION_OF_ASCENDANTS = 7;
    const MAX_GENERATION_OF_ASCENDANTS = 8;

    const TITLE = 'inbreeding coefficient report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const TWIG_FILE = 'Report/inbreeding_coefficient_report.html.twig';

    const PEDIGREE_NULL_FILLER = '-';
    const ULN_NULL_FILLER = '-';


    //Error messages
    const RAM_MISSING_INPUT               = 'STUD RAM: NO ULN OR PEDIGREE GIVEN';
    const RAM_ULN_FORMAT_INCORRECT        = 'STUD RAM: ULN FORMAT INCORRECT';
    const RAM_ULN_FOUND_BUT_NOT_MALE      = 'STUD RAM: ANIMAL FOUND IN DATABASE WITH GIVEN ULN IS NOT MALE';
    const RAM_PEDIGREE_FOUND_BUT_NOT_MALE = 'STUD RAM: ANIMAL FOUND IN DATABASE WITH GIVEN PEDIGREE IS NOT MALE';
    const RAM_PEDIGREE_NOT_FOUND          = 'STUD RAM: NO ANIMAL FOUND FOR GIVEN PEDIGREE';
    const RAM_ULN_NOT_FOUND               = 'STUD RAM: NO ANIMAL FOUND FOR GIVEN ULN';

    const EWE_MISSING_INPUT     = 'STUD EWE: NO ULN GIVEN';
    const EWE_ULN_FORMAT_INCORRECT = 'STUD EWE: ULN FORMAT INCORRECT';
    const EWE_NO_ANIMAL_FOUND   = 'STUD EWE: NO ANIMAL FOUND FOR GIVEN ULN';
    const EWE_FOUND_BUT_NOT_EWE = 'STUD EWE: ANIMAL WAS FOUND FOR GIVEN ULN, BUT WAS NOT AN EWE ENTITY';
    const EWE_NOT_OF_CLIENT     = 'STUD EWE: FOUND EWE DOES NOT BELONG TO CLIENT';

    const MAX_GENERATIONS_LIMIT_EXCEEDED     = 'MAX GENERATIONS LIMIT OF 8 EXCEEDED';

    //Validation
    const MAX_EWES_COUNT = 50; // -1 = no limit, also update error message when updating max count
    const EWES_COUNT_EXCEEDS_MAX = 'THE AMOUNT OF SELECTED EWES EXCEEDED 50';


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

    /** @var array */
    private static $customReportFilenames = [
        Locale::NL => 'inteeltcoeffient rapportage',
    ];

    /** @var array */
    private static $customReportFolderNames = [
        Locale::NL => 'inteeltcoeffient rapportage',
    ];


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getReport(Request $request)
    {
        $client = $this->userService->getAccountOwner($request);
        $this->content = RequestUtil::getContentAsArray($request);
        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY);

        $this->retrieveAndValidateInput();

        if (count($this->inputErrors) > 0) {
            return ResultUtil::errorResult('',Response::HTTP_BAD_REQUEST, $this->inputErrors);
        }

        $this->setLocaleFromQueryParameter($request);

        $this->setFileName();
        $this->setFolderName();

        $this->reportResults = new InbreedingCoefficientReportData($this->em, $this->translator, $this->ramData, $this->ewesData,
            $this->generationOfAscendants, $client);

        if ($fileType === FileType::CSV) {
            return $this->getCsvReport();
        }

        return $this->getPdfReport();
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

        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            $clientId = $this->getUser()->getId();
            foreach ($this->ewesData as $ulnString => $eweData)
            if ($clientId !== $eweData['owner_id']) {
                $this->inputErrors[] = $this->translateErrorMessages(self::EWE_NOT_OF_CLIENT) . ': ' .$ulnString;
            }
        }
    }



    /**
     * @return JsonResponse
     */
    private function getPdfReport()
    {
        $reportData = $this->reportResults->getData();
        $reportData[ReportLabel::IMAGES_DIRECTORY] = FilesystemUtil::getImagesDirectory($this->rootDir);

        return $this->getPdfReportBase(self::TWIG_FILE, $reportData, false);
    }


    /**
     * @return JsonResponse
     */
    private function getCsvReport()
    {
        return $this->generateFile($this->filename,
            $this->reportResults->getCsvData(),self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }


}