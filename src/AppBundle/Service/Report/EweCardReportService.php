<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
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
        $data = $this->getAnimalData($content);

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

    public function getAnimalData(ArrayCollection $content) {

        $animalIds = AnimalArrayReader::getAnimalsInContentArray($this->em, $content);

        $animalAndProductionValues = $this->getAnimalAndProductionData($animalIds);

        $offspringData = $this->getOffspringData($animalIds);

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
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getAnimalAndProductionData(array $animalIds): array
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
            'Pleegmoeder' as opfok, --opfok = Pleegmoeder / Lambar
            'Braams' as breeder_name,
            a.blindness_factor,
            a.scrapie_genotype,
            vd.formatted_predicate,
            true as has_given_birth_as_one_year_old,
            9 as litter_nummer,
            27 as animal_total_born,
            19 as total_matured,
            1 as total_deaths,
            1.48 as litter_index,
            247 as average_twt,
            0 as matured_for_others,
            0 as matured_at_others,
            3.0 as average_litter_size,
            4.3 as average_alive_per_year,
            3.2 as average_birth_weight,
            192 as average_growth_until_weaning, -- groei tot spenen
            2.9 as average_alive_per_litter,
            3.1 as average_matured_per_year,
            15.5 as average_weaning_weight,
            0.1 as average_deaths_litter,
            0.3 as average_matured_per_month,
            64 as average_weaning_age_in_days,
            192 as average_weaning_growth_of_all_sucklings,
            '18-06-2018' as breed_value_evaluation_date,
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
                 LEFT JOIN animal mom ON mom.id = a.parent_mother_id
                 LEFT JOIN animal dad ON dad.id = a.parent_father_id
                 LEFT JOIN (VALUES $genderTranslationValues) AS gender(english, dutch) ON a.type = gender.english
                 LEFT JOIN (VALUES $isoCountryAlphaTwoToNumericMapping) AS iso_country(alpha2, numeric) ON a.uln_country_code = iso_country.alpha2
        WHERE a.location_id NOTNULL AND a.is_alive
          AND r.total_born NOTNULL
          AND r.weight_at20weeks NOTNULL
          AND a.type = 'Ewe'
          AND a.id IN $animalIdsArrayString";

        return $this->conn->query($sql)->fetchAll();
    }

    /**
     * @param array $animalIds
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getOffspringData(array $animalIds): array
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
            NULLIF(COALESCE(a.uln_country_code, a.uln_number),'') as uln,
            dad.uln_country_code as dad_uln_country_code,
            dad.uln_number as dad_uln_number,
            NULLIF(COALESCE(dad.uln_country_code, dad.uln_number),'') as dad_uln,
            litter.born_alive_count + litter.stillborn_count as total_born,
            litter.stillborn_count, --dood
            gender.dutch as gender_dutch,
            a.type = 'Ram' as has_l_value,
            --gewicht
            3.0 as birth_weight,
            11.0 as weaning_weight,
            41.0 as delivery_weight,
            259 as average_growth,
            'Slacht' as destination,
            --EUR
            '' as saldo, --currently an empty string placeholder
            '' as price_per_kg --currently an empty string placeholder
        FROM animal a
            INNER JOIN view_animal_livestock_overview_details vd ON vd.animal_id = a.id
            LEFT JOIN litter ON litter.id = a.litter_id
            LEFT JOIN animal dad ON dad.id = a.parent_father_id
            LEFT JOIN (VALUES $genderTranslationValues) AS gender(english, dutch) ON a.type = gender.english
        WHERE
            a.parent_mother_id IN $animalIdsArrayString
        ORDER BY vd.date_of_birth ASC
";

        return $this->conn->query($sql)->fetchAll();
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
