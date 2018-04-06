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

            return $this->generateCsvFileBySqlQuery($this->getFilename(), $this->getSqlQuery($year, $pedigreeActiveEndDateLimit), !$this->outputReportsToCacheFolderForLocalTesting);

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

        return "SELECT
                  vld.ubn as ".$this->translateColumnHeader('ubn').",
                  vld.breeder_numbers as ".$this->translateColumnHeader('breedernumber').",
                  vld.owner_full_name as ".$this->translateColumnHeader('breedername').",
                  vld.city as ".$this->translateColumnHeader('city').",
                  vld.state as ".$this->translateColumnHeader('state').",
                  vld.pedigree_register_abbreviations as ".$this->translateColumnHeader('pedigree_register').",
                  COALESCE(register_activity.has_active_pedigree_register, FALSE) as ".$this->translateColumnHeader('has_active_pedigree_register').",
                  set1.litter_count as ".$this->translateColumnHeader('litter_count_year_min_1').",
                  set1.average_total_born_count as ".$this->translateColumnHeader('average_total_born_count_year_min_1').",
                  set1.average_born_alive_count as ".$this->translateColumnHeader('average_born_alive_count_year_min_1').",
                  set1.average_still_born_count as ".$this->translateColumnHeader('average_still_born_count_year_min_1').",
                  set1.still_born_percentage as ".$this->translateColumnHeader('still_born_percentage_year_min_1').",
                  set2.litter_count as ".$this->translateColumnHeader('litter_count_year_min_2_or_more').",
                  set2.average_total_born_count as ".$this->translateColumnHeader('average_total_born_count_year_min_2_or_more').",
                  set2.average_born_alive_count as ".$this->translateColumnHeader('average_born_alive_count_year_min_2_or_more').",
                  set2.average_still_born_count as ".$this->translateColumnHeader('average_still_born_count_year_min_2_or_more').",
                  set2.still_born_percentage as ".$this->translateColumnHeader('still_born_percentage_year_min_2_or_more').",
                  set2.average_age_in_days_of_mom_during_whelp as ".$this->translateColumnHeader('average_age_in_days_of_mom_during_whelp_year_min_2_or_more').",
                  set2.average_previous_litter_count_during_whelp as ".$this->translateColumnHeader('average_previous_litter_count_during_whelp_year_min_2_or_more')."
                FROM view_location_details vld
                  INNER JOIN (
                              SELECT
                                location_id, true as has_active_pedigree_register
                              FROM pedigree_register_registration
                              WHERE end_date ISNULL OR end_date > '".TimeUtil::getDayOfDateTime($pedigreeActiveEndDateLimit)->format('Y-m-d')."'
                              GROUP BY location_id
                            )register_activity ON register_activity.location_id = vld.location_id
                  LEFT JOIN (
                            ".$this->getLitterDataQuery($year, true)."
                    )set1 ON set1.ubn = vld.ubn
                  LEFT JOIN (
                            ".$this->getLitterDataQuery($year, false)."
                            )set2 ON set2.ubn = vld.ubn";
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
                      COALESCE(b.ubn, mom_ubn.ubn) as ubn,
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
                      ".($isSet1 ? '-- ' : '')."ROUND(AVG(l.litter_ordinal - 1),$avgDecCount) as average_previous_litter_count_during_whelp
                    FROM litter l
                      INNER JOIN declare_nsfo_base b ON b.id = l.id
                      INNER JOIN animal mom ON mom.id = l.animal_mother_id
                      LEFT JOIN (
                                  -- Get the current ubn or unique historic ubn of the mom
                                  SELECT
                                    COALESCE(current_location.ubn, historic_location.ubn) as ubn,
                                    unique_historic_l.animal_id
                                  FROM location historic_location
                                    INNER JOIN (
                                                 SELECT
                                                   animal_id,
                                                   max(location_id) as location_id
                                                 FROM (
                                                        SELECT
                                                          animal_id, location_id
                                                        FROM animal_residence r
                                                        GROUP BY animal_id, location_id HAVING COUNT(*) = 1
                                                      )r
                                                 GROUP BY animal_id
                                               )unique_historic_l ON unique_historic_l.location_id = historic_location.id
                                    INNER JOIN animal a ON a.id = unique_historic_l.animal_id
                                    INNER JOIN location current_location ON a.location_id = current_location.id
                                )mom_ubn ON mom_ubn.animal_id = mom.id
                      LEFT JOIN mate m ON m.id = l.mate_id
                    WHERE mom.breed_code = 'TE100'
                          AND (l.status = '".RequestStateType::COMPLETED."' OR l.status = '".RequestStateType::IMPORTED."')
                          AND l.litter_date NOTNULL
                          AND (m.pmsg ISNULL OR m.pmsg = FALSE)
                          AND DATE_PART('YEAR', l.litter_date) ".($isSet1 ? '=' : '<')." $year - 1
                    GROUP BY COALESCE(b.ubn, mom_ubn.ubn)";
    }
}