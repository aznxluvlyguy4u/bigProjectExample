<?php


namespace AppBundle\Service\Report;


use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\Locale;
use AppBundle\Util\DateUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BreedValuesReportQueryGenerator
{
    const ACCURACY_TABLE_LABEL_SUFFIX = '_acc';

    /** @var EntityManagerInterface */
    private $em;
    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    private $animalShouldHaveAtleastOneExistingBreedValueFilter;
    /** @var string */
    private $breedValuesSelectQueryPart;
    /** @var string */
    private $breedValuesPlusSignsQueryJoinPart;
    /** @var string */
    private $breedValuesNullFilter;
    

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }


    /**
     * @param string $sql
     * @return array
     * @throws DBALException
     */
    protected function fetchAll($sql)
    {
        return $this->em->getConnection()->query($sql)->fetchAll();
    }


    /**
     * @param bool $concatBreedValuesAndAccuracies
     * @param bool $includeAnimalsWithoutAnyBreedValues
     * @param bool $ignoreHiddenBreedValueTypes
     * @throws DBALException
     */
    private function createBreedIndexBatchAndQueryParts($concatBreedValuesAndAccuracies = true, $includeAnimalsWithoutAnyBreedValues = false, $ignoreHiddenBreedValueTypes = false)
    {
        // Reset values
        $this->animalShouldHaveAtleastOneExistingBreedValueFilter = '';


        //Create breed index batch query parts

        //TODO fix result_table_breed_grades index accuracy column names to match those in breed_index_type tables. Before activating the index part below.

        $existingBreedIndexColumnValues = [];
//        $prefix = '';
//        $nullCheckFilter = '';
//
//        $sql = "SELECT en FROM breed_index_type";
//        foreach ($this->conn->query($sql)->fetchAll() as $result)
//        {
//            $en = $result['en'];
//            $nullCheckFilter = $nullCheckFilter . $prefix . "SELECT '$en' as breed_index_type FROM breed_index b
//                      INNER JOIN lamb_meat_breed_index c ON b.id = c.id
//                    GROUP BY type";
//            $prefix = "
//            UNION
//            ";
//        }
//
//        $sql = "SELECT nl, result_table_value_variable, result_table_accuracy_variable
//                FROM breed_index_type
//                INNER JOIN (
//                    $nullCheckFilter
//                    )g ON g.breed_index_type = breed_index_type.en";
//        $existingBreedIndexColumnValues = $this->conn->query($sql)->fetchAll();


        //Create breed value batch query parts

        $sql = "SELECT nl, result_table_value_variable, result_table_accuracy_variable
                FROM breed_value_type bvt
                WHERE
                  (
                    id IN (
                      SELECT breed_value_type_id
                      FROM breed_value_genetic_base
                      GROUP BY breed_value_type_id
                    )
                    OR
                    nl IN (
                      SELECT type
                      FROM normal_distribution
                      GROUP BY type
                    )
                  ) ".($ignoreHiddenBreedValueTypes ? ' AND show_result' : '')."
                
                ORDER BY bvt.id ASC";

        $existingBreedValueColumnValues = $this->fetchAll($sql);

        $this->breedValuesSelectQueryPart = '';
        $this->breedValuesPlusSignsQueryJoinPart = '';
        $this->breedValuesNullFilter = '';
        $valuesPrefix = '';
        $filterPrefix = '';

        if ($concatBreedValuesAndAccuracies) {

            foreach ([$existingBreedIndexColumnValues, $existingBreedValueColumnValues] as $columnValuesSets) {

                foreach ($columnValuesSets as $columnValueSet) {
                    $breedValueLabel = $this->translatedBreedValueColumnHeader($columnValueSet['nl']);
                    $resultTableValueVar = $columnValueSet['result_table_value_variable'];
                    $resultTableAccuracyVar = $columnValueSet['result_table_accuracy_variable'];

                    $this->breedValuesSelectQueryPart = $this->breedValuesSelectQueryPart . $valuesPrefix . "NULLIF(CONCAT(
                         ".$resultTableValueVar."_plus_sign.mark,
                         COALESCE(CAST(ROUND(CAST(bg.".$resultTableValueVar." AS NUMERIC), 2) AS TEXT),''),'/',
                         COALESCE(CAST(ROUND(bg.".$resultTableAccuracyVar."*100) AS TEXT),'')
                     ),'/') as ".$breedValueLabel;

                    $valuesPrefix = ",
                        ";

                    $this->breedValuesPlusSignsQueryJoinPart = $this->breedValuesPlusSignsQueryJoinPart . "LEFT JOIN (VALUES (true, '+'),(false, '')) AS ".$resultTableValueVar."_plus_sign(is_positive, mark) ON (bg.".$resultTableValueVar." > 0) = ".$resultTableValueVar."_plus_sign.is_positive
                    ";

                    $this->breedValuesNullFilter = $this->breedValuesNullFilter . $filterPrefix . "bg.".$resultTableValueVar." NOTNULL
                    ";

                    $filterPrefix = ' OR ';
                }
            }

        } else {
            //Do NOT concat breed value and accuracies

            foreach ([$existingBreedIndexColumnValues, $existingBreedValueColumnValues] as $columnValuesSets) {

                foreach ($columnValuesSets as $columnValueSet) {
                    $breedValueLabel = $this->translatedBreedValueColumnHeader($columnValueSet['nl']);
                    $resultTableValueVar = $columnValueSet['result_table_value_variable'];
                    $resultTableAccuracyVar = $columnValueSet['result_table_accuracy_variable'];

                    $this->breedValuesSelectQueryPart = $this->breedValuesSelectQueryPart . $valuesPrefix
                        . " ROUND(CAST(bg.".$resultTableValueVar." AS NUMERIC), 2) as ".$breedValueLabel .",
                        ROUND(bg.".$resultTableAccuracyVar."*100) as ". $breedValueLabel. self::ACCURACY_TABLE_LABEL_SUFFIX;

                    $valuesPrefix = ",
                        ";

                    //keep  $this->breedValuesNullFilter blank

                    $this->breedValuesNullFilter = $this->breedValuesNullFilter . $filterPrefix . "bg.".$resultTableValueVar." NOTNULL
                    ";

                    $filterPrefix = ' OR ';
                }
            }

        }

        $this->animalShouldHaveAtleastOneExistingBreedValueFilter = '';
        if (!$includeAnimalsWithoutAnyBreedValues) {
            $this->animalShouldHaveAtleastOneExistingBreedValueFilter = "AND (
                        $this->breedValuesNullFilter
                )";
        }
    }


    /**
     * @param string $breedValueTypeNl
     * @return string
     */
    public function translatedBreedValueColumnHeader($breedValueTypeNl)
    {
        return strtr($breedValueTypeNl, [
           BreedValueTypeConstant::ODIN_BC => 'WormRes'
        ]);
    }


    /**
     * @param bool $concatBreedValuesAndAccuracies
     * @param bool $includeAnimalsWithoutAnyBreedValues
     * @param bool $ignoreHiddenBreedValueTypes
     * @return string
     * @throws DBALException
     */
    public function getFullBreedValuesReportOverviewQuery($concatBreedValuesAndAccuracies = true, $includeAnimalsWithoutAnyBreedValues = false, $ignoreHiddenBreedValueTypes = false)
    {
        $this->createBreedIndexBatchAndQueryParts($concatBreedValuesAndAccuracies, $includeAnimalsWithoutAnyBreedValues,
            $ignoreHiddenBreedValueTypes);

        $sql = "
            SELECT
            NULLIF(CONCAT(a.uln_country_code, a.uln_number),'') as uln,
            a.animal_order_number as werknummer,
            NULLIF(CONCAT(a.pedigree_country_code, a.pedigree_number),'') as stn,
            a.nickname as naam,
            DATE(a.date_of_birth) as geboortedatum,
            l.born_alive_count + l.stillborn_count as n_ling,
            gender.dutch as geslacht,
            a.breed_code as rascode,
            rastype.dutch as status,
            p.abbreviation as stamboek,
            NULLIF(CONCAT(
                     COALESCE(CAST(c.production_age AS TEXT), '-'),'/',
                     COALESCE(CAST(c.litter_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c.total_offspring_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c.born_alive_offspring_count AS TEXT), '-'),
                     production_asterisk.mark
                 ),'-/-/-/-') as productie,
            a.blindness_factor as blindfactor,
            a.predicate as predikaat,
            a.predicate_score as predikaat_score,
            a.scrapie_genotype,
            --LATEST EXTERIOR
            DATE(c.exterior_measurement_date) as exterieurmetingdatum,
            c.skull as kop,
            c.progress as ontwikkeling,
            c.muscularity as bespiering,
            c.proportion as evenredigheid,
            c.exterior_type as type,
            c.leg_work as beenwerk,
            c.fur as vacht,
            c.general_appearance as algemeen_voorkomen,
            c.height as schofthoogte,
            c.torso_length as lengte,
            c.breast_depth as borstdiepte,
            --DAD
            NULLIF(CONCAT(dad.uln_country_code, dad.uln_number),'') as uln_vader,
            NULLIF(CONCAT(dad.pedigree_country_code, dad.pedigree_number),'') as stamboeknummer_vader,
            dad.breed_code as rascode_vader,
            rastype_dad.dutch as rasstatus_vader,
            p_dad.abbreviation as stamboek_vader,
            NULLIF(CONCAT(
                     COALESCE(CAST(c_dad.production_age AS TEXT), '-'),'/',
                     COALESCE(CAST(c_dad.litter_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c_dad.total_offspring_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c_dad.born_alive_offspring_count AS TEXT), '-'),
                     production_asterisk_f.mark
                 ),'-/-/-/-') as productie_vader,
            --MOM
            NULLIF(CONCAT(mom.uln_country_code, mom.uln_number),'') as uln_moeder,
            NULLIF(CONCAT(mom.pedigree_country_code, mom.pedigree_number),'') as stamboeknummer_moeder,
            mom.breed_code as rascode_moeder,
            rastype_mom.dutch as rasstatus_moeder,
            p_mom.abbreviation as stamboek_moeder,
            NULLIF(CONCAT(
                     COALESCE(CAST(c_mom.production_age AS TEXT), '-'),'/',
                     COALESCE(CAST(c_mom.litter_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c_mom.total_offspring_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c_mom.born_alive_offspring_count AS TEXT), '-'),
                     production_asterisk_m.mark
                 ),'-/-/-/-') as productie_moeder,
            --Fokker (NAW gegevens en UBN)
            COALESCE(l_breeder.ubn, a.ubn_of_birth) as fokker_ubn,
            NULLIF(breeder.first_name,'') as voornaam_fokker,
            NULLIF(breeder.last_name,'') as achternaam_fokker,
            address_breeder.street_name as straat_fokker,
            address_breeder.address_number as huisnummer_fokker,
            address_breeder.address_number_suffix as huisnummertoevoeging_fokker,
            address_breeder.postal_code as postcode_fokker,
            address_breeder.city as stad_fokker,
            address_breeder.country as land_fokker,
            breeder.email_address as emailadres_fokker,
            NULLIF(breeder.cellphone_number,'') as mobiel_nummer_fokker,
            --Eigenaar (NAW gegevens en UBN)
            ubn_alive.ubn as huidig_ubn,
            NULLIF(o.first_name,'') as voornaam_eigenaar,
            NULLIF(o.last_name,'') as achternaam_eigenaar,
            address.street_name as straat_eigenaar,
            address.address_number as huisnummer_eigenaar,
            address.address_number_suffix as huisnummertoevoeging_eigenaar,
            address.postal_code as postcode_eigenaar,
            address.city as stad_eigenaar,
            address.country as land_eigenaar,
            o.email_address as emailadres_eigenaar,
            NULLIF(o.cellphone_number,'') as mobiel_nummer_eigenaar,
            
            --BREED VALUES
            ".$this->breedValuesSelectQueryPart."
            
            FROM animal a
                LEFT JOIN (
                          SELECT a.id as animal_id, l.ubn FROM animal a
                            INNER JOIN location l ON l.id = a.location_id
                          WHERE a.is_alive
                        )ubn_alive ON ubn_alive.animal_id = a.id
                LEFT JOIN pedigree_register p ON a.pedigree_register_id = p.id
                LEFT JOIN litter l ON l.id = a.litter_id
                LEFT JOIN animal_cache c ON c.animal_id = a.id
                LEFT JOIN result_table_breed_grades bg ON bg.animal_id = a.id
                LEFT JOIN animal mom ON mom.id = a.parent_mother_id
                LEFT JOIN animal dad ON dad.id = a.parent_father_id
                LEFT JOIN animal_cache c_dad ON c_dad.animal_id = dad.id
                LEFT JOIN animal_cache c_mom ON c_mom.animal_id = mom.id
                LEFT JOIN pedigree_register p_mom ON mom.pedigree_register_id = p_mom.id
                LEFT JOIN pedigree_register p_dad ON dad.pedigree_register_id = p_dad.id
                LEFT JOIN location loc ON loc.id = a.location_id
                LEFT JOIN company ON company.id = loc.company_id
                LEFT JOIN address ON address.id = company.address_id
                LEFT JOIN person o ON o.id = company.owner_id
                LEFT JOIN location l_breeder ON l_breeder.id = a.location_of_birth_id
                LEFT JOIN company c_breeder ON c_breeder.id = l_breeder.company_id
                LEFT JOIN address address_breeder ON address_breeder.id = c_breeder.address_id
                LEFT JOIN person breeder ON breeder.id = c_breeder.owner_id
                LEFT JOIN (VALUES ".SqlUtil::genderTranslationValues().") AS gender(english, dutch) ON a.type = gender.english
                LEFT JOIN (VALUES ".SqlUtil::breedTypeTranslationValues().") AS rastype(english, dutch) ON a.breed_type = rastype.english
                LEFT JOIN (VALUES ".SqlUtil::breedTypeTranslationValues().") AS rastype_mom(english, dutch) ON mom.breed_type = rastype_mom.english
                LEFT JOIN (VALUES ".SqlUtil::breedTypeTranslationValues().") AS rastype_dad(english, dutch) ON dad.breed_type = rastype_dad.english
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk(bool_val, mark) ON c.gave_birth_as_one_year_old = production_asterisk.bool_val
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk_f(bool_val, mark) ON c_dad.gave_birth_as_one_year_old = production_asterisk_f.bool_val
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk_m(bool_val, mark) ON c_mom.gave_birth_as_one_year_old = production_asterisk_m.bool_val
                ".$this->breedValuesPlusSignsQueryJoinPart."
            WHERE
                a.location_id NOTNULL
                $this->animalShouldHaveAtleastOneExistingBreedValueFilter";

        return $sql;
    }


    /**
     * @param int $locationId
     * @param array $ulnFilter
     * @param bool $matchLocationIdOfSelectedAnimals
     * @param bool $concatBreedValuesAndAccuracies
     * @param bool $includeAnimalsWithoutAnyBreedValues
     * @param bool $ignoreHiddenBreedValueTypes
     * @return string
     * @throws DBALException
     */
    public function createLiveStockReportQuery($locationId,
                                                 array $ulnFilter = [],
                                                 $matchLocationIdOfSelectedAnimals = false,
                                                 $concatBreedValuesAndAccuracies = true,
                                                 $includeAnimalsWithoutAnyBreedValues = true,
                                                 $ignoreHiddenBreedValueTypes = false
    )
    {
        $this->createBreedIndexBatchAndQueryParts($concatBreedValuesAndAccuracies, $includeAnimalsWithoutAnyBreedValues,
            $ignoreHiddenBreedValueTypes);

        $filterString = "WHERE a.is_alive = true AND a.location_id = ".$locationId." ORDER BY a.animal_order_number ASC";

        $ulnCount = count($ulnFilter);
        if ($ulnCount > 0) {
            $filterString = "WHERE ".SqlUtil::getUlnQueryFilter($ulnFilter, 'a.');

            if ($matchLocationIdOfSelectedAnimals) {
                $filterString = $filterString ." a.location_id = ".$locationId;
            }
        }

        $filterString .= ' ' . $this->animalShouldHaveAtleastOneExistingBreedValueFilter;

        $sql = "SELECT DISTINCT 
                    CONCAT(a.uln_country_code, a.uln_number) as a_uln,
                    CONCAT(a.pedigree_country_code, a.pedigree_number) as a_stn,
                    CONCAT(mom.uln_country_code, mom.uln_number) as m_uln,
                    CONCAT(mom.pedigree_country_code, mom.pedigree_number) as m_stn,
                    CONCAT(dad.uln_country_code, dad.uln_number) as f_uln,
                    CONCAT(dad.pedigree_country_code, dad.pedigree_number) as f_stn,
                    -- gender.dutch as gender,
                    a.gender as gender,
                    a.animal_order_number as a_animal_order_number,
                    to_char(a.date_of_birth, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as a_date_of_birth,
                    to_char(mom.date_of_birth, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as m_date_of_birth,
                    to_char(dad.date_of_birth, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as f_date_of_birth,
                    
                    a_breed_types.dutch_first_letter as a_dutch_breed_status,
                    a.breed_code as a_breed_code,
                    mom_breed_types.dutch_first_letter as m_dutch_breed_status,
                    mom.breed_code as m_breed_code,
                    dad_breed_types.dutch_first_letter as f_dutch_breed_status, 
                    dad.breed_code as f_breed_code,

                    a.scrapie_genotype as a_scrapie_genotype,
                    mom.scrapie_genotype as m_scrapie_genotype,
                    dad.scrapie_genotype as f_scrapie_genotype,

                    c.n_ling as a_n_ling,
                    c_mom.n_ling as m_n_ling,
                    c_dad.n_ling as f_n_ling,
                    a.predicate as a_predicate_value,
                    mom.predicate as m_predicate_value,
                    dad.predicate as f_predicate_value,
                    a.predicate_score as a_predicate_score,
                    mom.predicate_score as m_predicate_score,
                    dad.predicate_score as f_predicate_score,
                    c.muscularity as a_muscularity,
                    c_mom.muscularity as m_muscularity,
                    c_dad.muscularity as f_muscularity,
                    c.general_appearance as a_general_appearance,
                    c_mom.general_appearance as m_general_appearance,
                    c_dad.general_appearance as f_general_appearance,

                NULLIF(CONCAT(
                     COALESCE(CAST(c.production_age AS TEXT), '-'),'/',
                     COALESCE(CAST(c.litter_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c.total_offspring_count AS TEXT), '-'),'/',
                     COALESCE(CAST(c.born_alive_offspring_count AS TEXT), '-'),
                     production_asterisk.mark
                 ),'-/-/-/-') as production,

                NULLIF(CONCAT(
                         COALESCE(CAST(c_dad.production_age AS TEXT), '-'),'/',
                         COALESCE(CAST(c_dad.litter_count AS TEXT), '-'),'/',
                         COALESCE(CAST(c_dad.total_offspring_count AS TEXT), '-'),'/',
                         COALESCE(CAST(c_dad.born_alive_offspring_count AS TEXT), '-'),
                         production_asterisk_dad.mark
                     ),'-/-/-/-') as f_production,


                NULLIF(CONCAT(
                         COALESCE(CAST(c_mom.production_age AS TEXT), '-'),'/',
                         COALESCE(CAST(c_mom.litter_count AS TEXT), '-'),'/',
                         COALESCE(CAST(c_mom.total_offspring_count AS TEXT), '-'),'/',
                         COALESCE(CAST(c_mom.born_alive_offspring_count AS TEXT), '-'),
                         production_asterisk_mom.mark
                     ),'-/-/-/-') as m_production,
                a.ubn_of_birth,
                -- location_of_birth.ubn as ubn_of_birth,
                location.ubn as current_ubn,
                  
                  --BREED VALUES
                  ".$this->breedValuesSelectQueryPart."
                  
              FROM animal a
                LEFT JOIN animal mom ON a.parent_mother_id = mom.id
                LEFT JOIN animal dad ON a.parent_father_id = dad.id
                LEFT JOIN animal_cache c ON a.id = c.animal_id
                LEFT JOIN animal_cache c_mom ON mom.id = c_mom.animal_id
                LEFT JOIN animal_cache c_dad ON dad.id = c_dad.animal_id
                LEFT JOIN location ON a.location_id = location.id
                -- LEFT JOIN location location_of_birth ON a.location_of_birth_id = location_of_birth.id
                LEFT JOIN result_table_breed_grades bg ON a.id = bg.animal_id
                -- LEFT JOIN (VALUES ".SqlUtil::genderTranslationValues().") AS gender(english, dutch) ON a.type = gender.english
                LEFT JOIN (VALUES ".SqlUtil::breedTypeFirstLetterOnlyTranslationValues().") AS a_breed_types(english, dutch_first_letter) ON a.breed_type = a_breed_types.english
                LEFT JOIN (VALUES ".SqlUtil::breedTypeFirstLetterOnlyTranslationValues().") AS mom_breed_types(english, dutch_first_letter) ON mom.breed_type = mom_breed_types.english
                LEFT JOIN (VALUES ".SqlUtil::breedTypeFirstLetterOnlyTranslationValues().") AS dad_breed_types(english, dutch_first_letter) ON dad.breed_type = dad_breed_types.english
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk(bool_val, mark) ON c.gave_birth_as_one_year_old = production_asterisk.bool_val
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk_dad(bool_val, mark) ON c_dad.gave_birth_as_one_year_old = production_asterisk_dad.bool_val
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk_mom(bool_val, mark) ON c_mom.gave_birth_as_one_year_old = production_asterisk_mom.bool_val
                ".$this->breedValuesPlusSignsQueryJoinPart."
            ".$filterString
            ." ORDER BY a.animal_order_number ASC";

        return $sql;
    }


    /**
     * @param bool $concatBreedValuesAndAccuracies
     * @param bool $includeAnimalsWithoutAnyBreedValues
     * @param bool $ignoreHiddenBreedValueTypes
     * @param int $maxCurrentAnimalAgeInYears
     * @param \DateTime $pedigreeActiveEndDateLimit
     * @return string
     * @throws DBALException
     */
    public function createAnimalsOverviewReportQuery($concatBreedValuesAndAccuracies = true,
                                                     $includeAnimalsWithoutAnyBreedValues = true,
                                                     $ignoreHiddenBreedValueTypes = false,
                                                     $maxCurrentAnimalAgeInYears,
                                                     $pedigreeActiveEndDateLimit
    )
    {
        $this->createBreedIndexBatchAndQueryParts($concatBreedValuesAndAccuracies, $includeAnimalsWithoutAnyBreedValues,
            $ignoreHiddenBreedValueTypes);

        $filterString = "WHERE
                          a.date_of_birth NOTNULL
                          AND EXTRACT(YEAR FROM AGE(NOW(), a.date_of_birth)) < $maxCurrentAnimalAgeInYears
                          AND a.animal_id IN (
                            SELECT
                              animal_id
                            FROM animal_residence r
                            GROUP BY animal_id
                            UNION
                            SELECT
                              id as animal_id
                            FROM animal
                            WHERE location_id NOTNULL
                          ) ";

        $filterString .= ' ' . $this->animalShouldHaveAtleastOneExistingBreedValueFilter;

        $sql = "SELECT DISTINCT 
                    a.uln as ".$this->translateColumnHeader('a_uln').",
                    a.stn as ".$this->translateColumnHeader('a_stn').",
                    mom.uln as ".$this->translateColumnHeader('m_uln').",
                    mom.stn as ".$this->translateColumnHeader('m_stn').",
                    dad.uln as ".$this->translateColumnHeader('f_uln').",
                    dad.stn as ".$this->translateColumnHeader('f_stn').",
                    gender.translated_char as ".$this->translateColumnHeader('gender').",
                    a.is_alive as ".$this->translateColumnHeader('is_alive').",
                    a.animal_order_number as ".$this->translateColumnHeader('a_animal_order_number').",
                    a.dd_mm_yyyy_date_of_birth as ".$this->translateColumnHeader('a_date_of_birth').",
                    a.dd_mm_yyyy_date_of_death as ".$this->translateColumnHeader('a_date_of_death').",
                    mom.dd_mm_yyyy_date_of_birth as ".$this->translateColumnHeader('m_date_of_birth').",
                    dad.dd_mm_yyyy_date_of_birth as ".$this->translateColumnHeader('f_date_of_birth').",
                    
                    a.breed_type_as_dutch_first_letter as ".$this->translateColumnHeader('a_dutch_breed_status').",
                    a.breed_code as ".$this->translateColumnHeader('a_breed_code').",
                    mom.breed_type_as_dutch_first_letter as ".$this->translateColumnHeader('m_dutch_breed_status').",
                    mom.breed_code as ".$this->translateColumnHeader('m_breed_code').",
                    dad.breed_type_as_dutch_first_letter as ".$this->translateColumnHeader('f_dutch_breed_status').", 
                    dad.breed_code as ".$this->translateColumnHeader('f_breed_code').",

                    a.scrapie_genotype as ".$this->translateColumnHeader('a_scrapie_genotype').",
                    mom.scrapie_genotype as ".$this->translateColumnHeader('m_scrapie_genotype').",
                    dad.scrapie_genotype as ".$this->translateColumnHeader('f_scrapie_genotype').",

                    a.n_ling as ".$this->translateColumnHeader('a_')."n_ling , -- sql header cannot have a dash
                    mom.n_ling as ".$this->translateColumnHeader('m_')."n_ling , -- sql header cannot have a dash
                    dad.n_ling as ".$this->translateColumnHeader('f_')."n_ling , -- sql header cannot have a dash
                    
                    a.formatted_predicate as ".$this->translateColumnHeader('a_predicate').",
                    mom.formatted_predicate as ".$this->translateColumnHeader('m_predicate').",
                    dad.formatted_predicate as ".$this->translateColumnHeader('f_predicate').",
                    
                    mom.muscularity as ".$this->translateColumnHeader('m_muscularity').",
                    dad.muscularity as ".$this->translateColumnHeader('f_muscularity').",
                    
                    mom.general_appearance as ".$this->translateColumnHeader('m_general_appearance').",
                    dad.general_appearance as ".$this->translateColumnHeader('f_general_appearance').",
                    
                    a.dd_mm_yyyy_exterior_measurement_date as ".$this->translateColumnHeader('a_measurement_date').",
                    a.kind as ".$this->translateColumnHeader('a_kind').",
                    a.skull as ".$this->translateColumnHeader('a_skull').",
                    a.progress as ".$this->translateColumnHeader('a_progress').",
                    a.muscularity as ".$this->translateColumnHeader('a_muscularity').",
                    a.proportion as ".$this->translateColumnHeader('a_proportion').",
                    a.exterior_type as ".$this->translateColumnHeader('a_exterior_type').",                 
                    a.leg_work as ".$this->translateColumnHeader('a_leg_work').",
                    a.fur as ".$this->translateColumnHeader('a_fur').",
                    a.general_appearance as ".$this->translateColumnHeader('a_general_appearance').",
                    a.height as ".$this->translateColumnHeader('a_height').",
                    a.breast_depth as ".$this->translateColumnHeader('a_breast_depth').",
                    a.torso_length as ".$this->translateColumnHeader('a_torso_length').",
                    a.exterior_inspector_full_name as ".$this->translateColumnHeader('a_inspector').",
                    
                    a.pedigree_register_abbreviation as ".$this->translateColumnHeader('pedigree_register').",
                    -- ADD pedigree_register subscription is_active here
                   
                a.production as ".$this->translateColumnHeader('production').",
                dad.production as ".$this->translateColumnHeader('f_production').",
                mom.production as ".$this->translateColumnHeader('m_production').",
                
                a.ubn_of_birth as ".$this->translateColumnHeader('ubn_of_birth_text_value').",

                breeder.ubn as ".$this->translateColumnHeader('ubn_of_birth').",
                breeder.owner_full_name as ".$this->translateColumnHeader('breedername').",
                breeder.city as ".$this->translateColumnHeader('breeder_city').",
                breeder.state as ".$this->translateColumnHeader('breeder_state').",
                
                holder.ubn as ".$this->translateColumnHeader('current_ubn').",
                holder.owner_full_name as ".$this->translateColumnHeader('holdername').",
                holder.city as ".$this->translateColumnHeader('holder_city').",
                holder.state as ".$this->translateColumnHeader('holder_state').",
                COALESCE(register_activity.has_active_pedigree_register, FALSE) as ".$this->translateColumnHeader('has_active_pedigree_register').",
                  
                  --BREED VALUES
                  ".$this->breedValuesSelectQueryPart."
                  
              FROM view_animal_livestock_overview_details a
                LEFT JOIN view_minimal_parent_details mom ON a.parent_mother_id = mom.animal_id
                LEFT JOIN view_minimal_parent_details dad ON a.parent_father_id = dad.animal_id
                LEFT JOIN view_location_details holder ON holder.location_id = a.location_id
                LEFT JOIN view_location_details breeder ON breeder.location_id = a.location_of_birth_id
                
                LEFT JOIN result_table_breed_grades bg ON a.animal_id = bg.animal_id
                LEFT JOIN (VALUES ".$this->getGenderLetterTranslationValues().") AS gender(english_full, translated_char) ON a.gender = gender.english_full
                LEFT JOIN (
                            SELECT
                              location_id, true as has_active_pedigree_register
                            FROM pedigree_register_registration
                            WHERE end_date ISNULL OR end_date > '".TimeUtil::getDayOfDateTime($pedigreeActiveEndDateLimit)->format('Y-m-d')."'
                            GROUP BY location_id
                          )register_activity ON register_activity.location_id = a.location_id
                ".$this->breedValuesPlusSignsQueryJoinPart."
            ".$filterString
            //.' LIMIT 100'
        ;

        ReportServiceWithBreedValuesBase::closeColumnHeaderTranslation();

        return $sql;
    }


    private function getGenderLetterTranslationValues()
    {
        $translations = [
          GenderType::NEUTER => $this->translator->trans(ReportServiceWithBreedValuesBase::NEUTER_SINGLE_CHAR),
          GenderType::FEMALE => $this->translator->trans(ReportServiceWithBreedValuesBase::FEMALE_SINGLE_CHAR),
          GenderType::MALE => $this->translator->trans(ReportServiceWithBreedValuesBase::MALE_SINGLE_CHAR),
        ];

        return SqlUtil::createSqlValuesString($translations);
    }


    /**
     * @param string $columnHeader
     * @return string
     */
    private function translateColumnHeader($columnHeader)
    {
        return ReportServiceWithBreedValuesBase::translateColumnHeader($this->translator, $columnHeader);
    }

}