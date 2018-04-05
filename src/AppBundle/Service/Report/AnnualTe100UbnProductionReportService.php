<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnualTe100UbnProductionReportService extends ReportServiceWithBreedValuesBase implements ReportServiceInterface
{
    const TITLE = 'annual_te100_ubn_production';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    /**
     * @inheritDoc
     */
    function getReport(Request $request)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        try {

            $year = RequestUtil::getIntegerQuery($request,QueryParameter::YEAR, null);
            if (!$year) {
                return ResultUtil::errorResult($this->translate('YEAR IS MISSING',false,true), Response::HTTP_PRECONDITION_REQUIRED);
            }

            $this->setLocaleFromQueryParameter($request);

            $this->filename = $this->translate(self::FILENAME).'_'.$year;
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery($this->getFilename(), $this->getSqlQuery(), !$this->outputReportsToCacheFolderForLocalTesting);

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }


    private function getSqlQuery()
    {
        // TODO replace placeholder query with an actual query
        return 'SELECT * FROM animal LIMIT 1';
    }
}