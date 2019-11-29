<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\ReportLabel;
use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FertilizerCategory;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\DateUtil;
use AppBundle\Util\DsvWriterUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * Background information
 *
 * https://www.rvo.nl/onderwerpen/agrarisch-ondernemen/mestbeleid/mest/gebruiksnormen/veelgestelde-vragen
 *
 * animalsCountMonthlyFirstDay = animals for every first day of the month (for the previous 12 months)
 * animalsCountYearlyAverage = SUM (animalsCountMonthlyFirstDay) / 12
 *
 * stikstofCorrectie = animalsCountYearlyAverage_per_category * phosphateCorrection_per_category
 *
 *
 * Forfaitaire stikstof- en fosfaatgehalten in dierlijke mest 2019-2021
 * https://www.rvo.nl/sites/default/files/2018/01/Tabel-5-Forfaitaire-stikstof-en-fosfaatgehalten-in-dierlijke-mest-2018.pdf
 *
 * Tabel Diergebonden forfaitaire gehalten 2019-2021
 * https://www.rvo.nl/sites/default/files/2019/01/Tabel-4-Diergebonden-forfaitaire-gehalten%202019-2021.pdf
 *
 * Schaap 55
 *  Type | Categorie                                | excretie m^3 | kg stikstof | kg fosfaat |
 *   550 | Schapen voor de vlees- en melkproductie  |     0.5      |     9.9     |     3.3    |
 *   551 | Vleesschapen                             |     0.15     |     0.9     |     0.3    |
 *   552 | Opfokooien                               |      -       |     7.2     |     2.2    |
 *
 * Categorieen: RVO beschrijving
 *
 * Type 550
 * Schapen voor de vlees- en melkproductie (alle vrouwelijke schapen die ten minste eenmaal hebben gelammerd, inclusief
 * alle schapen tot ca. 4 maanden, voor zover gehouden op het bedrijf waar deze schapen geboren zijn en rammen)
 *
 * Type 551
 * Vleesschapen tot ca. 4 maanden, gehouden op bedrijven waar ze niet zijn geboren
 *
 * Type 552
 * Opfokooien, weideschapen en vleesschapen van ca. 4 maanden en ouder
 *
 *
 * Categorieen: Toepassing in Code
 * Zie fertilizerCategorySelectCriteria()
 */
class FertilizerAccountingReport extends ReportServiceBase
{
    const TITLE = 'fertilizer_accounting';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const ANIMAL_COUNT_DECIMAL_PRECISION = 2;
    const NITROGEN_DECIMAL_PRECISION = 2;
    const PHOSPHATE_DECIMAL_PRECISION = 2;

    const TWIG_FILE = 'Report/fertilizer_accounting_report.html.twig';

    /** @var Location $location */
    private $location;

    /** @var string $newestReferenceDateString */
    private $newestReferenceDateString;
    /** @var string $oldestReferenceDateString */
    private $oldestReferenceDateString;

    /** @var array */
    private $referenceDateStringsByMonth = [];

    public function validateReferenceDate(\DateTime $referenceDate) {
        ReportUtil::validateDateIsNotOlderThanOldestAutomatedSync($referenceDate, TranslationKey::REFERENCE_DATE, $this->translator);
        ReportUtil::validateDateIsNotInTheFuture($referenceDate, TranslationKey::REFERENCE_DATE, $this->translator);
    }

    /**
     * @inheritDoc
     */
    function getReport(Location $location, \DateTime $referenceDate, $extension)
    {
        try {
            $this->location = $location;
            $this->extension = strtolower($extension);

            $this->initializeReferenceDateValues($referenceDate);
            $this->setFileAndFolderNames();

            $sql = $this->query();
            $data = $this->em->getConnection()->query($sql)->fetch();

            if ($this->extension === FileType::PDF) {
                return $this->getPdfReport($data);
            } else {
                return $this->createCsvReport($data);
            }

            throw new \Exception('INVALID FILE TYPE', Response::HTTP_PRECONDITION_REQUIRED);

        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
            $this->logger->error($exception->getMessage());
            return ResultUtil::errorResultByException($exception);
        }

    }


    private function initializeReferenceDateValues(\DateTime $referenceDate) {
        $primaryReferenceDate = DateUtil::getFirstDateOfGivenDateTime($referenceDate);

        foreach (range(1, 12, 1) as $month) {
            $referenceDateOfMonth = $this->referenceDateOfMonth($month, $primaryReferenceDate);
            $this->referenceDateStringsByMonth[$month] = $referenceDateOfMonth;
        }

        $this->newestReferenceDateString = max($this->referenceDateStringsByMonth);
        $this->oldestReferenceDateString = min($this->referenceDateStringsByMonth);
    }


    private function setFileAndFolderNames()
    {
        $this->folderName = self::FOLDER_NAME;
        $this->filename = $this->translateColumnHeader(self::FILENAME).'-'.$this->getUbn()
            .'__'.$this->newestReferenceDateString.'--'.$this->oldestReferenceDateString.'_'.$this->translateColumnHeader('GENERATED ON');
    }


    private function createCsvReport(array $data) {
        $csvFormattedData = [];

        $yearlyDateRange = $this->oldestReferenceDateString.' => '.$this->newestReferenceDateString;

        $typeKey = 'type';
        $timespan = $this->trans('TIMESPAN');
        $numberKey = '#';
        $referenceDateKey = strtolower($this->trans('REFERENCE DATE'));

        $yearTranslation = strtolower($this->trans('YEAR'));
        $monthTranslation = strtolower($this->trans('MONTH'));
        $animalCountTranslation = strtolower($this->trans('ANIMAL COUNT'));

        $csvFormattedData[] = [
            $numberKey => 0,
            $timespan => $yearTranslation,
            $typeKey => $animalCountTranslation,
            $referenceDateKey => $yearlyDateRange,
            FertilizerCategory::_550 => $data[$this->yearlyAverageCountKey(FertilizerCategory::_550)],
            FertilizerCategory::_551 => $data[$this->yearlyAverageCountKey(FertilizerCategory::_551)],
            FertilizerCategory::_552 => $data[$this->yearlyAverageCountKey(FertilizerCategory::_552)],
        ];

        $csvFormattedData[] = [
            $numberKey => 0,
            $timespan => $yearTranslation,
            $typeKey => strtolower($this->trans('NITROGEN')).' (kg)',
            $referenceDateKey => $yearlyDateRange,
            FertilizerCategory::_550 => $data[$this->yearlyNitrogenKey(FertilizerCategory::_550)],
            FertilizerCategory::_551 => $data[$this->yearlyNitrogenKey(FertilizerCategory::_551)],
            FertilizerCategory::_552 => $data[$this->yearlyNitrogenKey(FertilizerCategory::_552)],
        ];

        $csvFormattedData[] = [
            $numberKey => 0,
            $timespan => $yearTranslation,
            $typeKey => strtolower($this->trans('PHOSPHATE')).' (kg)',
            $referenceDateKey => $yearlyDateRange,
            FertilizerCategory::_550 => $data[$this->yearlyPhosphateKey(FertilizerCategory::_550)],
            FertilizerCategory::_551 => $data[$this->yearlyPhosphateKey(FertilizerCategory::_551)],
            FertilizerCategory::_552 => $data[$this->yearlyPhosphateKey(FertilizerCategory::_552)],
        ];

        foreach ($this->referenceDateStringsByMonth as $month => $referenceDate) {
            $csvFormattedData[] = [
                $numberKey => $month,
                $timespan => $monthTranslation,
                $typeKey => $animalCountTranslation,
                $referenceDateKey => $data[$this->monthKey($month)],
                FertilizerCategory::_550 => $data[$this->monthlyCountKey($month,FertilizerCategory::_550)],
                FertilizerCategory::_551 => $data[$this->monthlyCountKey($month,FertilizerCategory::_551)],
                FertilizerCategory::_552 => $data[$this->monthlyCountKey($month,FertilizerCategory::_552)],
            ];
        }
        $filepath = $this->getFertilizerAccountingFilepath();
        DsvWriterUtil::writeNestedRecordToFile($csvFormattedData, $filepath);
        $this->deactivateColumnHeaderTranslation();

        return $this->uploadReportFileToS3($filepath);
    }

    /**
     * @return string
     */
    private function getFertilizerAccountingFilepath()
    {
        return FilesystemUtil::concatDirAndFilename($this->getCacheSubFolder(),$this->getFilename());
    }


    /**
     * @param array $reportContent
     * @return JsonResponse
     * @throws \Exception
     */
    private function getPdfReport(array $reportContent)
    {
        $newestReferenceDate = (new \DateTime($this->newestReferenceDateString))->format($this->trans('DATEFORMAT'));
        $oldestReferenceDate = (new \DateTime($this->oldestReferenceDateString))->format($this->trans('DATEFORMAT'));

        $data = [
            ReportLabel::VALUES => $reportContent,
            ReportLabel::FOOTNOTE => $this->getFootnote(),
            ReportLabel::UBN=> $this->getUbn(),
            ReportLabel::IMAGES_DIRECTORY => $this->getImagesDirectory(),
            ReportLabel::DATE => TimeUtil::getTimeStampToday('d-m-Y'),
            ReportLabel::REFERENCE_DATE => $newestReferenceDate,
            'animalCountsByCategoryHeader' => ucfirst(strtolower($this->trans('ANIMAL COUNTS').' '.$this->trans('BY').' '.$this->trans('ANIMAL CATEGORY'))),
            'totalHeader' => ucfirst(strtolower(
                $this->trans('ROLLING YEARLY AVERAGE').' '
                .$this->trans('TOTAL FERTILIZER PRODUCTION').' '
                .$this->trans('FROM').' '
                .$this->trans('REFERENCE DATE').' '
                .$oldestReferenceDate.' '
                .$this->trans('UNTIL').' '
                .$this->trans('REFERENCE DATE').' '
                .$newestReferenceDate
            )),
            ReportLabel::COLUMN_HEADERS => [
                ReportLabel::REFERENCE_DATE => $this->getReferenceDateLabel(),
                ReportLabel::ANIMAL_CATEGORY => $this->getAnimalCategoryLabel(),
                ReportLabel::ANIMAL_COUNT => $this->getAnimalCountLabel(),
                ReportLabel::PHOSPHATE => $this->getPhosphateLabel(),
                ReportLabel::NITROGEN => $this->getNitrogenLabel(),
                ReportLabel::AVERAGE_YEARLY_ANIMAL_COUNT => $this->translate('ROLLING YEARLY AVERAGE'),
            ]
        ];
        $this->deactivateColumnHeaderTranslation();
        return $this->getPdfReportBase(self::TWIG_FILE, $data, false);
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

    private function referenceDateOfMonth(int $month, \DateTime $primaryReferenceDate): string {
        $monthOffset = -12 + $month;
        return DateUtil::addMonths($primaryReferenceDate, $monthOffset)->format(SqlUtil::DATE_FORMAT);
    }

    private function monthlyCountSelects(int $category): string {
        $result = "";
        foreach ($this->referenceDateStringsByMonth as $month => $referenceDate) {
            $result .= $this->monthlyCountSelect($month, $category);
        }
        return $result;
    }

    private function monthlyCountSelect(int $month, int $category): string {
        return "sum(CASE WHEN ".$this->isCategoryMonthKey($month, $category)." AND ".$this->residenceKey($month)." THEN 1 ELSE 0 END) AS ".$this->monthlyCountKey($month, $category).',
        ';
    }

    private function residenceKey(int $month): string {
        return "residence_".$month;
    }

    private function isCategoryMonthKey(int $month, int $category): string {
        return "is_".$category."_".$month;
    }

    private function monthlyCountKey(int $month, int $category): string {
        $categoryLabel = 'category';
        $monthLabel = 'month';
        $countLabel = 'count';
        return $categoryLabel."_".$category."_".$monthLabel."_".$month."_".$countLabel;
    }

    private function monthKey(int $month): string {
        return "month_".$month;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function query(): string {
        return "SELECT 
        ".
            $this->averageCountSelect(FertilizerCategory::_550).
            $this->nitrogenSelect(FertilizerCategory::_550).
            $this->phosphateSelect(FertilizerCategory::_550).
            $this->averageCountSelect(FertilizerCategory::_551).
            $this->nitrogenSelect(FertilizerCategory::_551).
            $this->phosphateSelect(FertilizerCategory::_551).
            $this->averageCountSelect(FertilizerCategory::_552).
            $this->nitrogenSelect(FertilizerCategory::_552).
            $this->phosphateSelect(FertilizerCategory::_552).
            $this->referenceDatesSelect()."
            data.*
         FROM (
                SELECT 
                ".
                    $this->monthlyCountSelects(FertilizerCategory::_550).
                    $this->monthlyCountSelects(FertilizerCategory::_551).
                    $this->monthlyCountSelects(FertilizerCategory::_552).
                "
                NOW() as generated_on --this filler row prevents query from breaking due to trailing comma
        FROM (
                SELECT
            " .
                    $this->fertilizerCategorySelectResult(FertilizerCategory::_550) .
                    $this->fertilizerCategorySelectResult(FertilizerCategory::_551) .
                    $this->fertilizerCategorySelectResult(FertilizerCategory::_552) .
            "
            residence_details.*
        FROM animal a
            INNER JOIN view_animal_livestock_overview_details v ON v.animal_id = a.id
            INNER JOIN (
            SELECT
                ".$this->isBoolOrResidenceSelectResult()."
                animal_id
            FROM (
                     SELECT
                         " . $this->isResidenceSelectResult() . "
                         r.*
                     FROM animal_residence r
                     WHERE r.location_id = ".$this->getLocationId()." AND r.is_pending = FALSE
                     AND (r.end_date ISNULL OR r.end_date >= '$this->oldestReferenceDateString')
                     AND (r.start_date <= '$this->newestReferenceDateString')
                 )a GROUP BY animal_id
            )residence_details ON residence_details.animal_id = a.id
        WHERE ".$this->whereAtLeastOneResidenceIsWithinMinMaxRange()."
              )counts
        )data;";
    }

    private function referenceDatesSelect(): string {
        $result = "";
        foreach ($this->referenceDateStringsByMonth as $month => $referenceDateString) {
            $referenceDate = (new \DateTime($referenceDateString))->format(DateUtil::DATE_USER_DISPLAY_FORMAT);
            $result .= "'$referenceDate' as ".$this->monthKey($month).SqlUtil::SELECT_ROW_SEPARATOR;
        }
        return $result;
    }

    private function yearlyAverageCountKey(int $fertilizerCategory): string {
        return "yearly_".$fertilizerCategory."_average";
    }

    private function averageCountSelect(int $fertilizerCategory): string {
        return SqlUtil::ROUND_PREFIX . $this->averageSelectPart($fertilizerCategory).",".self::ANIMAL_COUNT_DECIMAL_PRECISION.") as ".$this->yearlyAverageCountKey($fertilizerCategory).",
        ";
    }

    private function yearlyPhosphateKey(int $fertilizerCategory): string {
        return "yearly_".$fertilizerCategory."_phosphate_kg";
    }

    private function phosphateSelect(int $fertilizerCategory): string {
        return SqlUtil::ROUND_PREFIX . $this->averageSelectPart($fertilizerCategory)." * ".
            self::getPhosphateKgFlatRateExcretionStandard($fertilizerCategory)
            .",".self::PHOSPHATE_DECIMAL_PRECISION.") as ".$this->yearlyPhosphateKey($fertilizerCategory).",
            ";
    }

    private function yearlyNitrogenKey(int $fertilizerCategory): string {
        return "yearly_".$fertilizerCategory."_nitrogen_kg";
    }

    private function nitrogenSelect(int $fertilizerCategory): string {
        return SqlUtil::ROUND_PREFIX . $this->averageSelectPart($fertilizerCategory)." * ".
            self::getNitrogenKgFlatRateExcretionStandard($fertilizerCategory)
            .",".self::NITROGEN_DECIMAL_PRECISION.") as ".$this->yearlyNitrogenKey($fertilizerCategory).",
            ";
    }

    private function averageSelectPart(int $fertilizerCategory): string {
        $select = "(
        ";

        $referenceDatesByMonthClone = $this->referenceDateStringsByMonth;

        foreach ($this->referenceDateStringsByMonth as $month => $referencedDate) {
            if (end($referenceDatesByMonthClone) && $month === key($referenceDatesByMonthClone)) {
                $suffix = "
                ";
            } else {
                $suffix = " +
                ";
            }
            $select .= $this->monthlyCountKey($month, $fertilizerCategory) . $suffix;
        }

        return $select."
        )/12";
    }

    private function isResidenceSelectResult(): String {
        $result = "";
        foreach ($this->referenceDateStringsByMonth as $month => $referencedDate) {
            $result .= $this->isResidenceSelectResultByMonth($month);
        }
        return $result;
    }

    private function isResidenceSelectResultByMonth(int $month): String {
        $referenceDateOfMonth = $this->referenceDateStringsByMonth[$month];
        return "(start_date NOTNULL AND end_date NOTNULL) AND DATE(start_date) <= '$referenceDateOfMonth' AND DATE(end_date) >= '$referenceDateOfMonth' OR --closed residence
        (start_date NOTNULL AND end_date ISNULL) AND DATE(start_date) <= '$referenceDateOfMonth' --open residence
         as ".$this->residenceKey($month).SqlUtil::SELECT_ROW_SEPARATOR;
    }

    private function isBoolOrResidenceSelectResult(): String {
        $result = "";
        foreach ($this->referenceDateStringsByMonth as $month => $referencedDate) {
            $residenceKey = $this->residenceKey($month);
            $result .= "bool_or($residenceKey) as $residenceKey".SqlUtil::SELECT_ROW_SEPARATOR;
        }
        return $result;
    }

    /**
     * @param string $referenceDateString
     * @param int $fertilizerCategory
     * @return string
     * @throws \Exception
     */
    private function fertilizerCategorySelectCriteria($referenceDateString, $fertilizerCategory)
    {
        $ubn = $this->getUbn();

        switch ($fertilizerCategory) {

            case FertilizerCategory::_550: // Animal Category 550: Animals for milk and meat production
                return "(
                         (a.gender = '".GenderType::FEMALE."' AND v.has_children_as_mom) OR
                         (a.gender = '".GenderType::MALE."' AND EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) > 1)
                       )";

            case FertilizerCategory::_551: // Animal Category 551: External young meat sheep
                return "((a.ubn_of_birth <> '$ubn' OR a.ubn_of_birth ISNULL) AND
                        EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) * 12 +
                        EXTRACT(MONTH FROM AGE('$referenceDateString', a.date_of_birth)) <= 3 --age in months on reference_date
                       )";

            case FertilizerCategory::_552: // Animal Category 552: All other animals 4 months or older
                return "(
                        NOT
                        ".$this->fertilizerCategorySelectCriteria($referenceDateString, FertilizerCategory::_550)."
                        AND NOT
                        ".$this->fertilizerCategorySelectCriteria($referenceDateString,FertilizerCategory::_551)."
                        AND (
                          EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) * 12 +
                          EXTRACT(MONTH FROM AGE('$referenceDateString', a.date_of_birth)) >= 4 --age in months on reference_date
                        ) 
                      )";
            default: throw new \Exception('Invalid FertilizerCategory: '.$fertilizerCategory, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param int $fertilizerCategory
     * @return string
     * @throws \Exception
     */
    private function fertilizerCategorySelectResult(int $fertilizerCategory): string
    {
        $result = "";
        foreach ($this->referenceDateStringsByMonth as $month => $referencedDate) {
            $result .= $this->fertilizerCategorySelectCriteria($referencedDate, $fertilizerCategory) .
                " as ".$this->isCategoryMonthKey($month, $fertilizerCategory).SqlUtil::SELECT_ROW_SEPARATOR;
        }
        return $result;
    }

    private function whereAtLeastOneResidenceIsWithinMinMaxRange(): string {
        $where = "( -- has at least one residence in one of the given reference dates
        ";

        $referenceDatesByMonthClone = $this->referenceDateStringsByMonth;

        foreach ($this->referenceDateStringsByMonth as $month => $referencedDate) {
            if (end($referenceDatesByMonthClone) && $month === key($referenceDatesByMonthClone)) {
                $suffix = " ";
            } else {
                $suffix = " OR
                ";
            }
            $where .= "residence_details." . $this->residenceKey($month) . $suffix;
        }

        return $where."
        )";
    }

    private function getUbn(): string {
        return $this->location->getUbn();
    }

    private function getLocationId(): string {
        return $this->location->getId();
    }
}