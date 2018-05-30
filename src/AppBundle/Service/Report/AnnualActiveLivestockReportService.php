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

        $animalResidenceJoin = SqlUtil::animalResidenceSqlJoin($referenceDateString,'vda.animal_id');
        $animalResidenceWhereCondition = SqlUtil::animalResidenceWhereCondition();

        return "SELECT
                  vda.uln as ".$this->translateColumnHeader('uln').",
                  vda.stn as ".$this->translateColumnHeader('stn').",
                  gender.translated_char as ".$this->translateColumnHeader('gender').",
                  vda.dd_mm_yyyy_date_of_birth as ".$this->translateColumnHeader('date_of_birth').",
                  vda.breed_code as ".$this->translateColumnHeader('breed_code').",
                  vda.breed_type_as_dutch_first_letter as ".$this->translateColumnHeader('breed type').",
                  vda.pedigree_register_abbreviation as ".$this->translateColumnHeader('pedigree_register')."
                FROM view_animal_livestock_overview_details vda
                  LEFT JOIN (VALUES ".$this->getGenderLetterTranslationValues().") AS gender(english_full, translated_char) ON vda.gender = gender.english_full
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