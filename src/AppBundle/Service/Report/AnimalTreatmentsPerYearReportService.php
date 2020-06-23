<?php


namespace AppBundle\Service\Report;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use DateTime;
use Doctrine\DBAL\DBALException;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;

class AnimalTreatmentsPerYearReportService extends ReportServiceBase
{
    const TITLE = 'animal_treatments_per_year_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    /**
     * @param $isAdmin
     * @param $year
     * @param Location $location
     * @return JsonResponse
     */
    function getReport($year, ?Location $location = null, $isAdmin = false)
    {
        try {
            if (!ctype_digit($year) && !is_int($year)) {
                return ResultUtil::errorResult("Year is not an integer", Response::HTTP_BAD_REQUEST);
            }

            $yearAsInt = intval($year);

            $this->filename = $this->getAnimalTreatmentsPerYearFileName($location, $year, $isAdmin);
            $this->extension = FileType::CSV;

            $csvData = $this->getCSVData($yearAsInt, $location);

            return $this->generateFile($this->filename,
                $csvData,self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
            );

        } catch (Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param Location|null $location
     * @param $year
     * @param bool $admin
     * @return string
     */
    private function getAnimalTreatmentsPerYearFileName(?Location $location, $year, $admin = false): string {
        $fileName = ReportUtil::translateFileName($this->translator, self::FILENAME);

        if ($admin) {
            $fileName .= '_admin';
        } else {
            $locationUBN = $location ? '_'.$location->getUbn() : '';
            $fileName .= $locationUBN;
        }

        return $fileName.'_'.$year;
    }

    /**
     * @param int $year
     * @param Location|null $location
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    private function getCSVData(int $year, ?Location $location)
    {
        if ($location && $location->getUbn()) {
            return $this->getDataForUserReport($year, $location->getUbn());
        }
        return $this->getDataForAdminReport($year);
    }


    private function getDataForAdminReport(int $year)
    {
        $mainFilter =
            "WHERE
            date_part('year', t.start_date) = $year -- Year filter (for user and admin)
        ";

        $sql = "
            SELECT 
                t.description AS treatment_description,
                MAX(t.start_date) AS latest_start_date,
                COUNT(t.id) AS treatment_count,
                a.id,
                a.gender,
                CONCAT(a.uln_country_code, a.uln_number) AS uln,
                a.date_of_birth,
                a.breed_code,
                CONCAT(a.pedigree_country_code, a.pedigree_number) AS animal_stn,
                CONCAT(father.pedigree_country_code, father.pedigree_number) AS stn_father,
                CONCAT(mother.pedigree_country_code, mother.pedigree_number) AS stn_mother,
                p.abbreviation AS pedigree_register,
                va.n_ling                
            FROM animal a
            INNER JOIN view_animal_livestock_overview_details va ON a.id = va.animal_id
            INNER JOIN treatment_animal ta ON a.id = ta.animal_id
            INNER JOIN treatment t ON ta.treatment_id = t.id
            INNER JOIN pedigree_register p ON a.pedigree_register_id = p.id
            INNER JOIN animal father ON father.id = a.parent_father_id
            INNER JOIN animal mother ON mother.id = a.parent_mother_id
            ".$mainFilter."
            GROUP BY 
            t.description, 
            t.start_date, 
            a.id, 
            p.abbreviation, 
            va.n_ling,         
            father.pedigree_country_code,
            father.pedigree_number,
            mother.pedigree_country_code,
            mother.pedigree_number
        ";

        $conn = $this->em->getConnection();
        $statement = $conn->prepare($sql);
        $statement->execute();

        $result = [];

        $data = $statement->fetchAll();

        $treatments = [];

        // get all unique treatment descriptions
        foreach ($data as $item) {
            $treatments[$item['id']][] = [
                'description' => $item['treatment_description'],
                'latest_start_date'  => $item['latest_start_date']
            ];
        }

        // loop to set the result data
        foreach ($data as $item) {
            $animalId = $item['id'];
            $date_of_birth = '-';

            if (!empty($item['date_of_birth'])) {
                $date_of_birth = new DateTime($item['date_of_birth']);
                $date_of_birth = $date_of_birth->format('d-m-Y');
            }

            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'uln')] = $item['uln'];
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'stn')] = $item['animal_stn'];
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'date_of_birth')] = $date_of_birth;
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'n_ling')] = ($item['n_ling']) ? $item['n_ling'] : '-';
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'gender')] =
                $this->translate($item['gender'], false, true);
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'breed_code')] = $item['breed_code'];
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'pedigree_register')] = $item['pedigree_register'];
            $result[$animalId][$this->translate('STN_FATHER', false)] = $item['stn_father'];
            $result[$animalId][$this->translate('STN_MOTHER', false)] = $item['stn_mother'];

            foreach ($treatments as $treatment) {
                foreach ($treatment as $subTreatment) {
                    $result[$animalId][$subTreatment['description']. ' ('.$this->translate('MOST_RECENT_TREATMENT_DATE', false).')'] = '';
                }
            }
        }

        foreach ($treatments as $key => $treatment) {
            foreach ($treatment as $item) {
                $latest_start_date = '';
                if (!empty($item['latest_start_date'])) {
                    $latest_start_date = date_create($item['latest_start_date'])->format('d-m-Y');
                }

                $result[$key][$item['description']. ' ('.$this->translate('MOST_RECENT_TREATMENT_DATE', false).')'] = $latest_start_date;
            }
        }

        return $result;
    }


    /**
     * @param  int  $treatmentYear
     * @param  string  $ubn
     * @return array
     * @throws DBALException
     */
    private function getDataForUserReport(int $treatmentYear, string $ubn)
    {
        $genderTranslationValues = SqlUtil::genderTranslationValues();
        $revoked = RequestStateType::REVOKED;

        $sql = "SELECT
                    medication_details.start_datum,
                    medication_details.eind_datum,
                    medication_details.omschrijving,
                    medication_details.behandelduur,
                    medication_details.middel,
                    -- animal details
                    CONCAT(a.uln_country_code,a.uln_number) as uln,
                    NULLIF(CONCAT(a.pedigree_country_code,a.pedigree_number),'') as stn,
                    a.collar_color as halsband_kleur,
                    a.collar_number as halsband_nummer,
                    a.animal_order_number as werknummer,
                    DATE(a.date_of_birth) as geboortedatum,
                    NULLIF(COALESCE(NULLIF(trim(trailing '-ling' from ac.n_ling),''), CAST(a.n_ling AS TEXT)),'') as n_ling,
                    gender.dutch as geslacht,
                    a.breed_code as rascode,
                    pr.abbreviation as stamboek,
                    NULLIF(CONCAT(dad.pedigree_country_code,dad.pedigree_number),'') as stn_vader,
                    NULLIF(CONCAT(mom.pedigree_country_code,mom.pedigree_number),'') as stn_moeder
                FROM animal a
                    INNER JOIN (
                        SELECT
                            ta.animal_id,
                            DATE(t.start_date) as start_datum,
                            DATE(t.end_date) as eind_datum,
                            t.description as omschrijving,
                            CASE WHEN tm.treatment_duration ISNULL THEN
                                null
                            WHEN tm.treatment_duration = 1 THEN
                                'eenmalig'
                            ELSE
                                CONCAT(tm.treatment_duration,' dagen')
                            END as behandelduur,
                            tm.name as middel
                        FROM treatment t
                            INNER JOIN treatment_animal ta on t.id = ta.treatment_id
                            -- This will only include treatments with at least one medication
                            INNER JOIN medication_selection s on s.treatment_id = t.id
                            INNER JOIN treatment_medication tm on s.treatment_medication_id = tm.id
                        WHERE t.is_active AND status <> '$revoked'
                            AND EXISTS (SELECT * FROM location l WHERE l.ubn = '$ubn' AND t.location_id = l.id)
                            AND (
                                date_part('YEAR', t.start_date) = $treatmentYear
                            )
                
                        UNION
                
                        SELECT
                            ta.animal_id,
                            DATE(t.start_date) as start_datum,
                            DATE(t.end_date) as eind_datum,
                            t.description as omschrijving,
                            null as behandelduur,
                            'geen' as middel
                        FROM treatment t
                            INNER JOIN treatment_animal ta on t.id = ta.treatment_id
                        WHERE t.is_active AND status <> '$revoked'
                            -- this will only include treatments without any medications
                            AND NOT EXISTS (SELECT * FROM medication_selection s WHERE s.treatment_id = t.id)
                            AND EXISTS (SELECT * FROM location l WHERE l.ubn = '$ubn' AND t.location_id = l.id)
                            AND (
                                date_part('YEAR', t.start_date) = $treatmentYear
                            )
                    )medication_details ON medication_details.animal_id = a.id
                    -- animal details
                    INNER JOIN animal_cache ac ON ac.animal_id = a.id
                    INNER JOIN (VALUES $genderTranslationValues) AS gender(english, dutch) ON a.type = gender.english
                    LEFT JOIN view_pedigree_register_abbreviation pr ON pr.pedigree_register_id = a.pedigree_register_id
                    LEFT JOIN animal mom ON mom.id = a.parent_mother_id
                    LEFT JOIN animal dad ON dad.id = a.parent_father_id
                ORDER BY start_datum, eind_datum, a.animal_order_number";

        $conn = $this->em->getConnection();
        $statement = $conn->prepare($sql);
        $statement->execute();

        return $statement->fetchAll();
    }
}
