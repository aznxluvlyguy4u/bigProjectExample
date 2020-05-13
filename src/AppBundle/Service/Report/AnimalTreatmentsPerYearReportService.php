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
     * @param $year
     * @param Location|null $location
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    private function getCSVData($year, ?Location $location)
    {
        $locationId = $location ? $location->getId() : null;
        $locationUBN = $location ? $location->getUbn() : null;
        $locationFilter = $location ? "AND (a.location_id = $locationId OR a.ubn_of_birth = '$locationUBN')" : "";

        $mainFilter =
            "WHERE
            date_part('year', t.start_date) = $year -- Year filter (for user and admin)
            $locationFilter
        ";

        $sql = "
            SELECT 
                a.id,
                t.description AS treatment_description,
                t.start_date AS latest_start_date,
                COUNT(t.id) AS treatment_count,
                a.gender,
                va.n_ling,
                CONCAT(a.uln_country_code, a.uln_number) AS uln,
                a.date_of_birth,
                a.breed_code,
                p.abbreviation AS pedigree_register
            FROM animal a
            INNER JOIN view_animal_livestock_overview_details va ON a.id = va.animal_id
            INNER JOIN treatment_animal ta ON a.id = ta.animal_id
            INNER JOIN treatment t ON ta.treatment_id = t.id
            INNER JOIN pedigree_register p ON a.pedigree_register_id = p.id
            ".$mainFilter."
            GROUP BY t.description, t.start_date, a.id, p.abbreviation, va.n_ling
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
        foreach ($data as $item) {

            $animalId = $item['id'];

            // Elk dier hoef alleen 1x te worden gecheckt
            if (key_exists($animalId, $result)) {
                continue;
            }

            $result[$animalId]['id'] = $animalId;
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'uln')] = $item['uln'];
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'date_of_birth')] = $item['date_of_birth'];
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'n_ling')] =
                ($item['n_ling']) ? $item['n_ling'] : '-';
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'gender')] = $item['gender'];
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'breed_code')] = $item['breed_code'];
            $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'pedigree_register')] = $item['pedigree_register'];
            foreach ($treatments as $treatment) {
                // Per dier moet welk een check worden gedaan voor alle behandelingen


                // Voeg hier altijd alle treatments toe, met de laatste behandeldatum van het dier.
                // Je moet die waarde dus opzoeken uit de $data.
                // Als ze leeg zijn, dan moet de waarde null zijn, denk ik. Of een lege string als dat niet werkt.
                // Je kunt het bijvoorbeeld zo doen.

                // Voeg hier altijd alle treatments toe, met de laatste behandeldatum van het dier.
                // Je moet die waarde dus opzoeken uit de $data.
                $filteredData = array_filter($data,
                    function (array $item) use ($animalId, $treatment) {
                        return $item['id'] === $animalId && $item['treatment_description'] === $treatment;
                    });
                // https://www.php.net/manual/en/function.array-shift
                // Haal de eerste item eruit.
                $animalTreatmentItem = array_shift($filteredData);

                // Gebruik daarna de null coalescing operator om de waarde eruit te halen
                // https://www.tutorialspoint.com/php7/php7_coalescing_operator.htm
                $latestTreatmentDate = $animalTreatmentItem['latest_start_date'] ?? null;

                $result[$animalId][ReportServiceBase::staticTranslateColumnHeader($this->translator, 'latest_treatment_date')] = $latestTreatmentDate;
            }
        }

        return $result;
    }
}
