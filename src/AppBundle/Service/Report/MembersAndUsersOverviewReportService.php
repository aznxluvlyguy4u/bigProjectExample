<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\Option\MembersAndUsersOverviewReportOptions;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class MembersAndUsersOverviewReportService extends ReportServiceBase
{
    const TITLE = 'nsfo_members_and_users_overview';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const AVERAGES_DECIMAL_COUNT = 2;
    const PERCENTAGES_DECIMAL_COUNT = 0;

    const LABEL_HAS_ACTIVE_PEDIGREE_REGISTER = 'heeft_actief_stamboek';
    const LABEL_ANIMAL_HEALTH_SUBSCRIPTION = 'diergezondheid';

    /**
     * @param MembersAndUsersOverviewReportOptions $options
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function getReport(MembersAndUsersOverviewReportOptions $options)
    {
        $referenceDate = $options->getReferenceDate();
        $mustHaveActiveHealthSubscription = $options->isMustHaveAnimalHealthSubscription();
        $pedigreeRegisterAbbreviation = $options->getPedigreeRegisterAbbreviation();
        $locale = $options->getLanguage();

        try {

            $this->validatePedigreeRegisterAbbreviation($pedigreeRegisterAbbreviation);

            $this->setLocale($locale);

            $referenceDateString = $referenceDate->format('Y-m-d');

            $this->filename = $this->translate(self::FILENAME).'_'.$referenceDateString;
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getSqlQuery($referenceDateString, $mustHaveActiveHealthSubscription, $pedigreeRegisterAbbreviation),
                $this->getBooleanColumns()
            );

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }


    private function validatePedigreeRegisterAbbreviation($pedigreeRegisterAbbreviation)
    {
        if (empty($pedigreeRegisterAbbreviation)) {
            return;
        }

        $sql = "SELECT
                COUNT(id)
                FROM pedigree_register WHERE lower(abbreviation) = :name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue("name", strtolower($pedigreeRegisterAbbreviation));
        $stmt->execute();
        $count = $stmt->fetch()['count'];

        if (empty($count)) {
            throw new PreconditionFailedHttpException("Invalid pedigree register abbreviation");
        }
    }


    /**
     * @return array
     */
    public function getBooleanColumns()
    {
        return [
            self::LABEL_HAS_ACTIVE_PEDIGREE_REGISTER,
            self::LABEL_ANIMAL_HEALTH_SUBSCRIPTION,
        ];
    }


    /**
     * @param string $referenceDateString
     * @param $mustHaveActiveHealthSubscription
     * @param string|null $pedigreeRegisterAbbreviation
     * @return string
     */
    private function getSqlQuery(string $referenceDateString, $mustHaveActiveHealthSubscription, string $pedigreeRegisterAbbreviation = null)
    {
        $this->deactivateColumnHeaderTranslation();

        $healthSubscriptionFilter = $mustHaveActiveHealthSubscription ? " AND c.animal_health_subscription " : " ";

        $quotedDoubleQuote = "'\"'";

        return "SELECT
  -- c.id as company_id,
  -- c.owner_id,
  locations.ubns,
  prb.breeder_numbers as fokkersnummers, --breeder_numbers
  owner.email_address as emailadressen, --email_address
  NULLIF(owner.cellphone_number,'') as mobielnummer_eigenaar,
  NULLIF(c.telephone_number,'') as telefoonnummer_bedrijf,
  -- NAW
  c.company_name as bedrijfsnaam, --company_name
  TRIM(CONCAT(ca.street_name,' ',ca.address_number,ca.address_number_suffix)) as bedrijf_adres, --company_address
  ca.postal_code as bedrijf_postcode, --company_postal_code
  ca.city as bedrijf_stad, --company_city
  country.code as bedrijf_landcode, --company_country_code
  -- country.id as company_country_id,
  CONCAT($quotedDoubleQuote,TRIM(CONCAT(owner.first_name,' ',owner.last_name)),$quotedDoubleQuote) as primaire_contactpersoon, --primary_contact_person
  secondary_users.other_active_users as andere_actieve_gebruikers, --other_active_users
  NULLIF(secondary_users.secondary_email_addresses,'') as emailadressen_secundaire_gebruikers,
  NULLIF(secondary_users.secondary_cellphone_numbers,'') as telefoonnummers_secundaire_gebruikers,
  pra.company_id NOTNULL as ".self::LABEL_HAS_ACTIVE_PEDIGREE_REGISTER.", --has_active_pedigree_register
  pra.pedigree_register_abbreviations as stamboeken, --pedigree_registers
  c.animal_health_subscription as ".self::LABEL_ANIMAL_HEALTH_SUBSCRIPTION.", --animal_health_subscription
  COALESCE(old_animal_count.animal_count_at_least_one_year_old, 0) as aantal_dieren_een_jaar_of_ouder,
  COALESCE(old_pedigree_ewe_count.pedigree_ewes_count_at_least_six_months_old, 0) as aantal_stamboek_ooien_zes_maanden_of_ouder,
  COALESCE(young_animal_count.animal_count_younger_than_one_year_old, 0) as aantal_dieren_jonger_dan_een_jaar
  ,sync_counts.*
FROM company c
       LEFT JOIN address ca ON ca.id = c.address_id
       LEFT JOIN person owner ON owner.id = c.owner_id
       LEFT JOIN country on ca.country_details_id = country.id
       LEFT JOIN (
        SELECT
          pra.company_id,
          TRIM(BOTH '{,}' FROM CAST(array_agg(pra.abbreviation ORDER BY abbreviation) AS TEXT))
            as pedigree_register_abbreviations
        FROM (
               SELECT
                 l.company_id,
                 pr.abbreviation
               FROM pedigree_register_registration prr
                      INNER JOIN pedigree_register pr ON prr.pedigree_register_id = pr.id
                      INNER JOIN location l ON l.id = prr.location_id
               WHERE prr.is_active
               GROUP BY company_id, pr.abbreviation
             )pra GROUP BY company_id
      )pra ON pra.company_id = c.id
       LEFT JOIN (
          SELECT
            prb.company_id,
            TRIM(BOTH '{,}' FROM CAST(array_agg(prb.breeder_number ORDER BY breeder_number) AS TEXT))
              as breeder_numbers
          FROM (
                 SELECT
                   l.company_id,
                   prr.breeder_number
                 FROM pedigree_register_registration prr
                        INNER JOIN location l ON l.id = prr.location_id
                 WHERE prr.is_active
                 GROUP BY company_id, prr.breeder_number
               )prb GROUP BY company_id
        )prb ON prb.company_id = c.id
       LEFT JOIN (
          SELECT
            employer_id,
            TRIM(BOTH '{,}' FROM CAST(
                array_agg(
                    TRIM(CONCAT(p.first_name,' ',p.last_name))
                    ORDER BY p.first_name, p.last_name
                  ) AS TEXT)
              ) as other_active_users,
            array_to_string(array_agg(p.email_address),',') as secondary_email_addresses,
            array_to_string(array_agg(p.cellphone_number),',') as secondary_cellphone_numbers
          FROM client
                 INNER JOIN person p on client.id = p.id
          WHERE client.employer_id NOTNULL AND p.is_active
          GROUP BY employer_id
        )secondary_users ON secondary_users.employer_id = c.id
       LEFT JOIN (
          SELECT
            company_id,
            TRIM(BOTH '{,}' FROM CAST(array_agg(ubn ORDER BY ubn) AS TEXT)) as ubns
          FROM location
          WHERE is_active
          GROUP BY company_id
        )locations ON locations.company_id = c.id
        LEFT JOIN (
       ".$this->getSyncCountsQuery($referenceDateString)."
        )sync_counts ON sync_counts.company_id = c.id

       LEFT JOIN (
          ".$this->getOlderAnimalsQuery($referenceDateString)."
       ) old_animal_count ON old_animal_count.company_id = c.id

       LEFT JOIN (
          ".$this->get6MonthsOrOlderPedigreeEwesQuery($referenceDateString)."
       ) old_pedigree_ewe_count ON old_pedigree_ewe_count.company_id = c.id

       LEFT JOIN (
          ".$this->getYoungAnimalsQuery($referenceDateString)."
       ) young_animal_count ON young_animal_count.company_id = c.id

        ".$this->getPedigreeRegisterJoin($pedigreeRegisterAbbreviation)."

WHERE
  -- OPTIONS
  c.is_active ".$healthSubscriptionFilter."
;";
    }


    private function getYoungAnimalsQuery($referenceDateString): string {
        return " SELECT
                  company_id,
                  count(*) as animal_count_younger_than_one_year_old
                FROM (
                      SELECT animal_id, l.company_id
                      FROM animal_residence r
                        INNER JOIN location l ON l.id = r.location_id
                        INNER JOIN animal a ON a.id = r.animal_id
                        INNER JOIN company c on l.company_id = c.id
                      WHERE is_pending = FALSE AND c.is_active AND DATE(start_date) <= '$referenceDateString'
                        AND (
                          end_date ISNULL OR
                          DATE(end_date) >= '$referenceDateString'
                        )
                        AND a.date_of_birth NOTNULL AND l.is_active AND
                            EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) = 0
                        --Group on animal to ignore double animal_residence entries
                      GROUP BY animal_id, l.company_id
                        ORDER BY l.company_id, animal_id
                
                )g
                GROUP BY company_id ";
    }


    private function getOlderAnimalsQuery($referenceDateString): string {
        return " SELECT
                  company_id,
                  count(*) as animal_count_at_least_one_year_old
                FROM (
                      SELECT animal_id, l.company_id
                      FROM animal_residence r
                        INNER JOIN location l ON l.id = r.location_id
                        INNER JOIN animal a ON a.id = r.animal_id
                        INNER JOIN company c on l.company_id = c.id
                      WHERE is_pending = FALSE AND c.is_active AND DATE(start_date) <= '$referenceDateString'
                        AND (
                          end_date ISNULL OR
                          DATE(end_date) >= '$referenceDateString'
                        )
                        AND a.date_of_birth NOTNULL AND
                            EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) > 0
                        --Group on animal to ignore double animal_residence entries
                      GROUP BY animal_id, l.company_id
                        ORDER BY l.company_id, animal_id
                
                )g
                GROUP BY company_id ";
    }


    private function get6MonthsOrOlderPedigreeEwesQuery($referenceDateString): string {
        $ewe = AnimalObjectType::Ewe;

        return " SELECT
                  company_id,
                  count(*) as pedigree_ewes_count_at_least_six_months_old
                FROM (
                      SELECT animal_id, l.company_id
                      FROM animal_residence r
                        INNER JOIN location l ON l.id = r.location_id
                        INNER JOIN animal a ON a.id = r.animal_id
                        INNER JOIN company c on l.company_id = c.id
                      WHERE is_pending = FALSE AND c.is_active AND DATE(start_date) <= '$referenceDateString'
                        AND (
                          end_date ISNULL OR
                          DATE(end_date) >= '$referenceDateString'
                        )
                        AND a.date_of_birth NOTNULL AND l.is_active AND
                              a.type = '$ewe' AND a.pedigree_register_id NOTNULL AND
                              (
                                EXTRACT(YEAR FROM AGE('$referenceDateString', a.date_of_birth)) * 12 +
                                EXTRACT(MONTH FROM AGE('$referenceDateString', a.date_of_birth))
                              ) >= 6 --age in months on reference_date
                        --Group on animal to ignore double animal_residence entries
                      GROUP BY animal_id, l.company_id
                        ORDER BY l.company_id, animal_id
                
                )g
                GROUP BY company_id ";
    }



    private function getSyncCountsQuery($referenceDateString): string {
        return " SELECT
            l.company_id,
            COUNT(l.id) as location_count,
            MAX(pre.pre_sync_date) as pre_sync_date,
            COALESCE(BOOL_AND(pre.is_rvo_leading), false) as all_pre_sync_is_rvo_leidend,
            MAX(post.post_sync_date) as post_sync_date,
            COALESCE(BOOL_AND(post.is_rvo_leading), false) as all_post_sync_is_rvo_leidend,
            SUM(pre.pre_sync_totaal_al_op_stallijst) as pre_sync_totaal_al_op_stallijst,
            SUM(pre.pre_sync_totaal_opgehaald_van_rvo) as pre_sync_totaal_opgehaald_van_rvo,
            SUM(post.post_sync_totaal_al_op_stallijst) as post_sync_totaal_al_op_stallijst,
            SUM(post.post_sync_totaal_opgehaald_van_rvo) as post_sync_totaal_opgehaald_van_rvo,
            SUM(pre.blocked_new_animals_count) as pre_sync_geblokkeerde_nieuwe_dieren,
            SUM(pre.removed_animals_count) as pre_sync_verwijderde_dieren,
            SUM(post.blocked_new_animals_count) as post_sync_geblokkeerde_nieuwe_dieren,
            SUM(post.removed_animals_count) as post_sync_verwijderde_dieren
          FROM location l
                 LEFT JOIN (
            SELECT
              ra.id as retrieve_animals_id_post,
              ra.location_id,
              ra.current_animals_count as post_sync_totaal_al_op_stallijst,
              ra.retrieved_animals_count as post_sync_totaal_opgehaald_van_rvo,
              ra.blocked_new_animals_count,
              ra.removed_animals_count,
              is_rvo_leading,
              DATE(ra.log_date) as post_sync_date
            FROM retrieve_animals ra
                   INNER JOIN location l on ra.location_id = l.id
                   INNER JOIN (
              SELECT
                ra.location_id,
                max(ra.id) as max_id_retrieve_animals
              FROM retrieve_animals ra
                     INNER JOIN (
                SELECT
                  ra.location_id,
                  MIN(ABS('$referenceDateString' - DATE(ra.log_date))) as min_date_diff_neg
                FROM retrieve_animals ra
                WHERE retrieved_animals_count NOTNULL AND
                    request_state = 'FINISHED' AND
                      '$referenceDateString' - DATE(ra.log_date) <= 0
                GROUP BY ra.location_id
              )g ON g.location_id = ra.location_id AND g.min_date_diff_neg = (ABS('$referenceDateString' - DATE(ra.log_date)))
              WHERE retrieved_animals_count NOTNULL AND
                  request_state = 'FINISHED' AND
                    '$referenceDateString' - DATE(ra.log_date) <= 0
              GROUP BY ra.location_id
            )g ON g.max_id_retrieve_animals = ra.id
          ) post ON post.location_id = l.id
                 LEFT JOIN (
            SELECT
              ra.id as retrieve_animals_id_pre,
              ra.location_id,
              ra.current_animals_count as pre_sync_totaal_al_op_stallijst,
              ra.retrieved_animals_count as pre_sync_totaal_opgehaald_van_rvo,
              ra.blocked_new_animals_count,
              ra.removed_animals_count,
              COALESCE(ra.is_rvo_leading, false) as is_rvo_leading,
              DATE(ra.log_date) as pre_sync_date
            FROM retrieve_animals ra
                   INNER JOIN location l on ra.location_id = l.id
                   INNER JOIN (
              SELECT
                ra.location_id,
                max(ra.id) as max_id_retrieve_animals
              FROM retrieve_animals ra
                     INNER JOIN (
                SELECT
                  ra.location_id,
                  MIN(ABS('$referenceDateString' - DATE(ra.log_date))) as min_date_diff_neg
                FROM retrieve_animals ra
                WHERE retrieved_animals_count NOTNULL AND
                    request_state = 'FINISHED' AND
                    0 <= '$referenceDateString' - DATE(ra.log_date)
                GROUP BY ra.location_id
              )g ON g.location_id = ra.location_id AND g.min_date_diff_neg = (ABS('$referenceDateString' - DATE(ra.log_date)))
              WHERE retrieved_animals_count NOTNULL AND
                  request_state = 'FINISHED' AND
                  0 <= '$referenceDateString' - DATE(ra.log_date)
              GROUP BY ra.location_id
            )g ON g.max_id_retrieve_animals = ra.id
          ) pre ON pre.location_id = l.id
          WHERE (post.retrieve_animals_id_post NOTNULL OR pre.retrieve_animals_id_pre NOTNULL)
          GROUP BY company_id ";
    }


    private function getPedigreeRegisterJoin($pedigreeRegisterAbbreviation): string {
        return !empty($pedigreeRegisterAbbreviation) ? "       INNER JOIN (
        SELECT
          pra.company_id
        FROM (
               SELECT
                 l.company_id
               FROM pedigree_register_registration prr
                      INNER JOIN pedigree_register pr ON prr.pedigree_register_id = pr.id
                      INNER JOIN location l ON l.id = prr.location_id
               WHERE prr.is_active
                 AND lower(pr.abbreviation) = '".strtolower($pedigreeRegisterAbbreviation)."'
               GROUP BY company_id, pr.abbreviation
             )pra GROUP BY company_id
      )register_filter ON register_filter.company_id = c.id " : " ";
    }
}
