<?php


namespace AppBundle\Util;

use AppBundle\Constant\TranslationKey;
use AppBundle\Enumerator\FileType;
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

            $errorMessage =
                self::trans($dateLabel, $translator) .
                ' (' . $referenceDate->format(DateUtil::DATE_USER_DISPLAY_FORMAT) . ') ' .
                self::trans(TranslationKey::CANNOT_BE_OLDER_THAN, $translator) . ' ' .
                $startDateAutomatedSync->format(DateUtil::DATE_USER_DISPLAY_FORMAT);

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
                ucfirst(self::trans($dateLabel, $translator)) . ' ' .
                self::trans( TranslationKey::CANNOT_BE_IN_THE_FUTURE, $translator)
            )));
        }
    }


    public static function validateFileType(
        $fileType,
        array $allowedFileTypes = [FileType::CSV, FileType::PDF],
        ?TranslatorInterface $translator = null
    ) {
        if (empty($fileType)) {
            throw new PreconditionFailedHttpException(
                ucfirst(self::trans(TranslationKey::FILE_TYPE, $translator)) . ' ' .
                self::trans(TranslationKey::IS_MISSING, $translator)
            );
        }

        if (!in_array($fileType, $allowedFileTypes)) {
            $errorMessage =
                ucfirst(self::trans(TranslationKey::INVALID_INPUT, $translator)) . ' ' .
                self::trans(TranslationKey::FILE_TYPE, $translator) . ': '.$fileType. '. ' .
                ucfirst(self::trans(TranslationKey::ALLOWED_VALUES, $translator)) . ': ' .
                implode(',',$allowedFileTypes)
            ;
            throw new PreconditionFailedHttpException($errorMessage);
        }
    }

    private static function trans($translationKey, ?TranslatorInterface $translator = null) {
        return $translator ? $translator->trans($translationKey) : $translationKey;
    }

    public static function translateFileName(TranslatorInterface $translator, $translationKey): string {
        return self::translateLowerCaseAndSpacesReplacedWithUnderscore($translator, $translationKey);
    }

    public static function translateColumnHeader(TranslatorInterface $translator, $translationKey): string {
        return self::translateLowerCaseAndSpacesReplacedWithUnderscore($translator, $translationKey);
    }

    private static function translateLowerCaseAndSpacesReplacedWithUnderscore(TranslatorInterface $translator, $translationKey): string {
        $translatedValue = strtolower($translator->trans(strtoupper($translationKey)));
        return strtr($translatedValue, [
            ' ' => '_'
        ]);
    }
}