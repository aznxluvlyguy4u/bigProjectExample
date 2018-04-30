<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnualTe100UbnProductionReportService extends ReportServiceWithBreedValuesBase implements ReportServiceInterface
{
    const TITLE = 'annual_te100_ubn_production';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const AVERAGES_DECIMAL_COUNT = 2;
    const PERCENTAGES_DECIMAL_COUNT = 0;

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
            $pedigreeActiveEndDateLimit = RequestUtil::getDateQuery($request,QueryParameter::END_DATE, new \DateTime());

            $this->filename = $this->translate(self::FILENAME).'_'.$year;
            $this->extension = FileType::CSV;

            $this->prepareDatabaseValues();

            return $this->generateCsvFileBySqlQuery($this->getFilename(), $this->getSqlQuery($year, $pedigreeActiveEndDateLimit));

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }


    private function prepareDatabaseValues()
    {
        /*
         * The litterOrdinals are used to determine the average_previous_litter_count_during_whelp
         */
        LitterUtil::removeLitterOrdinalFromRevokedLitters($this->conn);
        LitterUtil::updateLitterOrdinals($this->conn);
    }


    /**
     * @param int $year
     * @param \DateTime $pedigreeActiveEndDateLimit
     * @return string
     */
    private function getSqlQuery($year, $pedigreeActiveEndDateLimit)
    {
        $this->activateColumnHeaderTranslation();

        $y1 = '_'.$this->translateColumnHeader('year1');
        $y2plus = '_'.$this->translateColumnHeader('year2plus');
        $registrationActivationDate = TimeUtil::getDayOfDateTime($pedigreeActiveEndDateLimit)->format('Y-m-d');
        $registrationActivationDateLabel = strtr($registrationActivationDate, ['-' => '']);
        
        return "SELECT
                  bnubn.ubn_of_birth as ".$this->translateColumnHeader('ubn').",
                  bnubn.breeder_number as ".$this->translateColumnHeader('breedernumber').",
                  vld.owner_full_name as ".$this->translateColumnHeader('breedername').",
                  vld.city as ".$this->translateColumnHeader('city').",
                  vld.state as ".$this->translateColumnHeader('state').",
                  vld.pedigree_register_abbreviations as ".$this->translateColumnHeader('pedigree_register').",
                  COALESCE(register_activity.has_active_pedigree_register, FALSE) as ".$this->translateColumnHeader('has_active_pedigree_register').'_'.$registrationActivationDateLabel.",
                  set1.litter_count as ".$this->translateColumnHeader('litter_count').$y1.",
                  set1.average_total_born_count as ".$this->translateColumnHeader('avg_total_born_count').$y1.",
                  set1.average_born_alive_count as ".$this->translateColumnHeader('avg_born_alive_count').$y1.",
                  set1.average_still_born_count as ".$this->translateColumnHeader('avg_still_born_count').$y1.",
                  set1.still_born_percentage as ".$this->translateColumnHeader('still_born_percentage').$y1.",
                  set2.litter_count as ".$this->translateColumnHeader('litter_count').$y2plus.",
                  set2.average_total_born_count as ".$this->translateColumnHeader('avg_total_born_count').$y2plus.",
                  set2.average_born_alive_count as ".$this->translateColumnHeader('avg_born_alive_count').$y2plus.",
                  set2.average_still_born_count as ".$this->translateColumnHeader('avg_still_born_count').$y2plus.",
                  set2.still_born_percentage as ".$this->translateColumnHeader('still_born_percentage').$y2plus.",
                  set2.average_age_in_days_of_mom_during_whelp as ".$this->translateColumnHeader('avg_age_in_days_of_mom_during_whelp').$y2plus.",
                  set2.average_litter_count_during_whelp as ".$this->translateColumnHeader('avg_litter_count_during_whelp').$y2plus."
                FROM (
                  SELECT
                    vlitter.breeder_number,
                    MAX(vlitter.ubn_of_birth) as ubn_of_birth, -- each location_of_birth_id is only linked to one ubn_of_birth
                    vlitter.location_of_birth_id
                  FROM view_litter_details vlitter
                    WHERE location_of_birth_id NOTNULL AND breeder_number NOTNULL AND is_completed -- ignore imported litters
                      -- Only litters inserted through the NSFO system will be included, which will all have a location_of_birth_id
                  GROUP BY breeder_number, location_of_birth_id
                )bnubn
                  INNER JOIN view_location_details vld ON vld.location_id = bnubn.location_of_birth_id
                  INNER JOIN (
                              SELECT
                                location_id, true as has_active_pedigree_register
                              FROM pedigree_register_registration
                              WHERE end_date ISNULL OR end_date > '".$registrationActivationDate."'
                              GROUP BY location_id
                            )register_activity ON register_activity.location_id = vld.location_id
                  LEFT JOIN (
                            ".$this->getLitterDataQuery($year, true)."
                            )set1 ON set1.location_of_birth_id = bnubn.location_of_birth_id AND set1.breeder_number = bnubn.breeder_number
                  LEFT JOIN (
                            ".$this->getLitterDataQuery($year, false)."
                            )set2 ON set2.location_of_birth_id = bnubn.location_of_birth_id AND set2.breeder_number = bnubn.breeder_number
                ORDER BY bnubn.ubn_of_birth, bnubn.breeder_number ";
    }


    /**
     * @param int $year
     * @param boolean $isSet1
     * @return string
     */
    private function getLitterDataQuery($year, $isSet1)
    {
        $avgDecCount = self::AVERAGES_DECIMAL_COUNT;
        $percentDecCount = self::PERCENTAGES_DECIMAL_COUNT;

        return "SELECT
                      -- Set 1 & 2
                      vld.location_of_birth_id,
                      vld.breeder_number,
                      COUNT(l.id) as litter_count,
                      ROUND(AVG(l.born_alive_count + l.stillborn_count),$avgDecCount) as average_total_born_count,
                      ROUND(AVG(l.born_alive_count),$avgDecCount) as average_born_alive_count,
                      ROUND(AVG(l.stillborn_count),$avgDecCount) as average_still_born_count,
                      ROUND(AVG( -- Null values are ignore in the average
                                100.00 * CAST(l.stillborn_count AS NUMERIC) -- The NUMERIC is to make sure that the result is not rounded to an integer
                                / NULLIF((l.born_alive_count + l.stillborn_count), 0) -- If the divisor is zero, ignore the result as a null value
                            ),$percentDecCount) as still_born_percentage".($isSet1 ? '' : ',')."
                      -- Only for Set 2
                      ".($isSet1 ? '-- ' : '')."EXTRACT(DAYS FROM AVG(l.litter_date - mom.date_of_birth)) as average_age_in_days_of_mom_during_whelp,
                      ".($isSet1 ? '-- ' : '')."ROUND(AVG(l.litter_ordinal),$avgDecCount) as average_litter_count_during_whelp
                    FROM litter l
                      INNER JOIN view_litter_details vld ON vld.litter_id = l.id
                      INNER JOIN declare_nsfo_base b ON b.id = l.id
                      INNER JOIN animal mom ON mom.id = l.animal_mother_id
                      LEFT JOIN mate m ON m.id = l.mate_id
                    WHERE mom.breed_code = 'TE100'
                          AND l.status = '".RequestStateType::COMPLETED."' -- ignore imported litters
                          AND mom.date_of_birth NOTNULL
                          AND (m.pmsg ISNULL OR m.pmsg = FALSE)
                          AND DATE_PART('YEAR', mom.date_of_birth) ".($isSet1 ? '=' : '<')." $year - 1
                          AND DATE_PART('YEAR', l.litter_date) = ". $year."
                    GROUP BY vld.location_of_birth_id, vld.breeder_number";
    }
}