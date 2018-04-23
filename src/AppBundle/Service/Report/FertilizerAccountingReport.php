<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FertilizerCategory;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DateUtil;
use AppBundle\Util\DsvWriterUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\ProcessUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FertilizerAccountingReport extends ReportServiceBase implements ReportServiceInterface
{
    const TITLE = 'fertilizer_accounting';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const PROCESS_TIME_LIMIT_IN_MINUTES = 5;

    const ANIMAL_COUNT_DECIMAL_PRECISION = 2;
    const NITROGEN_DECIMAL_PRECISION = 2;
    const PHOSPHATE_DECIMAL_PRECISION = 2;

    /** @var Location $location */
    private $location;

    /** @var string $newestReferenceDate */
    private $newestReferenceDate;
    /** @var string $oldestReferenceDate */
    private $oldestReferenceDate;

    /**
     * @inheritDoc
     */
    function getReport(Request $request)
    {
        try {
            $this->location = $this->getSelectedLocation($request, true);
            $referenceDate = RequestUtil::getDateQuery($request,QueryParameter::REFERENCE_DATE, new \DateTime());

            $this->extension = FileType::CSV;
            $this->extension = $request->query->get(QueryParameter::FILE_TYPE_QUERY);

            ProcessUtil::setTimeLimitInMinutes(self::PROCESS_TIME_LIMIT_IN_MINUTES);

            // TODO remove later
            $useTestResults = true;
            if ($useTestResults) {
                $historicLiveStockCountsByFertilizerCategory = self::testResults();
            } else {
                $sql = $this->getHistoricLiveStockCountsByFertilizerCategoryQuery($referenceDate);
                $historicLiveStockCountsByFertilizerCategory = $this->em->getConnection()->query($sql)->fetchAll();
            }

            $totalResults = $this->yearlyAveragesWithFertilizerOutput($historicLiveStockCountsByFertilizerCategory);
            $this->retrieveNewestAndOldestReferenceDate($historicLiveStockCountsByFertilizerCategory);

            if ($request->query->get(QueryParameter::FILE_TYPE_QUERY) === FileType::CSV) {
                return $this->createCsvFile($historicLiveStockCountsByFertilizerCategory, $totalResults);
            } else {
                // TODO pass result arrays to twig and generate pdf file
                $this->deactivateColumnHeaderTranslation();
            }

            throw new \Exception('INVALID FILE TYPE', Response::HTTP_PRECONDITION_REQUIRED);

        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
            $this->logger->error($exception->getMessage());
            return ResultUtil::errorResultByException($exception);
        }

    }


    /**
     * @param array $historicLiveStockCountsByFertilizerCategory
     * @param array $totalResults
     * @return JsonResponse
     * @throws \Exception
     */
    private function createCsvFile($historicLiveStockCountsByFertilizerCategory, $totalResults)
    {
        $mergedSet = $this->mergeDataForCsvFile($historicLiveStockCountsByFertilizerCategory, $totalResults);
        $filepath = $this->getFertilizerAccountingFilepath();
        DsvWriterUtil::writeNestedRecordToFile($mergedSet, $filepath);
        $this->deactivateColumnHeaderTranslation();

        return $this->uploadReportFileToS3($filepath);
    }


    /**
     * @return string
     */
    private function getFertilizerAccountingFilepath()
    {
        $this->folderName = self::FOLDER_NAME;
        $this->filename = $this->translateColumnHeader(self::FILENAME).'-'.$this->location->getUbn()
        .'__'.$this->newestReferenceDate.'--'.$this->oldestReferenceDate.'_'.$this->translateColumnHeader('GENERATED ON');
        return FilesystemUtil::concatDirAndFilename($this->getCacheSubFolder(),$this->getFilename());
    }


    /**
     * @param array $historicLiveStockCountsByFertilizerCategory
     */
    private function retrieveNewestAndOldestReferenceDate(array $historicLiveStockCountsByFertilizerCategory)
    {
        $referenceDates = [];
        foreach ($historicLiveStockCountsByFertilizerCategory as $record)
        {
            $referenceDates[] = ArrayUtil::get($this->getReferenceDateLabel(), $record);
        }

        $this->newestReferenceDate = max($referenceDates);
        $this->oldestReferenceDate = min($referenceDates);
    }


    /**
     * @param \DateTime $referenceDate
     * @return string
     * @throws \Exception
     */
    private function getHistoricLiveStockCountsByFertilizerCategoryQuery(\DateTime $referenceDate)
    {
        $referenceDate = DateUtil::getFirstDateOfGivenDateTime($referenceDate);

        $sql = '';
        $prefix = '';

        for($i = 0; $i < 12; $i++)
        {
            $referenceDateString = $referenceDate->format('Y-m-d');

            foreach(
                [
                    FertilizerCategory::_550,
                    FertilizerCategory::_551,
                    FertilizerCategory::_552
                ] as $fertilizerCategory)
            {
                $sql .= $prefix.$this->getHistoricLiveStockCountsByFertilizerCategoryQueryBase($referenceDateString, $fertilizerCategory);
                $prefix = '
                UNION
                ';
            }

            $referenceDate = DateUtil::addMonths($referenceDate, -1);
        }

        return $sql . '
        ORDER BY reference_date DESC, category';
    }


    /**
     * @param $historicLiveStockCountsByFertilizerCategory
     * @param $totalResults
     * @return array
     */
    private function mergeDataForCsvFile($historicLiveStockCountsByFertilizerCategory, $totalResults)
    {
        $mergedSet = [];

        $nitrogenLabelWithKg = $this->getNitrogenLabel().'(kg)';
        $phosphateLabelWithKg = $this->getPhosphateLabel().'(kg)';

        foreach ($historicLiveStockCountsByFertilizerCategory as $key => $record)
        {
            $mergedSet[$key] = $record;
            $mergedSet[$key][$nitrogenLabelWithKg] = null;
            $mergedSet[$key][$phosphateLabelWithKg] = null;
        }

        foreach ($totalResults as $animalCategory => $record)
        {
            $mergedSet[] = [
                $this->getReferenceDateLabel() => $this->translate('ROLLING YEARLY AVERAGE'),
                $this->getAnimalCategoryLabel() => $animalCategory,
                $this->getAnimalCountLabel() => $record[ReportLabel::AVERAGE_YEARLY_ANIMAL_COUNT],
                $nitrogenLabelWithKg => $record[$this->getNitrogenLabel()],
                $phosphateLabelWithKg => $record[$this->getPhosphateLabel()],
            ];
        }

        $mergedSet[] = [
            $this->getReferenceDateLabel() => null,
            $this->getAnimalCategoryLabel() => null,
            $this->getAnimalCountLabel() => null,
            $nitrogenLabelWithKg => null,
            $phosphateLabelWithKg => null,
            'footnote' => $this->getFootnote(),
        ];

        return $mergedSet;
    }


    /**
     * @return string
     */
    private function getReferenceDateLabel()
    {
        return $this->translateColumnHeader('reference date');
    }


    /**
     * @return string
     */
    private function getAnimalCategoryLabel()
    {
        return $this->translateColumnHeader('animal category');
    }


    /**
     * @return string
     */
    private function getAnimalCountLabel()
    {
        return $this->translateColumnHeader('animal count');
    }


    /**
     * @return string
     */
    private function getPhosphateLabel()
    {
        return $this->translateColumnHeader(ReportLabel::PHOSPHATE);
    }


    /**
     * @return string
     */
    private function getNitrogenLabel()
    {
        return $this->translateColumnHeader(ReportLabel::NITROGEN);
    }


    /**
     * @param $referenceDateString
     * @param $fertilizerCategory
     * @return string
     * @throws \Exception
     */
    private function getHistoricLiveStockCountsByFertilizerCategoryQueryBase($referenceDateString, $fertilizerCategory)
    {
        $locationId = $this->location->getId();

        $referenceDateLabel = $this->getReferenceDateLabel();
        $fertilizerCategoryLabel = $this->getAnimalCategoryLabel();
        $animalCountLabel = $this->getAnimalCountLabel();

        return "SELECT
                      '$referenceDateString' as $referenceDateLabel,
                      $fertilizerCategory as $fertilizerCategoryLabel,
                      COUNT(a.id) as $animalCountLabel
                    FROM animal a
                      LEFT JOIN (
                                  SELECT
                                    r.animal_id,
                                    1 as priority
                                  FROM (
                                         SELECT
                                           r.animal_id,
                                           max(id) as max_id
                                         FROM animal_residence r
                                         WHERE
                                           start_date NOTNULL AND end_date NOTNULL AND
                                           DATE(start_date) <= '$referenceDateString' AND DATE(end_date) >= '$referenceDateString'
                                           AND is_pending = FALSE
                                           AND location_id = $locationId
                                         GROUP BY r.animal_id
                                       )closed_residence
                                    INNER JOIN animal_residence r ON r.id = closed_residence.max_id
                                )closed_residence ON closed_residence.animal_id = a.id
                      LEFT JOIN (
                                  SELECT
                                    open_residence.animal_id,
                                    2 as priority
                                  FROM (
                                         SELECT
                                           open_residence.animal_id,
                                           open_residence.max_start_date,
                                           max(id) as max_id
                                         FROM (
                                                SELECT
                                                  r.animal_id,
                                                  max(start_date) as max_start_date
                                                FROM animal_residence r
                                                WHERE
                                                  start_date NOTNULL AND end_date ISNULL AND
                                                  DATE(start_date) <= '$referenceDateString'
                                                  AND is_pending = FALSE
                                                  AND location_id = $locationId
                                                GROUP BY animal_id
                                              )open_residence
                                           INNER JOIN animal_residence r ON r.animal_id = open_residence.animal_id AND r.start_date = open_residence.max_start_date
                                         GROUP BY open_residence.animal_id, open_residence.max_start_date
                                       )open_residence
                                    INNER JOIN animal_residence r ON r.id = open_residence.max_id
                                )open_residence ON open_residence.animal_id = a.id
                      LEFT JOIN (
                        SELECT
                          mom.id,
                          COUNT(mom.id) > 0 as has_children_as_mom
                        FROM animal mom
                          INNER JOIN animal child ON child.parent_mother_id = mom.id
                        GROUP BY mom.id
                        )child_status ON child_status.id = a.id
                    WHERE (open_residence.animal_id NOTNULL OR closed_residence.animal_id NOTNULL)
      AND
      ".$this->fertilizerCategoryQueryFilter($referenceDateString, $fertilizerCategory);
    }


    /**
     * @param string $referenceDateString
     * @param int $fertilizerCategory
     * @return string
     * @throws \Exception
     */
    private function fertilizerCategoryQueryFilter($referenceDateString, $fertilizerCategory)
    {
        $ubn = $this->location->getUbn();

        switch ($fertilizerCategory) {

            case FertilizerCategory::_550:
                return "--Animal Category 550: Animals for milk and meat production
                        (
                         (a.gender = 'FEMALE' AND child_status.has_children_as_mom) OR
                         (a.ubn_of_birth = '$ubn' AND
                          EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) * 12 +
                          EXTRACT(MONTH FROM AGE('$referenceDateString', a.date_of_birth)) <= 4 --age in months on reference_date
                         ) OR
                         (a.gender = 'RAM' AND EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) > 1)
                       )";

            case FertilizerCategory::_551:
                return "--Animal Category 551: External young meat sheep
                       ((a.ubn_of_birth <> '$ubn' OR a.ubn_of_birth ISNULL) AND
                        EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) * 12 +
                        EXTRACT(MONTH FROM AGE('$referenceDateString', a.date_of_birth)) <= 4 --age in months on reference_date
                       )";

            case FertilizerCategory::_552:
                return "--Animal Category 552: All other animals 4 months or older
                      (
                        NOT
                        ".$this->fertilizerCategoryQueryFilter($referenceDateString, FertilizerCategory::_550)."
                        AND NOT
                        ".$this->fertilizerCategoryQueryFilter($referenceDateString,FertilizerCategory::_551)."
                        AND (
                          EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) * 12 +
                          EXTRACT(MONTH FROM AGE('$referenceDateString', a.date_of_birth)) >= 4 --age in months on reference_date
                        ) 
                      )";
            default: throw new \Exception('Invalid FertilizerCategory: '.$fertilizerCategory, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @param $historicLiveStockCountsByFertilizerCategory
     * @return array
     * @throws \Exception
     */
    private function yearlyAveragesWithFertilizerOutput($historicLiveStockCountsByFertilizerCategory)
    {
        $countsByCategory = [];

        $fertilizerCategoryLabel = $this->getAnimalCategoryLabel();
        $animalCountLabel = $this->getAnimalCountLabel();

        foreach ($historicLiveStockCountsByFertilizerCategory as $set)
        {
            $category = $set[$fertilizerCategoryLabel];
            $setCount = $set[$animalCountLabel];

            $countsByCategory[$category] = ArrayUtil::get($category, $countsByCategory, 0) + $setCount;
        }

        $results = [];
        foreach ($countsByCategory as $fertilizerCategory => $count)
        {
            $yearlyAverageCount = round($count/12, self::ANIMAL_COUNT_DECIMAL_PRECISION);

            $results[$fertilizerCategory] = [
                ReportLabel::AVERAGE_YEARLY_ANIMAL_COUNT => $yearlyAverageCount,
                $this->getNitrogenLabel() =>
                    round($yearlyAverageCount * self::getNitrogenKgFlatRateExcretionStandard($fertilizerCategory),
                    self::NITROGEN_DECIMAL_PRECISION),
                $this->getPhosphateLabel() =>
                    round($yearlyAverageCount * self::getPhosphateKgFlatRateExcretionStandard($fertilizerCategory),
                        self::PHOSPHATE_DECIMAL_PRECISION),
            ];
        }

        return $results;
    }


    /**
     * @param $fertilizerCategory
     * @return float
     * @throws \Exception
     */
    public static function getNitrogenKgFlatRateExcretionStandard($fertilizerCategory)
    {
        switch ($fertilizerCategory) {
            case FertilizerCategory::_550: return 9.9;
            case FertilizerCategory::_551: return 0.9;
            case FertilizerCategory::_552: return 7.2;
            default: throw new \Exception('Invalid FertilizerCategory: '.$fertilizerCategory, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @param $fertilizerCategory
     * @return float
     * @throws \Exception
     */
    public static function getPhosphateKgFlatRateExcretionStandard($fertilizerCategory)
    {
        switch ($fertilizerCategory) {
            case FertilizerCategory::_550: return 3.3;
            case FertilizerCategory::_551: return 0.3;
            case FertilizerCategory::_552: return 2.2;
            default: throw new \Exception('Invalid FertilizerCategory: '.$fertilizerCategory, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @return string
     */
    private function getFootnote()
    {
        return $this->trans('FERTILIZER ACCOUNTING NOTE', ['%year%' => DateUtil::currentYear()]);
    }


    // TODO remove later
    private static function testResults()
    {
        return [
            0 => [
                "peildatum" => "2016-06-01",
                "diercategorie" => 550,
                "dieraantal" => 6,
            ],
            1 => [
                "peildatum" => "2016-06-01",
                "diercategorie" => 551,
                "dieraantal" => 2,
            ],
            2 => [
                "peildatum" => "2016-06-01",
                "diercategorie" => 552,
                "dieraantal" => 2,
            ],
            3 => [
                "peildatum" => "2016-07-01",
                "diercategorie" => 550,
                "dieraantal" => 6,
            ],
            4 => [
                "peildatum" => "2016-07-01",
                "diercategorie" => 551,
                "dieraantal" => 2,
            ],
            5 => [
                "peildatum" => "2016-07-01",
                "diercategorie" => 552,
                "dieraantal" => 4,
            ],
            6 => [
                "peildatum" => "2016-08-01",
                "diercategorie" => 550,
                "dieraantal" => 147,
            ],
            7 => [
                "peildatum" => "2016-08-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            8 => [
                "peildatum" => "2016-08-01",
                "diercategorie" => 552,
                "dieraantal" => 11,
            ],
            9 => [
                "peildatum" => "2016-09-01",
                "diercategorie" => 550,
                "dieraantal" => 97,
            ],
            10 => [
                "peildatum" => "2016-09-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            11 => [
                "peildatum" => "2016-09-01",
                "diercategorie" => 552,
                "dieraantal" => 34,
            ],
            12 => [
                "peildatum" => "2016-10-01",
                "diercategorie" => 550,
                "dieraantal" => 97,
            ],
            13 => [
                "peildatum" => "2016-10-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            14 => [
                "peildatum" => "2016-10-01",
                "diercategorie" => 552,
                "dieraantal" => 34,
            ],
            15 => [
                "peildatum" => "2016-11-01",
                "diercategorie" => 550,
                "dieraantal" => 99,
            ],
            16 => [
                "peildatum" => "2016-11-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            17 => [
                "peildatum" => "2016-11-01",
                "diercategorie" => 552,
                "dieraantal" => 82,
            ],
            18 => [
                "peildatum" => "2016-12-01",
                "diercategorie" => 550,
                "dieraantal" => 99,
            ],
            19 => [
                "peildatum" => "2016-12-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            20 => [
                "peildatum" => "2016-12-01",
                "diercategorie" => 552,
                "dieraantal" => 77,
            ],
            21 => [
                "peildatum" => "2017-01-01",
                "diercategorie" => 550,
                "dieraantal" => 100,
            ],
            22 => [
                "peildatum" => "2017-01-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            23 => [
                "peildatum" => "2017-01-01",
                "diercategorie" => 552,
                "dieraantal" => 78,
            ],
            24 => [
                "peildatum" => "2017-02-01",
                "diercategorie" => 550,
                "dieraantal" => 101,
            ],
            25 => [
                "peildatum" => "2017-02-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            26 => [
                "peildatum" => "2017-02-01",
                "diercategorie" => 552,
                "dieraantal" => 80,
            ],
            27 => [
                "peildatum" => "2017-03-01",
                "diercategorie" => 550,
                "dieraantal" => 100,
            ],
            28 => [
                "peildatum" => "2017-03-01",
                "diercategorie" => 551,
                "dieraantal" => 0,
            ],
            29 => [
                "peildatum" => "2017-03-01",
                "diercategorie" => 552,
                "dieraantal" => 80,
            ],
            30 => [
                "peildatum" => "2017-04-01",
                "diercategorie" => 550,
                "dieraantal" => 255,
            ],
            31 => [
                "peildatum" => "2017-04-01",
                "diercategorie" => 551,
                "dieraantal" => 15,
            ],
            32 => [
                "peildatum" => "2017-04-01",
                "diercategorie" => 552,
                "dieraantal" => 81,
            ],
            33 => [
                "peildatum" => "2017-05-01",
                "diercategorie" => 550,
                "dieraantal" => 257,
            ],
            34 => [
                "peildatum" => "2017-05-01",
                "diercategorie" => 551,
                "dieraantal" => 15,
            ],
            35 => [
                "peildatum" => "2017-05-01",
                "diercategorie" => 552,
                "dieraantal" => 13,
            ]
        ];
    }
}