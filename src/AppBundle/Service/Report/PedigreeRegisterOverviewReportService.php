<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\QueryType;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\ExcelService;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ReportService
 * @package AppBundle\Service
 */
class PedigreeRegisterOverviewReportService extends ReportServiceBase
{
    const TITLE_PREFIX = 'Overzicht dieren van ';
    const KEYWORDS = "nsfo fokwaarden dieren overzicht";
    const DESCRIPTION = "Overzicht van dieren van stamboek inclusief benodigde metingen en fokwaarden";
    const FOLDER = '/pedigree_register_reports/';

    /**
     * PedigreeRegisterOverviewReportService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param ExcelService $excelService
     * @param Logger $logger
     * @param AWSSimpleStorageService $storageService
     */
    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService)
    {
        parent::__construct($em, $excelService, $logger, $storageService, self::FOLDER);

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

        $type = $request->query->get(QueryParameter::TYPE_QUERY);
        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY, FileType::XLS);
        $uploadToS3 = RequestUtil::getBooleanQuery($request,QueryParameter::S3_UPLOAD, true);

        return $this->generateFileByType($type, $uploadToS3, $fileType);
    }


    /**
     * @param string $type
     * @param boolean $uploadToS3
     * @param string $fileType
     * @return JsonResponse|string
     */
    public function generateFileByType($type, $uploadToS3, $fileType)
    {
        $today = TimeUtil::getTimeStampToday();
        $cfFilename = 'nsfo_cf_overzicht_'.$today;
        $ntsTsnhLaxFilename = 'nsfo_nts_tsnh_lax_overzicht_'.$today;

        $this->logger->notice('Retrieve '.$type.' data ... ');

        switch ($type) {
            case PedigreeAbbreviation::CF:
                $data = $this->cfData();
                $filename = $cfFilename;
                $title = self::TITLE_PREFIX.'stamboek CF';
                break;
            case PedigreeAbbreviation::NTS://go to case:LAX
            case PedigreeAbbreviation::TSNH://go to case:LAX
            case PedigreeAbbreviation::LAX:
                $data = $this->ntsTsnhLaxData();
                $filename = $ntsTsnhLaxFilename;
                $title = self::TITLE_PREFIX.'stamboek NTS, TSNH, LAX';
                break;
            default:
                $code = 428;
                $message = "A valid value for query parameter 'type' is missing. Valid values: CF, NTS, TSNH, LAX";
                return new JsonResponse(['code' => $code, "message" => $message], $code);
        }

        return $this->generateFile($filename, $data, $title, $fileType, $uploadToS3);
    }


    /**
     * @return array
     */
    private function cfData()
    {
        $cf = PedigreeAbbreviation::CF;

        $sql = "SELECT
                  p.abbreviation as stamboek,
                  p_dad.abbreviation as stamboek_vader,
                  p_mom.abbreviation as stamboek_moeder,
                  NULLIF(CONCAT(a.uln_country_code, a.uln_number),'') as uln,
                  NULLIF(CONCAT(a.pedigree_country_code, a.pedigree_number),'') as stamboeknummer,
                  DATE(a.date_of_birth) as geboortedatum,
                  gender.dutch as geslacht,
                  l.born_alive_count + l.stillborn_count as n_ling,
                  rastype.dutch as status,
                  a.breed_code as ras,
                  NULLIF(CONCAT(
                             COALESCE(CAST(c.production_age AS TEXT), '-'),'/',
                             COALESCE(CAST(c.litter_count AS TEXT), '-'),'/',
                             COALESCE(CAST(c.total_offspring_count AS TEXT), '-'),'/',
                             COALESCE(CAST(c.born_alive_offspring_count AS TEXT), '-'),
                             production_asterisk.mark
                         ),'-/-/-/-') as productie,
                  NULLIF(CONCAT(
                             total_born_plus_sign.mark,
                             COALESCE(CAST(ROUND(CAST(bg.total_born AS NUMERIC), 2) AS TEXT),''),'/',
                             COALESCE(CAST(ROUND(bg.total_born_accuracy*100) AS TEXT),'')
                         ),'/') as FW6_worpgr,
                  NULLIF(CONCAT(
                             tail_length_plus_sign.mark,
                             COALESCE(CAST(ROUND(CAST(bg.tail_length AS NUMERIC), 2) AS TEXT),''),'/',
                             COALESCE(CAST(ROUND(bg.tail_length_accuracy*100) AS TEXT),'')
                         ),'/') as FW10_staartlen,
                  NULLIF(CONCAT(dad.pedigree_country_code, dad.pedigree_number),'') as stamboeknummer_vader,
                  NULLIF(CONCAT(mom.pedigree_country_code, mom.pedigree_number),'') as stamboeknummer_moeder,
                  ubn_alive.ubn as huidig_ubn,
                  NULLIF(o.first_name,'') as voornaam_eigenaar,
                  NULLIF(o.last_name,'') as achternaam_eigenaar,
                  address.street_name as straat,
                  address.address_number as huisnummer,
                  address.address_number_suffix as huisnummertoevoeging,
                  address.postal_code as postcode,
                  address.city as stad,
                  address.country as land,
                  o.email_address as emailadres,
                  NULLIF(o.cellphone_number,'') as mobiel_nummer
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
                  LEFT JOIN pedigree_register p_mom ON mom.pedigree_register_id = p_mom.id
                  LEFT JOIN pedigree_register p_dad ON dad.pedigree_register_id = p_dad.id
                  LEFT JOIN location loc ON loc.id = a.location_id
                  LEFT JOIN company ON company.id = loc.company_id
                  LEFT JOIN address ON address.id = company.address_id
                  LEFT JOIN person o ON o.id = company.owner_id
                  LEFT JOIN (VALUES ".SqlUtil::genderTranslationValues().") AS gender(english, dutch) ON a.type = gender.english
                  LEFT JOIN (VALUES ".SqlUtil::breedTypeTranslationValues().") AS rastype(english, dutch) ON a.breed_type = rastype.english
                  LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk(bool_val, mark) ON c.gave_birth_as_one_year_old = production_asterisk.bool_val
                  LEFT JOIN (VALUES (true, '+'),(false, '')) AS total_born_plus_sign(is_positive, mark) ON (bg.total_born > 0) = total_born_plus_sign.is_positive
                  LEFT JOIN (VALUES (true, '+'),(false, '')) AS tail_length_plus_sign(is_positive, mark) ON (bg.tail_length > 0) = tail_length_plus_sign.is_positive
                WHERE
                  p.abbreviation = '$cf' OR p_dad.abbreviation = '$cf' OR p_mom.abbreviation = '$cf'";
        return $this->conn->query($sql)->fetchAll();
    }



    /**
     * @return array
     */
    private function ntsTsnhLaxData()
    {
        $nts = PedigreeAbbreviation::NTS;
        $tsnh = PedigreeAbbreviation::TSNH;
        $lax = PedigreeAbbreviation::LAX;

        $sql = "SELECT
                  p.abbreviation as stamboek,
                  p_dad.abbreviation as stamboek_vader,
                  p_mom.abbreviation as stamboek_moeder,
                  NULLIF(CONCAT(a.uln_country_code, a.uln_number),'') as uln,
                  NULLIF(CONCAT(a.pedigree_country_code, a.pedigree_number),'') as stamboeknummer,
                  DATE(a.date_of_birth) as geboortedatum,
                  gender.dutch as geslacht,
                  l.born_alive_count + l.stillborn_count as n_ling,
                  rastype.dutch as status,
                  a.breed_code as ras,
                  NULLIF(CONCAT(
                             COALESCE(CAST(c.production_age AS TEXT), '-'),'/',
                             COALESCE(CAST(c.litter_count AS TEXT), '-'),'/',
                             COALESCE(CAST(c.total_offspring_count AS TEXT), '-'),'/',
                             COALESCE(CAST(c.born_alive_offspring_count AS TEXT), '-'),
                             production_asterisk.mark
                         ),'-/-/-/-') as productie,
                  NULLIF(CONCAT(
                             total_born_plus_sign.mark,
                             COALESCE(CAST(ROUND(CAST(bg.total_born AS NUMERIC), 2) AS TEXT),''),'/',
                             COALESCE(CAST(ROUND(bg.total_born_accuracy*100) AS TEXT),'')
                         ),'/') as FW6_worpgr,
                  NULLIF(CONCAT(
                             growth_plus_sign.mark,
                             COALESCE(CAST(ROUND(CAST(bg.growth*1000 AS NUMERIC), 1) AS TEXT),''),'/',
                             COALESCE(CAST(ROUND(bg.growth_accuracy*100) AS TEXT),'')
                         ),'/') as FW7_groei,
                  NULLIF(CONCAT(
                             musclethickness_plus_sign.mark,
                             COALESCE(CAST(ROUND(CAST(bg.muscle_thickness AS NUMERIC), 2) AS TEXT),''),'/',
                             COALESCE(CAST(ROUND(bg.muscle_thickness_accuracy*100) AS TEXT),'')
                         ),'/') as FW8_spierd,
                  NULLIF(CONCAT(
                             fat3_length_plus_sign.mark,
                             COALESCE(CAST(ROUND(CAST(bg.fat_thickness3 AS NUMERIC), 2) AS TEXT),''),'/',
                             COALESCE(CAST(ROUND(bg.fat_thickness3accuracy*100) AS TEXT),'')
                         ),'/') as FW9_vetd,
                  DATE(c.exterior_measurement_date) as exterieurmetingdatum,
                  c.muscularity as bespiering,
                  c.general_appearance as algemeen_voorkomen,
                  c.height as schofthoogte,
                  c.torso_length as lengte,
                  c.breast_depth as borstdiepte,
                  NULLIF(CONCAT(dad.pedigree_country_code, dad.pedigree_number),'') as stamboeknummer_vader,
                  NULLIF(CONCAT(mom.pedigree_country_code, mom.pedigree_number),'') as stamboeknummer_moeder,
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
                  NULLIF(o.cellphone_number,'') as mobiel_nummer_eigenaar
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
                  LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk(bool_val, mark) ON c.gave_birth_as_one_year_old = production_asterisk.bool_val
                  LEFT JOIN (VALUES (true, '+'),(false, '')) AS total_born_plus_sign(is_positive, mark) ON (bg.total_born > 0) = total_born_plus_sign.is_positive
                  LEFT JOIN (VALUES (true, '+'),(false, '')) AS growth_plus_sign(is_positive, mark) ON (bg.growth > 0) = growth_plus_sign.is_positive
                  LEFT JOIN (VALUES (true, '+'),(false, '')) AS musclethickness_plus_sign(is_positive, mark) ON (bg.muscle_thickness > 0) = musclethickness_plus_sign.is_positive
                  LEFT JOIN (VALUES (true, '+'),(false, '')) AS fat3_length_plus_sign(is_positive, mark) ON (bg.fat_thickness3 > 0) = fat3_length_plus_sign.is_positive
                WHERE
                  (p.abbreviation = '$nts' OR p.abbreviation = '$tsnh' OR p.abbreviation = '$lax') OR
                  (p_mom.abbreviation = '$nts' OR p_mom.abbreviation = '$tsnh' OR p_mom.abbreviation = '$lax') OR
                  (p_dad.abbreviation = '$nts' OR p_dad.abbreviation = '$tsnh' OR p_dad.abbreviation = '$lax')";
        return $this->conn->query($sql)->fetchAll();
    }



}