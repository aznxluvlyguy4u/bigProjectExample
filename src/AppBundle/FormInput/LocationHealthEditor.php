<?php

namespace AppBundle\FormInput;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Util\DateUtil;
use AppBundle\Util\Finder;
use AppBundle\Util\LocationHealthUpdater;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class LocationHealthEditor
 * @package AppBundle\FormInput
 */
class LocationHealthEditor
{
    const defaultMaediVisnaStatus = MaediVisnaStatus::UNDER_OBSERVATION;
    const defaultScrapieStatus = ScrapieStatus::UNDER_OBSERVATION;

    /**
     * @param ObjectManager $em
     * @param ArrayCollection $content
     * @param Location $location
     * @return Location
     */
    public static function edit(ObjectManager $em, Location $location, ArrayCollection $content)
    {
        /* Initialize LocationHealth with blank values and no illnesses if null */
        if($location->getLocationHealth() == null) {
            $locationHealth = new LocationHealth();
            $location->setLocationHealth($locationHealth);
            $locationHealth->setLocation($location);
            $em->persist($location);
            $em->persist($locationHealth);
            $em->flush();
        }
        /** @var LocationHealth $locationHealth */
        $locationHealth = $location->getLocationHealth();

        //Get the latest locationHealth status to use as a benchmark
        $latestIllnessStatuses = Finder::findLatestActiveIllnessStatusesOfLocation($location, $em);

        
        /* Scrapie values */
        
        $lastScrapieStatus = $latestIllnessStatuses->get(JsonInputConstant::SCRAPIE_STATUS);
        $lastScrapieStatus = Utils::fillNullOrEmptyString($lastScrapieStatus, self::defaultScrapieStatus);
        $lastScrapieCheckDate = $latestIllnessStatuses->get(JsonInputConstant::SCRAPIE_CHECK_DATE);
        $lastReasonOfScrapieEdit = $latestIllnessStatuses->get(JsonInputConstant::SCRAPIE_REASON_OF_EDIT);

        if ($content->containsKey(JsonInputConstant::SCRAPIE_STATUS)) {
            $newScrapieStatus = StringUtil::replaceUnderscoresWithSpaces(
                Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_STATUS, $content));
        } else {
            $newScrapieStatus = ScrapieStatus::BLANK;
        }

        if (strtoupper($newScrapieStatus) === ScrapieStatus::BLANK) {
            $newScrapieCheckDate = DateUtil::endOfTime();
            $newScrapieEndDate = DateUtil::endOfTime();
        } else {
            $newScrapieCheckDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::SCRAPIE_CHECK_DATE, $content, true);
            $newScrapieEndDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::SCRAPIE_END_DATE, $content, true);
        }

        $arrayReasonOfScrapieEdit = trim(Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_REASON_OF_EDIT, $content));
        $newReasonOfScrapieEdit = (NullChecker::isNull($arrayReasonOfScrapieEdit) ? null : $arrayReasonOfScrapieEdit);

        if($newScrapieCheckDate == null) {
            $newScrapieCheckDate = new \DateTime('today');
            $scrapieDatesChanged = false; //ignore this check if no date is given
        } else {
            $scrapieDatesChanged = $newScrapieCheckDate != $lastScrapieCheckDate;
        }

        $scrapieStatusChanged = $newScrapieStatus != $lastScrapieStatus;
        $scrapieReasonOfEditChanged = $newReasonOfScrapieEdit != $lastReasonOfScrapieEdit;
        
        /* MaediVisna values */

        $lastMaediVisnaStatus = $latestIllnessStatuses->get(JsonInputConstant::MAEDI_VISNA_STATUS);
        $lastMaediVisnaStatus = Utils::fillNullOrEmptyString($lastMaediVisnaStatus, self::defaultMaediVisnaStatus);
        $lastMaediVisnaCheckDate = $latestIllnessStatuses->get(JsonInputConstant::MAEDI_VISNA_CHECK_DATE);
        $lastReasonOfMaediVisnaEdit = $latestIllnessStatuses->get(JsonInputConstant::MAEDI_VISNA_REASON_OF_EDIT);

        if ($content->containsKey(JsonInputConstant::MAEDI_VISNA_STATUS)) {
            $newMaediVisnaStatus = StringUtil::replaceUnderscoresWithSpaces(
                Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MAEDI_VISNA_STATUS, $content));
        } else {
            $newMaediVisnaStatus = MaediVisnaStatus::BLANK;
        }

        if (strtoupper($newMaediVisnaStatus) === MaediVisnaStatus::BLANK) {
            $newMaediVisnaCheckDate = DateUtil::endOfTime();
            $newMaediVisnaEndDate = DateUtil::endOfTime();
        } else {
            $newMaediVisnaCheckDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::MAEDI_VISNA_CHECK_DATE, $content, true);
            $newMaediVisnaEndDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::MAEDI_VISNA_END_DATE, $content, true);
        }

        $arrayReasonOfMaediVisnaEdit = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MAEDI_VISNA_REASON_OF_EDIT, $content);
        $newReasonOfMaediVisnaEdit = (NullChecker::isNull($arrayReasonOfMaediVisnaEdit) ? null : $arrayReasonOfMaediVisnaEdit);

        if($newMaediVisnaCheckDate == null) {
            $newMaediVisnaCheckDate = new \DateTime('today');
            $maediVisnaDatesChanged = false; //ignore this check if no date is given
        } else {
            $maediVisnaDatesChanged = $newMaediVisnaCheckDate != $lastMaediVisnaCheckDate;
        }

        $maediVisnaStatusChanged = $newMaediVisnaStatus != $lastMaediVisnaStatus;
        $maediVisnaReasonOfEditChanged = $newReasonOfMaediVisnaEdit != $lastReasonOfMaediVisnaEdit;
        
        /* LocationHealth Entity */

        //Only create a new Scrapie if there was any change in the values
        if($scrapieStatusChanged || $scrapieDatesChanged || $scrapieReasonOfEditChanged) {
            //First hide the obsolete scrapies
            LocationHealthUpdater::hideAllFollowingScrapies($em, $location, $newScrapieCheckDate);

            $scrapie = new Scrapie($newScrapieStatus);
            $scrapie->setCheckDate($newScrapieCheckDate);
            $scrapie->setEndDate($newScrapieEndDate);
            $scrapie->setIsManualEdit(true);
            $locationHealth->addScrapie($scrapie);
            $scrapie->setLocationHealth($locationHealth);
            $scrapie->setReasonOfEdit($newReasonOfScrapieEdit);
            $em->persist($scrapie);
        }

        //Only create a new MaediVisna if there was any change in the values
        if($maediVisnaStatusChanged || $maediVisnaDatesChanged || $maediVisnaReasonOfEditChanged) {
            //First hide the obsolete maedi visnas
            LocationHealthUpdater::hideAllFollowingMaediVisnas($em, $location, $newMaediVisnaCheckDate);

            $maediVisna = new MaediVisna($newMaediVisnaStatus, $newMaediVisnaCheckDate);
            $maediVisna->setCheckDate($newMaediVisnaCheckDate);
            $maediVisna->setEndDate($newMaediVisnaEndDate);
            $maediVisna->setIsManualEdit(true);
            $locationHealth->addMaediVisna($maediVisna);
            $maediVisna->setLocationHealth($locationHealth);
            $maediVisna->setReasonOfEdit($newReasonOfMaediVisnaEdit);
            $em->persist($maediVisna);
        }

        //Persist LocationHealth AND FLUSH
        if($maediVisnaStatusChanged) { $locationHealth->setCurrentMaediVisnaStatus($newMaediVisnaStatus); }
        if($scrapieStatusChanged) { $locationHealth->setCurrentScrapieStatus($newScrapieStatus); }

        if($maediVisnaStatusChanged || $maediVisnaDatesChanged || $scrapieStatusChanged || $scrapieDatesChanged || $scrapieReasonOfEditChanged || $maediVisnaReasonOfEditChanged) {
            $em->persist($locationHealth);
            $em->flush();
        }


        return $location;
    }
}