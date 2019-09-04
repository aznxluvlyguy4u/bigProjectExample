<?php


namespace AppBundle\Util;

use AppBundle\Constant\TranslationKey;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class ReportUtil
{
    /**
     * 2018-11-27 Is when a daily automated rvo leading animals sync has started for all locations.
     * This is the oldest date that can be used for data/reports relying on animal_residence information.
     *
     * @return \DateTime
     * @throws \Exception
     */
    public static function startDateForAutomatedSyncAllLocations(): \DateTime {
        return new \DateTime('2018-11-27');
    }

    /**
     * @param \DateTime $referenceDate
     * @param string $dateLabel
     * @param TranslatorInterface|null $translator
     * @throws \Exception
     */
    public static function validateDateIsNotOlderThanOldestAutomatedSync(
        \DateTime $referenceDate,
        string $dateLabel = TranslationKey::REFERENCE_DATE,
        ?TranslatorInterface $translator = null
    ) {
        $startDateAutomatedSync = self::startDateForAutomatedSyncAllLocations();
        if (TimeUtil::isDate1BeforeDate2($referenceDate, $startDateAutomatedSync)) {

            $errorMessageMiddle = TranslationKey::CANNOT_BE_OLDER_THAN;
            $errorMessage =
                ($translator ? $translator->trans($dateLabel) : $dateLabel) .
                ' (' . $referenceDate->format(SqlUtil::DATE_FORMAT) . ') ' .
                ($translator ? $translator->trans($errorMessageMiddle) : strtolower($errorMessageMiddle)) . ' ' .
                $startDateAutomatedSync->format(SqlUtil::DATE_FORMAT);

            throw new PreconditionFailedHttpException($errorMessage);
        }
    }


    /**
     * @param \DateTime $referenceDate
     * @param string $dateLabel
     * @param TranslatorInterface|null $translator
     */
    public static function validateDateIsNotInTheFuture(
        \DateTime $referenceDate,
        string $dateLabel = TranslationKey::REFERENCE_DATE,
        ?TranslatorInterface $translator = null
    ) {
        if (TimeUtil::isDateInFuture($referenceDate)) {
            throw new PreconditionFailedHttpException(ucfirst(strtolower(
                ($translator ? $translator->trans($dateLabel) : $dateLabel) . ' ' .
                ($translator ? $translator->trans(TranslationKey::CANNOT_BE_IN_THE_FUTURE) : TranslationKey::CANNOT_BE_IN_THE_FUTURE)
            )));
        }
    }
}