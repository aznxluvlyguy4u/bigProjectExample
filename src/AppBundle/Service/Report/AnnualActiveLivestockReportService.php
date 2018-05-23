<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\DateUtil;
use AppBundle\Util\ProcessUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnualActiveLivestockReportService extends ReportServiceBase implements ReportServiceInterface
{
    const TITLE = 'annual_active_livestock_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const PROCESS_TIME_LIMIT_IN_MINUTES = 2;

    /**
     * @inheritDoc
     */
    function getReport(Request $request)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        try {

            $referenceYear = RequestUtil::getIntegerQuery($request,QueryParameter::YEAR, null);
            if (!$referenceYear) {
                $referenceYear = DateUtil::currentYear() - 1;
            }

            if (!Validator::isYear($referenceYear)) {
                return ResultUtil::errorResult('Invalid reference year', Response::HTTP_PRECONDITION_REQUIRED);
            }

            ProcessUtil::setTimeLimitInMinutes(self::PROCESS_TIME_LIMIT_IN_MINUTES);

            $this->setFileNameValues($referenceYear);

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getQuery($referenceYear),
                $this->getBooleanColumns()
            );

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }


    /**
     * @param string $referenceYear
     */
    private function setFileNameValues($referenceYear)
    {
        $this->filename = $this->translate(self::FILENAME)
            .'__'.$this->translate('reference year').'_'.$referenceYear
            .'__'.$this->translate('generated on');
        $this->extension = FileType::CSV;
    }


    /**
     * @param string $referenceYear
     * @return string
     */
    private function getQuery($referenceYear)
    {
        $year = intval($referenceYear);
        $referenceDateString = $year.'-12-31';

        $animalResidenceJoin = SqlUtil::animalResidenceSqlJoin($referenceDateString,'a.id');
        $animalResidenceWhereCondition = SqlUtil::animalResidenceWhereCondition();

        return "SELECT
                  CONCAT(uln_country_code, uln_number) as ".$this->translateColumnHeader('uln').",
                  CONCAT(pedigree_country_code, pedigree_number) as ".$this->translateColumnHeader('stn').",
                  gender.translated_char as ".$this->translateColumnHeader('gender').",
                  DATE(date_of_birth) as ".$this->translateColumnHeader('date_of_birth').",
                  breed_code as ".$this->translateColumnHeader('breed_code').",
                  p.abbreviation as ".$this->translateColumnHeader('pedigree_register')."
                FROM animal a
                  LEFT JOIN (VALUES ".$this->getGenderLetterTranslationValues().") AS gender(english_full, translated_char) ON a.gender = gender.english_full
                  LEFT JOIN pedigree_register p ON a.pedigree_register_id = p.id
                      $animalResidenceJoin
                WHERE $animalResidenceWhereCondition";
    }


    /**
     * @return array
     */
    private function getBooleanColumns()
    {
        return [];
    }
}