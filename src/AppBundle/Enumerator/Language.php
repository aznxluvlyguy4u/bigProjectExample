<?php


namespace AppBundle\Enumerator;

use AppBundle\Constant\DeclareLogMessage;
use AppBundle\Traits\EnumInfo;

/**
 * Class Language
 * @package AppBundle\Enumerator
 */
class Language
{
    use EnumInfo;

    const EN = 0;
    const NL = 1;

    const OLD_TAG = 'old tag';
    const NEW_OWNER = 'new owner';
    const PREVIOUS_OWNER = 'previous owner';
    const FEMALE = 'female';
    const MALE = 'male';

    private static $values = [
        self::NL => [
            DeclareLogMessage::ANIMAL_FLAG_REPORTED => 'Diervlag gemeld',
            DeclareLogMessage::ARRIVAL_REPORTED => 'Aanvoer gemeld',
            DeclareLogMessage::BIRTH_REPORTED => 'Geboorte gemeld',
            DeclareLogMessage::DECLARATION_DETAIL_REPORTED => "Dierdetails gemeld",
            DeclareLogMessage::DEPART_REPORTED => "Afvoer gemeld",
            DeclareLogMessage::EXPORT_REPORTED => "Export gemeld",
            DeclareLogMessage::IMPORT_REPORTED => "Import gemeld",
            DeclareLogMessage::LOSS_REPORTED => "Sterfte gemeld",
            DeclareLogMessage::MATING_REPORTED => "Dekking gemeld",
            DeclareLogMessage::WEIGHT_REPORTED => "Weging gemeld",
            DeclareLogMessage::TAG_REPLACE_REPORTED => "Omnummering gemeld",
            self::OLD_TAG => "oud oormerk",
            self::NEW_OWNER => "nieuwe houder",
            self::PREVIOUS_OWNER => "vorige houder",
            self::FEMALE => 'Ooi',
            self::MALE => 'Ram',
        ]
    ];

    /**
     * @param int $language
     * @return string
     */
    public static function getValue($language, $value)
    {
        return $language === self::EN ? $value : self::$values[$language][$value];
    }
}