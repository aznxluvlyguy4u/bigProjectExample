<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Component\Count;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\Locale;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StoredProcedure;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class LiveStockReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'livestock_report';
    const TWIG_FILE = 'Report/livestock_report.html.twig';

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;

    const FILE_NAME_REPORT_TYPE = 'stallijst';
    const PEDIGREE_NULL_FILLER = '-';
    const ULN_NULL_FILLER = '-';
    const NEUTER_STRING = '-';
    const EWE_LETTER = 'O';
    const RAM_LETTER = 'R';

    /** @var Client */
    private $client;
    /** @var Location */
    private $location;

    /** @var ArrayCollection */
    private $content;
    /** @var array */
    private $data;
    /** @var bool */
    private $concatValueAndAccuracy;
    /** @var string */
    private $fileType;

    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvFromSqlResultsWriterService $csvWriter,
                                UserService $userService, TwigEngine $templating, TranslatorInterface $translator,
                                GeneratorInterface $knpGenerator,
                                BreedValuesReportQueryGenerator $breedValuesReportQueryGenerator,
                                $cacheDir, $rootDir
    )
    {
        parent::__construct($em, $excelService, $logger, $storageService, $csvWriter, $userService, $templating, $translator,
            $knpGenerator, $breedValuesReportQueryGenerator, $cacheDir, $rootDir, self::TITLE, self::TITLE);

    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getReport(Request $request)
    {
        $this->client = $this->userService->getAccountOwner($request);
        $this->location = $this->userService->getSelectedLocation($request);
        $this->fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY);
        $this->concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, self::CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT);
        $this->content = RequestUtil::getContentAsArray($request, true, null);

        $validationResult = $this->validateContent();
        if ($validationResult instanceof JsonResponse) { return $validationResult; }

        $this->setLocaleFromQueryParameter($request);

        $this->filename = self::FILE_NAME_REPORT_TYPE.'_'.$this->location->getUbn();

        $this->getReportData();

        if ($this->fileType === FileType::CSV) {
            return $this->getCsvReport();
        }

        return $this->getPdfReport();
    }


    /**
     * @return JsonResponse|bool
     */
    private function validateContent()
    {
        if ($this->content === null) { return true; }

        if (!$this->content->containsKey(JsonInputConstant::ANIMALS)) {
            //if 'animals' key is missing, process as default full livestock report
            $this->content = null;
            return true;
        }

        $animalUlns = $this->content->get(JsonInputConstant::ANIMALS);

        $mandatoryKeys = [
            JsonInputConstant::ULN_COUNTRY_CODE,
            JsonInputConstant::ULN_NUMBER,
            ];

        foreach ($animalUlns as $animalUln)
        {
            foreach ($mandatoryKeys as $mandatoryKey) {
                if (!key_exists($mandatoryKey, $animalUln)) {
                    return ResultUtil::errorResult("'".$mandatoryKey."' key is missing", 428);
                }
            }
        }

        //TODO Validate if animals belong to current livestock
        $isValidLivestockAnimal = true;

        //TODO Validate if animals belong to historic livestock
        $isValidHistoricLiveStockAnimal = true;

        if (!$isValidLivestockAnimal && !$isValidHistoricLiveStockAnimal) {
            return ResultUtil::errorResult('List contains animals not on current nor historic livestock list of ubn', 428);
        }

        return true;
    }


    /**
     * @return JsonResponse
     */
    private function getPdfReport()
    {
        return $this->getPdfReportBase(self::TWIG_FILE, $this->data, true);
    }


    private function getCsvReport()
    {
        $this->extension = FileType::CSV;

        $keysToIgnore = [
            'a_uln_without_order_number',
            'f_uln_without_order_number',
            'f_animal_order_number',
            'm_uln_without_order_number',
            'm_animal_order_number',
            'a_lamb_meat_index',
            'f_lamb_meat_index',
            'm_lamb_meat_index',
            'a_production_age',
            'a_litter_count',
            'a_total_offspring_count',
            'a_born_alive_offspring_count',
            'a_gave_birth_as_one_year_old',
            'f_production_age',
            'f_litter_count',
            'f_total_offspring_count',
            'f_born_alive_offspring_count',
            'f_gave_birth_as_one_year_old',
            'm_production_age',
            'm_litter_count',
            'm_total_offspring_count',
            'm_born_alive_offspring_count',
            'm_gave_birth_as_one_year_old',
        ];

        $csvData = $this->unsetNestedKeys($this->data, $keysToIgnore);
        $csvData = $this->translateColumnHeaders($csvData);
        $csvData = $this->moveBreedValueColumnsToEndArray($csvData);

        return $this->generateFile($this->filename, $csvData,
            self::TITLE,FileType::CSV,!ReportAPIController::IS_LOCAL_TESTING
        );
    }


    /**
     * @param string $value
     * @return string
     */
    private function trans($value)
    {
        return $this->translator->trans($value);
    }


    private function translateColumnHeaders($csvData)
    {
        if ($this->translator->getLocale() === Locale::EN) {
            return $csvData;
        }

        foreach ($csvData as $item => $records) {
            foreach ($records as $columnHeader => $value) {

                $prefix = mb_substr($columnHeader, 0, 2);
                $upperSuffix = strtoupper(mb_substr($columnHeader, 2, strlen($columnHeader)-2));

                switch ($prefix) {
                    case 'a_': $translatedColumnHeader = $this->trans('A') . '_' . $this->trans($upperSuffix); break;
                    case 'f_': $translatedColumnHeader = $this->trans('F') . '_' . $this->trans($upperSuffix); break;
                    case 'm_': $translatedColumnHeader = $this->trans('M') . '_' . $this->trans($upperSuffix); break;
                    default: $translatedColumnHeader = $this->trans(strtoupper($columnHeader)); break;
                }

                $translatedColumnHeader = strtr(strtolower($translatedColumnHeader), [' ' => '_']);

                if ($columnHeader !== $translatedColumnHeader) {
                    $csvData[$item][strtolower($translatedColumnHeader)] = $value;
                    unset($csvData[$item][$columnHeader]);
                }
            }
        }

        return $csvData;
    }


    private function getReportData()
    {
        if ($this->fileType === FileType::CSV) {
            $this->data = $this->retrieveLiveStockDataForCsv();
        } else {
            $this->data = [];
            $this->data[ReportLabel::DATE] = TimeUtil::getTimeStampToday('d-m-Y');
            $this->data[ReportLabel::BREEDER_NUMBER] = '-'; //TODO
            $this->data[ReportLabel::UBN] = $this->location->getUbn();
            $this->data[ReportLabel::NAME.'_and_'.ReportLabel::ADDRESS] = $this->parseNameAddressString();
            $this->data[ReportLabel::LIVESTOCK] = Count::getLiveStockCountLocation($this->em, $this->location, true);
            $this->data[ReportLabel::IMAGES_DIRECTORY] = FilesystemUtil::getImagesDirectory($this->rootDir);
            $this->data[ReportLabel::ANIMALS] = $this->retrieveLiveStockDataForPdf();
        }
    }


    /**
     * @return array
     */
    private function retrieveLiveStockDataForPdf()
    {
        if ($this->content !== null) {
            $matchLocationOfSelectedAnimals = false; //This ensures inclusion of historic animals
            $sql = StoredProcedure::createLiveStockReportSqlBase($this->location->getId(), $this->content->get(JsonInputConstant::ANIMALS), $matchLocationOfSelectedAnimals);
            $results = $this->conn->query($sql)->fetchAll();
        } else {
            $results = StoredProcedure::getProcedure($this->conn, StoredProcedure::GET_LIVESTOCK_REPORT, [$this->location->getId()]);
        }

        $keys = array_keys($results);
        foreach ($keys as $key) {
            $results[$key]['a_uln_without_order_number'] = StringUtil::getUlnWithoutOrderNumber($results[$key]['a_uln'], $results[$key]['a_animal_order_number']);
            $results[$key]['f_uln_without_order_number'] = StringUtil::getUlnWithoutOrderNumber($results[$key]['f_uln'], $results[$key]['f_animal_order_number']);
            $results[$key]['m_uln_without_order_number'] = StringUtil::getUlnWithoutOrderNumber($results[$key]['m_uln'], $results[$key]['m_animal_order_number']);

            $results[$key]['gender'] = $this->getGenderLetter($results[$key]['gender']);

            $results[$key]['a_date_of_birth'] = TimeUtil::flipDateStringOrder($results[$key]['a_date_of_birth']);
            $results[$key]['f_date_of_birth'] = TimeUtil::flipDateStringOrder($results[$key]['f_date_of_birth']);
            $results[$key]['m_date_of_birth'] = TimeUtil::flipDateStringOrder($results[$key]['m_date_of_birth']);

            $results[$key]['a_n_ling'] = str_replace('-ling', '', $results[$key]['a_n_ling']);
            $results[$key]['f_n_ling'] = str_replace('-ling', '', $results[$key]['f_n_ling']);
            $results[$key]['m_n_ling'] = str_replace('-ling', '', $results[$key]['m_n_ling']);

            $results[$key]['a_production'] = $this->getProduction($results, $key, 'a');
            $results[$key]['m_production'] = $this->getProduction($results, $key, 'm');
            $results[$key]['f_production'] = $this->getProduction($results, $key, 'f');

            $results[$key]['a_lamb_meat_index'] = BreedFormat::getJoinedLambMeatIndex($results[$key]['a_lamb_meat_index_value'], $results[$key]['a_lamb_meat_accuracy']);
            $results[$key]['m_lamb_meat_index'] = BreedFormat::getJoinedLambMeatIndex($results[$key]['m_lamb_meat_index_value'], $results[$key]['m_lamb_meat_accuracy']);
            $results[$key]['f_lamb_meat_index'] = BreedFormat::getJoinedLambMeatIndex($results[$key]['f_lamb_meat_index_value'], $results[$key]['f_lamb_meat_accuracy']);

            $results[$key]['a_breed_value_growth'] = BreedFormat::formatGrowthBreedValue($results[$key]['a_breed_value_growth_value'], $results[$key]['a_breed_value_growth_accuracy']);
            $results[$key]['m_breed_value_growth'] = BreedFormat::formatGrowthBreedValue($results[$key]['m_breed_value_growth_value'], $results[$key]['m_breed_value_growth_accuracy']);
            $results[$key]['f_breed_value_growth'] = BreedFormat::formatGrowthBreedValue($results[$key]['f_breed_value_growth_value'], $results[$key]['f_breed_value_growth_accuracy']);

            $results[$key]['a_breed_value_muscle_thickness'] = BreedFormat::formatMuscleThicknessBreedValue($results[$key]['a_breed_value_muscle_thickness_value'], $results[$key]['a_breed_value_muscle_thickness_accuracy']);
            $results[$key]['m_breed_value_muscle_thickness'] = BreedFormat::formatMuscleThicknessBreedValue($results[$key]['m_breed_value_muscle_thickness_value'], $results[$key]['m_breed_value_muscle_thickness_accuracy']);
            $results[$key]['f_breed_value_muscle_thickness'] = BreedFormat::formatMuscleThicknessBreedValue($results[$key]['f_breed_value_muscle_thickness_value'], $results[$key]['f_breed_value_muscle_thickness_accuracy']);

            $results[$key]['a_breed_value_fat'] = BreedFormat::formatFatThickness3BreedValue($results[$key]['a_breed_value_fat_value'], $results[$key]['a_breed_value_fat_accuracy']);
            $results[$key]['m_breed_value_fat'] = BreedFormat::formatFatThickness3BreedValue($results[$key]['m_breed_value_fat_value'], $results[$key]['m_breed_value_fat_accuracy']);
            $results[$key]['f_breed_value_fat'] = BreedFormat::formatFatThickness3BreedValue($results[$key]['f_breed_value_fat_value'], $results[$key]['f_breed_value_fat_accuracy']);

            $results[$key]['a_breed_value_litter_size'] = BreedFormat::formatBreedValue($results[$key]['a_breed_value_litter_size_value'], $results[$key]['a_breed_value_litter_size_accuracy']);
            $results[$key]['m_breed_value_litter_size'] = BreedFormat::formatBreedValue($results[$key]['m_breed_value_litter_size_value'], $results[$key]['m_breed_value_litter_size_accuracy']);
            $results[$key]['f_breed_value_litter_size'] = BreedFormat::formatBreedValue($results[$key]['f_breed_value_litter_size_value'], $results[$key]['f_breed_value_litter_size_accuracy']);


            // Format Predicate values
            $results[$key]['a_predicate'] = DisplayUtil::parsePredicateString($results[$key]['a_predicate_value'], $results[$key]['a_predicate_score']);
            $results[$key]['f_predicate'] = DisplayUtil::parsePredicateString($results[$key]['f_predicate_value'], $results[$key]['f_predicate_score']);
            $results[$key]['m_predicate'] = DisplayUtil::parsePredicateString($results[$key]['m_predicate_value'], $results[$key]['m_predicate_score']);
            unset($results[$key]['a_predicate_value']);
            unset($results[$key]['f_predicate_value']);
            unset($results[$key]['m_predicate_value']);
            unset($results[$key]['a_predicate_score']);
            unset($results[$key]['f_predicate_score']);
            unset($results[$key]['m_predicate_score']);

        }

        return $this->orderSqlResultsByOrderOfAnimalsInJsonBody($results);
    }


    /**
     * @return array
     */
    private function retrieveLiveStockDataForCsv()
    {
        $animals = [];
        $matchLocationOfSelectedAnimals = true;

        if ($this->content !== null) {
            $matchLocationOfSelectedAnimals = false; //This ensures inclusion of historic animals
            $animals = $this->content->get(JsonInputConstant::ANIMALS);
        }

        $sql = $this->breedValuesReportQueryGenerator->createLiveStockReportQuery(
            $this->location->getId(),
            $animals,
            $matchLocationOfSelectedAnimals,
            $this->concatValueAndAccuracy,
            true
        );

        $results = $this->conn->query($sql)->fetchAll();

        $keys = array_keys($results);
        foreach ($keys as $key) {

            $results[$key]['gender'] = $this->getGenderLetter($results[$key]['gender']);

            $results[$key]['a_n_ling'] = str_replace('-ling', '', $results[$key]['a_n_ling']);
            $results[$key]['f_n_ling'] = str_replace('-ling', '', $results[$key]['f_n_ling']);
            $results[$key]['m_n_ling'] = str_replace('-ling', '', $results[$key]['m_n_ling']);

            // Format Predicate values
            $results[$key]['a_predicate'] = DisplayUtil::parsePredicateString($results[$key]['a_predicate_value'], $results[$key]['a_predicate_score']);
            $results[$key]['f_predicate'] = DisplayUtil::parsePredicateString($results[$key]['f_predicate_value'], $results[$key]['f_predicate_score']);
            $results[$key]['m_predicate'] = DisplayUtil::parsePredicateString($results[$key]['m_predicate_value'], $results[$key]['m_predicate_score']);
            unset($results[$key]['a_predicate_value']);
            unset($results[$key]['f_predicate_value']);
            unset($results[$key]['m_predicate_value']);
            unset($results[$key]['a_predicate_score']);
            unset($results[$key]['f_predicate_score']);
            unset($results[$key]['m_predicate_score']);

        }

        return $this->orderSqlResultsByOrderOfAnimalsInJsonBody($results);
    }


    /**
     * @param array $results
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function moveBreedValueColumnsToEndArray(array $results)
    {
        $availableBreedColumnValues = $this->getAvailableBreedValueColumns($results);

        $keys = array_keys($results);
        foreach ($keys as $key) {

            // Place the breedValues at the end of the csv file
            foreach ($availableBreedColumnValues as $availableBreedColumnValue) {
                if (key_exists($availableBreedColumnValue, $results[$key])) {
                    $value = $results[$key][$availableBreedColumnValue];
                    unset($results[$key][$availableBreedColumnValue]);
                    $results[$key][$availableBreedColumnValue] = $value;
                }
            }

        }

        return $results;
    }


    /**
     * @param array $results
     * @return array
     */
    private function orderSqlResultsByOrderOfAnimalsInJsonBody(array $results)
    {
        if ($this->content === null) {
            return $results;
        }

        //Order results by order in jsonBody
        $orderedResults = [];

        foreach ($this->content->get(JsonInputConstant::ANIMALS) as $ordinal => $ulnSet) {
            $uln = $ulnSet[JsonInputConstant::ULN_COUNTRY_CODE] . $ulnSet[JsonInputConstant::ULN_NUMBER];

            foreach ($results as $resultKey => $result)
            {
                if ($result['a_uln'] === $uln) {
                    $orderedResults[$ordinal] = $result;
                    break;
                }
            }
        }

        return $orderedResults;
    }


    /**
     * @param array $csvResults
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAvailableBreedValueColumns(array $csvResults)
    {
        if (count($csvResults) === 0) {
            return [];
        }

        $allPossibleBreedCodeNames = [];

        $allCsvColumns = array_keys(reset($csvResults));

        $allBreedCodeNames = [];
        $sql = "SELECT nl FROM breed_value_type;";
        foreach ($this->conn->query($sql)->fetchAll() as $value) {
            $columnName = strtolower($value['nl']);
            $allBreedCodeNames[$columnName] = $columnName;
            if (!$this->concatValueAndAccuracy) {
                $accuracyColumnName = $columnName . BreedValuesReportQueryGenerator::ACCURACY_TABLE_LABEL_SUFFIX;
                $allBreedCodeNames[$accuracyColumnName] = $accuracyColumnName;
            }
        }

        foreach ($allCsvColumns as $columnName) {
            if (key_exists($columnName, $allBreedCodeNames)) {
                $allPossibleBreedCodeNames[$columnName] = $columnName;
            }
        }

        return $allPossibleBreedCodeNames;
    }


    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @return string
     */
    private function parseNameAddressString()
    {
        $address = $this->location->getAddress();
        $streetNameAndNumber = $address->getFullStreetNameAndNumber();
        $streetNameAndNumber = $streetNameAndNumber != null ? $streetNameAndNumber.', ' : '';
        return $this->location->getUbn().', '.$this->client->getFullName().', '.$streetNameAndNumber.$address->getPostalCode().', '.$address->getCity();
    }


    /**
     * @param array $results
     * @param int $key
     * @param string $animalPrefix
     * @return string
     */
    private function getProduction($results, $key, $animalPrefix)
    {
        $productionAge = intval($results[$key][$animalPrefix.'_production_age']);
        $litterCount = intval($results[$key][$animalPrefix.'_litter_count']);
        $totalOffSpringCount = intval($results[$key][$animalPrefix.'_total_offspring_count']);
        $bornAliveOffspringCount = intval($results[$key][$animalPrefix.'_born_alive_offspring_count']);
        $addProductionAsterisk = boolval($results[$key][$animalPrefix.'_gave_birth_as_one_year_old']);
        return DisplayUtil::parseProductionStringFromGivenParts($productionAge, $litterCount, $totalOffSpringCount, $bornAliveOffspringCount, $addProductionAsterisk);
    }


    /**
     * @param string $genderEnglish
     * @return string
     */
    private function getGenderLetter($genderEnglish)
    {
        /* variables translated to Dutch */
        if($genderEnglish == 'Ram' || $genderEnglish == GenderType::MALE || $genderEnglish == GenderType::M) {
            return self::RAM_LETTER;
        } elseif ($genderEnglish == 'Ewe' || $genderEnglish == GenderType::FEMALE || $genderEnglish == GenderType::V) {
            return self::EWE_LETTER;
        } else {
            return self::NEUTER_STRING;
        }
    }

}