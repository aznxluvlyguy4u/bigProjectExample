<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Component\Count;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StoredProcedure;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;

class LiveStockReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'livestock_report';
    const TWIG_FILE = 'Report/livestock_report.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;

    const FILE_NAME_REPORT_TYPE = 'LIVESTOCK';
    const PEDIGREE_NULL_FILLER = '-';
    const ULN_NULL_FILLER = '-';


    /**
     * @param Person $person
     * @param Location $location
     * @param $fileType
     * @param $concatValueAndAccuracy
     * @param $content
     * @param $locale
     * @return JsonResponse|bool
     */
    public function getReport(Person $person, Location $location, $fileType, $concatValueAndAccuracy, ArrayCollection $content, $locale)
    {
        $this->client = $person;
        $this->location = $location;
        $this->fileType = $fileType;
        $this->concatValueAndAccuracy = $concatValueAndAccuracy;
        $this->content = $content;

        $validationResult = $this->validateContent();
        if ($validationResult instanceof JsonResponse) { return $validationResult; }

        $this->setLocale($locale);

        $this->filename = $this->trans(self::FILE_NAME_REPORT_TYPE).'_'.$this->location->getUbn();
        $this->folderName = self::FOLDER_NAME;

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

        $csvData = $this->unsetNestedKeys($this->getData(), self::getLivestockKeysToIgnore());
        $csvData = $this->translateColumnHeaders($csvData);
        $csvData = $this->moveBreedValueColumnsToEndArray($csvData);

        return $this->generateFile($this->filename, $csvData,
            self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }


    /**
     * @return array
     */
    public static function getLivestockKeysToIgnore()
    {
        return [
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
            BreedValueTypeConstant::NATURAL_LOGARITHM_EGG_COUNT,
            BreedValueTypeConstant::NATURAL_LOGARITHM_EGG_COUNT.BreedValuesReportQueryGenerator::ACCURACY_TABLE_LABEL_SUFFIX,
            BreedValueTypeConstant::IGA_NEW_ZEALAND,
            BreedValueTypeConstant::IGA_NEW_ZEALAND.BreedValuesReportQueryGenerator::ACCURACY_TABLE_LABEL_SUFFIX,
            BreedValueTypeConstant::IGA_SCOTLAND,
            BreedValueTypeConstant::IGA_SCOTLAND.BreedValuesReportQueryGenerator::ACCURACY_TABLE_LABEL_SUFFIX,
        ];
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
            $this->data[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();
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

            $results[$key]['a_lamb_meat_index'] = BreedFormat::getJoinedIndex($results[$key]['a_lamb_meat_index_value'], $results[$key]['a_lamb_meat_accuracy']);
            $results[$key]['m_lamb_meat_index'] = BreedFormat::getJoinedIndex($results[$key]['m_lamb_meat_index_value'], $results[$key]['m_lamb_meat_accuracy']);
            $results[$key]['f_lamb_meat_index'] = BreedFormat::getJoinedIndex($results[$key]['f_lamb_meat_index_value'], $results[$key]['f_lamb_meat_accuracy']);

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
     * @throws DBALException
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
            true,
            true
        );

        $results = $this->preFormatLivestockSqlResult($this->conn->query($sql)->fetchAll());

        return $this->orderSqlResultsByOrderOfAnimalsInJsonBody($results);
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


}