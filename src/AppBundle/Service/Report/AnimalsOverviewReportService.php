<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\DateUtil;
use AppBundle\Util\ProcessUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimalsOverviewReportService extends ReportServiceWithBreedValuesBase implements ReportServiceInterface
{
    const TITLE = 'animals_overview_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;
    const MAX_CURRENT_ANIMAL_AGE_IN_YEARS = 15;

    const PROCESS_TIME_LIMIT_IN_MINUTES = 10;

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
            $pedigreeActiveEndDateLimit = RequestUtil::getDateQuery($request,QueryParameter::PEDIGREE_ACTIVE_END_DATE, new \DateTime());
            $activeUbnReferenceDate = RequestUtil::getDateQuery($request,QueryParameter::REFERENCE_DATE, new \DateTime());
            $activeUbnReferenceDateString = $activeUbnReferenceDate->format('Y-m-d');

            if (TimeUtil::isDateInFuture($activeUbnReferenceDate)) {
                return ResultUtil::errorResult($this->translateErrorMessages('REFERENCE DATE CANNOT BE IN THE FUTURE'), Response::HTTP_PRECONDITION_REQUIRED);
            }

            $this->setLocaleFromQueryParameter($request);

            ProcessUtil::setTimeLimitInMinutes(self::PROCESS_TIME_LIMIT_IN_MINUTES);

            $sql = $this->breedValuesReportQueryGenerator->createAnimalsOverviewReportQuery(
                $this->concatValueAndAccuracy,
                true,
                true,
                self::MAX_CURRENT_ANIMAL_AGE_IN_YEARS,
                $activeUbnReferenceDateString,
                $pedigreeActiveEndDateLimit
            );
            $this->filename = $this->translate(self::FILENAME)
                .'__'.$this->translate('reference date').'_'.$activeUbnReferenceDateString
                .'__'.$this->translate('generated on');
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery($this->getFilename(), $sql, !$this->outputReportsToCacheFolderForLocalTesting);

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }



}