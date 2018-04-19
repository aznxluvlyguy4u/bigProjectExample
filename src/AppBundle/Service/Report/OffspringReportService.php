<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\ProcessUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class OffspringReportService extends ReportServiceWithBreedValuesBase implements ReportServiceInterface
{
    const TITLE = 'offspring_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT = false;

    const ADMIN_PROCESS_TIME_LIMIT_IN_MINUTES = 3;

    /**
     * @inheritDoc
     */
    function getReport(Request $request)
    {
        if(AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            ProcessUtil::setTimeLimitInMinutes(self::ADMIN_PROCESS_TIME_LIMIT_IN_MINUTES);
        } else {
            // TODO Validate if animals belong to client
        }

        try {

            $this->concatValueAndAccuracy = RequestUtil::getBooleanQuery($request,QueryParameter::CONCAT_VALUE_AND_ACCURACY, self::CONCAT_BREED_VALUE_AND_ACCURACY_BY_DEFAULT);

            $this->setLocaleFromQueryParameter($request);

            $sql = $this->breedValuesReportQueryGenerator->createOffspringReportQuery(
                $this->concatValueAndAccuracy,
                true,
                true,
                ''
            );
            $this->filename = $this->translate(self::FILENAME)
                // TODO add parent/parent count to filename?
                .'__';
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery($this->getFilename(), $sql, !$this->outputReportsToCacheFolderForLocalTesting);

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

}