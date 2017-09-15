<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Request;

class BreedValuesOverviewReportService extends ReportServiceBase
{
    const TITLE = 'Fokwaardenoverzicht alle huidige dieren';
    const FILENAME = 'Fokwaardenoverzicht_alle_huidige_dieren';
    const KEYWORDS = "nsfo fokwaarden dieren overzicht";
    const DESCRIPTION = "Fokwaardenoverzicht van alle dieren op huidige stallijsten met minstens 1 fokwaarde";
    const FOLDER = '/pedigree_register_reports/';
    const ACCURACY_TABLE_LABEL_SUFFIX = '_acc';

    /**
     * PedigreeRegisterOverviewReportService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param ExcelService $excelService
     * @param Logger $logger
     * @param AWSSimpleStorageService $storageService
     * @param CsvWriter $csvWriter
     * @param UserService $userService
     * @param TwigEngine $templating
     * @param GeneratorInterface $knpGenerator
     * @param string $cacheDir
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvWriter $csvWriter, UserService $userService, TwigEngine $templating, GeneratorInterface $knpGenerator, $cacheDir, $rootDir)
    {
        parent::__construct($em, $excelService, $logger, $storageService, $csvWriter, $userService, $templating,
            $knpGenerator,$cacheDir, $rootDir, self::FOLDER, self::FILENAME);

        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;

        $this->excelService
            ->setKeywords(self::KEYWORDS)
            ->setDescription(self::DESCRIPTION)
        ;
    }


    /**
     * @param Request $request
     * @param $user
     * @return JsonResponse
     */
    public function request(Request $request, $user)
    {
        if(!AdminValidator::isAdmin($user, AccessLevelType::SUPER_ADMIN)) { //validate if user is at least a SUPER_ADMIN
            return AdminValidator::getStandardErrorResponse();
        }

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, FileType::XLS);
        $uploadToS3 = RequestUtil::getBooleanQuery($request,QueryParameter::S3_UPLOAD, true);
        $concatBreedValuesAndAccuracies = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, false);
        $includeAllLiveStockAnimals = RequestUtil::getBooleanQuery($request,QueryParameter::INCLUDE_ALL_LIVESTOCK_ANIMALS, false);

        return $this->generate($fileType, $concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals, $uploadToS3);
    }


    /**
     * @param string $fileType
     * @param boolean $concatBreedValuesAndAccuracies
     * @param boolean $includeAllLiveStockAnimals
     * @param boolean $uploadToS3
     * @return JsonResponse
     */
    public function generate($fileType, $concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals, $uploadToS3)
    {
        return $this->generateFile($this->getFilenameWithoutExtension(), $this->getData($concatBreedValuesAndAccuracies, $includeAllLiveStockAnimals), self::TITLE, $fileType, $uploadToS3);
    }


    /**
     * @param bool $concatBreedValuesAndAccuracies
     * @param bool $includeAllLiveStockAnimals
     * @return array
     */
    private function getData($concatBreedValuesAndAccuracies = true, $includeAllLiveStockAnimals = false)
    {
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
                FROM breed_value_type WHERE id IN (
                  SELECT breed_value_type_id FROM breed_value_genetic_base
                  GROUP BY breed_value_type_id ORDER BY breed_value_type_id
                )";
        $existingBreedValueColumnValues = $this->conn->query($sql)->fetchAll();

        $breedValues = '';
        $breedValuesPlusSigns = '';
        $breedValuesNullFilter = '';
        $valuesPrefix = '';
        $filterPrefix = '';

        if ($concatBreedValuesAndAccuracies) {

            foreach ([$existingBreedIndexColumnValues, $existingBreedValueColumnValues] as $columnValuesSets) {

                foreach ($columnValuesSets as $columnValueSet) {
                    $breedValueLabel = $columnValueSet['nl'];
                    $resultTableValueVar = $columnValueSet['result_table_value_variable'];
                    $resultTableAccuracyVar = $columnValueSet['result_table_accuracy_variable'];

                    $breedValues = $breedValues . $valuesPrefix . "NULLIF(CONCAT(
                         ".$resultTableValueVar."_plus_sign.mark,
                         COALESCE(CAST(ROUND(CAST(bg.".$resultTableValueVar." AS NUMERIC), 2) AS TEXT),''),'/',
                         COALESCE(CAST(ROUND(bg.".$resultTableAccuracyVar."*100) AS TEXT),'')
                     ),'/') as ".$breedValueLabel;

                    $valuesPrefix = ",
                        ";

                    $breedValuesPlusSigns = $breedValuesPlusSigns . "LEFT JOIN (VALUES (true, '+'),(false, '')) AS ".$resultTableValueVar."_plus_sign(is_positive, mark) ON (bg.".$resultTableValueVar." > 0) = ".$resultTableValueVar."_plus_sign.is_positive
                    ";

                    $breedValuesNullFilter = $breedValuesNullFilter . $filterPrefix . "bg.".$resultTableValueVar." NOTNULL
                    ";

                    $filterPrefix = ' OR ';
                }
            }

        } else {
            //Do NOT concat breed value and accuracies

            foreach ([$existingBreedIndexColumnValues, $existingBreedValueColumnValues] as $columnValuesSets) {

                foreach ($columnValuesSets as $columnValueSet) {
                    $breedValueLabel = $columnValueSet['nl'];
                    $resultTableValueVar = $columnValueSet['result_table_value_variable'];
                    $resultTableAccuracyVar = $columnValueSet['result_table_accuracy_variable'];

                    $breedValues = $breedValues . $valuesPrefix
                        . " ROUND(CAST(bg.".$resultTableValueVar." AS NUMERIC), 2) as ".$breedValueLabel .",
                        ROUND(bg.".$resultTableAccuracyVar."*100) as ". $breedValueLabel. self::ACCURACY_TABLE_LABEL_SUFFIX;

                    $valuesPrefix = ",
                        ";

                    //keep  $breedValuesNullFilter blank

                    $breedValuesNullFilter = $breedValuesNullFilter . $filterPrefix . "bg.".$resultTableValueVar." NOTNULL
                    ";

                    $filterPrefix = ' OR ';
                }
            }
            
        }

        $animalsFilter = '';
        if (!$includeAllLiveStockAnimals) {
            $animalsFilter = "AND (
                        $breedValuesNullFilter
                )";
        }


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
            ".$breedValues."
            
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
                ".$breedValuesPlusSigns."
            WHERE
                a.location_id NOTNULL
                $animalsFilter";
        return $this->conn->query($sql)->fetchAll();
    }
}