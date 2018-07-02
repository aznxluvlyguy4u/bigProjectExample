<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\FileType;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;

class AnnualActiveLivestockReportService extends ReportServiceBase
{
    const TITLE = 'annual_active_livestock_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    /**
     * @inheritDoc
     */
    function getReport($referenceYear)
    {
        try {
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