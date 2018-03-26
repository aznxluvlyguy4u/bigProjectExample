<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\ProcessUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class AnimalsOverviewReportService extends ReportServiceWithBreedValuesBase implements ReportServiceInterface
{
    const TITLE = 'animals_overview_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;
    const MAX_CURRENT_ANIMAL_AGE_IN_YEARS = 15;

    const PROCESS_TIME_LIMIT_IN_MINUTES = 3;

    /**
     * @inheritDoc
     */
    function getReport(Request $request)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        try {

            $this->concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, self::CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT);
            $pedigreeActiveEndDateLimit = RequestUtil::getDateQuery($request,QueryParameter::END_DATE, new \DateTime());

            $this->setLocaleFromQueryParameter($request);

            ProcessUtil::setTimeLimitInMinutes(self::PROCESS_TIME_LIMIT_IN_MINUTES);

            $sql = $this->breedValuesReportQueryGenerator->createAnimalsOverviewReportQuery(
                $this->concatValueAndAccuracy,
                true,
                true,
                self::MAX_CURRENT_ANIMAL_AGE_IN_YEARS,
                $pedigreeActiveEndDateLimit
            );

            $this->filename = $this->translate(self::FILENAME);
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery($this->getFilename(), $sql, !$this->outputReportsToCacheFolderForLocalTesting);

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }



}