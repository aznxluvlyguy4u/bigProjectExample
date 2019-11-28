<?php


namespace AppBundle\Service\Report;

use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Response;

class AnimalFeaturesPerYearOfBirthReportService extends ReportServiceWithBreedValuesBase
{
    const TITLE = 'animal_features_per_year_of_birth_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;

    /**
     * @param $yearOfBirth
     * @param Location $location
     * @param $concatValueAndAccuracy
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function getReport($concatValueAndAccuracy, $yearOfBirth, ?Location $location = null)
    {
        try {
            if (!ctype_digit($yearOfBirth) && !is_int($yearOfBirth)) {
                return ResultUtil::errorResult("Year is not an integer", Response::HTTP_BAD_REQUEST);
            }

            $yearOfBirthAsInt = intval($yearOfBirth);

            $this->concatValueAndAccuracy = $concatValueAndAccuracy;

            $sql = $this->breedValuesReportQueryGenerator->createAnimalFeaturesPerYearOfBirthReportQuery(
                $yearOfBirthAsInt,
                $location,
                $this->concatValueAndAccuracy,
                true,
                true
            );
            $this->filename = $this->getAnimalFeaturesPerYearOfBirthFileName($location);
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $sql,
                $this->breedValuesReportQueryGenerator->getAnimalFeaturesPerYearOfBirthReportBooleanColumns()
            );

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    private function getAnimalFeaturesPerYearOfBirthFileName($location): string {
        $ubn = is_null($location) ? "" : $location->getUbn() . '_';
        return ReportUtil::translateFileName($this->translator, self::FILENAME)
            . '_'. $ubn .
            ReportUtil::translateFileName($this->translator, TranslationKey::GENERATED_ON);
    }
}
