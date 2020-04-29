<?php

namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Entity\InbreedingCoefficientRepository;
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\Locale;
use AppBundle\Exception\BadRequestHttpExceptionWithMultipleErrors;
use AppBundle\model\report\InbreedingCoefficientInput;
use AppBundle\model\ParentIdsPair;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientReportUpdaterService;
use AppBundle\Setting\InbreedingCoefficientSetting;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class InbreedingCoefficientReportService extends ReportServiceBase
{
    const TITLE = 'inbreeding coefficient report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const TWIG_FILE = 'Report/inbreeding_coefficient_report.html.twig';


    //Error messages
    const RAM_MISSING_INPUT               = 'STUD RAM: NO ULN OR PEDIGREE GIVEN';
    const RAM_ULN_FORMAT_INCORRECT        = 'STUD RAM: ULN FORMAT INCORRECT';
    const RAM_ULN_FOUND_BUT_NOT_MALE      = 'STUD RAM: ANIMAL FOUND IN DATABASE WITH GIVEN ULN IS NOT MALE';
    const RAM_ULN_NOT_FOUND               = 'STUD RAM: NO ANIMAL FOUND FOR GIVEN ULN';

    const EWE_MISSING_INPUT     = 'STUD EWE: NO ULN GIVEN';
    const EWE_ULN_FORMAT_INCORRECT = 'STUD EWE: ULN FORMAT INCORRECT';
    const EWE_NO_ANIMAL_FOUND   = 'STUD EWE: NO ANIMAL FOUND FOR GIVEN ULN';
    const EWE_FOUND_BUT_NOT_EWE = 'STUD EWE: ANIMAL WAS FOUND FOR GIVEN ULN, BUT WAS NOT AN EWE ENTITY';

    //Validation
    const MAX_EWES_COUNT = 200; // -1 = no limit, also update error message when updating max count
    const MAX_RAMS_COUNT = 5; // -1 = no limit, also update error message when updating max count


    /** @var InbreedingCoefficientReportUpdaterService */
    private $inbreedingCoefficientReportUpdaterService;

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
     * @param array $ramsData
     * @param array $ewesData
     * @param $fileType
     * @param $locale
     * @return JsonResponse
     * @throws \Exception
     */
    public function getReport(Person $person, array $ramsData, array $ewesData, $fileType, $locale)
    {
        $this->client = $person instanceof Client ? $person : null;

        $this->setLocale($locale);

        $this->setFileName();
        $this->setFolderName();

        // Note the inbreeding coefficients should already be generated and saved
        $existingInbreedingCoefficients = $this->getExistingInbreedingCoefficients($ramsData, $ewesData);

        $this->reportResults = new InbreedingCoefficientReportData(
            $this->em,
            $this->translator,
            $ramsData,
            $ewesData,
            $existingInbreedingCoefficients,
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


    /**
     * @param array $ramsData
     * @param array $ewesData
     * @return array|InbreedingCoefficient[]
     */
    private function getExistingInbreedingCoefficients(array $ramsData, array $ewesData): array {
        $parentIdsPairs = self::getParentIdsPairsFromDataArrays($ramsData, $ewesData);

        /** @var InbreedingCoefficientRepository $inbreedingCoefficientRepository */
        $inbreedingCoefficientRepository = $this->em->getRepository(InbreedingCoefficient::class);
        return $inbreedingCoefficientRepository->findByPairs($parentIdsPairs);
    }


    /**
     * @param  EntityManagerInterface  $em
     * @param  TranslatorInterface  $translator
     * @param  ArrayCollection  $content
     * @return InbreedingCoefficientInput
     */
    public static function retrieveValidatedInput(
        EntityManagerInterface $em, TranslatorInterface $translator, ArrayCollection $content
    ): InbreedingCoefficientInput
    {
        $inputErrors = [];
        $primaryKeys = [JsonInputConstant::EWES, JsonInputConstant::RAMS];
        foreach ($primaryKeys as $key) {
            if (!$content->containsKey($key)) {
                $inputErrors[] = "Key '" . $key . "' is missing";
            }
        }

        self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);

        $ramsArray = $content->get(JsonInputConstant::RAMS);
        $ewesArray = $content->get(JsonInputConstant::EWES);

        $ramsData = self::getValidatedRamsArray($em, $translator, $ramsArray);
        $ewesData = self::getValidatedEwesArray($em, $translator, $ewesArray);

        return new InbreedingCoefficientInput(
            $ramsData,
            $ewesData
        );
    }


    private static function throwBadRequestExceptionIfInputErrorsExist(array $inputErrors)
    {
        if (count($inputErrors) > 0) {
            throw new BadRequestHttpExceptionWithMultipleErrors($inputErrors);
        }
    }


    private static function getParentIdsPairsFromDataArrays(array $ramsData, array $ewesData) {
        $parentIdsPairs = [];

        foreach ($ramsData as $ramData) {
            $ramId = $ramData['id'];

            foreach ($ewesData as $eweData) {

                $eweId = $eweData['id'];
                $key = $ramId . '-' . $eweId;
                if (key_exists($key, $parentIdsPairs)) {
                    continue;
                }
                $parentIdsPairs[] = new ParentIdsPair($ramId, $eweId);
            }
        }

        return $parentIdsPairs;
    }


    /**
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param array $ramsArray
     * @return array
     */
    private static function getValidatedRamsArray(EntityManagerInterface $em, TranslatorInterface $translator, $ramsArray): array
    {
        $inputErrors = [];
        if(self::MAX_EWES_COUNT > 0 && count($ramsArray) > self::MAX_RAMS_COUNT) {
            $inputErrors[] = $translator->trans('selected.exceeded.rams', ['%max%' => self::MAX_RAMS_COUNT]);
            return $inputErrors;
        }

        $ramsData = [];

        $ordinal = 1;
        foreach ($ramsArray as $ramArray) {
            $ramsData[] = self::getValidatedRamArray($em, $translator, $ramArray, $ordinal);
            $ordinal++;
        }

        if (empty($ramsData)) {
            $inputErrors[] = self::translateIc($translator, self::RAM_MISSING_INPUT);
        }

        self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);

        return $ramsData;
    }


    /**
     * Uses old translation convention
     * @return string|void
     */
    private static function translateIc(TranslatorInterface $translator, $message)
    {
        return ReportServiceBase::translateAllCapsTranslationErrorMessages(
            $translator, $message
        );
    }


    /**
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param $ramArray
     * @param $ordinal
     * @return array
     */
    private static function getValidatedRamArray(EntityManagerInterface $em, TranslatorInterface $translator, $ramArray, $ordinal): array
    {
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $ramArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $ramArray);
        $ulnString = $ulnCountryCode . $ulnNumber;

        $inputErrors = [];

        //Then validate the uln if it exists

        if (!$ulnCountryCode || !$ulnNumber || !Validator::verifyUlnFormat($ulnString)) {
            $inputErrors[] = self::translateIc($translator,self::RAM_ULN_FORMAT_INCORRECT) . ': '.$ulnString;
            self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);
        }

        //If animal is in database, verify the gender

        /** @var Animal $animal */
        $animal = $em->getRepository(Animal::class)->findByUlnCountryCodeAndNumber(
            $ulnCountryCode,$ulnNumber
        );

        if (!$animal) {
            $inputErrors[] = self::translateIc($translator,self::RAM_ULN_NOT_FOUND). ': '.$ulnString;
            self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);
        }

        if (!$animal instanceof Ram) {
            $inputErrors[] = self::translateIc($translator,self::RAM_ULN_FOUND_BUT_NOT_MALE). ': '.$ulnString;
            self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);
        }

        return [
            'id' => $animal->getId(),
            ReportLabel::ORDINAL => $ordinal,
            'uln_country_code' => $animal->getUlnCountryCode(),
            'uln_number' => $animal->getUlnNumber(),
            'pedigree_country_code' => $animal->getPedigreeCountryCode(),
            'pedigree_number' => $animal->getPedigreeNumber(),
            'type' => $animal->getObjectType()
        ];
    }


    public static function getRamsDataByIds(EntityManagerInterface $em, array $ramIds)
    {
        $idFilter = SqlUtil::getIdsFilterListString($ramIds);
        $sql = "SELECT
                    a.id,
                    a.uln_country_code,
                    a.uln_number,
                    a.pedigree_country_code,
                    a.pedigree_number,
                    a.type
                FROM animal a
                WHERE type = 'Ram' AND a.id IN ($idFilter)";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $orderedResults = [];

        foreach ($ramIds as $key => $ramId)
        {
            $ordinal = $key + 1;

            foreach ($results as $data)
            {
                // The intval() is necessary because stored value in the database is returned as a string
                if ($data['id'] === intval($ramId)) {
                    $data[ReportLabel::ORDINAL] = $ordinal;

                    $orderedResults[] = $data;
                    break;
                }
            }
        }

        return $orderedResults;
    }


    public static function getEwesDataByIds(EntityManagerInterface $em, array $eweIds): array
    {
        $idFilter = SqlUtil::getIdsFilterListString($eweIds);
        $where = "WHERE type = 'Ewe' AND a.id IN ($idFilter)";

        $ewesData = self::getEwesBySqlBase($em, $where);

        $orderedResults = [];

        foreach ($eweIds as $eweId)
        {
            foreach ($ewesData as $data)
            {
                // The intval() is necessary because stored value in the database is returned as a string
                if ($data['id'] === intval($eweId)) {
                    $orderedResults[] = $data;
                    break;
                }
            }
        }
        return $orderedResults;
    }


    private static function getEwesBySqlBase(EntityManagerInterface $em, string $where): array
    {
        $sql = "SELECT a.id, uln_country_code, uln_number, pedigree_country_code, pedigree_number, a.type, l.ubn, c.owner_id
FROM animal a
         LEFT JOIN location l ON l.id = a.location_id
         LEFT JOIN company c ON c.id = l.company_id
    ".$where;
        return $em->getConnection()->query($sql)->fetchAll();
    }


    /**
     * @param  TranslatorInterface  $translator
     * @param $ewesArray
     * @return array
     */
    private static function validateEwesArrayInputFormat(TranslatorInterface $translator, $ewesArray): array
    {
        $requestedEweUlnStrings = [];

        $inputErrors = [];

        if (empty($ewesArray)) {
            $inputErrors[] = self::translateIc($translator,self::EWE_MISSING_INPUT);
        } else {
            foreach ($ewesArray as $eweArray)
            {
                $ulnString = NullChecker::getUlnStringFromArray($eweArray, null);
                if($ulnString == null) {
                    $inputErrors[] = self::translateIc($translator,self::EWE_MISSING_INPUT);
                    break;
                }


                $isUlnFormatValid = Validator::verifyUlnFormat($ulnString);
                if(!$isUlnFormatValid) {
                    $inputErrors[] = self::translateIc($translator,self::EWE_ULN_FORMAT_INCORRECT) . ': '.$ulnString;
                }

                $requestedEweUlnStrings[$ulnString] = $ulnString;
            }
        }

        return $requestedEweUlnStrings;
    }


    /**
     * @param  EntityManagerInterface  $em
     * @param  TranslatorInterface  $translator
     * @param $ewesArray
     * @return array
     */
    private static function getValidatedEwesArray(EntityManagerInterface $em, TranslatorInterface $translator, $ewesArray): array
    {
        $inputErrors = [];
        if(self::MAX_EWES_COUNT > 0 && count($ewesArray) > self::MAX_EWES_COUNT) {
            $inputErrors[] = $translator->trans('selected.exceeded.ewes', ['%max%' => self::MAX_EWES_COUNT]);
            self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);
        }

        $requestedEweUlnStrings = self::validateEwesArrayInputFormat($translator, $ewesArray);

        if (empty($requestedEweUlnStrings)) {
            $inputErrors[] = self::translateIc($translator,self::EWE_MISSING_INPUT);
        }

        self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);

        $where = "WHERE ". SqlUtil::getUlnQueryFilter($ewesArray,'a.');
        $results = self::getEwesBySqlBase($em, $where);

        $ewesData = [];

        $foundUlns = [];
        $nonEweUlns = [];

        foreach ($ewesArray as $eweArray) {
            $ulnCountryCode = $eweArray[JsonInputConstant::ULN_COUNTRY_CODE];
            $ulnNumber = $eweArray[JsonInputConstant::ULN_NUMBER];

            foreach ($results as $result)
            {
                // Necessary to keep the original order of the input
                if (
                    $result[JsonInputConstant::ULN_COUNTRY_CODE] === $ulnCountryCode &&
                    $result[JsonInputConstant::ULN_NUMBER] === $ulnNumber
                ) {
                    $ulnString = $result[JsonInputConstant::ULN_COUNTRY_CODE] . $result[JsonInputConstant::ULN_NUMBER];
                    $foundUlns[$ulnString] = $ulnString;

                    if ($result['type'] === 'Ewe') {
                        $ewesData[$ulnString] = $result;
                    } else {
                        $nonEweUlns[$ulnString] = $ulnString;
                    }
                }
            }
        }


        //Check if any found uln only belong to non Ewes
        foreach ($nonEweUlns as $nonEweUln) {
            if (!key_exists($nonEweUln, $foundUlns)) {
                $inputErrors[] = self::translateIc($translator,self::EWE_FOUND_BUT_NOT_EWE). ': '.$nonEweUln;
            }
        }


        //Check for missing ewes
        foreach ($requestedEweUlnStrings as $requestedEweUlnString)
        {
            if (!key_exists($requestedEweUlnString, $foundUlns)) {
                $inputErrors[] = self::translateIc($translator,self::EWE_NO_ANIMAL_FOUND). ': '.$requestedEweUlnString;
            }
        }

        self::throwBadRequestExceptionIfInputErrorsExist($inputErrors);

        return $ewesData;
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


    public static function parseInbreedingCoefficientValueForDisplay(?float $value)
    {
        $displayValue = ($value ? $value : 0.0) * 100;
        return round($displayValue, InbreedingCoefficientSetting::DISPLAY_DECIMAL_PRECISION);
    }
}
