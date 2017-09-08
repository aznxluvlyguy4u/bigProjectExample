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
    
    private static $values = [
        self::NL => [
            DeclareLogMessage::ARRIVAL_REPORTED => 'Aanvoer gemeld',
            DeclareLogMessage::BIRTH_REPORTED => 'Geboorte gemeld',
            DeclareLogMessage::DEPART_REPORTED => "Afvoer gemeld",
            DeclareLogMessage::EXPORT_REPORTED => "Export gemeld",
            DeclareLogMessage::IMPORT_REPORTED => "Import gemeld",
            DeclareLogMessage::LOSS_REPORTED => "Sterfte gemeld",
            DeclareLogMessage::MATING_REPORTED => "Dekking gemeld",
            DeclareLogMessage::WEIGHT_REPORTED => "Weging gemeld",
            DeclareLogMessage::TAG_REPLACE_REPORTED => "Omnummering gemeld",
            "OLD TAG" => "Oude oormerk",
            "NEW OWNER" => "Nieuw houder",
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