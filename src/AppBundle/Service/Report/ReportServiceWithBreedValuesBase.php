<?php


namespace AppBundle\Service\Report;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\GenderType;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\DisplayUtil;
use AppBundle\Validation\UlnValidatorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;

class ReportServiceWithBreedValuesBase extends ReportServiceBase
{
    const NEUTER_STRING = '-';
    const EWE_LETTER = 'O';
    const RAM_LETTER = 'R';

    const NEUTER_SINGLE_CHAR = 'NEUTER_SINGLE_CHAR';
    const FEMALE_SINGLE_CHAR = 'FEMALE_SINGLE_CHAR';
    const MALE_SINGLE_CHAR = 'MALE_SINGLE_CHAR';

    /** @var Client */
    protected $client;
    /** @var Location */
    protected $location;

    /** @var ArrayCollection */
    protected $content;
    /** @var array */
    protected $data;
    /** @var bool */
    protected $concatValueAndAccuracy;
    /** @var string */
    protected $fileType;


    /** @var BreedValuesReportQueryGenerator */
    protected $breedValuesReportQueryGenerator;

    public function __construct(EntityManagerInterface $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvWriter $csvWriter, UserService $userService,
                                TwigEngine $templating,
                                TranslatorInterface $translator,
                                GeneratorInterface $knpGenerator,
                                UlnValidatorInterface $ulnValidator,
                                BreedValuesReportQueryGenerator $breedValuesReportQueryGenerator,
                                $cacheDir, $rootDir, $outputReportsToCacheFolderForLocalTesting,
                                $displayReportPdfOutputAsHtml
    )
    {
        parent::__construct($em, $excelService, $logger, $storageService, $csvWriter, $userService, $templating, $translator,
            $knpGenerator, $ulnValidator, $cacheDir, $rootDir, $outputReportsToCacheFolderForLocalTesting, $displayReportPdfOutputAsHtml);

        $this->breedValuesReportQueryGenerator = $breedValuesReportQueryGenerator;
    }


    /**
     * @param string $genderEnglish
     * @return string
     */
    protected function getGenderLetter($genderEnglish)
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


    /**
     * @param array $csvResults
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getAvailableBreedValueColumns(array $csvResults)
    {
        if (count($csvResults) === 0) {
            return [];
        }

        $allPossibleBreedCodeNames = [];

        $allCsvColumns = array_keys(reset($csvResults));

        $allBreedCodeNames = [];
        $allTranslatedBreedCodeNames = [];
        $sql = "SELECT nl FROM breed_value_type;";
        foreach ($this->conn->query($sql)->fetchAll() as $value) {
            $columnName = strtolower($value['nl']);
            $allBreedCodeNames[$columnName] = $columnName;

            $translatedColumnName = $this->translateBreedValueColumnHeader($columnName);
            $allTranslatedBreedCodeNames[$translatedColumnName] = $translatedColumnName;

            if (!$this->concatValueAndAccuracy) {
                $accuracyColumnName = $columnName . BreedValuesReportQueryGenerator::ACCURACY_TABLE_LABEL_SUFFIX;
                $allBreedCodeNames[$accuracyColumnName] = $accuracyColumnName;
                $translatedAccuracyColumnName = $translatedColumnName . BreedValuesReportQueryGenerator::ACCURACY_TABLE_LABEL_SUFFIX;
                $allTranslatedBreedCodeNames[$translatedAccuracyColumnName] = $translatedAccuracyColumnName;
            }
        }

        foreach ($allCsvColumns as $columnName) {
            if (key_exists($columnName, $allBreedCodeNames)) {
                $allPossibleBreedCodeNames[$columnName] = $columnName;
            }
            $translatedColumnName = $this->translateBreedValueColumnHeader($columnName);
            if (key_exists($columnName, $allTranslatedBreedCodeNames)) {
                $allPossibleBreedCodeNames[$translatedColumnName] = $translatedColumnName;
            }
        }

        return $allPossibleBreedCodeNames;
    }


    private function translateBreedValueColumnHeader($columnName)
    {
        return strtolower($this->trans(strtoupper(
            BreedValuesReportQueryGenerator::translatedBreedValueColumnHeader($columnName)
        )));
    }


    /**
     * @param array $results
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function moveBreedValueColumnsToEndArray(array $results)
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
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @param array $results
     * @return array
     */
    protected function preFormatLivestockSqlResult(array $results)
    {
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
        return $results;
    }
}