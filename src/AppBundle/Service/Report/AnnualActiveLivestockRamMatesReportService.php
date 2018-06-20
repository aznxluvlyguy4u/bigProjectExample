<?php


namespace AppBundle\Service\Report;

use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ResultUtil;

class AnnualActiveLivestockRamMatesReportService extends ReportServiceBase
{
    const TITLE = 'annual_active_livestock_ram_mates_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const PROCESS_TIME_LIMIT_IN_MINUTES = 2;

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
     * @param int $referenceYear
     * @return string
     */
    private function getQuery($referenceYear)
    {
        $year = intval($referenceYear);

        $companyLabel = $this->translateColumnHeader('company');
        $breederLabel = $this->translateColumnHeader('breeder');

        return "SELECT
                  vda.uln as ".$this->translateColumnHeader('uln').",
                  vda.stn as ".$this->translateColumnHeader('stn').",
                  gender.translated_char as ".$this->translateColumnHeader('gender').",
                  vda.dd_mm_yyyy_date_of_birth as ".$this->translateColumnHeader('date_of_birth').",
                  coalesce(mate_with_ki.count, 0) as ".$this->translateColumnHeader('mates with ki').",
                  coalesce(mate_without_ki.count, 0) as ".$this->translateColumnHeader('mates without ki').",
                  coalesce(mate_with_ki.count, 0) + coalesce(mate_without_ki.count, 0) as ".$this->translateColumnHeader('total mates count').",
                  vda.formatted_predicate as ".$this->translateColumnHeader('predicate').",
                  a.animal_order_number AS ".$this->translateColumnHeader('animal order number').",
                  a.breed_code as ".$this->translateColumnHeader('breed_code').",
                  vda.breed_type_as_dutch_first_letter as ".$this->translateColumnHeader('breed type').",
                  ra.abbreviation as ".$this->translateColumnHeader('pedigree_register').",
                  CONCAT(f.uln_country_code, f.uln_number) AS ".$this->translateColumnHeader('f_uln').",
                  CONCAT(f.pedigree_country_code, f.pedigree_number) AS ".$this->translateColumnHeader('f_stn').",
                  vdf.breed_type_as_dutch_first_letter as ".$this->translateColumnHeader('f_breed type').",
                  rf.abbreviation as ".$this->translateColumnHeader('f_pedigree_register').",
                  CONCAT(m.uln_country_code, m.uln_number) AS ".$this->translateColumnHeader('m_uln').",
                  CONCAT(m.pedigree_country_code, m.pedigree_number) AS ".$this->translateColumnHeader('m_stn').",
                  vdm.breed_type_as_dutch_first_letter as ".$this->translateColumnHeader('m_breed_type').",
                  rm.abbreviation as ".$this->translateColumnHeader('m_pedigree_register').",
                  a.ubn_of_birth as ".$this->translateColumnHeader('breeder ubn').",
                  lb.location_holder as ".$this->translateColumnHeader('breeder ubn holder').",
                  cb.company_name as ".$this->translateColumnHeader('company name').",
                  address_breeder.street_name as ".$breederLabel.'_'.$this->translateColumnHeader('street name').",
                  address_breeder.address_number as ".$breederLabel.'_'.$this->translateColumnHeader('address number').",
                  address_breeder.address_number_suffix as ".$breederLabel.'_'.$this->translateColumnHeader('address number suffix').",
                  address_breeder.postal_code as ".$breederLabel.'_'.$this->translateColumnHeader('postal code').",
                  address_breeder.city as ".$breederLabel.'_'.$this->translateColumnHeader('city').",
                  address_breeder.country as ".$breederLabel.'_'.$this->translateColumnHeader('country').",
                  address_company.street_name as ".$companyLabel.'_'.$this->translateColumnHeader('street name').",
                  address_company.address_number as ".$companyLabel.'_'.$this->translateColumnHeader('address number').",
                  address_company.address_number_suffix as ".$companyLabel.'_'.$this->translateColumnHeader('address number suffix').",
                  address_company.postal_code as ".$companyLabel.'_'.$this->translateColumnHeader('postal code').",
                  address_company.city as ".$companyLabel.'_'.$this->translateColumnHeader('city').",
                  address_company.country as ".$companyLabel.'_'.$this->translateColumnHeader('country')."
                FROM animal a
                  LEFT JOIN animal m ON a.parent_mother_id = m.id
                  LEFT JOIN animal f ON a.parent_father_id = f.id
                  LEFT JOIN location lb ON lb.id = a.location_of_birth_id
                  LEFT JOIN company cb ON cb.id = lb.company_id
                  LEFT JOIN address address_breeder ON address_breeder.id = lb.address_id
                  LEFT JOIN address address_company ON address_company.id = cb.address_id
                  LEFT JOIN pedigree_register rm ON rm.id = m.pedigree_register_id
                  LEFT JOIN pedigree_register rf ON rf.id = f.pedigree_register_id
                  LEFT JOIN pedigree_register ra ON ra.id = a.pedigree_register_id
                  LEFT JOIN view_animal_livestock_overview_details vda ON vda.animal_id = a.id
                  LEFT JOIN view_animal_livestock_overview_details vdf ON vdf.animal_id = f.id
                  LEFT JOIN view_animal_livestock_overview_details vdm ON vdm.animal_id = m.id
                  LEFT JOIN (VALUES ".$this->getGenderLetterTranslationValues().") AS gender(english_full, translated_char) ON a.gender = gender.english_full
                LEFT JOIN (
                    SELECT stud_ram_id, ki, COUNT(*) as count FROM mate m
                      INNER JOIN declare_nsfo_base b ON b.id = m.id
                    WHERE b.is_overwritten_version = FALSE AND request_state= '".RequestStateType::FINISHED."'
                      AND (DATE_PART('year', start_date) = $year OR DATE_PART('year', end_date) = $year)
                      AND ki = FALSE
                    GROUP BY stud_ram_id, ki
                    )mate_without_ki ON mate_without_ki.stud_ram_id = a.id
                LEFT JOIN (
                    SELECT stud_ram_id, ki, COUNT(*) as count FROM mate m
                      INNER JOIN declare_nsfo_base b ON b.id = m.id
                    WHERE b.is_overwritten_version = FALSE AND request_state= '".RequestStateType::FINISHED."'
                      AND (DATE_PART('year', start_date) = $year OR DATE_PART('year', end_date) = $year)
                      AND ki = TRUE
                    GROUP BY stud_ram_id, ki
                    )mate_with_ki ON mate_with_ki.stud_ram_id = a.id
                WHERE a.id IN (
                  SELECT m.stud_ram_id FROM mate m
                  INNER JOIN declare_nsfo_base b ON b.id = m.id
                  WHERE b.is_overwritten_version = FALSE AND request_state= '".RequestStateType::FINISHED."'
                        AND (DATE_PART('year', start_date) = $year OR DATE_PART('year', end_date) = $year)
                )
                ORDER BY coalesce(mate_with_ki.count, 0) + coalesce(mate_without_ki.count, 0) DESC";
    }


    /**
     * @return array
     */
    private function getBooleanColumns()
    {
        return [];
    }
}