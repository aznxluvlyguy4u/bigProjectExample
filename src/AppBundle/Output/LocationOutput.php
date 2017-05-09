<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 14-4-17
 * Time: 9:53
 */

namespace AppBundle\Output;


use AppBundle\Entity\Location;

class LocationOutput
{
    public static function generateInvoiceLocationArrayList($locations){
        $results = array();
        /** @var Location $location */
        foreach($locations as $location) {
            if ($location->getIsActive()) {
                $results[] = self::generateLocationInvoiceArray($location);
            }
        }
        return $results;
    }

    /**
     * @param Location $location
     * @return array
     */
    private static function generateLocationInvoiceArray($location) {
        return array(
            'id' => $location->getId(),
            'location_id' => $location->getLocationId(),
            'ubn' => $location->getUbn(),
            'location_address' => AddressOutput::createAddressOutput($location->getAddress())
        );
    }
}