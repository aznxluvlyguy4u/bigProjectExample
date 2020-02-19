<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\OffspringMaturityType;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\FilesystemUtil;
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

    const EWE_ID = 'ewe_id';

    /**
     * @param $actionBy
     * @param Location $location
     * @param ArrayCollection $content
     * @return JsonResponse
     */
    public function getReport($actionBy, Location $location, ArrayCollection $content)
    {
        $this->ulnValidator->validateUlns($content->get(Constant::ANIMALS_NAMESPACE));

        $this->filename = $this->getEweCardReportFileName($content);
        $this->folderName = self::FOLDER_NAME;
        $this->extension = self::defaultFileType();

        return $this->getPdfReport($actionBy, $location, $content);
    }

    /**
     * @param Person $actionBy
     * @param Location $location
     * @param ArrayCollection $content
     * @return JsonResponse
     */
    private function getPdfReport(Person $actionBy, Location $location, ArrayCollection $content)
    {
        $data = $this->getAnimalData($content, $location);

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

    public function getAnimalData(ArrayCollection $content, Location $location) {

        $animalIds = AnimalArrayReader::getAnimalsInContentArray($this->em, $content);

        $animalAndProductionValues = $this->getAnimalAndProductionData($animalIds, $location);

        $offspringData = $this->getOffspringData($animalIds, $location);

        $treatments = $this->getTreatmentsData($animalIds);

        $data = [];

        foreach ($animalIds as $animalId) {
            $data[$animalId] = [
                'animalAndProduction' => $this->filterAnimalAndProductionDataForAnimalId($animalId, $animalAndProductionValues),
                'offspring' => $this->filterDataForAnimalId($animalId, $offspringData),
                'treatments' => $this->filterDataForAnimalId($animalId, $treatments),
            ];
        }

        return $data;
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

    private function getAnimalIdsArrayString(array $animalIds): string {
        return "(".SqlUtil::getFilterListString($animalIds, false).")";
    }

    /**
     * @param array $animalIds
     * @param Location $location
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAnimalAndProductionData(array $animalIds, Location $location): array
    {
        $animalIdsArrayString = $this->getAnimalIdsArrayString($animalIds);
        $genderTranslationValues = SqlUtil::genderTranslationValues();
        $isoCountryAlphaTwoToNumericMapping = SqlUtil::isoCountryAlphaTwoToNumericMapping();

        $mainSectionValue = strtolower(SectionUtil::MAIN_SECTION);
        $complementarySectionValue = strtolower(SectionUtil::COMPLEMENTARY_SECTION);

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
            a.blindness_factor,
            a.scrapie_genotype,
            vd.formatted_predicate,
            c.gave_birth_as_one_year_old as has_given_birth_as_one_year_old,

            litters_kpi.litter_index as litter_index,
            litters_kpi.average_twt as average_twt,

            grouped_litter_data_by_litter.litter_number as litter_nummer,
            grouped_litter_data_by_litter.animal_total_born as animal_total_born,
            grouped_litter_data_by_litter.total_deaths as total_deaths,
            grouped_litter_data_by_litter.average_litter_size as average_litter_size,
            grouped_litter_data_by_litter.average_alive_per_litter as average_alive_per_litter,       
            grouped_litter_data_by_litter.average_deaths_litter as average_deaths_litter,       
       
            grouped_weights.average_birth_weight as average_birth_weight,
       
            CASE WHEN view_ewe_litter_age.ewe_id NOTNULL THEN
                CAST(ROUND((grouped_litter_data_by_litter.total_born_alive / view_ewe_litter_age.day_standardized_years)::numeric,1) AS TEXT)
            ELSE
               '-' 
            END as average_alive_per_year,
             
            COALESCE((own_offspring_matured_as_own_mother.count + other_offspring_matured_as_surrogate.count)::text, '-') as total_matured,       
            COALESCE(other_offspring_matured_as_surrogate.count::text, '-') as matured_for_others,
            COALESCE(own_offspring_matured_at_other_surrogate.count::text, '-') as matured_at_others,
            CASE WHEN view_ewe_litter_age.ewe_id NOTNULL THEN
                COALESCE(ROUND(((own_offspring_matured_as_own_mother.count + other_offspring_matured_as_surrogate.count) / view_ewe_litter_age.day_standardized_months)::numeric,1)::text, '-')
            ELSE
               '-' 
            END as average_matured_per_month,
            
            CASE WHEN view_ewe_litter_age.ewe_id NOTNULL THEN
                COALESCE(ROUND(((own_offspring_matured_as_own_mother.count + other_offspring_matured_as_surrogate.count) / view_ewe_litter_age.day_standardized_years)::numeric,1)::text, '-')
            ELSE
               '-' 
            END as average_matured_per_year,
            
            -- weaning/'spenen'-data is not available
            '-' as average_growth_until_weaning, -- groei tot spenen
            '-' as average_weaning_weight,
            '-' as average_weaning_age_in_days,
            '-' as average_weaning_growth_of_all_sucklings, 
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
                 LEFT JOIN (VALUES $isoCountryAlphaTwoToNumericMapping) AS iso_country(alpha2, numeric) ON a.uln_country_code = iso_country.alpha2
                 LEFT JOIN view_location_details vl on a.location_of_birth_id = vl.location_id
                 LEFT JOIN (
                     SELECT
                        animal_mother_id,
                        ROUND(AVG(birth_weight)::numeric,2) as average_birth_weight
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
                         SELECT
                             animal_mother_id,
                             MAX(standard_litter_ordinal) as litter_number,
                             SUM(born_alive_count + stillborn_count) as animal_total_born,
                             SUM(born_alive_count) as total_born_alive,
                             SUM(stillborn_count) as total_deaths,
                             ROUND(AVG(born_alive_count + stillborn_count),2) as average_litter_size,
                             ROUND(AVG(born_alive_count),2) as average_alive_per_litter,
                             ROUND(AVG(stillborn_count),2) as average_deaths_litter
                         FROM litter l
                         GROUP BY animal_mother_id
                )grouped_litter_data_by_litter ON grouped_litter_data_by_litter.animal_mother_id = a.id
                LEFT JOIN (
                    ".self::queryMaturedCount($location,OffspringMaturityType::OWN_OFFSPRING_MATURED_AS_OWN_MOTHER)."
                )own_offspring_matured_as_own_mother ON own_offspring_matured_as_own_mother.maturing_mother_id = a.id
                LEFT JOIN (
                    ".self::queryMaturedCount($location,OffspringMaturityType::OWN_OFFSPRING_MATURED_AT_OTHER_SURROGATE)."
                )own_offspring_matured_at_other_surrogate ON own_offspring_matured_at_other_surrogate.maturing_mother_id = a.id                
                LEFT JOIN (
                    ".self::queryMaturedCount($location,OffspringMaturityType::OTHER_OFFSPRING_MATURED_AS_SURROGATE)."
                )other_offspring_matured_as_surrogate ON other_offspring_matured_as_surrogate.maturing_mother_id = a.id
                LEFT JOIN view_ewe_litter_age ON view_ewe_litter_age.ewe_id = a.id       
        WHERE a.location_id NOTNULL AND a.is_alive
          AND r.total_born NOTNULL
          AND r.weight_at20weeks NOTNULL
          AND a.type = '".AnimalObjectType::Ewe."'
          AND a.id IN $animalIdsArrayString";

        return $this->conn->query($sql)->fetchAll();
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
            
            -- weaning/'spenen'-data is not available
            '' as weaning_weight,

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

        return $this->conn->query($sql)->fetchAll();
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


    /**
     * @param Location $location
     * @param string has to be from the enum OffspringMaturityType
     * @return string
     */
    private static function queryMaturedCount(Location $location, string $offspringMaturityType): string
    {
        $maturityDaysLimit = 90;

        $ubn = $location->getUbn();
        $activeRequestStates = SqlUtil::activeRequestStateTypesJoinedList();

        switch ($offspringMaturityType) {
            case OffspringMaturityType::OWN_OFFSPRING_MATURED_AS_OWN_MOTHER:
                $selectAndGroupBy = 'a.parent_mother_id';
                $mainFilterAnimal = 'mom';
                $mainFilterHasSurrogate = ' AND a.surrogate_id ISNULL';
                break;
            case OffspringMaturityType::OWN_OFFSPRING_MATURED_AT_OTHER_SURROGATE:
                $selectAndGroupBy = 'a.parent_mother_id';
                $mainFilterAnimal = 'mom';
                $mainFilterHasSurrogate = ' AND a.surrogate_id NOTNULL';
                break;
            case OffspringMaturityType::OTHER_OFFSPRING_MATURED_AS_SURROGATE:
                $selectAndGroupBy = 'a.surrogate_id';
                $mainFilterAnimal = 'surrogate';
                $mainFilterHasSurrogate = ' AND a.surrogate_id NOTNULL';
                break;
            default:
                throw new \Exception("Unsupported OffspringMaturityType for queryMaturedCount(), input: ".$offspringMaturityType);
        }

        return "SELECT
            $selectAndGroupBy as maturing_mother_id,
            COUNT(*) as count
        FROM animal a
                 INNER JOIN animal mom ON mom.id = a.parent_mother_id
                 LEFT JOIN animal surrogate ON surrogate.id = a.surrogate_id
                 INNER JOIN (
                    -- Depart before 90 days age
                    SELECT
                        animal_id
                    FROM animal a
                             INNER JOIN declare_depart depart on a.id = depart.animal_id
                             INNER JOIN declare_base db on depart.id = db.id
                    WHERE db.request_state IN ($activeRequestStates)
                      AND db.ubn = '$ubn'
                      AND a.date_of_birth NOTNULL
                      AND (EXTRACT(DAYS FROM (depart_date - a.date_of_birth)) < $maturityDaysLimit)
        
        
                    GROUP BY animal_id
        
                    UNION
        
                    -- At least on 90 days old, still alive and still on location
                    SELECT
                        r.animal_id
                    FROM animal_residence r
                             INNER JOIN location l ON l.id = r.location_id
                             INNER JOIN animal a on r.animal_id = a.id
                    WHERE l.ubn = '$ubn'
                      --Check residences that are related to the birth and do not end before 90 days
                      AND EXTRACT(DAYS FROM (start_date - a.date_of_birth)) <= 1
                      AND (
                            end_date ISNULL OR $maturityDaysLimit <= EXTRACT(DAYS FROM (end_date - a.date_of_birth))
                        )
                      --Must be alive until at least 90 days
                      AND (
                            a.date_of_death ISNULL OR
                            (a.date_of_death NOTNULl AND (EXTRACT(DAYS FROM (a.date_of_death - a.date_of_birth)) > $maturityDaysLimit))
                        )
                )matured_animal ON matured_animal.animal_id = a.id
        WHERE ($mainFilterAnimal.ubn_of_birth = '$ubn') $mainFilterHasSurrogate
        GROUP BY $selectAndGroupBy";
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

        $sql = "SELECT
            a.id as $eweId,
            vd.dd_mm_yyyy_date_of_birth as treatment_date,
            a.date_of_birth as treatment_date_iso_format,
            'some category' as category,
            'some sub category' as sub_category,
            'Enting' as treatment,
            '' as comment, --currently just an empty string filler
            '' as costs --currently just an empty string filler
        FROM animal a
            INNER JOIN view_animal_livestock_overview_details vd ON vd.animal_id = a.id
        WHERE a.location_id NOTNULL AND a.is_alive
            AND a.type = 'Ewe'
            AND a.id IN $animalIdsArrayString
        UNION
        SELECT
            a.id as $eweId,
            '31-03-2012' as treatment_date,
            '2012-03-31' as treatment_date_iso_format,
            'some other category' as category,
            'some other sub category' as sub_category,
            'Massage' as treatment,
            '' as comment, --currently just an empty string filler
            '' as costs --currently just an empty string filler
        FROM animal a
        INNER JOIN view_animal_livestock_overview_details vd ON vd.animal_id = a.id
        WHERE a.location_id NOTNULL AND a.is_alive
            AND a.type = 'Ewe'
            AND a.id IN $animalIdsArrayString
        UNION
        SELECT
            a.id as $eweId,
            '01-05-2014' as treatment_date,
            '2014-05-01' as treatment_date_iso_format,
            'another category' as category,
            'another sub category' as sub_category,
            'Tandheelkundige behandeling' as treatment,
            '' as comment, --currently just an empty string filler
            '' as costs --currently just an empty string filler
        FROM animal a
            INNER JOIN view_animal_livestock_overview_details vd ON vd.animal_id = a.id
        WHERE a.location_id NOTNULL AND a.is_alive
            AND a.type = 'Ewe'
            AND a.id IN $animalIdsArrayString
        ORDER BY treatment_date_iso_format
        ";

        return $this->conn->query($sql)->fetchAll();
    }
}
