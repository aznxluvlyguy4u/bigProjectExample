<?php

namespace AppBundle\Service\Report;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\BirthListReportOptions;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Exception\ReportHasNoDataHttpException;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class BirthListReportService extends ReportServiceBase
{
    const TITLE = 'birth_list_report';
    const TWIG_FILE = 'Report/birth_list_report.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const FILE_NAME_REPORT_TYPE = 'BIRTH_LIST';

    const MAX_MATE_AGE_IN_MONTHS = 6;

    /**
     * @param Person $person
     * @param Location $location
     * @param BirthListReportOptions $options
     * @return JsonResponse
     */
    public function getReport(Person $person, Location $location, BirthListReportOptions $options)
    {
        self::validateUser($person, $location);
        $this->setLocale($options->getLanguage());

        $this->filename = $this->trans(self::FILE_NAME_REPORT_TYPE).'_'.$location->getUbn();
        $this->folderName = self::FOLDER_NAME;

        return $this->getPdfReport($location, $options);
    }


    /**
     * @param Person $person
     * @param Location $location
     */
    public static function validateUser(Person $person, Location $location)
    {
        if (AdminValidator::isAdmin($person, AccessLevelType::ADMIN)) {
            return;
        }

        /** Client */
        if ($person instanceof Client) {
            $companyId = $location->getCompany() ? $location->getCompany()->getId() : null;

            if (!$companyId) {
                throw new PreconditionFailedHttpException('Location has no company');
            }

            foreach ($person->getCompanies() as $company) {
                if ($company->getId() === $companyId) {
                    return;
                }
            }

            $companyIdOfOwner = $person->getEmployer() ? $person->getEmployer()->getId() : null;
            if ($companyIdOfOwner === $companyId) {
                return;
            }
        }

        throw AdminValidator::standardException();
    }


    /**
     * @param Location $location
     * @param BirthListReportOptions $options
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getPdfReport(Location $location, BirthListReportOptions $options)
    {
        $testRamsToAdd = 0;
        $data = $this->getReportData($location, $options, $testRamsToAdd);

        $customPdfOptions = [
            'orientation'=>'Landscape',
            'default-header'=>false,
            'disable-smart-shrinking'=>true,
            'page-size' => 'A4',
            'margin-top'    => 6,
            'margin-right'  => 8,
            'margin-bottom' => 4,
            'margin-left'   => 8,
        ];

        return $this->getPdfReportBase(self::TWIG_FILE, $data,true, $customPdfOptions);
    }


    /**
     * @param Location $location
     * @param BirthListReportOptions $options
     * @param int $testRamsToAdd
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getReportData(Location $location, BirthListReportOptions $options, int $testRamsToAdd = 0): array
    {
        $pedigreeRegisterId = null;
        $pedigreeRegister = $this->em->getRepository(PedigreeRegister::class)
            ->findOneByAbbreviation($options->getPedigreeRegisterAbbreviation());
        if ($pedigreeRegister) {
            $pedigreeRegisterId = $pedigreeRegister->getId();
        }

        $breedCode = $options->getBreedCode();
        $locationId = $location->getId();

        $rams = $this->conn->query($this->sqlRams($locationId, $pedigreeRegisterId, $breedCode))->fetchAll();
        $ewesCount = $this->conn->query($this->sqlEwesCount($locationId, $pedigreeRegisterId, $breedCode))->fetch()['unique_ewe_count'];
        $mates = $this->conn->query($this->sqlMates($locationId, $pedigreeRegisterId, $breedCode))->fetchAll();
        $document = $this->conn->query($this->sqlDocument($locationId))->fetch();
        $date = TimeUtil::getTimeStampToday('d-m-Y');

        $rams = $this->addTestRams($rams, $testRamsToAdd);

        if (empty($mates)) {
            throw new ReportHasNoDataHttpException($this->translator);
        }

        return [
            'rams' => $rams,
            'ewes' => $ewesCount,
            'mates' => $mates,
            'document' => $document,
            'date' => $date,
            ReportLabel::IMAGES_DIRECTORY => $this->getImagesDirectory(),
        ];
    }


    /**
     * @param array $rams
     * @param int $testRamsToAdd
     * @return array
     */
    private function addTestRams(array $rams, int $testRamsToAdd = 0): array
    {
        if (empty($testRamsToAdd) || $testRamsToAdd < 0) {
            return $rams;
        }

        $ramsCount = count($rams);
        $startCount = $ramsCount + 1;
        $maxCount = $testRamsToAdd + $startCount - 1;

        for ($i = $startCount; $i <= $maxCount; $i++) {
            $rams[$i-1] = [
                'ram_id' => $i,
                'uln' => 'DU00000' . $i,
                'uln_country_code' => 'DU',
                'uln_number' => '00000' . $i,
                'mate_count' => $i,
            ];
        }
        return $rams;
    }


    /**
     * @param int|null $locationId
     * @param int|null $pedigreeRegisterId
     * @param string|null $breedCode
     * @return string
     */
    private function sqlMates(int $locationId = null,
                              int $pedigreeRegisterId = null,
                              string $breedCode = null): string
    {
        return $this->sqlMatesBase(true, null, $locationId, $pedigreeRegisterId, $breedCode);
    }


    /**
     * @param int|null $locationId
     * @param int|null $pedigreeRegisterId
     * @param string|null $breedCode
     * @return string
     */
    private function sqlEwesCount(int $locationId = null,
                                  int $pedigreeRegisterId = null,
                                  string $breedCode = null): string
    {
        return "SELECT
          COUNT(*) as unique_ewe_count
        FROM (
       ".$this->sqlMatesBase(false, 'ewe.id', $locationId, $pedigreeRegisterId, $breedCode)."
       GROUP BY ewe.id
       ) AS unique_ewes";
    }


    /**
     * @param int|null $locationId
     * @param int|null $pedigreeRegisterId
     * @param string|null $breedCode
     * @return string
     */
    private function sqlRams(int $locationId = null,
                             int $pedigreeRegisterId = null,
                             string $breedCode = null): string
    {
        $resultColumns = "ram.id as ram_id,
                  CONCAT(ram.uln_country_code, ram.uln_number) as uln,
                  ram.uln_country_code, ram.uln_number,
                  COUNT(ram.id) as mate_count";
        return $this->sqlMatesBase(false, $resultColumns, $locationId, $pedigreeRegisterId, $breedCode)
            ." GROUP BY ram.id, ram.uln_country_code, ram.uln_number
            ORDER BY COUNT(ram.id) DESC, ram.animal_order_number";
    }


    /**
     * @param bool $sortResults
     * @param string|null $resultColumns
     * @param int|null $locationId
     * @param int|null $pedigreeRegisterId
     * @param string|null $breedCode
     * @param bool $includeNestedSqlMatesBase
     * @return string
     */
    private function sqlMatesBase(bool $sortResults = true,
                                  string $resultColumns = null,
                                  int $locationId = null,
                                  int $pedigreeRegisterId = null,
                                  string $breedCode = null,
                                  bool $includeNestedSqlMatesBase = true
    ): string
    {
        $locationFilter = !is_int($locationId) ? ' ' : ' AND m.location_id = '.$locationId.' ';
        $eweFilter = !is_int($locationId) ? ' ' : ' ewe.location_id = '.$locationId.' AND ewe.is_alive AND ';
        $breedCodeFilter = empty($breedCode) ? ' ' :
            " AND (ewe.breed_code = '$breedCode') ";
        $pedigreeRegisterFilter = !is_int($pedigreeRegisterId) ? ' ' :
            " AND (ewe.pedigree_register_id = $pedigreeRegisterId) ";

        $orderBy = $sortResults ? ' ORDER BY m.start_date, ewe.animal_order_number ' : ' ';


        $defaultNullFiller = "'-'";

        $columns = empty($resultColumns) ?
        "COALESCE(NULLIF(TRIM(CONCAT(ewe.collar_color,' ',ewe.collar_number)),''),".$defaultNullFiller.") as ewe_collar,
                   CONCAT(ewe.uln_country_code, ewe.uln_number) as ewe_uln,
                   COALESCE(ewe.breed_code, ".$defaultNullFiller.") as ewe_breed_code,
                   ewe.uln_country_code as ewe_uln_country_code,
                   substr(ewe.uln_number, 0, length(ewe.uln_number) - 4) as ewe_uln_number_without_order_number,
                   ewe.animal_order_number as ewe_order_number,
                   CONCAT(ram.uln_country_code, ram.uln_number) as ram_uln,
                   ram.animal_order_number as ram_order_number,
                   (m.start_date + interval '145' day)::date as min_expected_litter_date,
                   (m.end_date + interval '145' day)::date as max_expected_litter_date,
                   m.end_date::date <> m.start_date::date as is_expected_litter_date_period,
                   (CASE WHEN (m.end_date::date = m.start_date::date)
                     THEN to_char((m.start_date + interval '145' day)::date, 'DD-MM-YYYY')
                     ELSE CONCAT(
                         to_char((m.start_date + interval '145' day)::date, 'DD-MM-YYYY'),' - ',
                         to_char((m.end_date + interval '145' day)::date, 'DD-MM-YYYY')
                       )
                     END
                     ) as formatted_expected_litter_date" : $resultColumns;

        $groupByUniqueEweJoin = $includeNestedSqlMatesBase ?
            "                  INNER JOIN (
                    ".$this->sqlMatesBase(false,
                '          ewe.id as stud_ewe_id,
          MAX(m.start_date) as max_start_date',
                $locationId, $pedigreeRegisterId, $breedCode,
                false
            )."
                    GROUP BY ewe.id
                  )last_mate ON m.start_date = last_mate.max_start_date AND m.stud_ewe_id = last_mate.stud_ewe_id"
            : ' ';

        return "SELECT
                  $columns
                FROM mate m
                  INNER JOIN declare_nsfo_base b ON b.id = m.id
                  INNER JOIN animal ewe ON ewe.id = m.stud_ewe_id
                  INNER JOIN animal ram ON ram.id = m.stud_ram_id
                  LEFT JOIN litter l on m.id = l.mate_id
                  $groupByUniqueEweJoin
                WHERE
                      ".$eweFilter." 
                      is_approved_by_third_party
                      AND b.is_overwritten_version = false                       
                      AND
                      (
                        b.request_state = '".RequestStateType::FINISHED."' OR 
                        b.request_state = '".RequestStateType::FINISHED_WITH_WARNING."' OR
                        b.request_state = '".RequestStateType::IMPORTED."'
                      ) AND
                      l.id ISNULL -- open mates
                      AND (EXTRACT(YEAR FROM AGE(start_date)) * 12 + EXTRACT(MONTH FROM AGE(start_date))) <= "
            .self::MAX_MATE_AGE_IN_MONTHS."-- max mate age based on min_mate_age
                ".$locationFilter.$breedCodeFilter.$pedigreeRegisterFilter.$orderBy;
    }


    /**
     * @param int $locationId
     * @return string
     */
    private function sqlDocument(int $locationId): string
    {
        return "SELECT
                  l.id,
                  l.ubn,
                  TRIM(CONCAT(p.first_name,' ',p.last_name)) as owner,
                  TRIM(CONCAT(a.street_name,' ',a.address_number,' ',a.address_number_suffix)) as address,
                  a.postal_code,
                  a.city
                FROM location l
                  INNER JOIN company c on l.company_id = c.id
                  INNER JOIN person p ON p.id = c.owner_id
                  INNER JOIN address a ON a.id = c.address_id
                WHERE l.id = ".$locationId;
    }
}
