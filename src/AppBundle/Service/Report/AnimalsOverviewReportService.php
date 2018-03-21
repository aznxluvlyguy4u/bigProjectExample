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
    const MAX_CURRENT_ANIMAL_AGE_IN_YEARS = 18;

    const PROCESS_TIME_LIMIT_IN_MINUTES = 180; // 3 hours

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

            $this->setLocaleFromQueryParameter($request);

            ProcessUtil::setTimeLimitInMinutes(self::PROCESS_TIME_LIMIT_IN_MINUTES);

            $this->data = $this->retrieveLiveStockDataForCsv();

            return $this->getCsvReport();

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @return array
     */
    private function retrieveLiveStockDataForCsv()
    {
        $sql = $this->breedValuesReportQueryGenerator->createAnimalsOverviewReportQuery(
            $this->concatValueAndAccuracy,
            true,
            true,
            self::MAX_CURRENT_ANIMAL_AGE_IN_YEARS
        );

        return $this->preFormatLivestockSqlResult($this->conn->query($sql)->fetchAll());
    }


    /**
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    private function getCsvReport()
    {
        $this->extension = FileType::CSV;

        $csvData = $this->unsetNestedKeys($this->getData(), LiveStockReportService::getLivestockKeysToIgnore());
        $csvData = $this->translateColumnHeaders($csvData);
        $csvData = $this->moveBreedValueColumnsToEndArray($csvData);

        return $this->generateFile($this->filename, $csvData,
            self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }
}