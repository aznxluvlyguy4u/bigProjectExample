<?php

namespace AppBundle\FormInput;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Location;
use AppBundle\Util\Finder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

/**
 * Class LocationHealth
 * @package AppBundle\FormInput
 */
class LocationHealth
{
    /**
     * @param EntityManager $em
     * @param ArrayCollection $content
     * @param Location $location
     * @return Location
     */
    public static function update(EntityManager $em, Location $location, ArrayCollection $content)
    {
        //At this moment checkDate is used as the startDate
        $scrapieStatus = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_STATUS, $content);
        $scrapieCheckDate = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_CHECK_DATE, $content);
        if($scrapieCheckDate)

        $scrapieEndDate = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_END_DATE, $content);


        $lastScrapie = Finder::findLatestActiveScrapie($location, $em);





        $lastMaediVisna = Finder::findLatestActiveMaediVisna($location, $em);

dump('ending');die;
        return $location;
    }
}