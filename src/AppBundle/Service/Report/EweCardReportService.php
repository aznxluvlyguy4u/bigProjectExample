<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;

use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\DateUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\SectionUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\ArrayCollection;

class EweCardReportService extends ReportServiceBase
{
    const TITLE = 'ewe cards report';
    const TWIG_FILE = 'Report/ewe_cards.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'EWE_CARD';
    const DATE_RESULT_NULL_REPLACEMENT = "-";

    const DUPLICATE_TREATMENTS_FOR_TESTING_COUNT = 1;
    const DUPLICATE_OFFSPRING_FOR_TESTING_COUNT = 1;

    const MIN_AGE_IN_DAYS_FOR_MATURITY = 90;

    const EWE_ID = 'ewe_id';

    /** @var array|int[] */
    private $animalIds;

    /**
     * @param $actionBy
     * @param Location $location
     * @param ArrayCollection $content
     * @return JsonResponse
     */
    public function getReport($actionBy, Location $location, ArrayCollection $content)
    {
        $this->ulnValidator->validateUlns($content->get(Constant::ANIMALS_NAMESPACE));

        $this->animalIds = AnimalArrayReader::getAnimalsInContentArray($this->em, $content);

        $this->prepareData();

        $this->filename = $this->getEweCardReportFileName($content);
        $this->folderName = self::FOLDER_NAME;
        $this->extension = self::defaultFileType();

        return $this->getPdfReport($actionBy, $location);
    }

    private function prepareData()
    {
        $ordinalUpdateCount = LitterUtil::updateLitterOrdinalsByMotherIds($this->conn, $this->animalIds);
        $this->logger->notice("Updated $ordinalUpdateCount ordinals");
        $birthIntervalsUpdated = LitterUtil::updateBirthInterValByMotherIds($this->conn, $this->animalIds);
        $this->logger->notice("Updated $birthIntervalsUpdated birthIntervals");
    }

    /**
     * @param Person $actionBy
     * @param Location $location
     * @param ArrayCollection $content
     * @return JsonResponse
     */
    private function getPdfReport(Person $actionBy, Location $location)
    {
        $data = [];
        $data['animals'] = $this->getAnimalData($location);
        $data['userData'] = $this->getUserData($location);

        $additionalData = [
            'bootstrap_css' => FilesystemUtil::getAssetsDirectory($this->rootDir). '/bootstrap-3.3.7-dist/css/bootstrap.min.css',
            'bootstrap_js' => FilesystemUtil::getAssetsDirectory($this->rootDir). '/bootstrap-3.3.7-dist/js/bootstrap.min.js',
            'images_dir' => FilesystemUtil::getImagesDirectory($this->rootDir),
            'fonts_dir' => FilesystemUtil::getAssetsDirectory(($this->rootDir)). '/fonts'
        ];
        $customPdfOptions = [
            'orientation'=>'Portrait',
            'default-header'=>false,
            'page-size' => 'A4',
            'margin-top'    => 3,
            'margin-right'  => 3,
            'margin-bottom' => 3,
            'margin-left'   => 3,
        ];

        return $this->getPdfReportBase(self::TWIG_FILE, $data, false,
            $customPdfOptions, $additionalData);
    }

    public static function defaultFileType(): String {
        return FileType::PDF;
    }

    public static function allowedFileTypes(): array {
        return [
            FileType::PDF
        ];
    }

    private function getEweCardReportFileName(ArrayCollection $content): string {
        $uln = AnimalArrayReader::getFirstUlnFromAnimalsArray($content);
        $fileName = ucfirst(ReportUtil::translateFileName($this->translator, self::FILE_NAME_REPORT_TYPE));

        // If only one animal is given, then use the ULN of that animal in the filename
        if (!empty($uln)) {
            $fileName .= '_' . $uln;
        }

        return $fileName;
    }

    public function getAnimalData(Location $location) {

        $animalAndProductionValues = $this->getAnimalAndProductionData($this->animalIds, $location->getUbn());

        $offspringData = $this->getOffspringData($this->animalIds, $location);

        $treatments = $this->getTreatmentsData($this->animalIds);

        $data = [];

        foreach ($this->animalIds as $animalId) {
            $offspringDataPerEwe = $this->filterDataForAnimalId($animalId, $offspringData);
            $data[$animalId] = [
                'animalAndProduction' => $this->filterAnimalAndProductionDataForAnimalId($animalId, $animalAndProductionValues),
                'offspring' => $offspringDataPerEwe,
                'offspringAggregateData' => $this->aggregateOffspringData($offspringDataPerEwe),
                'treatments' => $this->filterDataForAnimalId($animalId, $treatments),
                'medicationsCount' => $this->getMedicationCount($treatments),
            ];
        }

        return $data;
    }


    private function getMedicationCount(array $treatments): int
    {
        $medicationCountPerTreatment = array_map(function (array $treatment) {
            return count($treatment['medications']);
        }, $treatments);
        return array_sum($medicationCountPerTreatment);
    }

    private function getUserData(Location $location): array {

        $owner = $location->getCompany()->getOwner();
        $locationAddress = $location->getAddress();

        return [
            'fullName' => $owner->getFullName(),
            'ubn' => $location->getUbn(),
            'locationAddress' => $locationAddress->getFullStreetNameAndNumber(),
            'locationPostalCode' => $locationAddress->getPostalCode(),
            'locationCity' => $locationAddress->getCity()
        ];
    }

    private function filterAnimalAndProductionDataForAnimalId(int $animalId, array $data): array {
        $allowed  = [$animalId];
        $animalData = array_filter(
            $data,
            function (array $values) use ($allowed) {
                return in_array($values[self::EWE_ID], $allowed);
            }
        );

        return reset($animalData);
    }

    private function filterDataForAnimalId(int $animalId, array $data): array {
        $allowed  = [$animalId];
        return array_filter(
            $data,
            function (array $values) use ($allowed) {
                return in_array($values[self::EWE_ID], $allowed);
            }
        );
    }


    private function aggregateOffspringData(array $offspringDataPerEwe): array {
        $birthWeightTotal = 0;
        $weightAt8WeeksTotal = 0;
        $deliveryWeightTotal = 0;
        $averageGrowthTotal = 0;
        $saldoTotal = 0;
        $pricePerKgTotal = 0;

        $birthWeightCount = 0;
        $weightAt8WeeksCount = 0;
        $deliveryWeightCount = 0;
        $averageGrowthCount = 0;
        $saldoCount = 0;
        $pricePerKgCount = 0;

        foreach ($offspringDataPerEwe as $child) {
            $birthWeight = $child['birth_weight'];
            $weightAt8Weeks = $child['weight_at8weeks_kg'];
            $deliveryWeight = $child['delivery_weight'];
            $averageGrowth = $child['average_growth'];
            $saldo = $child['saldo'];
            $pricePerKg = $child['price_per_kg'];

            if (!empty($birthWeight)) {
                $birthWeightTotal += floatval($birthWeight);
                $birthWeightCount++;
            }

            if (!empty($weightAt8Weeks)) {
                $weightAt8WeeksTotal += floatval($weightAt8Weeks);
                $weightAt8WeeksCount++;
            }

            if (!empty($deliveryWeight)) {
                $deliveryWeightTotal += floatval($deliveryWeight);
                $deliveryWeightCount++;
            }

            if (!empty($averageGrowth)) {
                $averageGrowthTotal += floatval($averageGrowth);
                $averageGrowthCount++;
            }

            if (!empty($saldo)) {
                $saldoTotal += floatval($saldo);
                $saldoCount++;
            }

            if (!empty($pricePerKg)) {
                $pricePerKgTotal += floatval($pricePerKg);
                $pricePerKgCount++;
            }
        }

        return [
            ReportLabel::BIRTH_WEIGHT => [
                ReportLabel::TOTAL => $birthWeightTotal,
                ReportLabel::AVERAGE => $birthWeightTotal / (empty($birthWeightCount) ? 1 : $birthWeightCount),
                ReportLabel::IS_EMPTY => $birthWeightCount === 0,
            ],
            ReportLabel::WEIGHT_AT_8_WEEKS => [
                ReportLabel::TOTAL => $weightAt8WeeksTotal,
                ReportLabel::AVERAGE => $weightAt8WeeksTotal / (empty($weightAt8WeeksCount) ? 1 : $weightAt8WeeksCount),
                ReportLabel::IS_EMPTY => $weightAt8WeeksCount === 0,
            ],
            ReportLabel::DELIVERY_WEIGHT => [
                ReportLabel::TOTAL => $deliveryWeightTotal,
                ReportLabel::AVERAGE => $deliveryWeightTotal / (empty($deliveryWeightCount) ? 1 : $deliveryWeightCount),
                ReportLabel::IS_EMPTY => $deliveryWeightCount === 0,
            ],
            ReportLabel::AVERAGE_GROWTH => [
                ReportLabel::TOTAL => $averageGrowthTotal,
                ReportLabel::AVERAGE => $averageGrowthTotal / (empty($averageGrowthCount) ? 1 : $averageGrowthCount),
                ReportLabel::IS_EMPTY => $averageGrowthCount === 0,
            ],
            ReportLabel::SALDO => [
                ReportLabel::TOTAL => $saldoTotal,
                ReportLabel::AVERAGE => $saldoTotal / (empty($saldoCount) ? 1 : $saldoCount),
                ReportLabel::IS_EMPTY => $saldoCount === 0,
            ],
            ReportLabel::PRICE_PER_KG => [
                ReportLabel::TOTAL => $pricePerKgTotal,
                ReportLabel::AVERAGE => $pricePerKgTotal / (empty($pricePerKgCount) ? 1 : $pricePerKgCount),
                ReportLabel::IS_EMPTY => $pricePerKgCount === 0,
            ],
        ];
    }


    private function getAnimalIdsArrayString(array $animalIds): string {
        return "(".SqlUtil::getFilterListString($animalIds, false).")";
    }

    /**
     * @param array $animalIds
     * @param string $ubn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAnimalAndProductionData(array $animalIds, string $ubn): array
    {
        $animalIdsArrayString = $this->getAnimalIdsArrayString($animalIds);
        $genderTranslationValues = SqlUtil::genderTranslationValues();
        $isoCountryAlphaTwoToNumericMapping = SqlUtil::isoCountryAlphaTwoToNumericMapping();
        $blindnessFactorTranslationValues = SqlUtil::blindnessFactorTranslationValues();

        $mainSectionValue = SectionUtil::MAIN_SECTION;
        $complementarySectionValue = SectionUtil::COMPLEMENTARY_SECTION;

        $mainSectionBreedTypesArrayString = "(".SqlUtil::getFilterListString(SectionUtil::mainSectionBreedTypes(), true).")";
        $complementarySectionBreedTypesArrayString = "(".SqlUtil::getFilterListString(SectionUtil::secondarySectionBreedTypes(), true).")";

        $eweId = self::EWE_ID;

        $sql = "SELECT
            a.id as $eweId,
            a.breed_code, --\"Ras\"
            a.breed_type, --\"Rassstatus\"
            (CASE WHEN a.breed_type IN $mainSectionBreedTypesArrayString THEN
                    '$mainSectionValue'
                WHEN a.breed_type IN $complementarySectionBreedTypesArrayString THEN
                    '$complementarySectionValue'
                ELSE null END) as section_type, --see SectionUtil class for the calculation
            a.animal_order_number, --werknummer
            NULLIF(CONCAT(a.collar_color,a.collar_number),'') as collar,
            a.collar_color,
            a.collar_number,
            a.nickname, --naam
            iso_country.numeric as uln_numeric_country_code,
            a.uln_country_code,
            a.uln_number,
            vd.uln,
            a.pedigree_country_code,
            a.pedigree_number,
            vd.stn,
            vd.n_ling, --geboren als
            vd.dd_mm_yyyy_date_of_birth as date_of_birth,
            gender.dutch as gender_dutch,
            mom.uln_country_code as mom_uln_country_code,
            mom.uln_number as mom_uln_number,
            NULLIF(COALESCE(mom.uln_country_code, mom.uln_number),'') as mom_uln,
            mom.nickname as mom_nickname, --naam moeder
            mom.pedigree_country_code as mom_pedigree_country_code, --pedigree country code moeder
            mom.pedigree_number as mom_pedigree_number, --pedigree number moeder
            dad.uln_country_code as dad_uln_country_code,
            dad.uln_number as dad_uln_number,
            NULLIF(COALESCE(dad.uln_country_code, dad.uln_number),'') as dad_uln,
            dad.nickname as dad_nickname, --naam vader
            dad.pedigree_country_code as dad_pedigree_country_code, --pedigree country code vader
            dad.pedigree_number as dad_pedigree_number, --pedigree number vader
            (
                CASE WHEN a.surrogate_id NOTNULL THEN
                         'Pleegmoeder'
                     WHEN a.lambar = TRUE THEN
                         'Lambar'
                     ELSE
                         ''
                    END
            ) as opfok,
            COALESCE(vl.owner_full_name, '') as breeder_name,
            blindness_factor.dutch as blindness_factor,
            a.scrapie_genotype,
            vd.formatted_predicate,
            c.gave_birth_as_one_year_old as has_given_birth_as_one_year_old,

            arrival.arrival_date_dd_mm_yyyy as arrival_date,       
            CASE WHEN arrival.arrival_date < depart.depart_date THEN
                depart.depart_date_dd_mm_yyyy
            END depart_date,
       
            litters_kpi.litter_index as litter_index,
            litters_kpi.average_twt as average_twt,

            grouped_litter_data_by_litter.litter_number as litter_nummer,
            grouped_litter_data_by_litter.animal_total_born as animal_total_born,
            grouped_litter_data_by_litter.total_deaths as total_deaths,
            grouped_litter_data_by_litter.average_litter_size as average_litter_size,
            grouped_litter_data_by_litter.average_alive_per_litter as average_alive_per_litter,       
            grouped_litter_data_by_litter.average_deaths_litter as average_deaths_litter,       
       
            grouped_weights.average_birth_weight as average_birth_weight,
       
            CASE WHEN query_matured_counts.litter_count NOTNULL THEN
                CAST(ROUND((grouped_litter_data_by_litter.total_born_alive::float / query_matured_counts.litter_count)::numeric,1) AS TEXT)
            ELSE
               '-' 
            END as average_alive_per_year,
             
            query_matured_counts.total_matured,
            query_matured_counts.matured_for_others,
            query_matured_counts.matured_at_others,
            query_matured_counts.average_matured_per_year,
            
            grouped_8_weeks_data.average_growth_at_8_weeks as average_growth_at_8_weeks,
            grouped_8_weeks_data.average_weight_at_8_weeks as average_weight_at_8_weeks,
            grouped_8_weeks_data.average_weight_at_8_weeks_age_in_days as average_weight_at_8_weeks_age_in_days,
            grouped_8_weeks_data.average_growth_at_8_weeks_of_all_sucklings as average_growth_at_8_weeks_of_all_sucklings,
            (SELECT dd_mm_yyyy FROM view_breed_value_max_generation_date) as breed_value_evaluation_date,
            -- fokwaarden
            r.total_born,
            r.total_born_accuracy,
            r.still_born,
            r.still_born_accuracy,
            r.birth_interval, --tussenlamtijd
            r.birth_interval_accuracy,
            r.birth_weight,
            r.birth_weight_accuracy,
            r.weight_at8weeks,
            r.weight_at8weeks_accuracy,
            r.weight_at20weeks,
            r.weight_at20weeks_accuracy,
            r.muscle_thickness,
            r.muscle_thickness_accuracy,
            r.fat_thickness3 as fat_thickness,
            r.fat_thickness3accuracy as fat_thickness_accuracy
        FROM animal a
                 INNER JOIN view_animal_livestock_overview_details vd ON vd.animal_id = a.id
                 INNER JOIN result_table_breed_grades r ON r.animal_id = a.id
                 LEFT JOIN animal_cache c ON c.animal_id = a.id
                 LEFT JOIN animal mom ON mom.id = a.parent_mother_id
                 LEFT JOIN animal dad ON dad.id = a.parent_father_id
                 LEFT JOIN (VALUES $genderTranslationValues) AS gender(english, dutch) ON a.type = gender.english
                 LEFT JOIN (VALUES $blindnessFactorTranslationValues) AS blindness_factor(english, dutch) ON a.blindness_factor = blindness_factor.english
                 LEFT JOIN (VALUES $isoCountryAlphaTwoToNumericMapping) AS iso_country(alpha2, numeric) ON a.uln_country_code = iso_country.alpha2
                 LEFT JOIN view_location_details vl on a.location_of_birth_id = vl.location_id
                 LEFT JOIN (
                     SELECT
                        animal_mother_id,
                        ROUND(AVG(birth_weight)::numeric,1) as average_birth_weight
                    FROM animal_cache c
                             INNER JOIN animal a ON c.animal_id = a.id
                             INNER JOIN litter l ON l.id = a.litter_id
                    WHERE birth_weight NOTNULL
                    GROUP BY animal_mother_id
                 )grouped_weights ON grouped_weights.animal_mother_id = a.id
                LEFT JOIN (
                    SELECT
                        animal_mother_id,
                        ROUND(AVG(birth_interval)) as average_twt,
                        ROUND(365/AVG(birth_interval),2) as litter_index
                    FROM litter l
                    WHERE standard_litter_ordinal > 1 AND birth_interval NOTNULL
                    GROUP BY animal_mother_id
                )litters_kpi ON litters_kpi.animal_mother_id = a.id
                LEFT JOIN (
                         ".$this->getGroupedLitterDataByLitter($animalIdsArrayString)."
                )grouped_litter_data_by_litter ON grouped_litter_data_by_litter.mom_id = a.id
                LEFT JOIN (
                         ".$this->get8WeeksGroupedData($animalIdsArrayString)."
                )grouped_8_weeks_data ON grouped_8_weeks_data.mom_id = a.id
                LEFT JOIN (
                    ".self::queryMaturedCounts($animalIdsArrayString, $ubn)."
                )query_matured_counts ON query_matured_counts.ewe_id = a.id
                
                LEFT JOIN (
                    ".self::departQuery($animalIdsArrayString, $ubn)."
                )depart ON depart.animal_id = a.id
                LEFT JOIN (
                    ".self::arrivalQuery($animalIdsArrayString, $ubn)."
                )arrival ON arrival.animal_id = a.id
                
        WHERE a.id IN $animalIdsArrayString AND a.type = '".AnimalObjectType::Ewe."'";

        return $this->conn->query($sql)->fetchAll();
    }

    private function getGroupedLitterDataByLitter(string $animalIdsArrayString): string
    {
        return "SELECT
                 animal_mother_id as mom_id,
                 MAX(standard_litter_ordinal) as litter_number,
                 SUM(born_alive_count + stillborn_count) as animal_total_born,
                 SUM(born_alive_count) as total_born_alive,
                 SUM(stillborn_count) as total_deaths,
                 ROUND(AVG(born_alive_count + stillborn_count),2) as average_litter_size,
                 ROUND(AVG(born_alive_count),2) as average_alive_per_litter,
                 ROUND(AVG(stillborn_count),2) as average_deaths_litter
             FROM litter l
             WHERE l.animal_mother_id IN $animalIdsArrayString
             GROUP BY animal_mother_id";
    }

    private function get8WeeksGroupedData(string $animalIdsArrayString): string
    {
        return "SELECT
            a.parent_mother_id as mom_id,
            CASE WHEN AVG(ac.age_weight_at8weeks) NOTNULL AND AVG(ac.weight_at8weeks - ac.birth_weight) NOTNULL THEN
                ROUND((
                    (AVG(ac.weight_at8weeks - ac.birth_weight) * 1000)::float /
                    AVG(ac.age_weight_at8weeks)
                )::numeric,0)::text
            ELSE
                '-'
            END as average_growth_at_8_weeks,
            COALESCE(ROUND(AVG(ac.weight_at8weeks)::numeric,1)::text,'-') as average_weight_at_8_weeks,
            COALESCE(ROUND(AVG(ac.age_weight_at8weeks)::numeric,1)::text,'-') as average_weight_at_8_weeks_age_in_days,
        
            -- eigen lammeren van deze ooi die niet bij een pleegmoeder of lambar hebben gelopen
            CASE WHEN
                AVG(CASE WHEN a.surrogate_id ISNULL AND a.lambar = FALSE THEN ac.weight_at8weeks - ac.birth_weight END) NOTNULL AND
                AVG(CASE WHEN a.surrogate_id ISNULL AND a.lambar = FALSE THEN ac.age_weight_at8weeks END) NOTNULL
            THEN
                ROUND((
                    (AVG(CASE WHEN a.surrogate_id ISNULL AND a.lambar = FALSE THEN (ac.weight_at8weeks - ac.birth_weight) * 1000 END))::float /
                    AVG(CASE WHEN a.surrogate_id ISNULL AND a.lambar = FALSE THEN ac.age_weight_at8weeks END)
                )::numeric,0)::text
            ELSE
                '-'
            END as average_growth_at_8_weeks_of_all_sucklings
        FROM animal a
            INNER JOIN animal_cache ac on a.id = ac.animal_id
        WHERE a.parent_mother_id IN $animalIdsArrayString
        GROUP BY a.parent_mother_id";
    }

    /**
     * @param array $animalIds
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getOffspringData(array $animalIds, Location $location): array
    {
        $animalIdsArrayString = $this->getAnimalIdsArrayString($animalIds);
        $genderTranslationValues = SqlUtil::genderTranslationValues();

        $eweId = self::EWE_ID;

        $sql = "SELECT
            a.parent_mother_id as $eweId,
            a.litter_id,
            vd.dd_mm_yyyy_date_of_birth,
            a.uln_country_code as uln_country_code,
            a.uln_number as uln_number,
            a.lambar as lambar,
            a.surrogate_id as surrogate_id,
            NULLIF(COALESCE(a.uln_country_code, a.uln_number),'') as uln,
            dad.uln_country_code as dad_uln_country_code,
            dad.uln_number as dad_uln_number,
            NULLIF(COALESCE(dad.uln_country_code, dad.uln_number),'') as dad_uln,
            litter.born_alive_count + litter.stillborn_count as total_born,
            litter.stillborn_count, --dood
            gender.dutch as gender_dutch,
            a.type = '".AnimalObjectType::Ram."' as has_l_value,
            --gewicht
            ac.birth_weight as birth_weight,
            
            COALESCE(CAST(ac.weight_at8weeks AS text),'') as weight_at8weeks_kg,

            COALESCE(delivery_weight.weight,'') as delivery_weight,
            COALESCE(growth.average_growth_rate, '') as average_growth,
            COALESCE(transport.destination,'') as destination,
            --EUR
            '' as saldo, --currently an empty string placeholder
            '' as price_per_kg --currently an empty string placeholder
        FROM animal a
            INNER JOIN view_animal_livestock_overview_details vd ON vd.animal_id = a.id
            INNER JOIN animal_cache ac ON ac.animal_id = a.id
            LEFT JOIN litter ON litter.id = a.litter_id
            LEFT JOIN animal dad ON dad.id = a.parent_father_id
            LEFT JOIN (VALUES $genderTranslationValues) AS gender(english, dutch) ON a.type = gender.english
            LEFT JOIN (".self::queryAnimalDestination($location, true).")transport ON transport.animal_id = a.id
            LEFT JOIN (".self::queryAverageGrowth().")growth ON growth.animal_id = a.id
            LEFT JOIN (".self::queryWeightOfDelivery($location).")delivery_weight ON delivery_weight.animal_id = a.id
        WHERE
            a.parent_mother_id IN $animalIdsArrayString
        ORDER BY vd.date_of_birth ASC
";

        $primaryOutput = $this->conn->query($sql)->fetchAll();

        if (self::DUPLICATE_OFFSPRING_FOR_TESTING_COUNT <= 1) {
            return $primaryOutput;
        }

        $offspring = [];
        for ($i = 1; $i <= self::DUPLICATE_OFFSPRING_FOR_TESTING_COUNT; $i++) {
            foreach ($primaryOutput as $item) {
                $item['uln_country_code'] = $item['uln_country_code'].$i;
                $offspring[] = $item;
            }
        }
        return $offspring;
    }


    private static function queryAverageGrowth(): string
    {
        return "SELECT
    animal_id,
    CAST(AVG(growth_rate)::int AS TEXT) as average_growth_rate
FROM (
         SELECT
             w.animal_id,
             (w.weight - corrected_values.birth_weight) / EXTRACT(DAYS FROM (m.measurement_date - a.date_of_birth)) * 1000 as growth_rate
         FROM weight w
                  INNER JOIN animal a on w.animal_id = a.id
                  INNER JOIN measurement m ON m.id = w.id
                  INNER JOIN (
             SELECT
                 c.animal_id,
                 CASE WHEN c.birth_weight ISNULL THEN
                          (CASE WHEN va.n_ling::int = 1 THEN
                                    5.0
                                WHEN va.n_ling::int = 2 THEN
                                    4.0
                                WHEN va.n_ling::int = 3 THEN
                                    3.0
                                ELSE -- 4 or higher
                                    2.5
                              END
                              )
                      ELSE
                          c.birth_weight
                     END as birth_weight
             FROM animal_cache c
                      INNER JOIN view_animal_livestock_overview_details va ON va.animal_id = c.animal_id
             WHERE (c.birth_weight NOTNULL OR (va.n_ling NOTNULL AND 0 < va.n_ling::int))
         )corrected_values ON corrected_values.animal_id = w.animal_id
         WHERE m.is_active AND w.is_birth_weight = FALSE
           --ignore weights around date of birth to prevent division by zero
           --or possible strange growth values
           AND 4 < ABS(EXTRACT(DAYS FROM (m.measurement_date - a.date_of_birth)))
    )growth
GROUP BY animal_id";
    }


    /**
     * Nearest weight to last depart (or loss) for
     *
     * @param  Location  $location
     * @return string
     */
    private static function queryWeightOfDelivery(Location $location): string
    {
        return "SELECT
    w.animal_id,
    -- When more than one active weight is found on the exact same date time, take the highest weight
    CAST(ROUND(MAX(w.weight)::numeric,10) AS TEXT) as weight
FROM weight w
    INNER JOIN measurement m on w.id = m.id
    INNER JOIN (
    SELECT
        animal_id,
        MAX(measurement_date) as max_measurement_date
    FROM (
             SELECT
                 w.animal_id,
                 w.weight,
                 m.measurement_date,
                 last_transport.depart_date
             FROM weight w
                      INNER JOIN measurement m on w.id = m.id
                      INNER JOIN (
                 ".self::queryAnimalDestination($location, false)."
             ) last_transport ON last_transport.animal_id = w.animal_id
             WHERE m.is_active
               AND measurement_date <= last_transport.depart_date
               AND EXTRACT(DAYS FROM (last_transport.depart_date - m.measurement_date)) <= 14
         )weights_within_14_days_of_last_transport
    GROUP BY animal_id
    )last_qualified_weight_measurement_date ON last_qualified_weight_measurement_date.animal_id = w.animal_id
        AND last_qualified_weight_measurement_date.max_measurement_date = m.measurement_date
WHERE m.is_active
GROUP BY w.animal_id";
    }


    private static function queryAnimalDestination(Location $location, bool $includeLoss): string
    {
        return "SELECT
    transport.animal_id,
    transport.destination,
    transport.depart_date
FROM (
    ".self::lastDepartOrLossSubQuery($location,true, $includeLoss)."
)transport
INNER JOIN (
    SELECT
        transport.animal_id,
        MAX(transport.declare_id) as max_declare_id
    FROM (
             ".self::lastDepartOrLossSubQuery($location,false, $includeLoss)."
         )transport
             INNER JOIN (
        SELECT
            animal_id,
            MAX(depart_date) as max_depart_date
        FROM (
                 ".self::lastDepartOrLossSubQuery($location,false, $includeLoss)."
             )transport
        GROUP BY transport.animal_id
    )transport_max_date ON transport_max_date.animal_id = transport.animal_id AND transport_max_date.max_depart_date = transport.depart_date
    GROUP BY transport.animal_id
)transport_last_declare ON transport_last_declare.animal_id = transport.animal_id AND transport_last_declare.max_declare_id = transport.declare_id";
    }


    private static function lastDepartOrLossSubQuery(Location $location, bool $includeDestinationValue, bool $includeLoss): string
    {
        $ubn = $location->getUbn();

        $breedingReasonsOfDepart = SqlUtil::breedingReasonsOfDepart();

        $departDestinationValue = $includeDestinationValue ? "CASE WHEN reason_of_depart IN ($breedingReasonsOfDepart) THEN
                      'Fok'
                  ELSE
                      'Slacht'
                  END as destination," : "";

        $lossDestinationValue = $includeDestinationValue ? "'Dood' as destination,": "";

        $activeRequestStates = SqlUtil::activeRequestStateTypesJoinedList();

        $unionWithLossQuery = $includeLoss ? "UNION
                 SELECT
                     $lossDestinationValue
                     db.id as declare_id,
                     animal_id,
                     date_of_death as depart_date
                 FROM declare_loss loss
                          INNER JOIN declare_base db on loss.id = db.id
                 WHERE db.request_state IN ($activeRequestStates) AND db.ubn = '$ubn'" : "";

        return "SELECT
                     $departDestinationValue
                     db.id as declare_id,
                     animal_id,
                     depart_date
                 FROM declare_depart depart
                          INNER JOIN declare_base db on depart.id = db.id
                 WHERE db.request_state IN ($activeRequestStates) AND db.ubn = '$ubn'
                 ".$unionWithLossQuery;
    }


    private static function queryMaturedCounts(string $animalIdsArrayString, string $ubn): string
    {
        $activeRequestStates = SqlUtil::activeRequestStateTypesJoinedList();
        $minAgeInDaysForMaturity = self::MIN_AGE_IN_DAYS_FOR_MATURITY;

        return "SELECT
                    offspring_count.ewe_id,
                    matured_own_offspring + matured_for_others as total_matured,
                    matured_for_others,
                    matured_at_others,
                    l.litter_count,
                    CASE WHEN l.animal_mother_id NOTNULL THEN
                        COALESCE(ROUND(((matured_own_offspring + matured_for_others)::float / l.litter_count)::numeric,1)::text, '-')
                    ELSE
                       '-'
                    END as average_matured_per_year
                
                FROM (
                    SELECT
                        a.id as ewe_id,
                        COALESCE(own_offspring.matured_as_own_mother,0) as matured_own_offspring,
                        COALESCE(other_offspring_matured_as_surrogate.count,0) as matured_for_others,
                        COALESCE(own_offspring.matured_at_other_surrogate,0) as matured_at_others
                    FROM animal a
                        LEFT JOIN (
                            SELECT
                                a.parent_mother_id as own_mother_id,
                                COUNT(*) filter ( where a.surrogate_id ISNULL AND a.lambar = FALSE) as matured_as_own_mother,
                                COUNT(*) filter ( where a.surrogate_id NOTNULL OR lambar) as matured_at_other_surrogate
                            FROM animal a
                            LEFT JOIN (
                                SELECT
                                    loss.animal_id,
                                    MAX(loss.location_id) as location_id
                                FROM declare_loss loss
                                    INNER JOIN declare_base db ON db.id = loss.id
                                WHERE db.request_state IN ($activeRequestStates)
                                GROUP BY loss.animal_id
                            )loss ON loss.animal_id = a.id
                            WHERE
                                  a.parent_mother_id IN $animalIdsArrayString
                              AND (
                                  a.date_of_death ISNULL OR
                                   --did not die on own location before 90 days old
                                  NOT (a.date_of_death::date - a.date_of_birth::date < $minAgeInDaysForMaturity
                                  AND loss.location_id IN (SELECT id FROM location WHERE ubn = '$ubn'))
                              )
                              AND a.surrogate_id ISNULL
                            GROUP BY a.parent_mother_id
                        )own_offspring ON own_offspring.own_mother_id = a.id
                
                        LEFT JOIN (
                            SELECT
                                a.surrogate_id as maturing_mother_id,
                                COUNT(*) as count
                            FROM animal a
                            LEFT JOIN (
                                SELECT
                                    loss.animal_id,
                                    MAX(loss.location_id) as location_id
                                FROM declare_loss loss
                                    INNER JOIN declare_base db ON db.id = loss.id
                                WHERE db.request_state IN ($activeRequestStates)
                                GROUP BY loss.animal_id
                            )loss ON loss.animal_id = a.id                            
                            WHERE a.surrogate_id NOTNULL AND
                                  a.surrogate_id IN $animalIdsArrayString
                                  AND (
                                      a.date_of_death ISNULL OR
                                   --did not die on own location before 90 days old
                                  NOT (a.date_of_death::date - a.date_of_birth::date < $minAgeInDaysForMaturity
                                  AND loss.location_id IN (SELECT id FROM location WHERE ubn = '$ubn'))
                                  )
                            GROUP BY a.surrogate_id
                        )other_offspring_matured_as_surrogate ON other_offspring_matured_as_surrogate.maturing_mother_id = a.id
                    WHERE a.id IN $animalIdsArrayString
                    )offspring_count
                LEFT JOIN (
                    SELECT
                        animal_mother_id,
                        -- litter_ordinals are only set on active litters
                        MAX(litter_ordinal) as litter_count
                    FROM litter l
                    WHERE l.animal_mother_id IN $animalIdsArrayString
                    GROUP BY l.animal_mother_id
                )l ON l.animal_mother_id = offspring_count.ewe_id";
    }


    /**
     * @param array $animalIds
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getTreatmentsData(array $animalIds): array
    {
        $animalIdsArrayString = $this->getAnimalIdsArrayString($animalIds);

        $eweId = self::EWE_ID;
        $medicationsLabel = 'medications';
        $revokedState = RequestStateType::REVOKED;

        $sql = "SELECT
                    ta.animal_id as $eweId,
                    DATE(start_date) as start_date,
                    DATE(end_date) as end_date,
                    description,
                    m.medications as $medicationsLabel
                FROM treatment t
                    INNER JOIN treatment_animal ta on t.id = ta.treatment_id
                    -- We CANNOT assume each treatment always has at least one medication
                    LEFT JOIN (
                        SELECT
                            s.treatment_id,
                            array_agg(tm.name) as medications
                        FROM medication_selection s
                            INNER JOIN treatment_medication tm on s.treatment_medication_id = tm.id
                            INNER JOIN treatment_animal ta on s.treatment_id = ta.treatment_id
                        WHERE animal_id IN $animalIdsArrayString
                        GROUP BY s.treatment_id
                    )m ON m.treatment_id = t.id
                WHERE t.id IN (
                        SELECT
                            treatment_id
                        FROM treatment_animal
                        WHERE animal_id IN $animalIdsArrayString
                    ) AND status <> '$revokedState' ORDER BY start_date DESC";

        $results = $this->conn->query($sql)->fetchAll();

        $primaryOutput = array_map(function (array $result) use ($medicationsLabel) {
            $startDate = (new \DateTime($result['start_date']))->format(DateUtil::DATE_USER_DISPLAY_FORMAT);
            $endDate = (new \DateTime($result['end_date']))->format(DateUtil::DATE_USER_DISPLAY_FORMAT);
            $medicationsListAsString = $result[$medicationsLabel];

            $displayDate = $startDate === $endDate ? $startDate : $startDate . ' - ' .$endDate;

            $result[ReportLabel::DATE] = $displayDate;
            $result[$medicationsLabel] = [];

            $medications = SqlUtil::getArrayFromPostgreSqlArrayString($medicationsListAsString, false);

            if (empty($medications)) {
                $result[$medicationsLabel][0] = [
                    ReportLabel::NAME => '',
                ];

            } else {

                foreach ($medications as $key => $medicationLabel) {
                    $result[$medicationsLabel][$key] = [
                        ReportLabel::NAME => $medicationLabel,
                    ];
                }
            }

            return $result;
        }, $results);

        if (self::DUPLICATE_TREATMENTS_FOR_TESTING_COUNT <= 1) {
            return $primaryOutput;
        }

        $treatments = [];
        for ($i = 1; $i <= self::DUPLICATE_TREATMENTS_FOR_TESTING_COUNT; $i++) {
            foreach ($primaryOutput as $item) {

                foreach ($item[$medicationsLabel] as $key => $medication) {
                    $item[$medicationsLabel][$key][ReportLabel::NAME] = $item[$medicationsLabel][$key][ReportLabel::NAME].$i;
                }
                $treatments[] = $item;
            }
        }
        return $treatments;
    }


    public static function arrivalQuery(string $animalIdsArrayString, string $ubn): string
    {
        $dateFormat = SqlUtil::TO_CHAR_DATE_FORMAT;
        $activeRequestStates = SqlUtil::activeRequestStateTypesJoinedList();

        return "SELECT
                    animal_id,
                    MAX(arrival_date) as arrival_date,
                    to_char(MAX(arrival_date), '$dateFormat') as arrival_date_dd_mm_yyyy
                FROM (
                     SELECT
                        animal_id,
                        arrival_date
                    FROM declare_arrival arrival
                        INNER JOIN declare_base db on arrival.id = db.id
                    WHERE request_state IN ($activeRequestStates)
                        AND animal_id IN $animalIdsArrayString
                        AND arrival.location_id IN (SELECT id FROM location WHERE ubn = '$ubn')
                
                    UNION
                
                    SELECT
                        animal_id,
                        import_date as arrival_date
                    FROM declare_import import
                        INNER JOIN declare_base db on import.id = db.id
                    WHERE request_state IN ($activeRequestStates)
                        AND animal_id IN $animalIdsArrayString
                        AND import.location_id IN (SELECT id FROM location WHERE ubn = '$ubn')
                )arrival
                GROUP BY animal_id";
    }


    public static function departQuery(string $animalIdsArrayString, string $ubn): string
    {
        $dateFormat = SqlUtil::TO_CHAR_DATE_FORMAT;
        $activeRequestStates = SqlUtil::activeRequestStateTypesJoinedList();

        return "SELECT
                    animal_id,
                    MAX(depart_date) as depart_date,
                    to_char(MAX(depart_date), '$dateFormat') as depart_date_dd_mm_yyyy
                FROM (
                     SELECT
                        animal_id,
                        depart_date
                    FROM declare_depart depart
                        INNER JOIN declare_base db on depart.id = db.id
                    WHERE request_state IN ($activeRequestStates)
                        AND animal_id IN $animalIdsArrayString
                        AND depart.location_id IN (SELECT id FROM location WHERE ubn = '$ubn')
                
                    UNION
                
                    SELECT
                        animal_id,
                        export_date as depart_date
                    FROM declare_export export
                        INNER JOIN declare_base db on export.id = db.id
                    WHERE request_state IN ($activeRequestStates)
                        AND animal_id IN $animalIdsArrayString
                        AND export.location_id IN (SELECT id FROM location WHERE ubn = '$ubn')
                )depart
                GROUP BY animal_id";
    }
}
