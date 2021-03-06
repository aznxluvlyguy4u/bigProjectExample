<?php

namespace AppBundle\Util;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\GenderType;

class StringUtil
{
    const ULN_LENGTH = 12;
    const ANIMAL_ORDER_NUMBER_LENGTH = 5;
    const BREEDER_NUMBER_LENGTH = 5;
    const ENTITY_NAMESPACE = "AppBundle\\Entity\\";

    /**
     * @param $fullString
     * @param $beginning
     * @param $ending
     * @return string
     */
    public static function extractSandwichedSubString($fullString, $beginning, $ending)
    {
        $fromBeginningToEnd = strstr($fullString, $beginning);
        $fromEndingToEnd = strstr($fullString, $ending);
        return strtr($fromBeginningToEnd, [$fromEndingToEnd => '', $beginning => '']);
    }


    /**
     * Just remove the last 5 numbers of the uln
     *
     * @param string $ulnString
     * @param string $animalOrderNumberString
     * @return string
     */
    public static function getUlnWithoutOrderNumber($ulnString, $animalOrderNumberString)
    {
        $startChar = 0;
        $length = strlen($ulnString)-strlen($animalOrderNumberString);
        return mb_substr($ulnString, $startChar, $length);
    }


    /**
     * @param string $pedigreeNumber
     * @return string
     */
    public static function getBreederNumberFromPedigreeNumber($pedigreeNumber)
    {
        return self::padBreederNumberWithZeroes(substr($pedigreeNumber, 0, 5));
    }


    /**
     * @param string $postalCodeWithoutSpace
     * @param string $nullFiller
     * @return null|string
     */
    public static function addSpaceInDutchPostalCode($postalCodeWithoutSpace, $nullFiller = null)
    {
        if($postalCodeWithoutSpace != null && $postalCodeWithoutSpace != '' && $postalCodeWithoutSpace != ' ') {
            return substr($postalCodeWithoutSpace, 0 ,4).' '.substr($postalCodeWithoutSpace, 4);
        }
        return $nullFiller;
    }


    public static function trimAndReturnNullIfEmpty(?string $input): ?string
    {
        $trimmedInput = trim($input);
        return empty($trimmedInput) ? null : $trimmedInput;
    }


    /**
     * @param string $stnOrigin
     * @return string
     */
    public static function getBreederNumberFromStnOrigin($stnOrigin)
    {
        $prefix = 'NL ';

        $containsDash = is_int(strpos($stnOrigin, '-'));
        $isDutch = substr($stnOrigin, 0, 3) == $prefix;
        if(!$containsDash || !$isDutch) { return null; }

        $breederNumber = explode("-",ltrim($stnOrigin, $prefix))[0];

        return self::padBreederNumberWithZeroes($breederNumber);
    }


    /**
     * @param string $string
     * @return string
     */
    public static function getLast5CharactersFromString($string)
    {
        return self::getLastChars($string, 5);
    }


    /**
     * @param string $string
     * @param int $charCount
     * @return bool|null|string
     */
    public static function getLastChars($string, $charCount = 1)
    {
        if ($string == null) { return null; }
        return strlen($string) < $charCount ? $string : substr($string, strlen($string)-$charCount, strlen($string));
    }


    /**
     * In case of a duplicate uln, the convention is to bump the ulnNumber
     * by converting the first char of the animalOrderPart in the ulnNumber to 9.
     *
     * If format is invalid, null is returned
     *
     * @param string $ulnNumber
     * @return string
     */
    public static function bumpUlnNumber($ulnNumber)
    {
        if(!Validator::verifyUlnNumberFormat($ulnNumber)) { return null; }

        $newUlnNumber = substr($ulnNumber, 0, strlen($ulnNumber)); //copy the string before editing!
        $newUlnNumber[7] = '9';
        return $newUlnNumber;
    }


    /**
     * @param string $pedigreeNumber
     * @return null|string
     */
    public static function bumpPedigreeNumber($pedigreeNumber)
    {
        if (!Validator::verifyPedigreeNumberFormat($pedigreeNumber)) { return null; }

        $positionOfFirstAnimalOrderChar = 6;

        $firstAnimalOrderChar = substr($pedigreeNumber, $positionOfFirstAnimalOrderChar, 1);

        if ($firstAnimalOrderChar === 'Z') {
            return null;
        }

        $newFirstAnimalOrderNumber = $firstAnimalOrderChar === strval(intval($firstAnimalOrderChar)) ? 'A' : ++$firstAnimalOrderChar;
        $pedigreeNumber[$positionOfFirstAnimalOrderChar] = $newFirstAnimalOrderNumber;

        return $pedigreeNumber;
    }


    /**
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    public static function getFullName($firstName, $lastName)
    {
        $isFirstNameBlank = $firstName == null || $firstName == '';
        $isLastNameBlank = $lastName == null || $lastName == '';

        if(!$isFirstNameBlank && !$isLastNameBlank) {
            return $firstName.' '.$lastName;

        } elseif ($isFirstNameBlank && !$isLastNameBlank) {
            return $lastName;

        } elseif (!$isFirstNameBlank && $isLastNameBlank) {
            return $firstName;

        } else {
            //both first and last name are blank
            return '';
        }
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param int $maxLength
     * @return string
     */
    public static function getTrimmedFullNameWithAddedEllipsis($firstName, $lastName, $maxLength)
    {
        return self::trimStringWithAddedEllipsis(
               self::getFullName($firstName, $lastName), $maxLength
        );
    }

    /**
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    public static function trimStringWithAddedEllipsis($string, $maxLength)
    {
        if($string == null) { return null; }

        if(strlen($string) > $maxLength) {
            return substr($string, 0, $maxLength).'...';
        } else {
            return $string;
        }
    }


    /**
     * @param string $string
     * @param int $removeCharCount
     * @return string
     */
    public static function removeStringEnd($string, $removeCharCount)
    {
        return substr($string, 0, strlen($string) - $removeCharCount);
    }


    /**
     * @param string $string
     * @param string $suffix
     * @return mixed
     */
    public static function removeSuffix($string, $suffix)
    {
        return preg_replace('/'.$suffix.'$/s', '', $string);
    }


    /**
     * @param string $gender
     * @return string
     */
    public static function getGenderFullyWritten($gender)
    {
        if($gender == GenderType::M || $gender == GenderType::MALE) {
            return GenderType::MALE;
        } elseif($gender == GenderType::V || $gender == GenderType::FEMALE) {
            return GenderType::FEMALE;
        } elseif($gender == GenderType::O || $gender == GenderType::NEUTER) {
            return GenderType::NEUTER;
        } else {
            return $gender;
        }
    }


    /**
     * The PedigreeCode/STN format in csv files is: "XX 12AB3-67890" or "XX 123456789012".
     * There is always a space between the country code (XX) and the rest of the code (numbers and possible some letters).
     *
     * @param string $csvPedigreeCode
     * @return array|null
     */
    public static function getStnFromCsvFileString($csvPedigreeCode)
    {
        if($csvPedigreeCode == '' || $csvPedigreeCode == null) { return null; }
        elseif(strlen($csvPedigreeCode) < 4) { return null; }

        if(strpos($csvPedigreeCode, ' ') !== false) {
            $stnParts = explode(' ', $csvPedigreeCode);
            $countryCode = $stnParts[0];
            $number = str_replace('-', '', $stnParts[1]);

        } else {
            //if the countryCode and number are not separated by a space
            $countryCode = mb_substr($csvPedigreeCode, 0, 2, 'utf-8');
            $number = str_replace('-', '', mb_substr($csvPedigreeCode, 2, strlen($csvPedigreeCode)));
        }

        return array(JsonInputConstant::PEDIGREE_COUNTRY_CODE => $countryCode, JsonInputConstant::PEDIGREE_NUMBER => $number);
    }


    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function isStringContains($haystack, $needle)
    {
        if (strpos($haystack, $needle) !== FALSE) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $str1
     * @param string $str2
     * @param array $ignoreChars
     * @param bool $ignoreCapitalization
     * @return bool
     */
    public static function equals($str1, $str2, $ignoreChars = [], $ignoreCapitalization = false)
    {
        if ($ignoreCapitalization) {
            $str1 = mb_strtolower($str1);
            $str2 = mb_strtolower($str2);
        }

        if (count($ignoreChars) > 0) {

            $replacements = [];
            foreach ($ignoreChars as $char) {
                $replacements[$char] = '';
                $replacements[$char] = '';
            }

            $str1 = strtr($str1, $replacements);
            $str2 = strtr($str2, $replacements);
        }

        return $str1 === $str2;
    }


    /**
     * @param string $needle
     * @param array $haystack
     * @param array $ignoreChars
     * @param bool $ignoreCapitalization
     * @return bool
     */
    public static function equalsAtLeastOneInSet($needle, array $haystack, $ignoreChars = [], $ignoreCapitalization = false)
    {
        foreach ($haystack as $value) {
            if (self::equals($needle, $value, $ignoreChars, $ignoreCapitalization)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param boolean $boolean
     * @param string $nullString
     * @return string
     */
    public static function getBooleanAsString($boolean, $nullString = '')
    {
        if($boolean === null) {
            return $nullString;
        }
        return ($boolean) ? 'true' : 'false';
    }


    /**
     * @param string|bool $value
     * @param bool $defaultValue
     * @return bool
     */
    public static function getStringAsBoolean($value, $defaultValue)
    {
        if (is_string($value)) {

            $value = trim(strtolower($value));
            if ($defaultValue) {
                return $value !== 'false';
            }
            return $value === 'true';

        } elseif (is_bool($value)) {
            return $value;
        }

        return $defaultValue;
    }


    /**
     * @param mixed $value
     * @param boolean $wrapNonNullInQuotes
     * @return string
     */
    public static function getNullAsString($value, $wrapNonNullInQuotes = false)
    {
        return $value == null || $value == '' ? 'NULL' : ($wrapNonNullInQuotes ? "'".$value."'" : $value);
    }


    /**
     * @param $value
     * @return string
     */
    public static function getNullAsStringOrWrapInQuotes($value)
    {
        return self::getNullAsString($value, true);
    }


    /**
     * @param $string
     * @return string
     */
    public static function escapeSingleApostrophes($string)
    {
        return strtr($string, ["'" => "''"]);
    }


    /**
     * @param $stringOrArray
     * @return string|array|null
     */
    public static function replaceMultipleSpacesByOne($stringOrArray)
    {
        return preg_replace('!\s+!', ' ', $stringOrArray);
    }


    public static function getUnicodeSymbol($unicodeCodePoint)
    {
        return mb_convert_encoding('&#x'.$unicodeCodePoint.';', 'UTF-8', 'HTML-ENTITIES');
    }


    /**
     * Only the first half of the pedigreeNumber is capitalized.
     * A lowercase first letter after the dash replaces the first letter of an animalOrderNumber,
     * in case an identical one already exists.
     *
     * @param string $pedigreeNumber
     * @return string
     */
    public static function capitalizePedigreeNumber($pedigreeNumber)
    {
        if($pedigreeNumber != null) {

            $a = substr($pedigreeNumber, 0, 6); //First half including the dash
            $b = substr($pedigreeNumber, 6, 1); //First char after dash
            $c = substr($pedigreeNumber, 7, 4); //Last 4 chars

            $pedigreeNumber = strtoupper($a).$b.$c;
        }

        return $pedigreeNumber;
    }


    /**
     * @param string $classPath
     * @return string
     */
    public static function getEntityName($classPath)
    {
        $parts = explode('\\', $classPath);
        return end($parts);
    }


    /**
     * @param string $ulnNumber
     * @return string
     */
    public static function padUlnNumberWithZeroes($ulnNumber)
    {
        return str_pad($ulnNumber, self::ULN_LENGTH, 0, STR_PAD_LEFT);
    }


    /**
     * @param string $animalOrderNumber
     * @return string
     */
    public static function padAnimalOrderNumberWithZeroes($animalOrderNumber)
    {
        return str_pad($animalOrderNumber, self::ANIMAL_ORDER_NUMBER_LENGTH, 0, STR_PAD_LEFT);
    }


    /**
     * @param string $breederNumber
     * @return string
     */
    public static function padBreederNumberWithZeroes($breederNumber)
    {
        return str_pad($breederNumber, self::BREEDER_NUMBER_LENGTH, 0, STR_PAD_LEFT);
    }


    /**
     * Make sure only valid pedigreeNumbers are inserted!
     *
     * @param string $pedigreeNumber
     * @param boolean $mayOnlyContainDigits
     * @return string
     */
    public static function getAnimalOrderNumberFromPedigreeNumber($pedigreeNumber, $mayOnlyContainDigits = true)
    {
        $animalOrderNumber = self::padAnimalOrderNumberWithZeroes(explode('-', $pedigreeNumber)[1]);
        if($mayOnlyContainDigits) {
            return ctype_digit($animalOrderNumber) ? $animalOrderNumber : null;
        }
        return $animalOrderNumber;
    }


    /**
     * Remove last 5 characters and leading zeroes
     *
     * @param string $ulnNumber
     * @return string
     */
    public static function getUbnFromUlnNumber($ulnNumber)
    {
        if(!is_string($ulnNumber)) { return null; }
        if(strlen($ulnNumber) > 12) { return null; }

        $ubn = ltrim(substr(trim($ulnNumber), 0, -5), '0');

        return Validator::hasValidUbnFormat($ubn) ? $ubn : null;
    }


    /**
     * @param Animal $animal
     * @param \DateTime $date
     * @param bool $mustHaveDateOfBirth
     * @return null|string
     */
    public static function getAnimalIdAndDateString(Animal $animal, \DateTime $date, $mustHaveDateOfBirth = true)
    {
        if($animal == null || ($mustHaveDateOfBirth && $date == null)) { return null; }

        $dateString = '';
        if($date) {
            $dateString = $animal->getDateOfBirth()->format('Y-m-d');
        }

        return $animal->getId().'_'.$dateString;
    }


    /**
     * @param $string
     * @return string
     */
    public static function replaceCommasWithDots($string)
    {
        return strtr($string, [',' => '.'] );
    }


    /**
     * @param $string
     * @param null $nullReplacement
     * @return null|string
     */
    public static function stripNonNumericCharsAndConvertToInteger($string, $nullReplacement = null)
    {
        $int = ltrim(preg_replace("/[^0-9]/", "", $string),'0');
        return $int === '' ? null : $int;
    }


    /**
     * @param string $string
     * @return string
     */
    public static function convertCamelCaseToSnakeCase($string)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string)), '_');
    }


    /**
     * @param array $needles
     * @param $haystack
     * @return bool
     */
    public static function onlyContainsChars(array $needles, $haystack)
    {
        $regex = '((' . implode('|', $needles) . ')+)';
        preg_match($regex, $haystack, $matches);
        return in_array($haystack, $matches);
    }


    /**
     * @return string
     */
    public static function getResetToken()
    {
        return str_replace('/', '-', base64_encode(random_bytes(128)));
    }


    /**
     * @param string $string
     * @return string
     */
    public static function removeLeadingZeroes($string)
    {
        return ltrim($string, '0');
    }


    /**
     * @param string $string
     * @return string
     */
    public static function replaceUnderscoresWithSpaces($string)
    {
        return strtr($string, ['_' => ' ']);
    }


    /**
     * @param string $string
     * @return string
     */
    public static function removeSpaces($string)
    {
        return strtr($string, [' ' => '']);
    }


    /**
     * @param $string
     * @return string
     */
    public static function preparePlainTextInput($string): string
    {
        if (!is_string($string)) {
            return '';
        }
        // Note strtr does not work for \n, \r and \t
        // Remove new lines
        $string = str_replace("\n", "", $string);
        $string = str_replace("\r", "", $string);

        // remove tabs
        $string = str_replace("\t", "", $string);
        return strtr($string, [' ' => '',]);
    }


    /**
     * @param string $string
     * @return string
     */
    public static function replaceSpacesWithUnderscores($string)
    {
        return strtr($string, [' ' => '_']);
    }


    /**
     * @param string $string
     * @return string
     */
    public static function getCapitalizedFirstLetter($string)
    {
        return strtoupper(substr($string, 0, 1));
    }


    /**
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    public static function containsSubstring($needle, $haystack)
    {
        return !!strpos(strtolower($haystack), strtolower($needle));
    }


    /**
     * @param string $string
     * @return string
     */
    public static function convertEmptyStringToNull($string)
    {
        return $string === '' ? null : $string;
    }


    /**
     * @param string|null $string
     * @return null|string
     */
    public static function trimIfNotNull($string)
    {
        return $string === null ? null : trim($string);
    }


    /**
     * @param string $ubn
     * @return string
     */
    public static function preformatUbn($ubn): string
    {
        return !empty($ubn) && (is_int($ubn) || ctype_digit($ubn))
            ? ltrim(trim(strval($ubn)), '0') : '';
    }


    /**
     * @param string|null $string
     * @return null|string
     */
    public static function trimAndStringToUpperIfNotNull($string)
    {
        return $string === null ? null : trim(strtoupper($string));
    }


    public static function capitalizationSet()
    {
        return [
            'ubn' => 'UBN',
            'uln' => 'ULN',
            'ulns' => 'ULNs',
            'btw' => 'BTW',
            'kvk' => 'KVK',
        ];
    }


    /**
     * @param $string
     * @return string
     */
    public static function decamelize(string $string): ?string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }


    /**
     * @param string $declareClazz
     * @param bool $isPlural
     * @return string
     */
    public static function getDeclareTranslationKey($declareClazz, bool $isPlural = false): string
    {
        if (empty($declareClazz)) {
            return '';
        }
        $upperSnakeCased = strtoupper(self::decamelize(StringUtil::getEntityName($declareClazz)));
        $suffix = self::getLastChars($upperSnakeCased, 2) == 'SS' ? 'ES' : 'S';
        return $isPlural ? $upperSnakeCased . $suffix : $upperSnakeCased;
    }
}
