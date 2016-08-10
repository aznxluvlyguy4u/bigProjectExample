<?php

namespace AppBundle\Output;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use Doctrine\ORM\EntityManager;

/**
 * Class HealthOutput
 */
class HealthOutput extends Output
{
    /**
     * @param Location $location
     * @return array
     */
    public static function create(EntityManager $em, Location $location = null)
    {
        self:: setUbnAndLocationHealthValues($em, $location);

        $result = array(
                  "ubn" => self::$ubn,
                    "maedi_visna_status" => self::$maediVisnaStatus,
                    "maedi_visna_check_date" => self::$maediVisnaCheckDate,
                    "maedi_visna_end_date" => self::$maediVisnaEndDate,
                    "scrapie_status" => self::$scrapieStatus,
                    "scrapie_check_date" => self::$scrapieCheckDate,
                    "scrapie_end_date" => self::$scrapieEndDate
        );

        return $result;
    }

    /**
     * @param Company $company
     * @return array
     */
    public static function createCompanyHealth(EntityManager $em, Company $company)
    {
        $locations = $company->getLocations();
        $healthStatusses = array();

        foreach ($locations as $location) {
            /**
             * @var Location $location
             * @return array
             */

            self:: setUbnAndLocationHealthValues($em, $location);

            $healthStatusses[] = array(
                "ubn" => $location->getUbn(),
                "maedi_visna_status" => self::$maediVisnaStatus,
                "maedi_visna_check_date" => self::$maediVisnaCheckDate,
                "maedi_visna_end_date" => self::$maediVisnaEndDate,
                "scrapie_status" => self::$scrapieStatus,
                "scrapie_check_date" => self::$scrapieCheckDate,
                "scrapie_end_date" => self::$scrapieEndDate
            );
        }

        return $healthStatusses;
    }


}