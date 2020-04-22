<?php

namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Entity\InbreedingCoefficientRepository;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\Locale;
use AppBundle\model\ParentIdsPair;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientReportUpdaterService;
use AppBundle\Util\NullChecker;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Response;

class InbreedingCoefficientReportService extends ReportServiceBase
{
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

    //Validation
    const MAX_EWES_COUNT = 50; // -1 = no limit, also update error message when updating max count
    const EWES_COUNT_EXCEEDS_MAX = 'THE AMOUNT OF SELECTED EWES EXCEEDED 50';


    /** @var ArrayCollection */
    private $content;
    /** @var array */
    private $ramData;
    /** @var array */
    private $ewesData;

    /** @var InbreedingCoefficientReportUpdaterService */
    private $inbreedingCoefficientReportUpdaterService;
    /** @var ParentIdsPair[]|array */
    private $parentIdsPairs;

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
     * @param InbreedingCoefficientReportUpdaterService $inbreedingCoefficientReportUpdaterService
     */
    public function setInbreedingCoefficientReportUpdaterService(InbreedingCoefficientReportUpdaterService $inbreedingCoefficientReportUpdaterService)
    {
        $this->inbreedingCoefficientReportUpdaterService = $inbreedingCoefficientReportUpdaterService;
    }

    /**
     * @param Person $person
     * @param $content
     * @param $fileType
     * @param $locale
     * @return JsonResponse
     * @throws \Exception
     */
    public function getReport(Person $person, $content, $fileType, $locale)
    {
        $this->content = $content;
        $this->client = $person instanceof Client ? $person : null;
        $this->retrieveAndValidateInput();

        if (count($this->inputErrors) > 0) {
            return ResultUtil::errorResult('',Response::HTTP_BAD_REQUEST, $this->inputErrors);
        }

        $this->setLocale($locale);

        $this->setFileName();
        $this->setFolderName();

        $this->generateAndRetrieveInbreedingCoefficients();
        $existingInbreedingCoefficients = $this->getExistingInbreedingCoefficients();

        $this->reportResults = new InbreedingCoefficientReportData($this->em, $this->translator, $this->ramData,
            $this->ewesData, $existingInbreedingCoefficients,
            $this->client);

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


    private function generateAndRetrieveInbreedingCoefficients(int $loopCount = 0)
    {
        $maxTries = 5;

        try {
            $existingInbreedingCoefficients = $this->getExistingInbreedingCoefficients();

            /** @var int[]|array $inbreedingCoefficientKeys */
            $inbreedingCoefficientKeys = array_map(function(InbreedingCoefficient $inbreedingCoefficient) {
                return $inbreedingCoefficient->getPairId();
            }, $existingInbreedingCoefficients);

            $pairsWithoutInbreedingCoefficient = [];
            foreach ($this->parentIdsPairs as $parentIdsPair) {
                $inbreedingCoefficientKey = InbreedingCoefficient::generatePairId($parentIdsPair->getRamId(), $parentIdsPair->getEweId());
                if (!in_array($parentIdsPair, $inbreedingCoefficientKeys) && !key_exists($inbreedingCoefficientKey, $pairsWithoutInbreedingCoefficient)) {
                    $pairsWithoutInbreedingCoefficient[$inbreedingCoefficientKey] = $parentIdsPair;
                }
            }

            $this->inbreedingCoefficientReportUpdaterService->generateInbreedingCoefficients($pairsWithoutInbreedingCoefficient,false);
        } catch (UniqueConstraintViolationException $exception) {
            if ($loopCount <= $maxTries) {
                $this->generateAndRetrieveInbreedingCoefficients(++$loopCount);
            } else {
                throw $exception;
            }
        }
    }


    /**
     * @return array|InbreedingCoefficient[]
     */
    private function getExistingInbreedingCoefficients(): array {
        /** @var InbreedingCoefficientRepository $inbreedingCoefficientRepository */
        $inbreedingCoefficientRepository = $this->em->getRepository(InbreedingCoefficient::class);
        return $inbreedingCoefficientRepository->findByPairs($this->parentIdsPairs);
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

        $this->setParentIdsPairs();
    }


    private function setParentIdsPairs() {
        $this->parentIdsPairs = [];
        $ramId = $this->ramData['id'];
        foreach ($this->ewesData as $eweData) {
            $eweId = $eweData['id'];
            $key = $ramId . '-' . $eweId;
            if (key_exists($key, $this->parentIdsPairs)) {
                continue;
            }
            $this->parentIdsPairs[] = new ParentIdsPair($ramId, $eweId);
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


    private function validateEwesArrayInputFormat($ewesArray): array
    {
        $requestedEweUlnStrings = [];

        if (empty($ewesArray)) {
            $this->inputErrors[] = $this->translateErrorMessages(self::EWE_MISSING_INPUT);
        } else {
            foreach ($ewesArray as $eweArray)
            {
                $ulnString = NullChecker::getUlnStringFromArray($eweArray, null);
                if($ulnString == null) {
                    $this->inputErrors[] = $this->translateErrorMessages(self::EWE_MISSING_INPUT);
                    break;
                }


                $isUlnFormatValid = Validator::verifyUlnFormat($ulnString);
                if(!$isUlnFormatValid) {
                    $this->inputErrors[] = $this->translateErrorMessages(self::EWE_ULN_FORMAT_INCORRECT) . ': '.$ulnString;
                }

                $requestedEweUlnStrings[$ulnString] = $ulnString;
            }
        }

        return $requestedEweUlnStrings;
    }


    /**
     * @param array $ewesArray
     * @return bool
     */
    private function validateEwesArray($ewesArray)
    {
        $requestedEweUlnStrings = $this->validateEwesArrayInputFormat($ewesArray);

        if (empty($requestedEweUlnStrings)) {
            return false;
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

        $this->validateEwesOwnerShip();

        return true;
    }

    /**
     * Check ownership if not admin
     */
    private function validateEwesOwnerShip() {
        if ($this->client instanceof Client) {
            $clientId = $this->client->getId();
            foreach ($this->ewesData as $ulnString => $eweData) {
                if ($clientId !== $eweData['owner_id']) {
                    $this->inputErrors[] = $this->translateErrorMessages(self::EWE_NOT_OF_CLIENT) . ': ' .$ulnString;
                }
            }
        }
    }



    /**
     * @return JsonResponse
     */
    private function getPdfReport()
    {
        $reportData = $this->reportResults->getData();
        $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

        return $this->getPdfReportBase(self::TWIG_FILE, $reportData, false);
    }


    /**
     * @return JsonResponse
     * @throws \Exception
     */
    private function getCsvReport()
    {
        return $this->generateFile($this->filename,
            $this->reportResults->getCsvData(),self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }


}
