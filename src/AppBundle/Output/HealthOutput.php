<?php

namespace AppBundle\Output;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class HealthOutput
 */
class HealthOutput extends Output
{
    /**
     * @param Location $location
     * @return array
     */
    public static function create(ObjectManager $em, Location $location = null)
    {
        self:: setUbnAndLocationHealthValues($em, $location);

        $result = array(
                  "ubn" => self::$ubn,
                    "maedi_visna_status" => self::$maediVisnaStatus,
                    "maedi_visna_check_date" => self::$maediVisnaCheckDate,
                    "maedi_visna_end_date" => self::$maediVisnaEndDate,
                    "scrapie_status" => self::$scrapieStatus,
                    "scrapie_check_date" => self::$scrapieCheckDate,
                    "scrapie_end_date" => self::$scrapieEndDate,
                    "maedi_visna_reason_of_edit" => self::$maediVisnaReasonOfEdit,
                    "scrapie_reason_of_edit" => self::$scrapieReasonOfEdit
        );

        return $result;
    }

    /**
     * @param Company $company
     * @return array
     */
    public static function createCompanyHealth(ObjectManager $em, Company $company)
    {
        $locations = $company->getLocations();
        $healthStatusses = array();

        foreach ($locations as $location) {

            /**
             * @var Location $location
             * @return array
             */
            if($location->getIsActive()) {
                self:: setUbnAndLocationHealthValues($em, $location);

                $healthStatusses[] = array(
                    "ubn" => $location->getUbn(),
                    "maedi_visna_status" => self::$maediVisnaStatus,
                    "maedi_visna_check_date" => self::$maediVisnaCheckDate,
                    "maedi_visna_end_date" => self::$maediVisnaEndDate,
                    "scrapie_status" => self::$scrapieStatus,
                    "scrapie_check_date" => self::$scrapieCheckDate,
                    "scrapie_end_date" => self::$scrapieEndDate,
                    "maedi_visna_reason_of_edit" => self::$maediVisnaReasonOfEdit,
                    "scrapie_reason_of_edit" => self::$scrapieReasonOfEdit
                );
            }
        }

        return $healthStatusses;
    }


}