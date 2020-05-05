<?php


namespace AppBundle\Service\Report;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\DBAL\DBALException;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class AnimalTreatmentsPerYearOfBirthReportService extends ReportServiceBase
{
    const TITLE = 'animal_treatments_per_year_of_birth_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    /**
     * @param $isAdmin
     * @param $yearOfBirth
     * @param Location $location
     * @return JsonResponse
     */
    function getReport($yearOfBirth, ?Location $location = null, $isAdmin = false)
    {
        try {
            if (!ctype_digit($yearOfBirth) && !is_int($yearOfBirth)) {
                return ResultUtil::errorResult("Year is not an integer", Response::HTTP_BAD_REQUEST);
            }

            $yearOfBirthAsInt = intval($yearOfBirth);

            $this->filename = $this->getAnimalTreatmentsPerYearOfBirthFileName($yearOfBirth, $isAdmin);
            $this->extension = FileType::CSV;

            $csvData = $this->getCSVData($yearOfBirthAsInt, $location);

            $response = $this->generateFile($this->filename,
                $csvData,self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
            );

            return $this->generateFile($this->filename,
                $csvData,self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
            );

        } catch (Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param $year
     * @param bool $admin
     * @return string
     * @throws Exception
     */
    private function getAnimalTreatmentsPerYearOfBirthFileName($year, $admin = false): string {
        $fileName = self::FILENAME.'_USER';
        if ($admin) {
            $fileName = self::FILENAME.'_ADMIN';
        }

        return ReportUtil::translateFileName($this->translator, $fileName).'_'.$year;
    }

    /**
     * @param $yearOfBirth
     * @param Location|null $location
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    private function getCSVData($yearOfBirth, ?Location $location)
    {
        $locationId = $location ? $location->getId() : null;
        $locationUBN = $location ? $location->getUbn() : null;
        $locationFilter = $location ? "AND (a.location_id = $locationId OR a.ubn_of_birth = '$locationUBN')" : "";

        $mainFilter =
            "WHERE
            date_part('year', t.start_date) = $yearOfBirth -- Year filter (for user and admin)
            $locationFilter
        ";

        $sql = "
            SELECT 
                a.id,
                t.description AS treatment_description,
                COUNT(t.id) AS treatment_count,
                a.gender,
                a.n_ling,
                CONCAT(a.uln_country_code, a.uln_number) AS uln,
                a.date_of_birth,
                a.breed_code,
                p.abbreviation AS pedigree_register
            FROM animal a
            INNER JOIN treatment_animal ta ON a.id = ta.animal_id
            INNER JOIN treatment t ON ta.treatment_id = t.id
            INNER JOIN pedigree_register p ON a.pedigree_register_id = p.id
            ".$mainFilter."
            GROUP BY t.description, a.id, p.abbreviation
        ";

        $conn = $this->em->getConnection();
        $statement = $conn->prepare($sql);
        $statement->execute();

        $result = [];

        $data = $statement->fetchAll();

        $treatments = [];

        // get all treatment descriptions
        foreach ($data as $item) {
            $treatments[] = $item['treatment_description'];
        }

        // remove duplicates
        $treatments = array_values(array_unique($treatments));

        // loop to set result data and empty value for treatment count
        foreach ($treatments as $treatment) {
            foreach ($data as $item) {
                $result[$item['id']]['id'] = $item['id'];
                $result[$item['id']][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'uln')] = $item['uln'];
                $result[$item['id']][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'date_of_birth')] = $item['date_of_birth'];
                $result[$item['id']][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'n_ling')] =
                        ($item['n_ling']) ? $item['n_ling'] : '-';
                $result[$item['id']][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'gender')] = $item['gender'];
                $result[$item['id']][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'breed_code')] = $item['breed_code'];
                $result[$item['id']][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'pedigree_register')] = $item['pedigree_register'];
                $result[$item['id']][$treatment.' (Aantal)'] = 0;
            }
        }

        // loop to set the treatment count for the found treatments
        foreach ($data as $item) {
            $result[$item['id']][$item['treatment_description'].' (Aantal)'] = $item['treatment_count'];
        }

        return $result;
    }
}
