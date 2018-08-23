<?php


namespace AppBundle\Entity;
use Monolog\Logger;

/**
 * Class PedigreeRegisterRegistrationRepository
 * @package AppBundle\Entity
 */
class PedigreeRegisterRegistrationRepository extends BaseRepository
{
    /**
     * @param Company $company
     * @param Logger $logger
     * @return array
     */
    function getCompanyBreederNumbersWithPedigreeRegisterAbbreviations(Company $company, Logger $logger)
    {
        if (!$company || !$company->getId()) {
            return [];
        }

        try {
            $sql = "SELECT
                  pr.abbreviation as pedigree_register_abbreviation,
                  prr.breeder_number
                FROM pedigree_register_registration prr
                  INNER JOIN location l on prr.location_id = l.id
                  INNER JOIN company c on l.company_id = c.id
                  INNER JOIN pedigree_register pr on prr.pedigree_register_id = pr.id
                WHERE prr.is_active AND c.id = ".$company->getId();
            return $this->getConnection()->query($sql)->fetchAll();
        } catch (\Exception $exception) {
            if ($logger) {
                $logger->error($exception->getMessage());
                $logger->error($exception->getTraceAsString());
            }
        }
        return [];
    }
}